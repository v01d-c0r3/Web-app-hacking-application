FROM php:8.3-apache

# pdo_sqlite ships with the official image; enable rewrite for the front controller
RUN a2enmod rewrite headers \
 && sed -ri 's!/var/www/html!/var/www/fellside/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
 && printf '<Directory /var/www/fellside/public>\n  AllowOverride All\n  Require all granted\n</Directory>\nServerName fellside.local\n' > /etc/apache2/conf-available/fellside.conf \
 && a2enconf fellside

WORKDIR /var/www/fellside
COPY . .

# Self-host the Archivo font so the site looks right on offline lab networks.
# If the build machine has no internet, the site falls back to system fonts.
RUN sh docker/fetch-fonts.sh public/assets/fonts || echo "Font download skipped; using fallback fonts"

RUN mkdir -p /var/www/data public/uploads \
 && chown -R www-data:www-data /var/www/data public/uploads

COPY docker/entrypoint.sh /usr/local/bin/fellside-entrypoint
RUN chmod +x /usr/local/bin/fellside-entrypoint

EXPOSE 80
ENTRYPOINT ["fellside-entrypoint"]
CMD ["apache2-foreground"]
