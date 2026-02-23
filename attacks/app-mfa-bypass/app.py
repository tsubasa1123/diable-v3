from flask import Flask, render_template, request, session, redirect, url_for, jsonify
import random
import string
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'vulnerable_secret_key_for_lab'

# In-memory storage for OTPs (vulnerable by design)
otp_storage = {}

# Student credentials
VALID_CREDENTIALS = {
    'student': 'password123'
}

FLAG = "CTF{MFA_BYPASS_MITM_ATTACK_SUCCESS_2024}"

def generate_otp():
    """Generate a 4-digit OTP (intentionally weak)"""
    return ''.join([str(random.randint(0, 9)) for _ in range(4)])

def store_otp(username, otp):
    """Store OTP with expiration time (5 minutes)"""
    expiration = datetime.now() + timedelta(minutes=5)
    
    if username not in otp_storage:
        otp_storage[username] = []
    
    # Vulnerable: storing multiple OTPs for the same user
    otp_storage[username].append({
        'otp': otp,
        'expires_at': expiration
    })
    
    # Keep only last 100 OTPs (still vulnerable)
    if len(otp_storage[username]) > 100:
        otp_storage[username] = otp_storage[username][-100:]

def verify_otp(username, otp):
    """Verify if OTP is valid (vulnerable - checks all active OTPs)"""
    if username not in otp_storage:
        return False
    
    current_time = datetime.now()
    valid_otps = []
    
    # Check all non-expired OTPs (VULNERABILITY!)
    for otp_data in otp_storage[username]:
        if otp_data['expires_at'] > current_time:
            valid_otps.append(otp_data)
            if otp_data['otp'] == otp:
                # Remove only this OTP after successful verification
                otp_storage[username].remove(otp_data)
                return True
    
    # Clean up expired OTPs
    otp_storage[username] = valid_otps
    return False

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/login', methods=['POST'])
def login():
    username = request.form.get('username')
    password = request.form.get('password')
    
    if username in VALID_CREDENTIALS and VALID_CREDENTIALS[username] == password:
        session['username'] = username
        session['authenticated'] = False
        
        # Generate and store OTP
        otp = generate_otp()
        store_otp(username, otp)
        
        # In a real app, this would be sent via SMS/Email
        # For the lab, we'll display it in the console
        print(f"[OTP Generated] Username: {username}, OTP: {otp}")
        
        return redirect(url_for('mfa'))
    
    return render_template('index.html', error='Invalid credentials')

@app.route('/mfa')
def mfa():
    if 'username' not in session:
        return redirect(url_for('index'))
    
    if session.get('authenticated'):
        return redirect(url_for('dashboard'))
    
    return render_template('mfa.html', username=session['username'])

@app.route('/verify_otp', methods=['POST'])
def verify_otp_route():
    if 'username' not in session:
        return jsonify({'success': False, 'message': 'Not logged in'})
    
    otp = request.form.get('otp')
    username = session['username']
    
    # NO RATE LIMITING - VULNERABILITY!
    if verify_otp(username, otp):
        session['authenticated'] = True
        return jsonify({'success': True, 'redirect': url_for('dashboard')})
    
    return jsonify({'success': False, 'message': 'Invalid OTP'})

@app.route('/resend_otp', methods=['POST'])
def resend_otp():
    if 'username' not in session:
        return jsonify({'success': False, 'message': 'Not logged in'})
    
    username = session['username']
    
    # NO RATE LIMITING - VULNERABILITY!
    otp = generate_otp()
    store_otp(username, otp)
    
    print(f"[OTP Resent] Username: {username}, OTP: {otp}")
    
    return jsonify({'success': True, 'message': 'OTP resent successfully'})

@app.route('/dashboard')
def dashboard():
    if not session.get('authenticated'):
        return redirect(url_for('index'))
    
    return render_template('dashboard.html', flag=FLAG, username=session['username'])

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('index'))

# Debug endpoint to see active OTPs (for educational purposes)
@app.route('/debug/otps')
def debug_otps():
    if 'username' not in session:
        return jsonify({'error': 'Not logged in'})
    
    username = session['username']
    if username not in otp_storage:
        return jsonify({'otps': []})
    
    current_time = datetime.now()
    active_otps = [
        {
            'otp': otp_data['otp'],
            'expires_in': str(otp_data['expires_at'] - current_time)
        }
        for otp_data in otp_storage[username]
        if otp_data['expires_at'] > current_time
    ]
    
    return jsonify({'otps': active_otps, 'count': len(active_otps)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
