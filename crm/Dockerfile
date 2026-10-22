ARG BASE_IMAGE=ghcr.io/hmntd/pens-crm-base:8.5-alpine
FROM ${BASE_IMAGE}

WORKDIR /var/www

COPY --chown=www:www . /var/www

USER www

EXPOSE 9000

CMD ["php-fpm"]

CMD ["php-fpm"]