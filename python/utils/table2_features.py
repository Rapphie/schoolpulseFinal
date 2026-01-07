# utils/table2_features.py
import pandas as pd
import numpy as np

MONTH_MAP = {
    'January':1,'February':2,'March':3,'April':4,'May':5,'June':6,
    'July':7,'August':8,'September':9,'October':10,'November':11,'December':12
}

def build_table2_predictions():

    # =========================
    # LOAD DATA
    # =========================
    from .data_fetcher import fetch_demographics, fetch_attendance, fetch_scores

    df_demo = fetch_demographics()
    df_att = fetch_attendance()
    df_scores = fetch_scores()

    # =========================
    # CLEAN SCORES
    # =========================
    df_scores["Date"] = pd.to_datetime(df_scores["Date"], errors="coerce")
    df_scores["Score"] = pd.to_numeric(df_scores["Score"], errors="coerce").fillna(0)
    df_scores["Total_Score"] = pd.to_numeric(df_scores["Total_Score"], errors="coerce").fillna(1)
    df_scores["Year"] = pd.to_numeric(df_scores["Year"], errors="coerce")
    df_scores["Month"] = df_scores["Month"].astype(str)
    df_scores["Month_Num"] = df_scores["Month"].map(MONTH_MAP)

    # =========================
    # DETECT LATEST MONTH
    # =========================
    latest = df_scores.dropna(subset=["Year","Month_Num"]).sort_values(
        ["Year","Month_Num"], ascending=False
    ).iloc[0]

    current_month = latest["Month"]
    current_year = int(latest["Year"])

    df_scores_curr = df_scores[
        (df_scores["Year"] == current_year) &
        (df_scores["Month"] == current_month)
    ]

    if df_scores_curr.empty:
        df_scores_curr = df_scores.copy()

    # =========================
    # SCORE TYPE AGG
    # =========================
    score_type_agg = (
        df_scores_curr
        .groupby(["Student_ID","Subject","Score_Type"], as_index=False)
        .agg({"Score":"sum","Total_Score":"sum"})
    )

    score_type_agg["Score_Percentage"] = (
        score_type_agg["Score"] / score_type_agg["Total_Score"] * 100
    ).round(1)

    # =========================
    # STRENGTH / WEAKNESS
    # =========================
    def find_strength_weakness(group):
        if group["Score_Percentage"].nunique() <= 1:
            return pd.Series(["Balanced","Balanced"])
        max_row = group.loc[group["Score_Percentage"].idxmax()]
        min_row = group.loc[group["Score_Percentage"].idxmin()]
        return pd.Series([
            f"{max_row['Subject']} – {max_row['Score_Type']} ({max_row['Score_Percentage']}%)",
            f"{min_row['Subject']} – {min_row['Score_Type']} ({min_row['Score_Percentage']}%)"
        ])

    strength_weakness = (
        score_type_agg
        .groupby("Student_ID", group_keys=False)
        .apply(find_strength_weakness)
        .reset_index()
        .rename(columns={0:"Strength",1:"Weakness"})
    )

    # =========================
    # PERFORMANCE
    # =========================
    performance = (
        score_type_agg
        .groupby("Student_ID", as_index=False)
        .agg(PerformancePercentage=("Score_Percentage","mean"))
    ).round(1)

    # =========================
    # ATTENDANCE
    # =========================
    if {"Present","Total_School_Days"}.issubset(df_att.columns):
        df_att["AttendancePercentage"] = (
            df_att["Present"] / df_att["Total_School_Days"] * 100
        ).round(1)
    else:
        df_att["AttendancePercentage"] = 0.0

    df_att["Year"] = pd.to_numeric(df_att["Year"], errors="coerce")
    df_att["Month"] = df_att["Month"].astype(str)

    df_att_curr = df_att[
        (df_att["Year"] == current_year) &
        (df_att["Month"] == current_month)
    ]

    if df_att_curr.empty:
        df_att_curr = df_att.copy()

    attendance = (
        df_att_curr
        .groupby("Student_ID", as_index=False)
        .agg(AttendancePercentage=("AttendancePercentage","mean"))
    )

    # =========================
    # FINAL MERGE
    # =========================
    df = (
        df_demo
        .merge(performance, on="Student_ID", how="left")
        .merge(attendance, on="Student_ID", how="left")
        .merge(strength_weakness, on="Student_ID", how="left")
    )

    df[["PerformancePercentage","AttendancePercentage"]] = (
        df[["PerformancePercentage","AttendancePercentage"]].fillna(0)
    )

    df["EngagementScore"] = (
        df["PerformancePercentage"]*0.60 +
        df["AttendancePercentage"]*0.40
    ).round(1)

    cols = [
        "Student_ID","Name","Sexuality",
        "AttendancePercentage","PerformancePercentage",
        "Strength","Weakness","EngagementScore"
    ]
    cols = [c for c in cols if c in df.columns]

    top_high = (
        df.sort_values("EngagementScore", ascending=False)
        .drop_duplicates("Student_ID")
        .head(3)[cols]
    )

    top_low = (
        df.sort_values("EngagementScore", ascending=True)
        .drop_duplicates("Student_ID")
        .head(3)[cols]
    )

    return top_high, top_low
