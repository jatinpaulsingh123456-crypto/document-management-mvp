FROM yiisoftware/yii2-php:8.4-apache

WORKDIR /app

RUN sed -i -e 's|/app/web|/app/api/web|g' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader

COPY . .

# Yii writable directories
RUN mkdir -p \
    /app/api/runtime \
    /app/backend/runtime \
    /app/frontend/runtime \
    /app/common/storage/files \
    && chown -R www-data:www-data \
    /app/api/runtime \
    /app/backend/runtime \
    /app/frontend/runtime \
    /app/common/storage/files \
    && chmod -R 775 \
    /app/api/runtime \
    /app/backend/runtime \
    /app/frontend/runtime \
    /app/common/storage/files

EXPOSE 80
