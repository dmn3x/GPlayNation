FROM php:8.4-cli

# GPlayNation needs:
# - bcmath: exact money math (Game::effectivePrice, commission service)
# - gd: image handling (upload validation tests, fake image generation)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app
