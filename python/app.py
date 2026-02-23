import os
import re
from typing import Any

import joblib
import numpy as np
import pandas as pd
from fastapi import FastAPI
from pydantic import BaseModel

# Define request format
class Features(BaseModel):
    values: list[float]

class BatchFeatures(BaseModel):
    batch: list[list[float]]
    student_ids: list[int | str] | None = None
    student_names: list[str] | None = None


# Get the directory where this script is located
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_DIR = os.path.join(SCRIPT_DIR, "models")
OLD_MODEL_DIR = os.path.join(MODEL_DIR, "old")
NEW_MODEL_DIR = os.path.join(MODEL_DIR, "new")

def _load_joblib_with_fallback(filename: str, candidate_dirs: list[str]) -> tuple[Any, str]:
    errors = []
    for directory in candidate_dirs:
        candidate_path = os.path.join(directory, filename)
        if not os.path.exists(candidate_path):
            errors.append(f"{candidate_path} (missing)")
            continue
        try:
            return joblib.load(candidate_path), candidate_path
        except Exception as exc:
            errors.append(f"{candidate_path} ({exc})")
    raise FileNotFoundError("; ".join(errors))


def _safe_float(value: Any, decimals: int | None = None) -> float | None:
    try:
        numeric_value = float(value)
    except (TypeError, ValueError):
        return None

    if np.isnan(numeric_value):
        return None

    if decimals is None:
        return numeric_value

    return round(numeric_value, decimals)


def _extract_numeric_student_id(value: Any) -> int | None:
    if value is None:
        return None

    text = str(value).strip()
    if text == "":
        return None

    match = re.search(r"(\d+)", text)
    if not match:
        return None

    return int(match.group(1))


def _student_id_tokens(value: Any) -> set[str]:
    tokens = set()

    if value is None:
        return tokens

    text = str(value).strip()
    if text == "":
        return tokens

    tokens.add(text.upper())
    numeric_student_id = _extract_numeric_student_id(text)
    if numeric_student_id is not None:
        tokens.add(str(numeric_student_id).upper())
        tokens.add(f"S{numeric_student_id:03d}".upper())
        tokens.add(f"S{numeric_student_id}".upper())

    return tokens


def _normalize_status_label(status: Any, fallback_probability: float | None = None) -> str:
    if status is not None:
        status_text = str(status).strip()
        if status_text != "" and status_text.lower() != "nan":
            return status_text.title()

    if fallback_probability is None:
        return "N/A"

    if fallback_probability >= 0.7:
        return "High"
    if fallback_probability >= 0.3:
        return "Mid"
    return "Low"


