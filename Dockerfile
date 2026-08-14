FROM php:8.3-cli

WORKDIR /app

COPY . .

RUN docker-php-ext-install curl

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /app"]
