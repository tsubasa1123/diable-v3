from flask import Flask, render_template, request, jsonify
from datetime import datetime
import sqlite3
import re

app = Flask(__name__)

DB_PATH = "events.db"

def db():
    con = sqlite3.connect(DB_PATH)
    con.row_factory = sqlite3.Row
    return con

def init_db():
    con = db()
    cur = con.cursor()
    cur.execute("""
      CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ts TEXT NOT NULL,
        event_type TEXT NOT NULL,
        page TEXT NOT NULL,
        campaign_id TEXT NOT NULL,
        username_masked TEXT,
        password_strength INTEGER,
        user_agent TEXT,
        client_hint TEXT,
        ip_masked TEXT,
        os TEXT,
        browser TEXT
      )
    """)
    con.commit()
    con.close()

def mask_username(u: str) -> str:
    if not u:
        return None
    u = u.strip()
    if "@" in u:
        name, dom = u.split("@", 1)
        name_m = (name[:1] + "***") if len(name) > 0 else "***"
        dom_parts = dom.split(".")
        dom_m = (dom_parts[0][:1] + "***") if dom_parts and dom_parts[0] else "***"
        tld = dom_parts[1] if len(dom_parts) > 1 else "com"
        return f"{name_m}@{dom_m}.{tld}"
    # fallback: mask generic usernames
    return (u[:1] + "***") if len(u) > 0 else "***"

def password_strength_score(pw: str) -> int:
    # 0..4 simplistic scoring (demo only) - DOES NOT STORE PASSWORD
    if pw is None:
        return None
    pw = pw.strip()
    if len(pw) == 0:
        return 0
    score = 0
    if len(pw) >= 8: score += 1
    if re.search(r"[A-Z]", pw): score += 1
    if re.search(r"[a-z]", pw): score += 1
    if re.search(r"[0-9]", pw) or re.search(r"[^A-Za-z0-9]", pw): score += 1
    return min(score, 4)

import hashlib

def parse_os_browser(ua: str):
    ua = ua or ""
    os_ = "Unknown"
    br = "Unknown"

    if "Windows" in ua: os_ = "Windows"
    elif "Mac OS X" in ua and "Mobile" not in ua: os_ = "macOS"
    elif "Android" in ua: os_ = "Android"
    elif "iPhone" in ua or "iPad" in ua: os_ = "iOS"
    elif "Linux" in ua: os_ = "Linux"

    # Browser (ordre important)
    if "Edg/" in ua: br = "Edge"
    elif "Chrome/" in ua and "Safari/" in ua: br = "Chrome"
    elif "Firefox/" in ua: br = "Firefox"
    elif "Safari/" in ua and "Chrome/" not in ua: br = "Safari"

    return os_, br

def masked_ip_simulated(campaign_id: str, ua: str, hint: str):
    """
    Génère une IP *simulée* stable par navigateur (anonymisée).
    On ne récupère pas de vraie IP.
    """
    base = f"{campaign_id}|{ua}|{hint}".encode("utf-8")
    h = hashlib.sha256(base).digest()
    # IPv4 privée simulée: 10.x.y.z
    a = 10
    b = h[0]
    c = h[1]
    d = h[2]
    # Anonymisation: 10.b.*.*
    return f"{a}.{b}.*.*"

def log_event(event_type, page, campaign_id="demo-001", username=None, pw=None, client_hint=None):
    ua = request.headers.get("User-Agent", "")
    username_masked = mask_username(username) if username else None
    pw_strength = password_strength_score(pw) if pw is not None else None

    # Anonymised "IP" + OS/Browser from UA (simulation, not real IP)
    os_, br = parse_os_browser(ua)
    ip_masked = masked_ip_simulated(campaign_id, ua, client_hint or "")

    con = db()
    cur = con.cursor()
    cur.execute("""
      INSERT INTO events (
        ts, event_type, page, campaign_id,
        username_masked, password_strength,
        user_agent, client_hint,
        ip_masked, os, browser
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, (
      datetime.utcnow().isoformat() + "Z",
      event_type,
      page,
      campaign_id,
      username_masked,
      pw_strength,
      ua,
      client_hint,
      ip_masked,
      os_,
      br
    ))
    con.commit()
    con.close()

@app.get("/")
def victim_inbox():
    return render_template("victim_inbox.html")

@app.get("/login")
def victim_login():
    return render_template("victim_login.html")

@app.post("/submit-login")
def submit_login():
    data = request.form
    username = data.get("email", "")
    pw = data.get("password", "")

    # Log a "submitted" event safely
    log_event(event_type="login_submitted", page="/login", username=username, pw=pw, client_hint=data.get("client_hint"))

    # Redirect to "success" regardless of inputs
    return render_template("victim_success.html")

@app.get("/attacker")
def attacker_dashboard():
    return render_template("attacker_dashboard.html")

@app.post("/api/event")
def api_event():
    payload = request.get_json(force=True, silent=True) or {}
    # events like: email_received, email_opened, link_clicked, page_view
    log_event(
        event_type=payload.get("event_type", "unknown"),
        page=payload.get("page", "unknown"),
        campaign_id=payload.get("campaign_id", "demo-001"),
        username=payload.get("username", None),
        pw=None,  # never accept password here
        client_hint=payload.get("client_hint", None),
    )
    return jsonify({"ok": True})

@app.get("/api/events")
def api_events():
    con = db()
    cur = con.cursor()
    rows = cur.execute("SELECT * FROM events ORDER BY id DESC LIMIT 500").fetchall()
    con.close()
    return jsonify([dict(r) for r in rows])

@app.get("/api/metrics")
def api_metrics():
    con = db()
    cur = con.cursor()

    total_received = cur.execute("SELECT COUNT(*) c FROM events WHERE event_type='email_received'").fetchone()["c"]
    total_opened   = cur.execute("SELECT COUNT(*) c FROM events WHERE event_type='email_opened'").fetchone()["c"]
    total_clicked  = cur.execute("SELECT COUNT(*) c FROM events WHERE event_type='link_clicked'").fetchone()["c"]
    total_submit   = cur.execute("SELECT COUNT(*) c FROM events WHERE event_type='login_submitted'").fetchone()["c"]

    # crude time-to-click: between first email_received and first link_clicked per campaign (demo)
    # Keep it simple for now: return counts + computed rates
    con.close()

    def rate(num, den):
        return round((num / den) * 100, 2) if den else 0.0

    return jsonify({
        "received": total_received,
        "opened": total_opened,
        "clicked": total_clicked,
        "submitted": total_submit,
        "open_rate_pct": rate(total_opened, total_received),
        "click_rate_pct": rate(total_clicked, total_received),
        "submit_rate_pct": rate(total_submit, total_clicked)  # conversion after click
    })

@app.get("/home")
def home():
    return render_template("home.html")

if __name__ == "__main__":
    init_db()
    app.run(host="0.0.0.0", port=5000, debug=True)