def _name_token(value: Any) -> str:
    if value is None:
        return ""

    text = str(value).strip().lower()
    if text == "":
        return ""

    # Normalize punctuation/spaces so artifact names can match DB names.
    text = text.replace(",", " ")
    text = re.sub(r"[^a-z0-9\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def _name_tokens_from_parts(first_name: Any, last_name: Any) -> set[str]:
    first = str(first_name or "").strip()
    last = str(last_name or "").strip()
    tokens = {
        _name_token(f"{first} {last}"),
        _name_token(f"{last} {first}"),
        _name_token(f"{last}, {first}"),
    }
    return {token for token in tokens if token != ""}


CURRENT_PREDICTIONS_DF = pd.DataFrame()
NEXT_PREDICTIONS_DF = pd.DataFrame()
ENGAGEMENT_PREDICTIONS: dict[str, Any] = {}
CURRENT_PREDICTION_LOOKUP: dict[str, dict[str, Any]] = {}
CURRENT_PREDICTION_LOOKUP_BY_NAME: dict[str, dict[str, Any]] = {}
DB_STUDENT_ID_BY_ID_TOKEN: dict[str, int] = {}
DB_STUDENT_NAME_TOKENS_BY_ID: dict[int, set[str]] = {}
DB_STUDENT_ID_BY_NAME_TOKEN: dict[str, int] = {}
DB_STUDENT_INDEX_READY = False
DB_STUDENT_INDEX_ERROR: str | None = None


def _ensure_db_student_index() -> bool:
    global DB_STUDENT_INDEX_READY, DB_STUDENT_INDEX_ERROR

    if DB_STUDENT_INDEX_READY:
        return True
    if DB_STUDENT_INDEX_ERROR is not None:
        return False

    try:
        from utils.db_utils import get_db_engine

        engine = get_db_engine()
        students_df = pd.read_sql("SELECT id, first_name, last_name FROM students", engine)
    except Exception as exc:
        DB_STUDENT_INDEX_ERROR = str(exc)
        print(f"Warning: Could not load DB student index: {exc}")
        return False

    for _, row in students_df.iterrows():
        student_id = _extract_numeric_student_id(row.get("id"))
        if student_id is None:
            continue

        DB_STUDENT_NAME_TOKENS_BY_ID[student_id] = _name_tokens_from_parts(row.get("first_name"), row.get("last_name"))
        for token in _student_id_tokens(student_id):
            DB_STUDENT_ID_BY_ID_TOKEN[token] = student_id
        for token in DB_STUDENT_NAME_TOKENS_BY_ID[student_id]:
            DB_STUDENT_ID_BY_NAME_TOKEN[token] = student_id

    DB_STUDENT_INDEX_READY = True
    print(f"Loaded DB student index entries: {len(DB_STUDENT_NAME_TOKENS_BY_ID)}")
    return True


def _resolve_db_student_id(raw_student_id: Any = None, raw_name: Any = None) -> int | None:
    if _ensure_db_student_index():
        name_token = _name_token(raw_name)
        if name_token != "":
            mapped = DB_STUDENT_ID_BY_NAME_TOKEN.get(name_token)
            if mapped is not None:
                return mapped

        for token in _student_id_tokens(raw_student_id):
            mapped = DB_STUDENT_ID_BY_ID_TOKEN.get(token)
            if mapped is not None:
                return mapped

        return None

    return _extract_numeric_student_id(raw_student_id)

# Load legacy Random Forest model bundles (still used as fallback for direct inference)
try:
    table1_model_data, table1_model_path = _load_joblib_with_fallback(
        "student_table1.joblib",
        [MODEL_DIR, OLD_MODEL_DIR],
    )
    table1_model = table1_model_data["model"]
    table1_features = table1_model_data["features"]
    table1_label_encoders = table1_model_data["label_encoders"]
    table1_config = table1_model_data.get("config", {})
    TABLE1_MODEL_LOADED = True
    print(f"Loaded Table 1 model from: {table1_model_path}")
except Exception as e:
    print(f"Warning: Could not load Table 1 model: {e}")
    TABLE1_MODEL_LOADED = False

try:
    table3_model_data, table3_model_path = _load_joblib_with_fallback(
        "student_table3.joblib",
        [MODEL_DIR, OLD_MODEL_DIR],
    )
    table3_model = table3_model_data["model"]
    table3_features = table3_model_data["features"]
    table3_label_encoders = table3_model_data["label_encoders"]
    TABLE3_MODEL_LOADED = True
    print(f"Loaded Table 3 model from: {table3_model_path}")
except Exception as e:
    print(f"Warning: Could not load Table 3 model: {e}")
    TABLE3_MODEL_LOADED = False

# Load new production prediction artifacts (precomputed outputs)
try:
    CURRENT_PREDICTIONS_DF = joblib.load(os.path.join(NEW_MODEL_DIR, "current_predictions.joblib"))
    NEXT_PREDICTIONS_DF = joblib.load(os.path.join(NEW_MODEL_DIR, "next_predictions.joblib"))
    ENGAGEMENT_PREDICTIONS = joblib.load(os.path.join(NEW_MODEL_DIR, "engagement_predictions.joblib"))

    if not isinstance(CURRENT_PREDICTIONS_DF, pd.DataFrame):
        raise TypeError("current_predictions.joblib must contain a pandas DataFrame")
    if not isinstance(NEXT_PREDICTIONS_DF, pd.DataFrame):
        raise TypeError("next_predictions.joblib must contain a pandas DataFrame")
    if not isinstance(ENGAGEMENT_PREDICTIONS, dict):
        raise TypeError("engagement_predictions.joblib must contain a dict")

    for key in ("master", "honors", "support", "as_of"):
        if key not in ENGAGEMENT_PREDICTIONS:
            raise KeyError(f"engagement_predictions payload missing key: {key}")

    # Build a lookup used by batch prediction fallback when only new artifacts are available.
    for _, row in CURRENT_PREDICTIONS_DF.iterrows():
        risk_score = _safe_float(row.get("Risk Category Score"), decimals=1)
        if risk_score is None:
            risk_score = _safe_float(row.get("Risk Raw (Model)"), decimals=1)
        if risk_score is None:
            continue

        entry = {
            "risk_score": risk_score,
            "status": _normalize_status_label(row.get("Status"), fallback_probability=risk_score / 100.0),
        }
        for token in _student_id_tokens(row.get("ID")):
            CURRENT_PREDICTION_LOOKUP[token] = entry
        name_token = _name_token(row.get("Name"))
        if name_token != "":
            CURRENT_PREDICTION_LOOKUP_BY_NAME[name_token] = entry

    NEW_ARTIFACTS_LOADED = True
    print(f"Loaded new prediction artifacts from: {NEW_MODEL_DIR}")
    _ensure_db_student_index()
except Exception as e:
    print(f"Warning: Could not load new prediction artifacts: {e}")
    NEW_ARTIFACTS_LOADED = False


app = FastAPI()


def prob_label(p):
    """Convert probability to risk label"""
    if p >= 0.7:
        return "High"
    elif p >= 0.3:
        return "Mid"
    return "Low"


def _lookup_prediction_from_new_artifacts(student_id: Any) -> dict[str, Any] | None:
    numeric_student_id = _extract_numeric_student_id(student_id)
    if numeric_student_id is not None and _ensure_db_student_index():
        for db_name_token in DB_STUDENT_NAME_TOKENS_BY_ID.get(numeric_student_id, set()):
            if db_name_token in CURRENT_PREDICTION_LOOKUP_BY_NAME:
                return CURRENT_PREDICTION_LOOKUP_BY_NAME[db_name_token]

    input_name_token = _name_token(student_id)
    if input_name_token in CURRENT_PREDICTION_LOOKUP_BY_NAME:
        return CURRENT_PREDICTION_LOOKUP_BY_NAME[input_name_token]

    for token in _student_id_tokens(student_id):
        if token in CURRENT_PREDICTION_LOOKUP:
            return CURRENT_PREDICTION_LOOKUP[token]

    return None


def _build_table1_from_new_artifacts() -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    if CURRENT_PREDICTIONS_DF.empty:
        return rows

    for _, row in CURRENT_PREDICTIONS_DF.iterrows():
        risk_score = _safe_float(row.get("Risk Category Score"), decimals=1)
        if risk_score is None:
            risk_score = _safe_float(row.get("Risk Raw (Model)"), decimals=1)
        risk_label = _normalize_status_label(row.get("Status"), fallback_probability=(risk_score or 0) / 100.0)

        rows.append(
            {
                "Student_ID": _resolve_db_student_id(raw_student_id=row.get("ID"), raw_name=row.get("Name")),
                "Student_ID_raw": row.get("ID"),
                "Name": row.get("Name", ""),
                "Status": row.get("Status"),
                "Risk_Category_Score": risk_score,
                "Risk_Raw_Model": _safe_float(row.get("Risk Raw (Model)"), decimals=1),
                "Prob_HighRisk_pct": risk_score,
                "Risk_Label": risk_label,
                "Forecast_Attendance_pct": _safe_float(row.get("Forecast % (Attendance)"), decimals=1),
                "Current_Status_pct": _safe_float(row.get("Current Status"), decimals=1),
                "History_pct": _safe_float(row.get("History %"), decimals=1),
                "Drop_vs_History": _safe_float(row.get("Drop vs History"), decimals=1),
                "Commute": _safe_float(row.get("Commute"), decimals=1),
            }
        )

    return rows


def _build_table2_from_new_artifacts() -> list[dict[str, Any]]:
    master = ENGAGEMENT_PREDICTIONS.get("master")
    if not isinstance(master, pd.DataFrame) or master.empty:
        return []

    rows: list[dict[str, Any]] = []
    for _, row in master.iterrows():
        rows.append(
            {
                "Student_ID": _resolve_db_student_id(raw_student_id=row.get("Student ID"), raw_name=row.get("Student Name")),
                "Student_ID_raw": row.get("Student ID"),
                "Name": row.get("Student Name", ""),
                "PerformancePercentage": _safe_float(row.get("Average Grade (%)"), decimals=1),
                "AttendancePercentage": _safe_float(row.get("Current Attendance (%)"), decimals=1),
                "EngagementScore": _safe_float(row.get("Engagement Score (0-100)"), decimals=2),
                "Strength": row.get("Best Subject", "N/A"),
                "Weakness": row.get("Needs Improvement In", "N/A"),
                "HonorsEligibility": row.get("Honors Eligibility", "N/A"),
            }
        )

    return rows


def _build_table3_from_new_artifacts() -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    if NEXT_PREDICTIONS_DF.empty:
        return rows

    as_of = ENGAGEMENT_PREDICTIONS.get("as_of", {}) if isinstance(ENGAGEMENT_PREDICTIONS, dict) else {}
    as_of_month = as_of.get("month_name")
    as_of_year = as_of.get("year")

    for _, row in NEXT_PREDICTIONS_DF.iterrows():
        risk_score = _safe_float(row.get("Risk Score"), decimals=1)
        risk_label = _normalize_status_label(row.get("Status"), fallback_probability=(risk_score or 0) / 100.0)

        rows.append(
            {
                "Student_ID": _resolve_db_student_id(raw_student_id=row.get("ID"), raw_name=row.get("Student Name")),
                "Student_ID_raw": row.get("ID"),
                "Name": row.get("Student Name", ""),
                "Month": as_of_month,
                "Year": as_of_year,
                "Status": row.get("Status"),
                "Risk_Score": risk_score,
                "Prob_HighRisk_pct": risk_score,
                "Risk_Label": risk_label,
                "Forecast_pct": _safe_float(row.get("Forecast %"), decimals=1),
                "Current_Status_pct": _safe_float(row.get("Current Status"), decimals=1),
                "History_pct": _safe_float(row.get("History %"), decimals=1),
                "Trend": _safe_float(row.get("Trend"), decimals=1),
                "Commute": _safe_float(row.get("Commute"), decimals=1),
                "SES": row.get("SES"),
            }
        )

    return rows


def _build_feature_tables_from_new_artifacts() -> dict[str, Any]:
    as_of = ENGAGEMENT_PREDICTIONS.get("as_of", {}) if isinstance(ENGAGEMENT_PREDICTIONS, dict) else {}
    as_of_date = as_of.get("as_of_date", "unknown")

    return {
        "success": True,
        "source": "new_artifacts",
        "as_of": as_of,
        "table1": {
            "title": "Table 1: Current Predictions",
            "description": f"Precomputed current-month risk predictions from current_predictions.joblib (as_of={as_of_date})",
            "data": _build_table1_from_new_artifacts(),
        },
        "table2": {
            "title": "Table 2: Engagement Analysis",
            "description": f"Precomputed engagement analysis from engagement_predictions.joblib['master'] (as_of={as_of_date})",
            "data": _build_table2_from_new_artifacts(),
        },
        "table3": {
            "title": "Table 3: Next Predictions",
            "description": f"Precomputed next-month risk predictions from next_predictions.joblib (as_of={as_of_date})",
            "data": _build_table3_from_new_artifacts(),
        },
    }


def _artifact_records(data_key: str) -> list[dict[str, Any]]:
    value = ENGAGEMENT_PREDICTIONS.get(data_key)
    if isinstance(value, pd.DataFrame):
        return value.to_dict(orient="records")
    return []


def _batch_predictions_from_database_table1(
    student_ids: list[Any],
    student_names: list[str],
    requested_count: int,
) -> dict[str, Any] | None:
    try:
        tables_payload = get_feature_tables()
    except Exception as exc:
        print(f"Warning: database model fallback failed: {exc}")
        return None

    if not isinstance(tables_payload, dict) or not tables_payload.get("success"):
        return None
    if tables_payload.get("source") != "database":
        return None

    table1_payload = tables_payload.get("table1") or {}
    table1_rows = table1_payload.get("data") or []
    if not isinstance(table1_rows, list):
        return None

    rows_by_id: dict[int, dict[str, Any]] = {}
    rows_by_name: dict[str, dict[str, Any]] = {}

    for row in table1_rows:
        if not isinstance(row, dict):
            continue

        numeric_student_id = _extract_numeric_student_id(row.get("Student_ID"))
        if numeric_student_id is not None:
            rows_by_id[numeric_student_id] = row

        student_name_token = _name_token(row.get("Name"))
        if student_name_token != "":
            rows_by_name[student_name_token] = row

    predictions: list[dict[str, Any]] = []
    for index in range(requested_count):
        student_id = student_ids[index] if index < len(student_ids) else None
        student_name = student_names[index] if index < len(student_names) else None

        row = None
        numeric_student_id = _extract_numeric_student_id(student_id)
        if numeric_student_id is not None:
            row = rows_by_id.get(numeric_student_id)

        if row is None and student_name is not None:
            row = rows_by_name.get(_name_token(student_name))

        if row is None and student_id is not None:
            row = rows_by_name.get(_name_token(student_id))

        if row is None:
            predictions.append({"prediction_confidence": None, "risk_label": "N/A"})
            continue

        risk_pct = _safe_float(row.get("Prob_HighRisk_pct"), decimals=4)
        if risk_pct is None:
            predictions.append({"prediction_confidence": None, "risk_label": "N/A"})
            continue

        probability = max(0.0, min(1.0, risk_pct / 100.0))
        predictions.append(
            {
                "prediction_confidence": float(probability),
                "risk_label": _normalize_status_label(
                    row.get("Risk_Label"),
                    fallback_probability=probability,
                ),
            }
        )

    while len(predictions) < requested_count:
        predictions.append({"prediction_confidence": None, "risk_label": "N/A"})

    return {"predictions": predictions, "source": "database_model"}


@app.post("/prediction_probability")
def prediction_probability(features: Features):
    """
    Single prediction endpoint.
    Accepts a list of feature values and returns the prediction probability.
    """
    if not TABLE1_MODEL_LOADED:
        return {"error": "Model not loaded", "prediction_confidence": None}

    try:
        X = np.array(features.values).reshape(1, -1)
        prob = table1_model.predict_proba(X)[0, 1]
        return {
            "prediction_confidence": float(prob),
            "risk_label": prob_label(prob)
        }
    except Exception as e:
        return {"error": str(e), "prediction_confidence": None}


@app.post("/prediction_probability_batch")
def prediction_probability_batch(batch_features: BatchFeatures):
    """
    Batch prediction endpoint.
    Accepts a list of feature vectors and returns predictions for all.
    """
    if not batch_features.batch:
        return {"predictions": []}

    requested_count = len(batch_features.batch)
    student_ids = batch_features.student_ids or []
    student_names = batch_features.student_names or []

    if TABLE1_MODEL_LOADED:
        try:
            X = np.array(batch_features.batch)
            if X.ndim != 2:
                raise ValueError(f"X has invalid shape: {X.shape}")
            if X.shape[1] != len(table1_features):
                raise ValueError(
                    f"X has {X.shape[1]} features, but RandomForestClassifier is expecting {len(table1_features)} features as input."
                )
            probs = table1_model.predict_proba(X)[:, 1]
            predictions = [
                {
                    "prediction_confidence": float(p),
                    "risk_label": prob_label(p)
                }
                for p in probs
            ]
            return {"predictions": predictions, "source": "legacy_model"}
        except Exception as e:
            print(f"Warning: legacy batch prediction failed, attempting database fallback: {e}")
            if student_ids or student_names:
                db_predictions = _batch_predictions_from_database_table1(
                    student_ids=student_ids,
                    student_names=student_names,
                    requested_count=requested_count,
                )
                if db_predictions is not None:
                    return db_predictions
                print("Warning: database model fallback did not produce predictions.")

    return {
        "error": "Database-backed prediction failed. Ensure model, DB data, and student identifiers are available.",
        "predictions": [],
    }


@app.get("/predictions/current")
def predictions_current():
    if not NEW_ARTIFACTS_LOADED:
        return {"success": False, "error": "New prediction artifacts not loaded", "data": []}
    return {"success": True, "data": CURRENT_PREDICTIONS_DF.to_dict(orient="records")}


@app.get("/predictions/next")
def predictions_next():
    if not NEW_ARTIFACTS_LOADED:
        return {"success": False, "error": "New prediction artifacts not loaded", "data": []}
    return {"success": True, "data": NEXT_PREDICTIONS_DF.to_dict(orient="records")}


@app.get("/predictions/engagement/master")
def predictions_engagement_master():
    if not NEW_ARTIFACTS_LOADED:
        return {"success": False, "error": "New prediction artifacts not loaded", "data": []}
    return {"success": True, "data": _artifact_records("master")}


@app.get("/predictions/engagement/honors")
def predictions_engagement_honors():
    if not NEW_ARTIFACTS_LOADED:
        return {"success": False, "error": "New prediction artifacts not loaded", "data": []}
    return {"success": True, "data": _artifact_records("honors")}


@app.get("/predictions/engagement/support")
def predictions_engagement_support():
    if not NEW_ARTIFACTS_LOADED:
        return {"success": False, "error": "New prediction artifacts not loaded", "data": []}
    return {"success": True, "data": _artifact_records("support")}


@app.get("/predictions/engagement/as-of")
def predictions_engagement_as_of():
    if not NEW_ARTIFACTS_LOADED:
        return {"success": False, "error": "New prediction artifacts not loaded", "data": {}}
    return {"success": True, "data": ENGAGEMENT_PREDICTIONS.get("as_of", {})}


@app.get("/features/tables")
def get_feature_tables():
    """
    Returns the three feature tables with ML predictions:
    - Table 1: Mid-month prediction (predicts end-of-month absenteeism risk)
    - Table 2: Engagement analysis with strengths/weaknesses
    - Table 3: Next month prediction (predicts next month absenteeism risk)
    """
    from utils.data_fetcher import fetch_demographics, fetch_attendance, fetch_scores

    try:
        demographics = fetch_demographics()
        attendance = fetch_attendance()
        scores = fetch_scores()

        month_map = {
            'January':1,'February':2,'March':3,'April':4,'May':5,'June':6,
            'July':7,'August':8,'September':9,'October':10,'November':11,'December':12
        }

        # =========================================
        # TABLE 1: Mid-Month Prediction (End of Month Risk)
        # Uses Random Forest to predict absenteeism risk
        # =========================================
        table1_result = []

        if TABLE1_MODEL_LOADED and not attendance.empty:
            try:
                # Prepare data similar to build_table1_predictions
                scores["Date"] = pd.to_datetime(scores["Date"], errors="coerce")
                scores["Score"] = pd.to_numeric(scores["Score"], errors="coerce")
                scores["Total_Score"] = pd.to_numeric(scores["Total_Score"], errors="coerce")
                scores["Daily_Percent"] = (scores["Score"] / scores["Total_Score"]) * 100

                daily_agg = (
                    scores.dropna(subset=["Date"])
                    .groupby(["Student_ID", "Year", "Month", "Date"], as_index=False)
                    .agg(Daily_Perf=("Daily_Percent", "mean"))
                )

                attendance["Month_Num"] = attendance["Month"].map(month_map)

                # Also compute monthly performance aggregates
                perf_month = (
                    scores.dropna(subset=["Date"])
                    .groupby(["Student_ID", "Year", "Month"], as_index=False)
                    .agg(Perf_Month=("Daily_Percent", "mean"))
                )

                # Sort attendance by year and month
                attendance_sorted = attendance.sort_values(["Student_ID", "Year", "Month_Num"])

                latest_mid = attendance.sort_values(["Year", "Month_Num"], ascending=False).iloc[0]
                cur_year = int(latest_mid["Year"])
                cur_month = latest_mid["Month"]
                cur_month_num = month_map[cur_month]

                pred_rows = []
                for sid in demographics["Student_ID"].unique():
                    # Get all attendance records for this student, sorted by time
                    student_att = attendance_sorted[attendance_sorted["Student_ID"] == sid].reset_index(drop=True)

                    if student_att.empty:
                        continue

                    # Find the current month row
                    cur_idx = student_att[
                        (student_att["Year"] == cur_year) &
                        (student_att["Month"] == cur_month)
                    ].index

                    if len(cur_idx) == 0:
                        continue

                    cur_idx = cur_idx[0]
                    cur_row = student_att.iloc[cur_idx]

                    # Helper to calculate attendance percentage
                    def calc_att(row):
                        if row is None or row["Total_School_Days"] == 0:
                            return 0
                        return round((row["Present"] / row["Total_School_Days"]) * 100, 1)

                    # Current month attendance
                    att_current = calc_att(cur_row)

                    # Past 1 month attendance (if exists)
                    att_past1 = calc_att(student_att.iloc[cur_idx - 1]) if cur_idx >= 1 else att_current

                    # Past 2 months attendance (if exists)
                    att_past2 = calc_att(student_att.iloc[cur_idx - 2]) if cur_idx >= 2 else att_past1

                    # Get performance for current month
                    cur_perf_row = perf_month[
                        (perf_month["Student_ID"] == sid) &
                        (perf_month["Year"] == cur_year) &
                        (perf_month["Month"] == cur_month)
                    ]
                    perf_current = round(cur_perf_row["Perf_Month"].iloc[0], 2) if not cur_perf_row.empty else 0

                    # Get past 1 month performance
                    past1_year, past1_month_num = (cur_year, cur_month_num - 1) if cur_month_num > 1 else (cur_year - 1, 12)
                    past1_month = list(month_map.keys())[past1_month_num - 1]
                    past1_perf_row = perf_month[
                        (perf_month["Student_ID"] == sid) &
                        (perf_month["Year"] == past1_year) &
                        (perf_month["Month"] == past1_month)
                    ]
                    perf_past1 = round(past1_perf_row["Perf_Month"].iloc[0], 2) if not past1_perf_row.empty else perf_current

                    # Get past 2 months performance
                    past2_year, past2_month_num = (past1_year, past1_month_num - 1) if past1_month_num > 1 else (past1_year - 1, 12)
                    past2_month = list(month_map.keys())[past2_month_num - 1]
                    past2_perf_row = perf_month[
                        (perf_month["Student_ID"] == sid) &
                        (perf_month["Year"] == past2_year) &
                        (perf_month["Month"] == past2_month)
                    ]
                    perf_past2 = round(past2_perf_row["Perf_Month"].iloc[0], 2) if not past2_perf_row.empty else perf_past1

                    # Calculate derived features based on the formulas
                    # Weighted_Attendance = (Att_Current + Att_Past1 + Att_Past2) / 3
                    weighted_attendance = (att_current + att_past1 + att_past2) / 3

                    # Weighted_Performance = (Perf_Current + Perf_Past1 + Perf_Past2) / 3
                    weighted_performance = (perf_current + perf_past1 + perf_past2) / 3

                    # Weighted_Current = 0.4 * Weighted_Attendance + 0.6 * Weighted_Performance
                    weighted_current = 0.4 * weighted_attendance + 0.6 * weighted_performance

                    # Performance_Trend = Perf_Current - (Perf_Past1 + Perf_Past2) / 2
                    performance_trend = perf_current - (perf_past1 + perf_past2) / 2

                    # Weighted_Trend = Weighted_Current - 0.4 * ((Att_Past1 + Att_Past2) / 2) + 0.6 * ((Perf_Past1 + Perf_Past2) / 2)
                    weighted_trend = weighted_current - (0.4 * ((att_past1 + att_past2) / 2) + 0.6 * ((perf_past1 + perf_past2) / 2))

                    demo = demographics[demographics["Student_ID"] == sid].iloc[0].to_dict()
                    pred_rows.append({
                        "Student_ID": sid,
                        "Att_Current_mid": att_current,
                        "Perf_Current_mid": perf_current,
                        # Add the computed features with model's expected names
                        "Att_Current": att_current,
                        "Perf_Current": perf_current,
                        "Perf_Past1": perf_past1,
                        "Weighted_Attendance": weighted_attendance,
                        "Weighted_Performance": weighted_performance,
                        "Weighted_Current": weighted_current,
                        "Performance_Trend": performance_trend,
                        "Weighted_Trend": weighted_trend,
                        **demo
                    })

                pred_df = pd.DataFrame(pred_rows)

                if not pred_df.empty:
                    # Encode categorical features
                    for col, le in table1_label_encoders.items():
                        if col in pred_df.columns:
                            pred_df[col] = pred_df[col].astype(str).fillna("Unknown")
                            pred_df[col] = pred_df[col].apply(
                                lambda x: x if x in le.classes_ else "Unknown"
                            )
                            if "Unknown" not in le.classes_:
                                le.classes_ = np.append(le.classes_, "Unknown")
                            pred_df[col + "_enc"] = le.transform(pred_df[col])

                    # Add missing features
                    for f in table1_features:
                        if f not in pred_df.columns:
                            pred_df[f] = 0.0

                    X_pred = pred_df[table1_features].fillna(0)

                    # Run prediction
                    pred_df["Prob_HighRisk_EndMonth"] = table1_model.predict_proba(X_pred)[:, 1]
                    pred_df["Prob_HighRisk_EndMonth_pct"] = (pred_df["Prob_HighRisk_EndMonth"] * 100).round(1)
                    pred_df["Risk_Label"] = pred_df["Prob_HighRisk_EndMonth"].apply(prob_label)

                    # Sort by risk (highest first)
                    pred_df = pred_df.sort_values("Prob_HighRisk_EndMonth", ascending=False)

                    # Prepare output
                    for _, row in pred_df.iterrows():
                        table1_result.append({
                            "Student_ID": int(row["Student_ID"]),
                            "Name": row.get("Name", ""),
                            "Sexuality": row.get("Sexuality", ""),
                            "Distance_km": row.get("Distance_km", 0),
                            "Transportation": row.get("Transportation", ""),
                            "Socioeconomic_Status": row.get("Socioeconomic_Status", ""),
                            "Att_Current": row.get("Att_Current_mid", 0),
                            "Perf_Current": row.get("Perf_Current_mid", 0),
                            "Prob_HighRisk_pct": row.get("Prob_HighRisk_EndMonth_pct", 0),
                            "Risk_Label": row.get("Risk_Label", "N/A")
                        })
            except Exception as e:
                print(f"Table 1 prediction error: {e}")
                # Fall back to basic data without predictions
                table1_result = demographics.to_dict(orient="records")
        else:
            # No model or no attendance data - return basic demographics
            table1_result = demographics.to_dict(orient="records")

        # =========================================
        # TABLE 2: Engagement & Strengths/Weaknesses
        # (No ML prediction, just analysis)
        # =========================================

        # Handle empty scores case
        if scores.empty:
            # No scores data - create table2 with just demographics and default values
            table2 = demographics[["Student_ID", "Name"]].copy()
            table2["PerformancePercentage"] = 0
            table2["AttendancePercentage"] = 0
            table2["EngagementScore"] = 0
            table2["Strength"] = "N/A"
            table2["Weakness"] = "N/A"
            table2_result = table2.to_dict(orient="records")
        else:
            scores["Score"] = pd.to_numeric(scores["Score"], errors="coerce").fillna(0)
            scores["Total_Score"] = pd.to_numeric(scores["Total_Score"], errors="coerce").fillna(1)

            score_type_agg = (
                scores.groupby(["Student_ID", "Subject", "Score_Type"], as_index=False)
                .agg({"Score": "sum", "Total_Score": "sum"})
            )
            score_type_agg["Score_Pct"] = (score_type_agg["Score"] / score_type_agg["Total_Score"] * 100).round(1)

            def find_strength_weakness(group):
                student_id = group["Student_ID"].iloc[0]
                if group["Score_Pct"].nunique() <= 1:
                    return {"Student_ID": student_id, "Strength": "Balanced", "Weakness": "Balanced"}
                max_row = group.loc[group["Score_Pct"].idxmax()]
                min_row = group.loc[group["Score_Pct"].idxmin()]
                return {
                    "Student_ID": student_id,
                    "Strength": f"{max_row['Subject']} – {max_row['Score_Type']} ({max_row['Score_Pct']}%)",
                    "Weakness": f"{min_row['Subject']} – {min_row['Score_Type']} ({min_row['Score_Pct']}%)"
                }

            # Build strength/weakness DataFrame by iterating over groups
            sw_records = [find_strength_weakness(g) for _, g in score_type_agg.groupby("Student_ID")]
            strength_weakness = pd.DataFrame(sw_records) if sw_records else pd.DataFrame(columns=["Student_ID", "Strength", "Weakness"])
            performance = score_type_agg.groupby("Student_ID", as_index=False).agg(PerformancePercentage=("Score_Pct", "mean")).round(1)

            if not attendance.empty and {"Present", "Total_School_Days"}.issubset(attendance.columns):
                att_pct = attendance.copy()
                att_pct["AttendancePercentage"] = (att_pct["Present"] / att_pct["Total_School_Days"] * 100).round(1)
                att_summary = att_pct.groupby("Student_ID", as_index=False).agg(AttendancePercentage=("AttendancePercentage", "mean"))
            else:
                att_summary = pd.DataFrame({"Student_ID": demographics["Student_ID"], "AttendancePercentage": 0})

            table2 = (
                demographics[["Student_ID", "Name"]]
                .merge(performance, on="Student_ID", how="left")
                .merge(att_summary, on="Student_ID", how="left")
                .merge(strength_weakness, on="Student_ID", how="left")
            )
            table2["PerformancePercentage"] = table2["PerformancePercentage"].fillna(0)
            table2["AttendancePercentage"] = table2["AttendancePercentage"].fillna(0)
            table2["EngagementScore"] = (table2["PerformancePercentage"] * 0.6 + table2["AttendancePercentage"] * 0.4).round(1)
            table2["Strength"] = table2["Strength"].fillna("N/A")
            table2["Weakness"] = table2["Weakness"].fillna("N/A")

            table2_result = table2.to_dict(orient="records")

        # =========================================
        # TABLE 3: Next Month Prediction
        # Uses Random Forest to predict next month risk
        # =========================================
        table3_result = []

        if TABLE3_MODEL_LOADED and not attendance.empty and not scores.empty:
            try:
                attendance["Month_Num"] = attendance["Month"].map(month_map)
                scores["Pct"] = (scores["Score"] / scores["Total_Score"]) * 100

                perf_month = (
                    scores.dropna(subset=["Date"])
                    .groupby(["Student_ID", "Year", "Month"], as_index=False)
                    .agg(Perf_Current=("Pct", "mean"))
                )

                df = attendance.merge(perf_month, on=["Student_ID", "Year", "Month"], how="left")
                df["Perf_Current"] = df["Perf_Current"].fillna(0)
                df = df.sort_values(["Student_ID", "Year", "Month_Num"])

                # Build rolling features
                rows = []
                for sid, g in df.groupby("Student_ID"):
                    g = g.reset_index(drop=True)
                    for i in range(len(g)):
                        cur = g.iloc[i]
                        def safe_att(row):
                            return (row["Present"] / row["Total_School_Days"]) * 100 if row["Total_School_Days"] > 0 else 0

                        # Current values
                        att_current = round(safe_att(cur), 1)
                        perf_current = cur["Perf_Current"]

                        # Past 1 month (use current if not available)
                        att_past1 = round(safe_att(g.iloc[i-1]), 1) if i-1 >= 0 else att_current
                        perf_past1 = g.iloc[i-1]["Perf_Current"] if i-1 >= 0 else perf_current

                        # Past 2 months (use past1 if not available)
                        att_past2 = round(safe_att(g.iloc[i-2]), 1) if i-2 >= 0 else att_past1
                        perf_past2 = g.iloc[i-2]["Perf_Current"] if i-2 >= 0 else perf_past1

                        # Weighted averages based on formulas
                        # Weighted_Attendance = (Att_Current + Att_Past1 + Att_Past2) / 3
                        weighted_attendance = (att_current + att_past1 + att_past2) / 3

                        # Weighted_Performance = (Perf_Current + Perf_Past1 + Perf_Past2) / 3
                        weighted_performance = (perf_current + perf_past1 + perf_past2) / 3

                        # Weighted_Current = 0.4 * Weighted_Attendance + 0.6 * Weighted_Performance
                        weighted_current = 0.4 * weighted_attendance + 0.6 * weighted_performance

                        # Performance_Trend = Perf_Current - (Perf_Past1 + Perf_Past2) / 2
                        performance_trend = perf_current - (perf_past1 + perf_past2) / 2

                        # Weighted_Trend = Weighted_Current - (0.4 * ((Att_Past1 + Att_Past2) / 2) + 0.6 * ((Perf_Past1 + Perf_Past2) / 2))
                        weighted_trend = weighted_current - (0.4 * ((att_past1 + att_past2) / 2) + 0.6 * ((perf_past1 + perf_past2) / 2))

                        r = {
                            "Student_ID": sid,
                            "Att_Current": att_current,
                            "Perf_Current": perf_current,
                            "Att_Past1": att_past1,
                            "Perf_Past1": perf_past1,
                            "Att_Past2": att_past2,
                            "Perf_Past2": perf_past2,
                            "Weighted_Attendance": weighted_attendance,
                            "Weighted_Performance": weighted_performance,
                            "Weighted_Current": weighted_current,
                            "Performance_Trend": performance_trend,
                            "Weighted_Trend": weighted_trend,
                            "Month_Num": cur["Month_Num"],
                            "Month": cur["Month"],
                            "Year": cur["Year"]
                        }
                        rows.append(r)

                feat_df = pd.DataFrame(rows).merge(demographics, on="Student_ID", how="left")

                # Filter to latest month
                latest = attendance.sort_values(["Year", "Month_Num"], ascending=False).iloc[0]
                feat_df = feat_df[
                    (feat_df["Year"] == latest["Year"]) &
                    (feat_df["Month"] == latest["Month"])
                ]

                if not feat_df.empty:
                    # Encode categoricals
                    for col, le in table3_label_encoders.items():
                        if col in feat_df.columns:
                            feat_df[col] = feat_df[col].astype(str).fillna("Unknown")
                            feat_df[col] = feat_df[col].apply(lambda x: x if x in le.classes_ else "Unknown")
                            if "Unknown" not in le.classes_:
                                le.classes_ = np.append(le.classes_, "Unknown")
                            feat_df[col + "_enc"] = le.transform(feat_df[col])

                    for f in table3_features:
                        if f not in feat_df.columns:
                            feat_df[f] = 0.0

                    X = feat_df[table3_features].fillna(0)

                    # Predict
                    feat_df["Prob_HighRisk_NextMonth"] = table3_model.predict_proba(X)[:, 1]
                    feat_df["Prob_HighRisk_NextMonth_pct"] = (feat_df["Prob_HighRisk_NextMonth"] * 100).round(1)
                    feat_df["Risk_Label"] = feat_df["Prob_HighRisk_NextMonth"].apply(prob_label)

                    # Sort by risk
                    feat_df = feat_df.sort_values("Prob_HighRisk_NextMonth", ascending=False)

                    for _, row in feat_df.iterrows():
                        table3_result.append({
                            "Student_ID": int(row["Student_ID"]),
                            "Name": row.get("Name", ""),
                            "Month": row.get("Month", ""),
                            "Year": int(row.get("Year", 0)),
                            "Att_Current": round(row.get("Att_Current", 0), 1),
                            "Perf_Current": round(row.get("Perf_Current", 0), 1),
                            "Att_Past1": round(row.get("Att_Past1", 0), 1),
                            "Perf_Past1": round(row.get("Perf_Past1", 0), 1),
                            "Att_Past2": round(row.get("Att_Past2", 0), 1),
                            "Perf_Past2": round(row.get("Perf_Past2", 0), 1),
                            "Weighted_Attendance": round(row.get("Weighted_Attendance", 0), 1),
                            "Weighted_Performance": round(row.get("Weighted_Performance", 0), 1),
                            "Weighted_Current": round(row.get("Weighted_Current", 0), 1),
                            "Performance_Trend": round(row.get("Performance_Trend", 0), 1),
                            "Weighted_Trend": round(row.get("Weighted_Trend", 0), 1),
                            "Prob_HighRisk_pct": row.get("Prob_HighRisk_NextMonth_pct", 0),
                            "Risk_Label": row.get("Risk_Label", "N/A")
                        })
            except Exception as e:
                print(f"Table 3 prediction error: {e}")

        return {
            "success": True,
            "source": "database",
            "table1": {
                "title": "Table 1: Mid-Month Prediction (End of Month Risk)",
                "description": "Random Forest prediction of absenteeism risk by end of current month based on mid-month data",
                "data": table1_result
            },
            "table2": {
                "title": "Table 2: Engagement Analysis",
                "description": "Student engagement scores with academic strengths and weaknesses",
                "data": table2_result
            },
            "table3": {
                "title": "Table 3: Next Month Prediction",
                "description": "Random Forest prediction of absenteeism risk for the following month based on rolling trends",
                "data": table3_result
            }
        }

    except Exception as e:
        import traceback
        traceback.print_exc()
        return {
            "success": False,
            "error": str(e),
            "table1": {"title": "Table 1", "description": "", "data": []},
            "table2": {"title": "Table 2", "description": "", "data": []},
            "table3": {"title": "Table 3", "description": "", "data": []}
        }
