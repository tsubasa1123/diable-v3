from flask import jsonify
from db import init_db

def reset_handler():
    try:
        init_db()
        return jsonify({"status": "ok", "message": "Reset effectué (DB réinitialisée)."}), 200
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500