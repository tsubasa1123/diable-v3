FROM php:8.1-apache

RUN a2enmod rewrite headers

COPY app/ /var/www/html/

RUN chmod -R 755 /var/www/html/ && mkdir -p /tmp && chmod 777 /tmp

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]
