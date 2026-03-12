FROM php:8.1-apache

LABEL maintainer="DIABLE Team"
LABEL description="SSRF lab - server-side URL fetch vulnerable to local resource access"
LABEL version="1.0"

RUN apt-get update && apt-get install -y curl && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY src/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
ENV DEBUG_MODE=false

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/health.php || exit 1

CMD ["apache2-foreground"]
