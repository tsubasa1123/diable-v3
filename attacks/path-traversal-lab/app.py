from flask import Flask, request, render_template, abort
import os

app = Flask(__name__)

# Racine du projet
ROOT_DIR = os.path.abspath(os.path.dirname(__file__))

# Dossier autorisé (vulnérable volontairement)
BASE_DIR = os.path.join(ROOT_DIR, "files")

# ================================
# Page principale pédagogique
# ================================
@app.route("/")
def index():
    return render_template("index.html")


# ================================
# Endpoint volontairement vulnérable
# ================================
@app.route("/view")
def view_file():
    filename = request.args.get("file")

    if not filename:
        return "Paramètre 'file' manquant", 400

    filepath = os.path.abspath(os.path.join(BASE_DIR, filename))

    try:
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            return f"<pre>{f.read()}</pre>"
    except Exception as e:
        return f"Erreur : {str(e)}", 500


# ================================
# Version sécurisée (démonstration)
# ================================
@app.route("/secure-view")
def secure_view():
    filename = request.args.get("file")

    if not filename:
        return "Paramètre 'file' manquant", 400

    filepath = os.path.normpath(os.path.join(BASE_DIR, filename))

    if not filepath.startswith(BASE_DIR):
        abort(403)

    try:
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            return f"<pre>{f.read()}</pre>"
    except Exception as e:
        return f"Erreur : {str(e)}", 500


# ================================
# Lancement Docker friendly
# ================================
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
