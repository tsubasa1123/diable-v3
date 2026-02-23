# 🔓 IDOR Lab - Insecure Direct Object Reference

**Educational Security Testing Environment**

This lab demonstrates one of the most common web application vulnerabilities: **Insecure Direct Object Reference (IDOR)**. Students will learn how attackers can access unauthorized data by manipulating object identifiers.

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

IDOR occurs when an application provides direct access to objects based on user-supplied input. Without proper authorization checks, attackers can access data belonging to other users by simply changing the object reference.

**Learning Objectives:**
- Understand how IDOR vulnerabilities work
- Learn to identify IDOR in web applications
- Practice exploiting IDOR vulnerabilities
- Understand proper access control implementation

---

## 🔍 Vulnerability Description

### What is IDOR?

**Insecure Direct Object Reference (IDOR)** is a type of access control vulnerability that occurs when:

1. An application uses user-supplied input to access objects directly
2. The application fails to verify if the user has permission to access the requested object
3. An attacker can manipulate the reference to access unauthorized data

### Real-World Impact

IDOR vulnerabilities have led to major data breaches:
- **Facebook** - Accessing private photos of any user
- **Instagram** - Viewing private account data
- **Major banks** - Accessing other customers' account information
- **Healthcare systems** - Viewing patient records

### The Vulnerability in This Lab

The application has multiple IDOR vulnerabilities:

1. **Profile Access**: Users can view any profile by changing the `user_id` parameter
2. **API Endpoint**: The `/api/user/<id>` endpoint doesn't verify authorization
3. **Enumeration**: User IDs are sequential and predictable (0, 1, 2, 3...)

**Key Issue**: The application checks if a user is logged in but doesn't verify if they should access the requested resource.

---

## 🚀 Lab Setup

### Prerequisites

- Docker and Docker Compose installed
- Python 3.x (optional, for exploit script)
- Web browser

### Installation

1. **Navigate to the lab directory:**
```bash
cd idor-lab
```

2. **Build and run the Docker container:**
```bash
./deploy.sh
```

Or manually:
```bash
docker-compose up --build -d
```

3. **Access the application:**
Open your browser and navigate to:
```
http://localhost:8082
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

1. Login with the provided credentials (you'll be user ID = 1)
2. Find and exploit the IDOR vulnerability
3. Access the administrator's profile (user ID = 0)
4. Retrieve the FLAG from the admin profile

### Success Criteria

You successfully complete the lab when you retrieve the FLAG from the administrator's profile.

---

## 🛠️ Attack Methods

### Method 1: Manual URL Manipulation (Recommended for Learning)

This is the simplest and most educational method.

1. **Login to the Application**
   - Go to `http://localhost:8082`
   - Login with: `student` / `password123`

2. **Observe Your Profile URL**
   - After login, you'll be at your profile page
   - Notice the URL: `http://localhost:8082/profile?user_id=1`
   - The `user_id=1` parameter indicates you're user ID 1

3. **Exploit the IDOR**
   - Simply change the URL to: `http://localhost:8082/profile?user_id=0`
   - Press Enter
   - You should now see the administrator's profile!

4. **Retrieve the FLAG**
   - The FLAG will be displayed on the admin's profile
   - Copy the FLAG to confirm successful exploitation

**That's it!** This simple URL manipulation demonstrates a critical security flaw.

---

### Method 2: Using the Users List

1. **Login** with the student credentials
2. **Click "All Users"** in the navigation bar
3. **Observe the user IDs** displayed on each card
4. **Click on any user** to view their profile
5. **Notice** you can access profiles you shouldn't be able to see

---

### Method 3: API Exploitation

The application also has a vulnerable API endpoint.

1. **Login** to the application
2. **Open browser Developer Tools** (F12)
3. **Go to the Console tab**
4. **Run this JavaScript:**

```javascript
// Fetch admin data via API
fetch('/api/user/0')
    .then(response => response.json())
    .then(data => {
        console.log('Admin data:', data);
        if(data.flag) {
            console.log('FLAG:', data.flag);
        }
    });
```

5. **Check the console** for the admin's data and FLAG

---

### Method 4: Automated Exploit Script

For advanced users, an automated Python script is provided.

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
- Enumerate users (IDs -1 to 10)
- Exploit the IDOR vulnerability
- Extract and display the FLAG

---

## ✅ Solution

### Quick Solution

After logging in as `student`, simply change the URL:

**From:**
```
http://localhost:8082/profile?user_id=1
```

**To:**
```
http://localhost:8082/profile?user_id=0
```

### Expected FLAG

```
CTF{IDOR_VULN_ACCESS_CONTROL_BYPASS_2024}
```

### Why It Works

1. The application checks **if** you're logged in (authentication ✓)
2. But it doesn't check **what** you're allowed to access (authorization ✗)
3. You can view any user's profile just by knowing their ID
4. User IDs are predictable (sequential: 0, 1, 2, 3...)

---

## 🛡️ Mitigation Strategies

### How to Prevent IDOR

#### 1. Implement Proper Authorization Checks

**Vulnerable Code:**
```python
@app.route('/profile')
def profile():
    user_id = request.args.get('user_id')
    # NO AUTHORIZATION CHECK!
    user_data = USERS[user_id]
    return render_template('profile.html', user=user_data)
```

**Secure Code:**
```python
@app.route('/profile')
def profile():
    requested_user_id = request.args.get('user_id')
    logged_in_user_id = session['user_id']
    
    # AUTHORIZATION CHECK
    if requested_user_id != logged_in_user_id:
        if not is_admin(logged_in_user_id):
            return "Unauthorized", 403
    
    user_data = USERS[requested_user_id]
    return render_template('profile.html', user=user_data)
```

