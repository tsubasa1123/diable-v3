import os

DEBUG_MODE = os.getenv("DEBUG_MODE", "false").lower() == "true"
SERVICE_NAME = "cve-2014-6287-hfs-lab"

# Simulated HFS file listing (fake shared files)
FILE_LISTING = [
    {"name": "backup.zip",   "size": "14 MB",  "date": "2024-01-10"},
    {"name": "notes.txt",    "size": "3 KB",   "date": "2024-01-12"},
    {"name": "secret.docx",  "size": "230 KB", "date": "2024-01-15"},
    {"name": "readme.html",  "size": "12 KB",  "date": "2024-01-16"},
]

# Fake command output log (populated at runtime)
command_log = []