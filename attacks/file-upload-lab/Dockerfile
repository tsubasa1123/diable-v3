# Image de base
FROM php:8.2-apache

# Métadonnées OBLIGATOIRES
LABEL maintainer="DIABLE Team"
LABEL description="File Upload to RCE Lab - Démonstration de téléchargement malveillant"
LABEL version="1.0"

# Installation des dépendances (curl pour health check)
RUN apt-get update && apt-get install -y \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Création du dossier uploads avec les bons droits
RUN mkdir -p /var/www/html/uploads \
    && chmod 777 /var/www/html/uploads

# Copie du code source
WORKDIR /var/www/html
COPY src/ /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 777 /var/www/html/uploads

# Health check OBLIGATOIRE
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Exposition du port
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
