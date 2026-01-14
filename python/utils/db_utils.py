import os
from sqlalchemy import create_engine
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '../../.env'))

def get_db_engine():
    """
    Creates and returns a SQLAlchemy engine based on the .env configuration.
    """
    db_connection = os.getenv('DB_CONNECTION', 'mysql')
    db_host = os.getenv('DB_HOST', '127.0.0.1')
    db_port = os.getenv('DB_PORT', '3306')
    db_database = os.getenv('DB_DATABASE', 'schoolpulsefinaldbdefense')
    db_username = os.getenv('DB_USERNAME', 'root')
    db_password = os.getenv('DB_PASSWORD', '')

    connection_string = f"{db_connection}+pymysql://{db_username}:{db_password}@{db_host}:{db_port}/{db_database}"

    engine = create_engine(connection_string)
    return engine
