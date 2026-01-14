from app import app
from fastapi.testclient import TestClient
import json

client = TestClient(app)
response = client.get('/features/tables')
print('Status:', response.status_code)

if response.status_code == 200:
    data = response.json()
    t1 = data.get('table1', [])
    print(f'Type of table1: {type(t1)}')
    print(f'Table1 content:')
    print(json.dumps(t1, indent=2, default=str)[:2000])
else:
    print('Error:', response.text)
