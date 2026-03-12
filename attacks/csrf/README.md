# 🔐 CSRF — Static Token Reuse

A lab demonstrating a weak CSRF protection mechanism based on a static and predictable token that can be reused by an attacker.

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
- GET /attacker.html → Attacker page used to forge the request
- GET /reset.php → Reset session
- GET /health.php → Health check

---

## 🛡 Weak Protection Mechanism

This lab uses a weak CSRF protection pattern:

- A CSRF token is present in the form
- The token value is static and predictable
- The same token can be observed and reused
- The server accepts the forged request if the known token is provided

Because the token is not random and not robustly bound to the session context, the protection can be bypassed.

---

## 🧪 Exploitation Steps

1. Open the application at `http://localhost:8084`
2. Log in using `user / password`
3. Inspect the transfer form and identify the CSRF token
4. Observe that the token is static and predictable
5. Open `attacker.html`
6. Copy the token into the attacker form
7. Launch the forged request
8. Return to the main page and observe:
   - the balance decreases
   - the action is executed
   - the flag is displayed in the success popup

---

## ✅ Expected Result

The exploitation is successful when:

- the forged transfer request is accepted
- the balance is reduced
- the success popup appears
- the flag is displayed

---

## 🔧 Fix

- Generate a random CSRF token per session or per request
- Store and validate the token server-side
- Ensure the token is unpredictable
- Combine CSRF protection with `SameSite` cookies and origin checks

---

## ⚠ Disclaimer

Educational use only.
