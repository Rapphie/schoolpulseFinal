import pandas as pd
from sqlalchemy import text
from .db_utils import get_db_engine

"""
=============================================================================
DATA FORMAT REFERENCE (from original Excel/CSV files used to train model)
=============================================================================

Demographics (expanded_demographics.xlsx):
    Headers: ['Student_ID', 'Name', 'Sexuality', 'Distance_km', 'Transportation', 'Socioeconomic_Status']
    Example: 'S001', 'Xena Romero', 'Female', 3.74, 'Motorcycle', 'Low'

    Sexuality values: 'Male', 'Female'
    Transportation values: 'Motorcycle', 'Tricycle', 'Walk', 'Jeepney', etc.
    Socioeconomic_Status values: 'Low', 'Mid', 'High'

Attendance (expanded_monthly_attendance.xlsx):
    Headers: ['Student_ID', 'Month', 'Year', 'Total_School_Days', 'Present', 'Absent', 'Late']
    Example: 'S001', 'July', 2023, 20, 15, 5, 3

    Month values: Full month names ('January', 'February', ... 'December')

Scores (expanded_raw_scores_modified.csv):
    Headers: ['Student_ID', 'Date', 'Month', 'Year', 'Subject', 'Score_Type', 'Score', 'Total_Score']
    Example: 'S001', '1/1/2023', 'January', 2023, 'Filipino', 'Oral_Participation', 7, 10

    Score_Type values: 'Oral_Participation', 'Written_Work', 'Performance_Task', 'Quarterly_Assessment'
    Subject values: 'Filipino', 'Science', 'Araling Panlipunan', 'Mathematics', 'English'
=============================================================================
"""


def fetch_demographics():
    """
    Fetches student demographics from the database and formats it
    to match the structure of expanded_demographics.xlsx.

    Expected Output Columns:
    ['Student_ID', 'Name', 'Sexuality', 'Distance_km', 'Transportation', 'Socioeconomic_Status']
    """
    engine = get_db_engine()

    query = """
    SELECT
        s.id as Student_ID,
        CONCAT(s.first_name, ' ', s.last_name) as Name,
        s.gender as Sexuality,
        s.family_income as Socioeconomic_Status,
        s.distance_km as Distance_km,
        s.transportation as Transportation
    FROM students s
    """
    df = pd.read_sql(query, engine)

    # Ensure ID is treated consistently (original uses 'S001' format string)
    df['Student_ID'] = df['Student_ID'].astype(int)

    # Gender/Sexuality to Title Case (male -> Male) to match training data
    if 'Sexuality' in df.columns:
        df['Sexuality'] = df['Sexuality'].str.title()

    # Standardize Socioeconomic_Status (capitalize to match 'Low', 'Mid', 'High')
    if 'Socioeconomic_Status' in df.columns:
        df['Socioeconomic_Status'] = df['Socioeconomic_Status'].apply(
            lambda x: x.title() if isinstance(x, str) else x
        )

    # Standardize Transportation (capitalize to match training data)
    if 'Transportation' in df.columns:
        df['Transportation'] = df['Transportation'].apply(
            lambda x: x.title() if isinstance(x, str) else x
        )

    # Fill NaN for columns that may not exist in DB
    df['Distance_km'] = pd.to_numeric(df['Distance_km'], errors='coerce').fillna(0.0)
    df['Transportation'] = df['Transportation'].fillna("Unknown")

    # Reorder columns to match original format exactly
    df = df[['Student_ID', 'Name', 'Sexuality', 'Distance_km', 'Transportation', 'Socioeconomic_Status']]

    return df

def fetch_attendance():
    """
    Fetches attendance data and aggregates it to monthly level
    to match expanded_monthly_attendance.xlsx.

    Expected Output Columns:
    ['Student_ID', 'Month', 'Year', 'Total_School_Days', 'Present', 'Absent', 'Late']

    Month should be full month name ('January', 'February', etc.)
    """
    engine = get_db_engine()

    query = """
    SELECT
        student_id as Student_ID,
        YEAR(date) as Year,
        MONTHNAME(date) as Month,
        COUNT(*) as Total_School_Days,
        COUNT(CASE WHEN status = 'present' THEN 1 END) as Present,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as Absent,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as Late
    FROM attendances
    GROUP BY student_id, YEAR(date), MONTHNAME(date)
    ORDER BY student_id, Year, MONTH(date)
    """
    df = pd.read_sql(query, engine)
    df['Student_ID'] = df['Student_ID'].astype(int)

    # Reorder columns to match original format
    df = df[['Student_ID', 'Month', 'Year', 'Total_School_Days', 'Present', 'Absent', 'Late']]

    return df

