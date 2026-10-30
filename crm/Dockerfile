ARG BASE_IMAGE=ghcr.io/hmntd/pens-crm-base:8.5-alpine
FROM ${BASE_IMAGE}

ARG INSTALL_DEPS=false

WORKDIR /var/www

COPY --chown=www:www crm/ /var/www/
COPY --chown=www:www protos/ /var/www/protos/

RUN mkdir -p /var/www/storage/app/public \
        /var/www/storage/framework/cache/data \
        /var/www/storage/framework/sessions \
        /var/www/storage/framework/views \
        /var/www/storage/logs \
        /var/www/bootstrap/cache \
    && chown -R www:www /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

USER www

RUN if [ "$INSTALL_DEPS" = "true" ]; then \
        composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction \
        && npm ci \
        && npm run build \
        && rm -rf node_modules; \
    fi

EXPOSE 9000

CMD ["php-fpm"]