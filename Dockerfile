FROM php:8.3-cli
ENV DB_PATH=/persist/rallyx.sqlite PHP_CLI_SERVER_WORKERS=4
WORKDIR /app
COPY . /app/
RUN printf '#!/bin/sh\nmkdir -p /persist/uploads /app/uploads\nrm -rf /app/uploads/files\nln -sfn /persist/uploads /app/uploads/files\nexec php -d upload_max_filesize=512M -d post_max_size=512M -d max_execution_time=300 -d memory_limit=256M -S 0.0.0.0:80 -t /app\n' > /start.sh && chmod +x /start.sh
EXPOSE 80
CMD ["/start.sh"]