def fetch_scores():
    """
    Fetches assessment scores and formats it
    to match expanded_raw_scores_modified.csv.

    Expected Output Columns:
    ['Student_ID', 'Date', 'Month', 'Year', 'Subject', 'Score_Type', 'Score', 'Total_Score']

    Score_Type values: 'Oral_Participation', 'Written_Work', 'Performance_Task', 'Quarterly_Assessment'
    Subject values: 'Filipino', 'Science', 'Araling Panlipunan', 'Mathematics', 'English'
    Month should be full month name ('January', 'February', etc.)
    """
    engine = get_db_engine()
    query = """
    SELECT
        sc.student_id as Student_ID,
        a.assessment_date as Date,
        sub.name as Subject,
        a.type as Score_Type,
        sc.score as Score,
        a.max_score as Total_Score
    FROM assessment_scores sc
    JOIN assessments a ON sc.assessment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    ORDER BY sc.student_id, a.assessment_date
    """
    df = pd.read_sql(query, engine)

    df['Student_ID'] = df['Student_ID'].astype(int)
    df['Date'] = pd.to_datetime(df['Date'])
    df['Month'] = df['Date'].dt.month_name()  # Full month name: 'January', 'February', etc.
    df['Year'] = df['Date'].dt.year

    # Map DB score types to model-expected format (with underscores)
    # DB: 'written_works', 'performance_tasks', 'quarterly_assessments', 'oral_participation'
    # Model: 'Oral_Participation', 'Written_Work', 'Performance_Task', 'Quarterly_Assessment'
    type_map = {
        'oral_participation': 'Oral_Participation',
        'written_works': 'Written_Work',
        'written_work': 'Written_Work',
        'performance_tasks': 'Performance_Task',
        'performance_task': 'Performance_Task',
        'quarterly_assessments': 'Quarterly_Assessment',
        'quarterly_assessment': 'Quarterly_Assessment'
    }
    df['Score_Type'] = df['Score_Type'].str.lower().map(type_map).fillna(df['Score_Type'])

    # Ensure Score and Total_Score are numeric
    df['Score'] = pd.to_numeric(df['Score'], errors='coerce').fillna(0)
    df['Total_Score'] = pd.to_numeric(df['Total_Score'], errors='coerce').fillna(1)

    # Reorder columns to match original CSV format
    df = df[['Student_ID', 'Date', 'Month', 'Year', 'Subject', 'Score_Type', 'Score', 'Total_Score']]

    return df


def verify_data_format():
    """
    Utility function to verify that fetched data matches the expected format
    from original training files. Prints a comparison report.
    """
    print("=" * 70)
    print("DATA FORMAT VERIFICATION")
    print("=" * 70)

    # Expected formats
    expected_demo_cols = ['Student_ID', 'Name', 'Sexuality', 'Distance_km', 'Transportation', 'Socioeconomic_Status']
    expected_att_cols = ['Student_ID', 'Month', 'Year', 'Total_School_Days', 'Present', 'Absent', 'Late']
    expected_scores_cols = ['Student_ID', 'Date', 'Month', 'Year', 'Subject', 'Score_Type', 'Score', 'Total_Score']

    expected_score_types = ['Oral_Participation', 'Written_Work', 'Performance_Task', 'Quarterly_Assessment']

    try:
        print("\n1. DEMOGRAPHICS:")
        demo = fetch_demographics()
        print(f"   Expected columns: {expected_demo_cols}")
        print(f"   Fetched columns:  {demo.columns.tolist()}")
        print(f"   Match: {'✓' if demo.columns.tolist() == expected_demo_cols else '✗'}")
        print(f"   Rows fetched: {len(demo)}")
        if not demo.empty:
            print(f"   Sample: {demo.iloc[0].to_dict()}")
    except Exception as e:
        print(f"   ERROR: {e}")

    try:
        print("\n2. ATTENDANCE:")
        att = fetch_attendance()
        print(f"   Expected columns: {expected_att_cols}")
        print(f"   Fetched columns:  {att.columns.tolist()}")
        print(f"   Match: {'✓' if att.columns.tolist() == expected_att_cols else '✗'}")
        print(f"   Rows fetched: {len(att)}")
        if not att.empty:
            print(f"   Sample: {att.iloc[0].to_dict()}")
    except Exception as e:
        print(f"   ERROR: {e}")

    try:
        print("\n3. SCORES:")
        scores = fetch_scores()
        print(f"   Expected columns: {expected_scores_cols}")
        print(f"   Fetched columns:  {scores.columns.tolist()}")
        print(f"   Match: {'✓' if scores.columns.tolist() == expected_scores_cols else '✗'}")
        print(f"   Rows fetched: {len(scores)}")
        if not scores.empty:
            print(f"   Sample: {scores.iloc[0].to_dict()}")
            print(f"\n   Score_Type values in DB: {scores['Score_Type'].unique().tolist()}")
            print(f"   Expected Score_Types:    {expected_score_types}")
            missing = set(expected_score_types) - set(scores['Score_Type'].unique())
            if missing:
                print(f"   Missing Score_Types: {list(missing)}")
            else:
                print(f"   All expected Score_Types present: ✓")
    except Exception as e:
        print(f"   ERROR: {e}")

    print("\n" + "=" * 70)


if __name__ == "__main__":
    verify_data_format()
