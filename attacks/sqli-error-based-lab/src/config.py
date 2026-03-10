import os

LAB_NAME = os.getenv("LAB_NAME", "sqli-error-based")
PORT = int(os.getenv("PORT", "5000"))
DB_PATH = os.getenv("DB_PATH", "/app/data/shop.db")
FLAG = os.getenv("LAB_FLAG", "DIABLE{SQLI_ERROR_BASED_OK}")