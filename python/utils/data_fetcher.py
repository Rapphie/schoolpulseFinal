import pandas as pd
from sqlalchemy import text
from .db_utils import get_db_engine

def fetch_demographics():
    """
    Fetches student demographics from the database and formats it
    to match the structure of expanded_demographics.xlsx.

    Model Expects:
    ['Student_ID', 'Name', 'Sexuality', 'Distance_km', 'Transportation', 'Socioeconomic_Status']
    """
    engine = get_db_engine()

    # We map existing DB columns to Model features where possible.
    # Note: 'Distance_km' and 'Transportation' do not exist in the current students table,
    # so we return default values to allow the pipeline to run.
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

    # Ensure ID is treated consistently
    df['Student_ID'] = df['Student_ID'].astype(int)

    # e Gender/Sexuality to Title Case (male -> Male) to match training data
    if 'Sexuality' in df.columns:
        df['Sexuality'] = df['Sexuality'].str.title()

    # Standardize Socioeconomic_Status inputs if needed (e.g. capitalize)
    if 'Socioeconomic_Status' in df.columns:
        df['Socioeconomic_Status'] = df['Socioeconomic_Status'].apply(
            lambda x: x.title() if isinstance(x, str) else x
        )

    # Fill NaN for new columns if they are empty in DB
    df['Distance_km'] = df['Distance_km'].fillna(0.0)
    df['Transportation'] = df['Transportation'].fillna("Unknown")

    # Reorder to match existing format exactly (optional but good for debugging)
    df = df[['Student_ID', 'Name', 'Sexuality', 'Distance_km', 'Transportation', 'Socioeconomic_Status']]

    return df

def fetch_attendance():
    """
    Fetches attendance data and aggregates it to monthly level
    to match expanded_monthly_attendance.xlsx.
    """
    engine = get_db_engine()
    # We need to aggregate daily attendance to monthly summaries
    # status 'present' counts as 1, others 0 for "Present" column (assuming logic)
    # Total entries per student per month is "Total_School_Days"

    # Note: Adjust logic if 'late' counts as present or particular status handling is needed.
    query = """
    SELECT
        student_id as Student_ID,
        YEAR(date) as Year,
        MONTHNAME(date) as Month,
        COUNT(CASE WHEN status = 'present' THEN 1 END) as Present,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as Late,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as Absent,
        COUNT(*) as Total_School_Days
    FROM attendances
    GROUP BY student_id, YEAR(date), MONTHNAME(date)
    """
    df = pd.read_sql(query, engine)
    df['Student_ID'] = df['Student_ID'].astype(int)
    return df

def fetch_scores():
    """
    Fetches assessment scores and formats it
    to match expanded_raw_scores_modified.csv.
    """
    engine = get_db_engine()
    query = """
    SELECT
        sc.student_id as Student_ID,
        sub.name as Subject,
        a.assessment_date as Date,
        a.type as Score_Type,
        sc.score as Score,
        a.max_score as Total_Score
    FROM assessment_scores sc
    JOIN assessments a ON sc.assessment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    """
    df = pd.read_sql(query, engine)

    df['Student_ID'] = df['Student_ID'].astype(int)
    df['Date'] = pd.to_datetime(df['Date'])
    df['Year'] = df['Date'].dt.year
    df['Month'] = df['Date'].dt.month_name()

    # DB: written_works, performance_tasks, quarterly_assessments
    type_map = {
        'written_works': 'Written Work',
        'performance_tasks': 'Performance Task',
        'quarterly_assessments': 'Quarterly Assessment'
    }
    df['Score_Type'] = df['Score_Type'].map(type_map).fillna(df['Score_Type'])

    return df
