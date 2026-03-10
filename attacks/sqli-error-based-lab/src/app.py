from flask import Flask, request, jsonify, render_template
import time

from config import PORT, FLAG
from db import init_db, get_conn
from health import health_handler
from reset import reset_handler

app = Flask(
    __name__,
    template_folder="templates",
    static_folder="static",
    static_url_path="/static",
)

# Events pour debug (sans payloads)
EVENTS = []


def log_event(mode: str, typ: str, detail: str):
    EVENTS.append({
        "ts": time.strftime("%Y-%m-%d %H:%M:%S"),
        "mode": mode,   # system | vuln | secure
        "type": typ,    # query | error | validation | reset | debug
        "detail": detail[:500],
    })
    if len(EVENTS) > 300:
        del EVENTS[:80]


# -------------------------
# UI (page unique /)
# -------------------------
@app.get("/")
def home():
    return render_template("index.html")


# -------------------------
# Endpoints obligatoires
# -------------------------
@app.get("/health")
def health():
    return health_handler()


@app.get("/reset")
@app.post("/reset")
def reset():
    resp = reset_handler()
    EVENTS.clear()
    log_event("system", "reset", "Reset DB + clear events")
    return resp


# -------------------------
# API vuln (Error-Based)
# -------------------------
@app.post("/api/vuln/product")
def api_vuln_product():
    """
    Mode vulnérable: concaténation SQL + fuite d'erreur SQL (Error-Based).
    """
    data = request.get_json(silent=True) or {}
    pid = str(data.get("product_id", "")).strip()

    query = f"SELECT * FROM products WHERE id = {pid}"
    log_event("vuln", "query", "Product search (raw input)")

    try:
        conn = get_conn()
        cur = conn.cursor()
        cur.execute(query)
        rows = cur.fetchall()
        conn.close()
        return jsonify({"products": [list(r) for r in rows]})
    except Exception as e:
        # fuite volontaire (lab pédagogique)
        log_event("vuln", "error", str(e))
        return jsonify({"error": f"{e}\n\nQuery executed: {query}"}), 400


# -------------------------
# API secure
# -------------------------
@app.post("/api/secure/product")
def api_secure_product():
    """
    Mode sécurisé: validation stricte + requête paramétrée + erreur générique.
    """
    data = request.get_json(silent=True) or {}
    raw = str(data.get("product_id", "")).strip()

    try:
        pid = int(raw)
    except ValueError:
        log_event("secure", "validation", "Invalid product_id (not an integer)")
        return jsonify({"error": "Invalid product ID"}), 400

    try:
        conn = get_conn()
        cur = conn.cursor()
        cur.execute("SELECT * FROM products WHERE id = ?", (pid,))
        rows = cur.fetchall()
        conn.close()

        log_event("secure", "query", "Product search (parameterized)")
        return jsonify({"products": [list(r) for r in rows]})
    except Exception:
        log_event("secure", "error", "Generic error (details not exposed)")
        return jsonify({"error": "An error occurred"}), 500


# -------------------------
# Debug (HTML + API)
# -------------------------
@app.get("/debug")
def debug_page():
    # Page HTML (debug.html)
    return render_template("debug.html")


@app.get("/api/debug/events")
def debug_events():
    # JSON events pour debug.html
    return jsonify({"events": EVENTS})


@app.post("/api/debug/clear")
def debug_clear():
    EVENTS.clear()
    log_event("system", "debug", "Events cleared")
    return jsonify({"status": "ok"})


# -------------------------
# Flag (CTF-style)
# -------------------------
@app.get("/flag")
def flag():
    """
    Flag délivré si:
    - une erreur SQL a été observée en mode vulnérable
    - et une action secure a eu lieu (query/validation)
    """
    has_error = any(e["mode"] == "vuln" and e["type"] == "error" for e in EVENTS)
    saw_secure = any(e["mode"] == "secure" and e["type"] in ("query", "validation") for e in EVENTS)

    if has_error and saw_secure:
        return jsonify({"status": "ok", "flag": FLAG}), 200

    return jsonify({
        "status": "not_completed",
        "message": "Objectif non atteint : observer une erreur SQL en mode vulnérable puis comparer avec le mode sécurisé."
    }), 403


# -------------------------
# Main
# -------------------------
if __name__ == "__main__":
    init_db()
    app.run(host="0.0.0.0", port=PORT, debug=False)