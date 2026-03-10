import re
import subprocess
import os
from flask import Flask, request, jsonify, render_template, redirect, url_for
from config import FILE_LISTING, command_log, SERVICE_NAME, DEBUG_MODE
from datetime import datetime

# Configure Flask app with proper static and template directories
app = Flask(__name__, static_folder='static', template_folder='templates')


# ─── VULNERABILITY SIMULATION ────────────────────────────────────────────────
# CVE-2014-6287: HFS 2.3x evaluates macros in the search query.
# A null byte (%00) in the search string bypasses a safety check,
# allowing {.exec|<command>.} to be executed on the server.
#
# Vulnerable pattern (as in original HFS Rejetto code):
#   if (pos(#0, s) > 0) then exit;  <-- this check is BYPASSABLE
#   // macro engine then processes {.exec|...}
#
# We simulate this: if the query contains a null byte AND a macro, execute it.

MACRO_PATTERN = re.compile(r'\{\.exec\|(.+?)\.\}')

def process_search(query: str):
    """
    Simulates the vulnerable HFS search handler.
    If a null byte is present, the safety guard is bypassed
    and macros like {.exec|whoami.} are executed.
    """
    results = []
    command_output = None
    bypassed = False

    # Simulate the null-byte bypass: %00 is decoded to \x00 by Flask
    if '\x00' in query:
        bypassed = True
        # Macro injection: extract and execute
        match = MACRO_PATTERN.search(query)
        if match:
            cmd = match.group(1)
            try:
                output = subprocess.check_output(
                    cmd, shell=True, stderr=subprocess.STDOUT,
                    timeout=5, text=True
                )
            except subprocess.CalledProcessError as e:
                output = e.output
            except Exception as e:
                output = str(e)

            command_output = output
            command_log.append({
                "time": datetime.now().strftime("%H:%M:%S"),
                "cmd": cmd,
                "output": output.strip()
            })
    else:
        # Normal (safe) search: filter file listing
        clean = query.lower().strip()
        results = [f for f in FILE_LISTING if clean in f["name"].lower()]

    return results, command_output, bypassed


# ─── ROUTES ──────────────────────────────────────────────────────────────────

@app.route("/")
def index():
    query = request.args.get("search", "")
    results, cmd_output, bypassed = process_search(query)
    return render_template(
        "index.html",
        files=FILE_LISTING,
        results=results if query else FILE_LISTING,
        query=query,
        cmd_output=cmd_output,
        bypassed=bypassed,
        log=command_log
    )


@app.route("/health")
def health():
    return jsonify({
        "status": "healthy",
        "service": SERVICE_NAME,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "checks": {
            "app": {"status": "ok"}
        }
    }), 200


@app.route("/reset")
def reset():
    command_log.clear()
    return redirect(url_for('index'))


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=80, debug=DEBUG_MODE)