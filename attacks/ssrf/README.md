# 🌐 SSRF — Server-Side URL Fetch

A lab demonstrating a simple Server-Side Request Forgery (SSRF) vulnerability where the server fetches a user-controlled URL without validation.

---

## 📁 Project Structure

    ssrf/
    ├── Dockerfile
    ├── README.md
    └── src/
        ├── config.php
        ├── fetch.php
        ├── health.php
        ├── index.php
        ├── reset.php
        └── style.css

---

## 🐳 Run with Docker

Build:

    cd labs/ssrf
    docker build -t diable-ssrf .

Run:

    docker run --rm -d --name diable-ssrf -p 8083:80 diable-ssrf

Open:

    http://localhost:8083

Stop:

    docker stop diable-ssrf

---

## 🎯 Endpoint

    GET /fetch.php?url=

Example:

    http://localhost:8083/fetch.php?url=http://example.com

---

## 🚨 The Vulnerability

The application allows the user to provide any URL to the server.
The server then fetches this URL directly, without applying any validation or restriction.

Because the request is executed server-side, an attacker may force the application to access unintended or internal resources.

---

## ✅ Example SSRF Attack

Example using a local resource inside the same container:

    http://localhost:8083/fetch.php?url=http://localhost/health.php

Expected result:
- The request is executed by the server
- The response from `health.php` is returned
- This shows that the server can access local resources from its own environment

In this scenario, `localhost` refers to the vulnerable server itself (or the Docker container), not to the attacker's machine.

---

## 🛡 Fix

- Allow only expected schemes such as `http` and `https`
- Validate and normalize user-supplied URLs
- Block localhost and private/internal IP ranges
- Use a strict allowlist of authorized destinations
- Restrict outbound connections at network level

---

## ⚠ Disclaimer

Educational use only.
