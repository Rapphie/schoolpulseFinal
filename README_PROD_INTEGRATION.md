# Production Integration Guide (Joblib Outputs)

## 1. Purpose
This guide explains how to integrate the outputs of `production_randomforest_pipeline.py` into a production system.

The pipeline currently writes three system-facing artifacts:

- `artifacts/randomforest/current_predictions.joblib`
- `artifacts/randomforest/next_predictions.joblib`
- `artifacts/randomforest/engagement_predictions.joblib`

## 2. Important Runtime Behavior
`save_artifacts()` in `production_randomforest_pipeline.py` cleans the whole `artifacts/randomforest` folder before writing new outputs.

Impact:
- Do not store unrelated files in `artifacts/randomforest`.
- Treat the folder as fully managed by the pipeline.

## 3. Input Data Requirements
By default, the pipeline reads:

- `demographics_unique.csv`
- `expanded_attendance.csv`
- `expanded_raw_scores.csv`

These defaults are defined in `PipelineConfig`.

## 4. Output Contracts
### 4.1 `current_predictions.joblib`
Type: `pandas.DataFrame`

Columns:
- `ID`
- `Name`
- `Status`
- `Risk Category Score`
- `Risk Raw (Model)`
- `Forecast % (Attendance)`
- `Current Status`
- `History %`
- `Drop vs History`
- `Commute`

### 4.2 `next_predictions.joblib`
Type: `pandas.DataFrame`

Columns:
- `ID`
- `Student Name`
- `Status`
- `Risk Score`
- `Forecast %`
- `Current Status`
- `History %`
- `Trend`
- `Commute`
- `SES`

### 4.3 `engagement_predictions.joblib`
Type: `dict`

Keys:
- `master` (`pandas.DataFrame`)
- `honors` (`pandas.DataFrame`)
- `support` (`pandas.DataFrame`)
- `as_of` (`dict`)

`as_of` example:
```json
{
  "as_of_date": "2026-03-27",
  "year": 2026,
  "month_num": 3,
  "month_name": "March"
}
```

## 5. Environment Pinning (Strongly Recommended)
Pin versions to avoid serialization/deserialization issues:

- Python `3.13.2`
- pandas `2.3.3`
- numpy `2.3.4`
- scikit-learn `1.8.0`
- joblib `1.5.3`

## 6. Batch Refresh Runbook
Run from project root:

```powershell
cd c:\Users\raffy\codes\ixynrs\deploy
python production_randomforest_pipeline.py
```

After run, verify:
- `artifacts/randomforest/current_predictions.joblib`
- `artifacts/randomforest/next_predictions.joblib`
- `artifacts/randomforest/engagement_predictions.joblib`

## 7. Safe Loader Snippet
```python
from pathlib import Path
import joblib
import pandas as pd

BASE = Path("artifacts/randomforest")

def load_prediction_store():
    current_df = joblib.load(BASE / "current_predictions.joblib")
    next_df = joblib.load(BASE / "next_predictions.joblib")
    engagement = joblib.load(BASE / "engagement_predictions.joblib")

    if not isinstance(current_df, pd.DataFrame):
        raise TypeError("current_predictions.joblib must contain a DataFrame")
    if not isinstance(next_df, pd.DataFrame):
        raise TypeError("next_predictions.joblib must contain a DataFrame")
    if not isinstance(engagement, dict):
        raise TypeError("engagement_predictions.joblib must contain a dict")

    for key in ("master", "honors", "support", "as_of"):
        if key not in engagement:
            raise KeyError(f"engagement payload missing key: {key}")

    return {
        "current": current_df,
        "next": next_df,
        "engagement_master": engagement["master"],
        "engagement_honors": engagement["honors"],
        "engagement_support": engagement["support"],
        "engagement_as_of": engagement["as_of"],
    }
```

## 8. Service Integration Pattern
1. Load all three artifacts at service startup.
2. Keep tables in memory for low-latency reads.
3. Expose API endpoints backed by in-memory tables.
4. On scheduled refresh:
- run pipeline
- load all three new artifacts
- atomically swap in-memory references

## 9. Minimal API Suggestions
1. `GET /predictions/current`
- source: `current_predictions.joblib`

2. `GET /predictions/next`
- source: `next_predictions.joblib`

3. `GET /predictions/engagement/master`
- source: `engagement_predictions.joblib["master"]`

4. `GET /predictions/engagement/honors`
- source: `engagement_predictions.joblib["honors"]`

5. `GET /predictions/engagement/support`
- source: `engagement_predictions.joblib["support"]`

6. `GET /predictions/engagement/as-of`
- source: `engagement_predictions.joblib["as_of"]`

## 10. Health and Validation Checks
Run these checks at startup or post-refresh:

1. File existence check for all three artifacts.
2. Type check:
- current/next -> DataFrame
- engagement -> dict
3. Schema check for required columns.
4. Non-empty check:
- current and next should typically have rows (default top 15).
5. Freshness check:
- inspect file modified time against expected schedule.

## 11. Failure Handling
If refresh fails:
1. Keep previous in-memory tables active.
2. Log error with stage (`load`, `type validation`, `schema validation`).
3. Alert operator and retry by policy.

## 12. Handoff Checklist
Before go-live, confirm:
1. Pipeline run succeeds in target environment.
2. Version pins are enforced.
3. Loader validation is implemented.
4. Endpoints are mapped to correct artifact objects.
5. Refresh process uses atomic swap and rollback behavior.
6. Monitoring and alerts are in place.

