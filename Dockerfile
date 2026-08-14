FROM php:8.3-cli

WORKDIR /app

COPY . .

RUN mkdir -p /app/uploads/properties /app/uploads/property_drafts && chmod -R 775 /app/uploads

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /app"]
