# 🔐 CSRF — Static Token Weak Protection

A lab demonstrating a weak CSRF protection mechanism based on a static and predictable token.

---

## 📁 Project Structure

    csrf/
    ├── Dockerfile
    ├── README.md
    └── src/
        ├── attacker.html
        ├── config.php
        ├── health.php
        ├── index.php
        ├── login.php
        ├── reset.php
        ├── style.css
        └── transfer.php

---

## 🐳 Run with Docker

Build:

    cd labs/csrf
    docker build -t diable-csrf .

Run:

    docker run --rm -d --name diable-csrf -p 8084:80 diable-csrf

Open:

    http://localhost:8084

Stop:

    docker stop diable-csrf

---

## 🎯 Endpoints

- GET / → Transfer form
- GET /login.php → Login page
- POST /transfer.php → Sensitive action
- GET /attacker.html → CSRF proof of concept
- GET /reset.php → Reset session
- GET /health.php → Health check

---

## 🛡 Weak Protection Mechanism

This lab uses a weak CSRF protection pattern:

- A CSRF token is present in the form
- The token value is static and predictable
- The same token can be reused by an attacker
- The server accepts the forged request if the known token is provided

Because the token is not truly unpredictable, the protection can be bypassed.

---

## 🧪 Expected Behavior

1. Log in using `user / password`
2. Open `attacker.html` in another tab
3. The malicious form is submitted automatically
4. The forged request is accepted
5. The balance decreases
6. The flag appears if the transfer conditions are met

---

## ⚠ Disclaimer

Educational use only.
