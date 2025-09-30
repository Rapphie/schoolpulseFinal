from fastapi import FastAPI
from pydantic import BaseModel
import joblib
import numpy as np


# Define request format
class Features(BaseModel):
    values: list[float]


# Load model once at startup
model = joblib.load("random_forest_model.joblib")

app = FastAPI()


@app.post("/predict")
def predict(data: Features):
    X = np.array([data.values])
    pred = model.predict(X)
    return {"prediction": int(pred[0])}
