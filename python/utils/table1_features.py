import pandas as pd
import numpy as np

DAILY_MAX_SCORE = 310

def prob_label(p):
    if p >= 0.7:
        return "High"
    elif p >= 0.3:
        return "Mid"
    else:
        return "Low"

def build_table1_predictions(model, best_features, label_encoders, config):

from .data_fetcher import fetch_demographics, fetch_attendance, fetch_scores

DAILY_MAX_SCORE = 310

def prob_label(p):
    if p >= 0.7:
        return "High"
    elif p >= 0.3:
        return "Mid"
    else:
        return "Low"

def build_table1_predictions(model, best_features, label_encoders, config):

    demographics = fetch_demographics()
    attendance = fetch_attendance()
    scores = fetch_scores()

    # Standardization (strip handled in fetcher but good to keep if needed)
    # demographics.columns etc are already clean from fetcher.

    scores["Date"] = pd.to_datetime(scores["Date"], errors="coerce")
    scores["Score"] = pd.to_numeric(scores["Score"], errors="coerce")
    scores["Total_Score"] = pd.to_numeric(scores["Total_Score"], errors="coerce")

    # ===============================
    # NORMALIZED DAILY PERFORMANCE
    # ===============================
    scores["Daily_Percent"] = (scores["Score"] / scores["Total_Score"]) * 100

    daily_agg = (
        scores.dropna(subset=["Date"])
        .groupby(["Student_ID", "Year", "Month", "Date"], as_index=False)
        .agg(Daily_Perf=("Daily_Percent", "mean"))
    )

    # ===============================
    # CURRENT MID-MONTH (Latest Available Data)
    # ===============================
    # Map months to numbers for sorting
    month_map = {
        'January':1,'February':2,'March':3,'April':4,'May':5,'June':6,
        'July':7,'August':8,'September':9,'October':10,'November':11,'December':12
    }
    attendance["Month_Num"] = attendance["Month"].map(month_map)

    if attendance.empty:
         raise ValueError("No attendance data found in database")

    # Sort by Year and Month to find the latest available period
    latest_mid = attendance.sort_values(
        ["Year", "Month_Num"], ascending=False
    ).iloc[0]

    cur_year = int(latest_mid["Year"])
    cur_month = latest_mid["Month"]

    # We use all rows matching this latest year/month as our "current" snapshot
    mid_rows = attendance[
        (attendance["Year"] == cur_year) &
        (attendance["Month"] == cur_month)
    ]

    pred_rows = []

    for sid in demographics["Student_ID"].unique():

        row = mid_rows[
            (mid_rows["Student_ID"] == sid)
            & (mid_rows["Year"] == cur_year)
            & (mid_rows["Month"] == cur_month)
        ]

        if row.empty:
            continue

        present = row.iloc[0]["Present"]
        total = row.iloc[0]["Total_School_Days"]
        att_current = round((present / total) * 100, 1) if total > 0 else 0

        student_scores = daily_agg[
            (daily_agg["Student_ID"] == sid)
            & (daily_agg["Year"] == cur_year)
            & (daily_agg["Month"] == cur_month)
        ]

        perf_current = (
            round(student_scores["Daily_Perf"].mean(), 2)
            if not student_scores.empty else 0
        )

        demo = demographics[demographics["Student_ID"] == sid].iloc[0].to_dict()

        pred_rows.append({
            "Student_ID": sid,
            "Att_Current_mid": att_current,
            "Perf_Current_mid": perf_current,
            **demo
        })

    pred_df = pd.DataFrame(pred_rows)

    # ===============================
    # ENCODE CATEGORICAL FEATURES
    # ===============================
    for col, le in label_encoders.items():
        pred_df[col] = pred_df[col].astype(str).fillna("Unknown")
        pred_df[col] = pred_df[col].apply(
            lambda x: x if x in le.classes_ else "Unknown"
        )

        if "Unknown" not in le.classes_:
            le.classes_ = np.append(le.classes_, "Unknown")

        pred_df[col + "_enc"] = le.transform(pred_df[col])

    for f in best_features:
        if f not in pred_df.columns:
            pred_df[f] = 0.0

    X_pred = pred_df[best_features].fillna(0)

    # ===============================
    # PREDICTION
    # ===============================
    pred_df["Prob_HighRisk_EndMonth"] = model.predict_proba(X_pred)[:, 1]
    pred_df["Prob_HighRisk_EndMonth_pct"] = (
        pred_df["Prob_HighRisk_EndMonth"] * 100
    ).round(1)
    pred_df["Prob_HighRisk_Label"] = pred_df["Prob_HighRisk_EndMonth"].apply(prob_label)

    final_cols = [
        "Student_ID",
        "Name",
        "Sexuality",
        "Distance_km",
        "Transportation",
        "Socioeconomic_Status",
        "Att_Current_mid",
        "Perf_Current_mid",
        "Prob_HighRisk_EndMonth",
        "Prob_HighRisk_EndMonth_pct",
        "Prob_HighRisk_Label"
    ]

    return pred_df[final_cols].sort_values(
        "Prob_HighRisk_EndMonth", ascending=False
    )
