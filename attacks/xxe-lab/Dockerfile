FROM php:8.2-apache

LABEL maintainer="DIABLE Team"
LABEL description="XXE Lab - Lecture de fichiers via XML"
LABEL version="1.0"

# Installer les extensions XML
RUN apt-get update && apt-get install -y \
    libxml2 \
    libxml2-dev \
    curl \
    && docker-php-ext-install simplexml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Créer des fichiers sensibles pour la démo
RUN echo "FLAG{XXE_Success_12345}" > /tmp/flag.txt && \
    echo "root:x:0:0:root:/root:/bin/bash" > /tmp/passwd && \
    echo "DB_PASSWORD=SuperSecret123" > /tmp/config.txt

WORKDIR /var/www/html
COPY src/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

EXPOSE 80
CMD ["apache2-foreground"]
