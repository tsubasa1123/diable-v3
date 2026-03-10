FROM python:3.11-slim

LABEL maintainer="DIABLE Team"
LABEL description="CVE-2014-6287 - HFS RCE via macro injection in search query"
LABEL version="1.0"

RUN apt-get update && apt-get install -y curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY src/ /app/

RUN pip install flask --no-cache-dir

EXPOSE 80

ENV DEBUG_MODE=false
ENV FLASK_APP=app.py
ENV FLASK_ENV=production

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:80/health || exit 1

CMD ["python", "-m", "flask", "run", "--host=0.0.0.0", "--port=80"]