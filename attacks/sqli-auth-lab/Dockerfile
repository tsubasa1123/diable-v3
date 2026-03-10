FROM php:8.1-apache

LABEL maintainer="DIABLE Team"
LABEL description="Lab Docker DIABLE - Injection SQL avec authentification et recherche vulnérables"
LABEL version="1.0"

RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    curl \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY src/ /var/www/html/

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html

RUN php /var/www/html/reset.php >/dev/null 2>&1 || true \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 80

ENV DEBUG_MODE=false
ENV DB_PATH=/var/www/html/data/lab.db
ENV LAB_NAME=sqli-auth-lab

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

CMD ["apache2-foreground"]