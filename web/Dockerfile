FROM php:8.1-apache

# mysqli for the (intentionally unsafe) DB queries
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Show PHP/SQL errors in the page — helps error-based SQLi during the lab
RUN { \
      echo "display_errors = On"; \
      echo "display_startup_errors = On"; \
      echo "error_reporting = E_ALL"; \
    } > /usr/local/etc/php/conf.d/zz-lab-errors.ini

RUN a2enmod rewrite

# Baked-in copy (volume mount in docker-compose overrides this for live edits)
COPY src/ /var/www/html/

EXPOSE 80
