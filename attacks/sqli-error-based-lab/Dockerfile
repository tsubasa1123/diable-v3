FROM python:3.11-slim

LABEL maintainer="Rachid Mejdoubi"
LABEL project="DIABLE-like Lab"
LABEL lab="sqli-error-based-lab"
LABEL version="1.0"

WORKDIR /app

RUN apt-get update && apt-get install -y curl \
  && rm -rf /var/lib/apt/lists/*

COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r /app/requirements.txt

COPY src/ /app/src/

ENV PORT=5000
ENV DB_PATH=/app/data/shop.db
ENV LAB_NAME=sqli-error-based
ENV LAB_FLAG=DIABLE{SQLI_ERROR_BASED_OK}

EXPOSE 5000

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:5000/health || exit 1

CMD ["python", "/app/src/app.py"]