from fastapi import FastAPI
from pydantic import BaseModel
import joblib
import numpy as np
import pandas as pd
import os

# Define request format
class Features(BaseModel):
    values: list[float]

class BatchFeatures(BaseModel):
    batch: list[list[float]]


# Get the directory where this script is located
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

# Load the Random Forest models and their configurations
try:
    table1_model_data = joblib.load(os.path.join(SCRIPT_DIR, "models", "student_table1.joblib"))
    table1_model = table1_model_data["model"]
    table1_features = table1_model_data["features"]
    table1_label_encoders = table1_model_data["label_encoders"]
    table1_config = table1_model_data.get("config", {})
    TABLE1_MODEL_LOADED = True
except Exception as e:
    print(f"Warning: Could not load Table 1 model: {e}")
    TABLE1_MODEL_LOADED = False

try:
    table3_model_data = joblib.load(os.path.join(SCRIPT_DIR, "models", "student_table3.joblib"))
    table3_model = table3_model_data["model"]
    table3_features = table3_model_data["features"]
    table3_label_encoders = table3_model_data["label_encoders"]
    TABLE3_MODEL_LOADED = True
except Exception as e:
    print(f"Warning: Could not load Table 3 model: {e}")
    TABLE3_MODEL_LOADED = False


app = FastAPI()


def prob_label(p):
    """Convert probability to risk label"""
    if p >= 0.7:
        return "High"
    elif p >= 0.3:
        return "Mid"
    return "Low"


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
    if not TABLE1_MODEL_LOADED:
        return {"error": "Model not loaded", "predictions": []}

    try:
        X = np.array(batch_features.batch)
        probs = table1_model.predict_proba(X)[:, 1]
        predictions = [
            {
                "prediction_confidence": float(p),
                "risk_label": prob_label(p)
            }
            for p in probs
        ]
        return {"predictions": predictions}
    except Exception as e:
        return {"error": str(e), "predictions": []}


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
                latest_mid = attendance.sort_values(["Year", "Month_Num"], ascending=False).iloc[0]
                cur_year = int(latest_mid["Year"])
                cur_month = latest_mid["Month"]

                mid_rows = attendance[
                    (attendance["Year"] == cur_year) & (attendance["Month"] == cur_month)
                ]

                pred_rows = []
                for sid in demographics["Student_ID"].unique():
                    row = mid_rows[
                        (mid_rows["Student_ID"] == sid) &
                        (mid_rows["Year"] == cur_year) &
                        (mid_rows["Month"] == cur_month)
                    ]
                    if row.empty:
                        continue

                    present = row.iloc[0]["Present"]
                    total = row.iloc[0]["Total_School_Days"]
                    att_current = round((present / total) * 100, 1) if total > 0 else 0

                    student_scores = daily_agg[
                        (daily_agg["Student_ID"] == sid) &
                        (daily_agg["Year"] == cur_year) &
                        (daily_agg["Month"] == cur_month)
                    ]
                    perf_current = round(student_scores["Daily_Perf"].mean(), 2) if not student_scores.empty else 0

                    demo = demographics[demographics["Student_ID"] == sid].iloc[0].to_dict()
                    pred_rows.append({
                        "Student_ID": sid,
                        "Att_Current_mid": att_current,
                        "Perf_Current_mid": perf_current,
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
        scores["Score"] = pd.to_numeric(scores["Score"], errors="coerce").fillna(0)
        scores["Total_Score"] = pd.to_numeric(scores["Total_Score"], errors="coerce").fillna(1)

        score_type_agg = (
            scores.groupby(["Student_ID", "Subject", "Score_Type"], as_index=False)
            .agg({"Score": "sum", "Total_Score": "sum"})
        )
        score_type_agg["Score_Pct"] = (score_type_agg["Score"] / score_type_agg["Total_Score"] * 100).round(1)

        def find_strength_weakness(group):
            if group["Score_Pct"].nunique() <= 1:
                return pd.Series({"Strength": "Balanced", "Weakness": "Balanced"})
            max_row = group.loc[group["Score_Pct"].idxmax()]
            min_row = group.loc[group["Score_Pct"].idxmin()]
            return pd.Series({
                "Strength": f"{max_row['Subject']} – {max_row['Score_Type']} ({max_row['Score_Pct']}%)",
                "Weakness": f"{min_row['Subject']} – {min_row['Score_Type']} ({min_row['Score_Pct']}%)"
            })

        strength_weakness = score_type_agg.groupby("Student_ID", group_keys=False).apply(find_strength_weakness).reset_index()
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

        if TABLE3_MODEL_LOADED and not attendance.empty:
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

                        r = {
                            "Student_ID": sid,
                            "Att_Current": round(safe_att(cur), 1),
                            "Perf_Current": cur["Perf_Current"],
                            "Att_Past1": round(safe_att(g.iloc[i-1]), 1) if i-1 >= 0 else 0,
                            "Perf_Past1": g.iloc[i-1]["Perf_Current"] if i-1 >= 0 else 0,
                            "Att_Past2": round(safe_att(g.iloc[i-2]), 1) if i-2 >= 0 else 0,
                            "Perf_Past2": g.iloc[i-2]["Perf_Current"] if i-2 >= 0 else 0,
                            "Month_Num": cur["Month_Num"],
                            "Month": cur["Month"],
                            "Year": cur["Year"]
                        }
                        r["Weighted_Attendance"] = np.mean([r["Att_Current"], r["Att_Past1"], r["Att_Past2"]])
                        r["Weighted_Performance"] = np.mean([r["Perf_Current"], r["Perf_Past1"], r["Perf_Past2"]])
                        r["Weighted_Current"] = 0.4 * r["Weighted_Attendance"] + 0.6 * r["Weighted_Performance"]
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
                            "Prob_HighRisk_pct": row.get("Prob_HighRisk_NextMonth_pct", 0),
                            "Risk_Label": row.get("Risk_Label", "N/A")
                        })
            except Exception as e:
                print(f"Table 3 prediction error: {e}")

        return {
            "success": True,
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
