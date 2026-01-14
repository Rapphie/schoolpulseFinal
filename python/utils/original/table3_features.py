# utils/table3_features.py
import pandas as pd
import numpy as np

MONTHS = [
    "January","February","March","April","May","June",
    "July","August","September","October","November","December"
]
month_to_num = {m: i+1 for i, m in enumerate(MONTHS)}
num_to_month = {i+1: m for i, m in enumerate(MONTHS)}

def prob_label(p):
    if p >= 0.7:
        return "High"
    elif p >= 0.3:
        return "Mid"
    return "Low"


def build_table3_predictions(model, features, label_encoders):

    # =========================
    # LOAD DATA
    # =========================
    demographics = pd.read_excel("data/expanded_demographics.xlsx")
    attendance = pd.read_excel("data/expanded_monthly_attendance.xlsx")
    scores = pd.read_csv("data/expanded_raw_scores_modified.csv")

    demographics.columns = demographics.columns.str.strip()
    attendance.columns = attendance.columns.str.strip()
    scores.columns = scores.columns.str.strip()

    attendance["Month_Num"] = attendance["Month"].map(month_to_num)

    # =========================
    # PERFORMANCE (MONTHLY)
    # =========================
    scores["Date"] = pd.to_datetime(scores["Date"], errors="coerce")
    scores["Score"] = pd.to_numeric(scores["Score"], errors="coerce").fillna(0)
    scores["Total_Score"] = pd.to_numeric(scores["Total_Score"], errors="coerce").fillna(0)

    scores["Pct"] = (scores["Score"] / scores["Total_Score"]) * 100

    perf_month = (
        scores.dropna(subset=["Date"])
        .groupby(["Student_ID","Year","Month"], as_index=False)
        .agg(Perf_Current=("Pct","mean"))
    )

    # =========================
    # MERGE
    # =========================
    df = attendance.merge(
        perf_month, on=["Student_ID","Year","Month"], how="left"
    )
    df["Perf_Current"] = df["Perf_Current"].fillna(0)
    df = df.sort_values(["Student_ID","Year","Month_Num"])

    # =========================
    # BUILD ROLLING FEATURES
    # =========================
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
                "Att_Past1": round(safe_att(g.iloc[i-1]),1) if i-1 >= 0 else 0,
                "Perf_Past1": g.iloc[i-1]["Perf_Current"] if i-1 >= 0 else 0,
                "Att_Past2": round(safe_att(g.iloc[i-2]),1) if i-2 >= 0 else 0,
                "Perf_Past2": g.iloc[i-2]["Perf_Current"] if i-2 >= 0 else 0,
                "Month_Num": cur["Month_Num"],
                "Month": cur["Month"],
                "Year": cur["Year"]
            }

            r["Weighted_Attendance"] = np.mean([r["Att_Current"], r["Att_Past1"], r["Att_Past2"]])
            r["Weighted_Performance"] = np.mean([r["Perf_Current"], r["Perf_Past1"], r["Perf_Past2"]])
            r["Weighted_Current"] = 0.4*r["Weighted_Attendance"] + 0.6*r["Weighted_Performance"]

            rows.append(r)

    feat_df = pd.DataFrame(rows).merge(demographics, on="Student_ID", how="left")

    # =========================
    # LATEST MONTH ONLY
    # =========================
    latest = attendance.sort_values(
        ["Year","Month_Num"], ascending=False
    ).iloc[0]

    feat_df = feat_df[
        (feat_df["Year"] == latest["Year"]) &
        (feat_df["Month"] == latest["Month"])
    ]

    # =========================
    # ENCODE CATEGORICALS
    # =========================
    for col, le in label_encoders.items():
        feat_df[col] = feat_df[col].astype(str).fillna("Unknown")
        feat_df[col] = feat_df[col].apply(lambda x: x if x in le.classes_ else "Unknown")

        if "Unknown" not in le.classes_:
            le.classes_ = np.append(le.classes_, "Unknown")

        feat_df[col + "_enc"] = le.transform(feat_df[col])

    for f in features:
        if f not in feat_df.columns:
            feat_df[f] = 0.0

    # =========================
    # PREDICT
    # =========================
    X = feat_df[features].fillna(0)
    feat_df["Prob_HighRisk_NextMonth"] = model.predict_proba(X)[:,1]
    feat_df["Prob_HighRisk_NextMonth_pct"] = (feat_df["Prob_HighRisk_NextMonth"]*100).round(1)
    feat_df["Prob_HighRisk_Label"] = feat_df["Prob_HighRisk_NextMonth"].apply(prob_label)

    return feat_df.sort_values(
        "Prob_HighRisk_NextMonth", ascending=False
    )
