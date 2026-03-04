# health.py — only needed if you split blueprints
import time
from flask import Blueprint, jsonify
import config

health_bp = Blueprint("health", __name__)
_START = time.time()

@health_bp.route("/health")
def health():
    return jsonify({
        "status": "ok",
        "app": config.APP_NAME,
        "version": config.APP_VERSION,
        "uptime_seconds": round(time.time() - _START),
    }), 200