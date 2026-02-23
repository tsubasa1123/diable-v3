from flask import Flask, render_template, request, session, redirect, url_for, jsonify
import json

app = Flask(__name__)
app.secret_key = 'idor_vulnerable_secret_key_for_lab'

# User database with admin having id=0
USERS = {
    0: {
        'id': 0,
        'username': 'admin',
        'password': 'admin_secret_pass',
        'email': 'admin@idor-lab.com',
        'role': 'Administrator',
        'bio': 'System Administrator with full access',
        'flag': 'CTF{IDOR_VULN_ACCESS_CONTROL_BYPASS_2024}',
        'profile_pic': '👑',
        'created': '2020-01-01',
        'permissions': ['read', 'write', 'delete', 'admin']
    },
    1: {
        'id': 1,
        'username': 'student',
        'password': 'password123',
        'email': 'student@idor-lab.com',
        'role': 'Student',
        'bio': 'Regular student user account',
        'flag': None,
        'profile_pic': '👤',
        'created': '2024-01-15',
        'permissions': ['read']
    },
    2: {
        'id': 2,
        'username': 'alice',
        'password': 'alice123',
        'email': 'alice@idor-lab.com',
        'role': 'Student',
        'bio': 'Another student learning about security',
        'flag': None,
        'profile_pic': '👩',
        'created': '2024-01-20',
        'permissions': ['read']
    },
    3: {
        'id': 3,
        'username': 'bob',
        'password': 'bob123',
        'email': 'bob@idor-lab.com',
        'role': 'Student',
        'bio': 'Security enthusiast and ethical hacker',
        'flag': None,
        'profile_pic': '👨',
        'created': '2024-01-25',
        'permissions': ['read']
    }
}

@app.route('/')
def index():
    if 'user_id' in session:
        return redirect(url_for('profile', user_id=session['user_id']))
    return render_template('index.html')

@app.route('/login', methods=['POST'])
def login():
    username = request.form.get('username')
    password = request.form.get('password')
    
    # Find user by username and password
    for user_id, user_data in USERS.items():
        if user_data['username'] == username and user_data['password'] == password:
            session['user_id'] = user_id
            session['username'] = username
            return redirect(url_for('profile', user_id=user_id))
    
    return render_template('index.html', error='Invalid credentials')

@app.route('/profile')
def profile():
    if 'user_id' not in session:
        return redirect(url_for('index'))
    
    # VULNERABILITY: Get user_id from query parameter, not from session
    # This allows users to access other users' profiles
    requested_user_id = request.args.get('user_id', session['user_id'], type=int)
    
    # NO ACCESS CONTROL CHECK - IDOR VULNERABILITY!
    if requested_user_id in USERS:
        user_data = USERS[requested_user_id]
        logged_in_user_id = session['user_id']
        
        # Pass both the viewed profile and logged-in user info
        return render_template('profile.html', 
                             user=user_data, 
                             logged_in_user_id=logged_in_user_id,
                             is_own_profile=(requested_user_id == logged_in_user_id))
    else:
        return render_template('error.html', message='User not found'), 404

@app.route('/api/user/<int:user_id>')
def api_user(user_id):
    """API endpoint that's also vulnerable to IDOR"""
    if 'user_id' not in session:
        return jsonify({'error': 'Unauthorized'}), 401
    
    # VULNERABILITY: No check if logged-in user should access this user_id
    if user_id in USERS:
        user_data = USERS[user_id].copy()
        # Don't expose password in API, but still vulnerable
        user_data.pop('password', None)
        return jsonify(user_data)
    else:
        return jsonify({'error': 'User not found'}), 404

@app.route('/users')
def users_list():
    """List all users (shows IDs - makes IDOR easier to exploit)"""
    if 'user_id' not in session:
        return redirect(url_for('index'))
    
    # Show list of users with their IDs (helps attacker enumerate)
    users = []
    for uid, udata in USERS.items():
        users.append({
            'id': uid,
            'username': udata['username'],
            'role': udata['role'],
            'profile_pic': udata['profile_pic']
        })
    
    return render_template('users.html', users=users, logged_in_user_id=session['user_id'])

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('index'))

# Debug endpoint for educational purposes
@app.route('/debug/users')
def debug_users():
    """Shows all user IDs - helpful for lab participants"""
    if 'user_id' not in session:
        return jsonify({'error': 'Please login first'})
    
    user_list = []
    for uid, udata in USERS.items():
        user_list.append({
            'id': uid,
            'username': udata['username'],
            'role': udata['role']
        })
    
    return jsonify({'users': user_list, 'hint': 'Try accessing /profile?user_id=X where X is different user IDs'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
