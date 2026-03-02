# Heartbleed Lab

This lab simulates the infamous **Heartbleed** vulnerability (CVE-2014-0160) in OpenSSL.

It uses the `vulhub/heartbleed` Docker image which runs a vulnerable HTTPS service on port 4443.

## Usage

From the project root:
```bash
# build and start lab alongside others
docker-compose up -d heartbleed-lab
# access via https://localhost:8087 (self-signed certificate)
``` 

The service responds with a simple HTML page. You can use tools like `nmap --script ssl-heartbleed` or python scripts to exploit it.

> Note: The container image is pulled from Docker Hub; ensure you have network access.