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

# Shared log directory for the SIEM / CrowdStrike collector to tail.
# World-writable so mod_php (www-data) can write even over a host bind-mount (lab).
RUN mkdir -p /var/log/senarath && chmod 0777 /var/log/senarath

# Also send Apache access/error logs into that shared directory.
COPY apache-senarath.conf /etc/apache2/conf-enabled/senarath-logging.conf

# Baked-in copy (volume mount in docker-compose overrides this for live edits)
COPY src/ /var/www/html/

EXPOSE 80
