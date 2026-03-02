import time
from flask import jsonify
from config import LAB_NAME, DB_PATH
from db import get_conn

def health_handler():
    status_code = 200
    try:
        conn = get_conn()
        conn.execute("SELECT 1")
        conn.close()
        checks = {"database": {"status": "ok", "path": DB_PATH}}
    except Exception as e:
        status_code = 503
        checks = {"database": {"status": "error", "detail": str(e), "path": DB_PATH}}

    return jsonify({
        "status": "healthy" if status_code == 200 else "unhealthy",
        "service": LAB_NAME,
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
        "checks": checks
    }), status_code