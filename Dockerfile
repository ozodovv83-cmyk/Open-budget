FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libonig-dev \
    ca-certificates \
    && docker-php-ext-install curl mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

ENV PORT=8080
EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:$PORT index.php"]
