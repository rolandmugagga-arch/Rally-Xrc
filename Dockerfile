FROM php:8.3-apache
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
 && ln -s ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
 && ln -s ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
 && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && printf 'upload_max_filesize=512M\npost_max_size=512M\nmax_execution_time=300\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/
ENV DB_PATH=/persist/rallyx.sqlite
RUN printf '#!/bin/sh\nmkdir -p /persist/uploads /var/www/html/uploads\nchown -R www-data:www-data /persist\nrm -rf /var/www/html/uploads/files\nln -sfn /persist/uploads /var/www/html/uploads/files\nexec apache2-foreground\n' > /start.sh && chmod +x /start.sh && chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["/start.sh"]
