import joblib
import os

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_DIR = os.path.join(SCRIPT_DIR, '..', 'models')

def resave(path):
    print(f"Resaving {path}")
    try:
        data = joblib.load(path)
        # Overwrite with same filename to update serialization metadata
        joblib.dump(data, path)
        print(f"Resaved {path} successfully")
    except Exception as e:
        print(f"Failed to resave {path}: {e}")

def main():
    files = ['student_table1.joblib', 'student_table3.joblib']
    for f in files:
        p = os.path.join(MODEL_DIR, f)
        if os.path.exists(p):
            resave(p)
        else:
            print(f"Model not found: {p}")

if __name__ == '__main__':
    main()
