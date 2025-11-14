from fastapi import FastAPI
from pydantic import BaseModel
import joblib
import numpy as np


# Define request format
class Features(BaseModel):
    values: list[float]

class BatchFeatures(BaseModel):
    batch: list[list[float]]

# Load model once at startup
model, feature_names = joblib.load("random_forest_model.joblib")

app = FastAPI()

@app.post("/prediction_probability")
def predict_proba_endpoint(data: Features):
    X = np.array([data.values])

    # Use predict_proba instead of predict
    probabilities = model.predict_proba(X)

    # Extract the probability for the positive class (index 1)
    # The output is typically [[P(Class 0), P(Class 1)]]
    positive_class_probability = probabilities[0][1]

    return {

        "prediction_confidence": round(positive_class_probability * 100, 2)
    }


@app.post("/prediction_probability_batch")
def predict_proba_batch_endpoint(data: BatchFeatures):
    # Expect data.batch to be a list of feature vectors
    if not data.batch:
        return {"predictions": []}

    X = np.array(data.batch)
    probabilities = model.predict_proba(X)
    # Extract positive class probability for each row
    positive_probs = [float(p[1]) for p in probabilities]
    # Return as percentages rounded to 2 decimals
    confidences = [round(p * 100, 2) for p in positive_probs]
    return {"predictions": confidences}
