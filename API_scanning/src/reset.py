# reset.py — only needed if you split blueprints
import copy
from flask import Blueprint, jsonify
import config

reset_bp = Blueprint("reset", __name__)

@reset_bp.route("/reset", methods=["POST"])
def reset():
    config.USERS.clear()
    config.USERS.extend(copy.deepcopy(config.USERS_DEFAULT))
    return jsonify({"status": "ok", "message": "Lab reset to default state."}), 200