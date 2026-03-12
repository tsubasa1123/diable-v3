# 🌐 SSRF — Local Resource Discovery and Access

A lab demonstrating a Server-Side Request Forgery (SSRF) vulnerability where the server fetches a user-controlled URL without validation, allowing access to local resources from the server itself.

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
        ├── private-status.php
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

Because the request is executed server-side, an attacker may force the application to access unintended, local, or internal resources.

---

## 🧪 Exploitation Flow

This lab is designed as a progressive SSRF scenario.

### Step 1 — External URL fetch

Try a public URL such as:

    http://example.com

Expected result:
- The server fetches the external resource
- The response headers and content are displayed
- This confirms that the user controls the destination

### Step 2 — Local resource discovery

Try a local endpoint such as:

    http://localhost/health.php

or:

    http://127.0.0.1/health.php

Expected result:
- The server accesses its own local resource
- A response from `health.php` is returned
- A hint indicates that another internal endpoint exists

### Step 3 — Internal target access

Use the hint to reach the more sensitive local endpoint:

    http://localhost/private-status.php

or:

    http://127.0.0.1/private-status.php

Expected result:
- The response from the internal resource is displayed
- The exploitation is considered successful
- A success popup displays the flag

---

## ✅ Important Note

In this lab, `localhost` refers to the vulnerable server itself (the container), not to the attacker’s machine.

This is what makes the SSRF interesting: the attacker provides the destination, but the request is sent by the server from its own execution context.

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
