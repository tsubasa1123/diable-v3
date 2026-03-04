# app.py - Nexcorp Intranet Portal — Main Flask Application
# INTENTIONALLY VULNERABLE - Security Training Lab
#
# FLAGS:
#   DIABLE{n0_auth_n0_problem}  -> GET /api/v1/admin/config      (no auth required)
#   DIABLE{d3bug_left_0pen}     -> GET /api/debug/status         (debug route in prod)
#   DIABLE{id0r_is_4_real}      -> GET /api/v1/employees/1       (IDOR on admin user)
#   DIABLE{h34d3r_hunt3r}       -> X-Flag header on every response (visible in Burp)

from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import config
import copy

app = Flask(__name__, static_folder=".", static_url_path="")
app.config["SECRET_KEY"] = config.SECRET_KEY

CORS(app, resources={r"/api/*": {"origins": "*"}})  # VULNERABILITY: wildcard CORS

sessions = {}  # token -> user dict


# ── Flag 4: injected into every HTTP response header ──────────────────────────
@app.after_request
def inject_flag_header(response):
    response.headers["X-Powered-By"] = "NexcorpPortal/3.4.1"
    response.headers["X-Flag"] = "DIABLE{h34d3r_hunt3r}"
    return response


# ── Auth helpers ───────────────────────────────────────────────────────────────
def get_session():
    token = request.headers.get("Authorization", "").replace("Bearer ", "")
    return sessions.get(token)

def require_auth():
    s = get_session()
    if not s:
        return None, (jsonify({"error": "Unauthorized"}), 401)
    return s, None


# ── Frontend ───────────────────────────────────────────────────────────────────
@app.route("/")
def index():
    return send_from_directory(".", "index.html")


# ── Auth ───────────────────────────────────────────────────────────────────────
@app.route("/api/v1/auth/login", methods=["POST"])
def login():
    data = request.get_json() or {}
    user = next(
        (u for u in config.USERS
         if u["email"] == data.get("email") and u["password"] == data.get("password")),
        None
    )
    if not user:
        return jsonify({"error": "Invalid credentials"}), 401
    token = f"nxc-{user['id']}-{user['role']}"
    sessions[token] = user
    return jsonify({
        "token": token,
        "user": {"id": user["id"], "name": user["name"], "role": user["role"]}
    })

@app.route("/api/v1/auth/logout", methods=["POST"])
def logout():
    token = request.headers.get("Authorization", "").replace("Bearer ", "")
    sessions.pop(token, None)
    return jsonify({"status": "ok"})

@app.route("/api/v1/users/me")
def me():
    s, err = require_auth()
    if err: return err
    return jsonify({"id": s["id"], "name": s["name"], "role": s["role"], "dept": s["dept"]})


# ── Dashboard ──────────────────────────────────────────────────────────────────
@app.route("/api/v1/dashboard/stats")
def stats():
    s, err = require_auth()
    if err: return err
    return jsonify({
        "tickets":       len(config.TICKETS),
        "employees":     len(config.USERS),
        "announcements": len(config.ANNOUNCEMENTS),
        "assets":        3
    })

@app.route("/api/v1/announcements")
def announcements():
    s, err = require_auth()
    if err: return err
    return jsonify(config.ANNOUNCEMENTS)

@app.route("/api/v1/tickets")
def tickets():
    s, err = require_auth()
    if err: return err
    return jsonify([t for t in config.TICKETS if t["user_id"] == s["id"]])


# ── Directory ──────────────────────────────────────────────────────────────────
@app.route("/api/v1/employees")
def employees():
    s, err = require_auth()
    if err: return err
    return jsonify([
        {"id": u["id"], "name": u["name"], "email": u["email"], "dept": u["dept"]}
        for u in config.USERS
    ])

# VULNERABILITY: IDOR — no object-level auth check
# FLAG: DIABLE{id0r_is_4_real} is embedded in user id=1 (Alice Chen, admin)
@app.route("/api/v1/employees/<int:uid>")
def employee(uid):
    s, err = require_auth()
    if err: return err
    # Missing: if uid != s["id"] and s["role"] != "admin": return 403
    user = next((u for u in config.USERS if u["id"] == uid), None)
    if not user:
        return jsonify({"error": "Not found"}), 404
    return jsonify(user)  # full DB row: ssn, salary, password, and flag field


# ── Admin endpoints — VULNERABLE ───────────────────────────────────────────────

# VULNERABILITY: authenticated but role never checked
@app.route("/api/v1/admin/users")
def admin_users():
    s, err = require_auth()
    if err: return err
    # Missing: if s["role"] != "admin": return 403
    return jsonify({"users": config.USERS})

# VULNERABILITY: zero authentication
@app.route("/api/v1/admin/audit-log")
def audit_log():
    return jsonify({"logs": config.AUDIT_LOG})

# VULNERABILITY: unauthenticated + returns all secrets
# FLAG: DIABLE{n0_auth_n0_problem} inside the config payload
@app.route("/api/v1/admin/config")
def admin_config():
    payload = dict(config.SERVER_CONFIG)
    payload["flag"] = "DIABLE{n0_auth_n0_problem}"
    return jsonify({"config": payload})

# VULNERABILITY: token checked, role not verified → privilege escalation
@app.route("/api/v1/admin/users/promote", methods=["POST"])
def promote():
    s, err = require_auth()
    if err: return err
    # Missing: if s["role"] != "admin": return 403
    data = request.get_json() or {}
    user = next((u for u in config.USERS if u["id"] == data.get("userId")), None)
    if not user:
        return jsonify({"error": "User not found"}), 404
    old_role = user["role"]
    user["role"] = data.get("role", "manager")
    return jsonify({
        "message": f"{user['name']} promoted: {old_role} -> {user['role']}",
        "user": user
    })


# ── Debug / Internal — VULNERABLE ──────────────────────────────────────────────

# VULNERABILITY: debug route left active in production
# FLAG: DIABLE{d3bug_left_0pen} inside the response
@app.route("/api/debug/status")
def debug_status():
    return jsonify({
        "status":  "ok",
        "env":     "production",
        "debug":   True,
        "uptime":  "14d 6h 22m",
        "flag":    "DIABLE{d3bug_left_0pen}",
        "config":  config.SERVER_CONFIG
    })

# VULNERABILITY: internal endpoint accessible from public network
@app.route("/api/internal/metrics")
def metrics():
    return jsonify({
        "requests_per_sec": 142,
        "active_sessions":  len(sessions),
        "db_connections":   12,
        "memory_mb":        256
    })


# ── Lab utilities ──────────────────────────────────────────────────────────────
@app.route("/health")
def health():
    return jsonify({
        "status":  "ok",
        "app":     config.APP_NAME,
        "version": config.APP_VERSION
    }), 200

@app.route("/reset", methods=["POST"])
def reset():
    config.USERS.clear()
    config.USERS.extend(copy.deepcopy(config.USERS_DEFAULT))
    sessions.clear()
    return jsonify({"status": "ok", "message": "Lab reset to default state."}), 200


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=config.DEBUG)