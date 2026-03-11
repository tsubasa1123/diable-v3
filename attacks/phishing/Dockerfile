FROM python:3.11-slim

LABEL maintainer="DIABLE Team"
LABEL description="Lab phishing pédagogique"
LABEL version="1.0"

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY src/ /app/src/

EXPOSE 5000

ENV DEBUG_MODE=false
ENV DB_PATH=/data/events.db

WORKDIR /app/src

CMD ["python", "app.py"]