#### 2. Use Indirect Object References

Instead of exposing internal IDs, use random tokens or UUIDs:

**Before (Vulnerable):**
```
/profile?user_id=1
```

**After (Secure):**
```
/profile?token=a3f7b9c2-4e1d-8f6a-2c9b-7e4f1a3d8c6b
```

#### 3. Implement Access Control Lists (ACL)

```python
def can_access_profile(user_id, requested_profile_id):
    # Check if user has permission
    user = get_user(user_id)
    
    # Admin can access all profiles
    if user.role == 'admin':
        return True
    
    # Users can only access their own profile
    if user_id == requested_profile_id:
        return True
    
    # Check if profiles are shared (e.g., friends)
    if are_friends(user_id, requested_profile_id):
        return True
    
    return False
```

#### 4. Use Framework-Level Access Controls

Many frameworks provide built-in authorization:

**Flask-Login Example:**
```python
from flask_login import login_required, current_user

@app.route('/profile/<int:user_id>')
@login_required
def profile(user_id):
    if user_id != current_user.id and not current_user.is_admin:
        abort(403)
    
    user = User.query.get_or_404(user_id)
    return render_template('profile.html', user=user)
```

#### 5. Log and Monitor Access Patterns

```python
def log_profile_access(accessing_user_id, accessed_user_id):
    if accessing_user_id != accessed_user_id:
        logger.warning(
            f"User {accessing_user_id} accessed profile {accessed_user_id}"
        )
        
        # Alert on suspicious patterns
        if is_suspicious_pattern(accessing_user_id):
            send_security_alert(accessing_user_id)
```

---

## 📊 IDOR Testing Checklist

When testing for IDOR vulnerabilities:

- [ ] Identify all object references in URLs (IDs, keys, filenames)
- [ ] Try accessing objects belonging to other users
- [ ] Test with sequential IDs (1, 2, 3...)
- [ ] Try negative IDs (-1, 0)
- [ ] Test API endpoints separately
- [ ] Check if references are predictable
- [ ] Verify if authorization is checked server-side
- [ ] Test with different user roles
- [ ] Check for mass assignment vulnerabilities
- [ ] Look for information disclosure in responses

---

## 🔬 Understanding the Attack Flow

### Normal Flow (Expected)
```
1. User logs in as 'student' (ID=1)
2. Application shows student's profile
3. URL: /profile?user_id=1
4. Student sees their own data ✓
```

### Attack Flow (IDOR)
```
1. User logs in as 'student' (ID=1)
2. User manually changes URL to /profile?user_id=0
3. Application retrieves admin data (NO AUTH CHECK!)
4. Student sees admin's data including FLAG ✗
```

### What Went Wrong?

```python
# The application only checks:
if 'user_id' in session:  # Is user logged in? YES ✓
    
# But doesn't check:
if session['user_id'] == requested_user_id:  # Authorization? NO ✗
```

---

## 🎯 Bonus Challenges

### Challenge 1: Find All Users

Can you enumerate all users in the system?
- How many users exist?
- What are their IDs?
- Can you create a script to automate this?

### Challenge 2: API Exploitation

The application has an API endpoint: `/api/user/<id>`
- Can you exploit it the same way?
- What information does it expose?
- How would you secure it?

### Challenge 3: Fix the Vulnerability

- Fork the lab code
- Implement proper authorization checks
- Test that the vulnerability is fixed
- Document your changes

### Challenge 4: Create Detection Rules

- Write code to detect IDOR attempts
- Log suspicious access patterns
- Create alerts for security team

---

## 📚 Additional Resources

### OWASP Resources
- [OWASP Testing Guide - IDOR](https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/05-Authorization_Testing/04-Testing_for_Insecure_Direct_Object_References)
- [OWASP Top 10 - Broken Access Control](https://owasp.org/Top10/A01_2021-Broken_Access_Control/)

### CWE References
- [CWE-639: Authorization Bypass Through User-Controlled Key](https://cwe.mitre.org/data/definitions/639.html)
- [CWE-284: Improper Access Control](https://cwe.mitre.org/data/definitions/284.html)

### Real-World Examples
- HackerOne IDOR Reports
- Bug Bounty Write-ups
- CVE Database

---

## ⚠️ Disclaimer

**FOR EDUCATIONAL PURPOSES ONLY**

This lab is designed for educational and training purposes in a controlled environment. Do not use these techniques on systems you do not own or have explicit permission to test.

Unauthorized access to computer systems is illegal and unethical.

---

## 📝 Lab Details

- **Vulnerability Type:** Insecure Direct Object Reference (IDOR)
- **Attack Technique:** URL Parameter Manipulation
- **Difficulty:** Beginner
- **Time Required:** 5-15 minutes
- **OWASP Category:** A01:2021 – Broken Access Control
- **CWE:** CWE-639

---

## 🎓 What You've Learned

After completing this lab, you should understand:

1. **What IDOR is** and how it occurs
2. **How to identify IDOR** vulnerabilities in applications
3. **How to exploit IDOR** through URL manipulation
4. **Why proper authorization** is critical
5. **How to prevent IDOR** in your applications
6. **The difference** between authentication and authorization

---

## 🤝 Contributing

This is an educational project. Suggestions for improvements are welcome!

---

**Happy Hacking! 🚀**

Remember: With great power comes great responsibility. Use your skills to make applications more secure!
