from flask import Flask, request, render_template, abort
import os

app = Flask(__name__)

ROOT_DIR = os.path.abspath(os.path.dirname(__file__))
BASE_DIR = os.path.join(ROOT_DIR, "files")

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/view")
def view_file():
    filename = request.args.get("file")

    if not filename:
        return "Paramètre 'file' manquant", 400

    filepath = os.path.abspath(os.path.join(BASE_DIR, filename))

    try:
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()

        # 🚩 FLAG SYSTEM — détecte si l'utilisateur a atteint le flag
        if "flag.txt" in filepath:
            return f"""
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>🚩 Flag Trouvé !</title>
                <style>
                    * {{ margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }}
                    body {{ background: #0d1117; display: flex; justify-content: center; align-items: center; min-height: 100vh; }}
                    .card {{
                        background: #161b22;
                        border: 2px solid #238636;
                        border-radius: 16px;
                        padding: 50px 40px;
                        text-align: center;
                        max-width: 600px;
                        width: 90%;
                        box-shadow: 0 0 40px rgba(35, 134, 54, 0.3);
                    }}
                    .trophy {{ font-size: 5rem; margin-bottom: 20px; animation: bounce 1s infinite alternate; }}
                    @keyframes bounce {{ from {{ transform: translateY(0); }} to {{ transform: translateY(-10px); }} }}
                    h1 {{ color: #3fb950; font-size: 2rem; margin-bottom: 10px; }}
                    .subtitle {{ color: #8b949e; margin-bottom: 30px; font-size: 1rem; }}
                    .flag-box {{
                        background: #0d1117;
                        border: 1px solid #30363d;
                        border-radius: 8px;
                        padding: 15px 25px;
                        font-family: monospace;
                        font-size: 1.1rem;
                        color: #79c0ff;
                        margin-bottom: 30px;
                        word-break: break-all;
                    }}
                    .badge {{
                        display: inline-block;
                        background: #238636;
                        color: white;
                        padding: 8px 20px;
                        border-radius: 20px;
                        font-size: 0.9rem;
                        font-weight: bold;
                        margin-bottom: 25px;
                    }}
                    .explanation {{
                        background: #1c2128;
                        border-left: 4px solid #d29922;
                        border-radius: 0 8px 8px 0;
                        padding: 15px 20px;
                        text-align: left;
                        color: #e6edf3;
                        font-size: 0.9rem;
                        line-height: 1.6;
                    }}
                    .explanation strong {{ color: #d29922; }}
                    a {{
                        display: inline-block;
                        margin-top: 25px;
                        color: #58a6ff;
                        text-decoration: none;
                        font-size: 0.9rem;
                    }}
                    a:hover {{ text-decoration: underline; }}
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="trophy">🏆</div>
                    <h1>Flag Trouvé !</h1>
                    <p class="subtitle">Tu as réussi l'attaque Directory Traversal</p>
                    <div class="badge">✅ Défi Complété</div>
                    <div class="flag-box">🚩 {content.strip()}</div>
                    <div class="explanation">
                        <strong>Comment tu as réussi ?</strong><br>
                        En utilisant <code>../</code> dans le paramètre <code>file</code>, tu es sorti du dossier
                        autorisé <code>/files/</code> et tu as accédé à <code>/secret/flag.txt</code>
                        qui est un fichier confidentiel hors du périmètre prévu.<br><br>
                        <strong>La vraie leçon :</strong> Le serveur faisait confiance à ton entrée sans valider le chemin final.
                    </div>
                    <a href="/">← Retour à l'accueil</a>
                </div>
            </body>
            </html>
            """

        return f"<pre>{content}</pre>"

    except Exception as e:
        return f"Erreur : {str(e)}", 500

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

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)