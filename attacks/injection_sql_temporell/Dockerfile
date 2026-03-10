# ── Dockerfile ──────────────────────────────────────────────────
FROM python:3.12-slim

LABEL maintainer="mohamed er-raouan"
LABEL description="Time-Based SQL Injection - Demo éducative"

# Répertoire de travail
WORKDIR /app

# Dépendances Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Code source
COPY app.py .
COPY templates/ templates/

# Initialiser la DB au démarrage via entrypoint
ENV FLASK_ENV=production
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

# Exposer le port Flask
EXPOSE 5000

# Point d'entrée : initialise la DB puis lance Flask
CMD ["python", "app.py"]