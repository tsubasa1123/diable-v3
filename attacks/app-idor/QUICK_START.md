# 🚀 Quick Start Guide - IDOR Lab

## Get Started in 2 Minutes!

### 1. Deploy the Lab

```bash
cd idor-lab
./deploy.sh
```

### 2. Access the Application

Open: **http://localhost:8082**

### 3. Login

- **Username:** student
- **Password:** password123

### 4. Exploit (30 seconds!)

After login, change the URL from:
```
http://localhost:8082/profile?user_id=1
```

To:
```
http://localhost:8082/profile?user_id=0
```

### 5. Get FLAG! 🎉

You should now see the administrator's profile with the FLAG!

---

## Alternative: Use Exploit Script

```bash
pip install requests
python3 exploit.py
```

---

## What Just Happened?

You exploited an **IDOR vulnerability** by:
1. Changing the `user_id` parameter in the URL
2. Accessing the admin profile (ID=0)
3. Retrieving sensitive data you shouldn't have access to

---

## Expected FLAG

```
CTF{IDOR_VULN_ACCESS_CONTROL_BYPASS_2024}
```

---

## File Structure

```
idor-lab/
├── README.md              # Full documentation
├── STUDENT_GUIDE.md       # Detailed walkthrough
├── QUICK_START.md         # This file
├── app.py                 # Vulnerable Flask app
├── exploit.py             # Automated exploit
├── deploy.sh              # Deployment script
├── Dockerfile             # Docker config
├── docker-compose.yml     # Docker Compose (port 8082)
├── requirements.txt       # Python dependencies
└── templates/             # HTML templates
    ├── index.html         # Login page
    ├── profile.html       # Profile page
    ├── users.html         # Users list
    └── error.html         # Error page
```

---

## Stop the Lab

```bash
docker-compose down
```

---

## Need Help?

- **Full Guide:** See README.md
- **Step-by-Step:** See STUDENT_GUIDE.md
- **Stuck?** Check the URL - make sure you changed user_id to 0!

---

## Learning Objectives

✅ Understand IDOR vulnerabilities  
✅ Learn URL parameter manipulation  
✅ Understand authentication vs authorization  
✅ Know how to prevent IDOR attacks  

---

**That's it! Simple and powerful.** 🔓

For detailed learning, check out the full guides!
