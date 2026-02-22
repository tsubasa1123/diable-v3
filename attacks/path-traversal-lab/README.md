🔍 Directory Traversal Lab
A hands-on educational lab to understand and practice Directory Traversal / Path Traversal attacks in a safe and controlled environment.

📁 Project Structure
directory-traversal-lab/
├── Dockerfile
├── app.py
├── requirements.txt
├── templates/
│   └── index.html
├── files/
│   └── public.txt
└── secret/
    └── flag.txt

🐳 Run with Docker
1. Clone the repository
bashgit clone https://github.com/tsubasa1123/diable-v3
cd diable-v3/attacks/directory-traversal-lab
2. Build the Docker image
bashdocker build -t directory-traversal-lab .
3. Run the container
bashdocker run -p 5000:5000 directory-traversal-lab
4. Open your browser and go to
http://localhost:5000

🎯 Endpoints
EndpointDescriptionGET /Home page — explains the labGET /view?file=public.txtVulnerable endpoint — no path validationGET /secure-view?file=public.txtSecure endpoint — blocks traversal attempts

🚨 The Vulnerability
The /view endpoint is intentionally vulnerable. It takes a file parameter and opens it without validating the path, allowing an attacker to escape the intended directory using ../ sequences.
Example attack:
http://localhost:5000/view?file=../../secret/flag.txt

🛡️ The Fix
The /secure-view endpoint demonstrates the proper fix — it checks that the resolved path stays inside the allowed files/ directory before opening the file.
pythonif not filepath.startswith(BASE_DIR):
    abort(403)

⚠️ Disclaimer
This lab is for educational purposes only. Do not use these techniques on systems you do not own or have explicit permission to test.