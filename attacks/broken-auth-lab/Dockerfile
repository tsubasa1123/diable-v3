FROM php:8.2-apache

LABEL maintainer="DIABLE Team"
LABEL description="Broken Authentication Lab - User enumeration, brute-force, OTP weak, cookie manipulation"
LABEL version="1.0"

# Installation SQLite pour la base de données
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    curl \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY src/ /var/www/html/

# Création de la base de données
RUN mkdir -p /var/www/html/db && \
    sqlite3 /var/www/html/db/database.sqlite < /var/www/html/db/init.sql && \
    chmod 777 /var/www/html/db && \
    chmod 666 /var/www/html/db/database.sqlite && \
    # Vérification
    echo "=== Vérification de la base ===" && \
    ls -la /var/www/html/db/
# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 777 /var/www/html/db/database.sqlite

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

EXPOSE 80
CMD ["apache2-foreground"]
