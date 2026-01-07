"""
Test script to verify the database connection and fetch demographics data.
This demonstrates how the Python ML pipeline connects to the Laravel database.

Usage:
    cd python
    python test_db_connection.py
"""

import sys
import os


sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from utils.data_fetcher import fetch_demographics, fetch_attendance, fetch_scores

def main():
    print("=" * 60)
    print("SchoolPulse - Database Connection Test")
    print("=" * 60)

    # Test 1: Fetch Demographics (Table 1 Features)
    print("\n[1] Testing Demographics Fetch (Table 1 Features)...")
    try:
        demographics_df = fetch_demographics()
        print(f"    ✓ Successfully fetched {len(demographics_df)} student records")
        print("\n    Sample data (first 5 rows):")
        print(demographics_df.head().to_string(index=False))
        print(f"\n    Columns: {list(demographics_df.columns)}")
    except Exception as e:
        print(f"    ✗ Error fetching demographics: {e}")
        return 1

    # Test 2: Fetch Attendance (Table 2 Features)
    print("\n" + "-" * 60)
    print("\n[2] Testing Attendance Fetch (Table 2 Features)...")
    try:
        attendance_df = fetch_attendance()
        print(f"    ✓ Successfully fetched {len(attendance_df)} attendance records")
        if len(attendance_df) > 0:
            print("\n    Sample data (first 5 rows):")
            print(attendance_df.head().to_string(index=False))
            print(f"\n    Columns: {list(attendance_df.columns)}")
        else:
            print("    ⚠ No attendance records found in database")
    except Exception as e:
        print(f"    ✗ Error fetching attendance: {e}")

    # Test 3: Fetch Scores (Table 3 Features)
    print("\n" + "-" * 60)
    print("\n[3] Testing Scores Fetch (Table 3 Features)...")
    try:
        scores_df = fetch_scores()
        print(f"    ✓ Successfully fetched {len(scores_df)} score records")
        if len(scores_df) > 0:
            print("\n    Sample data (first 5 rows):")
            print(scores_df.head().to_string(index=False))
            print(f"\n    Columns: {list(scores_df.columns)}")
        else:
            print("    ⚠ No score records found in database")
    except Exception as e:
        print(f"    ✗ Error fetching scores: {e}")

    print("\n" + "=" * 60)
    print("Database connection test completed successfully!")
    print("=" * 60)

    return 0

if __name__ == "__main__":
    sys.exit(main())
