FROM php:8.4-cli

# GPlayNation needs bcmath for exact money math (Game::effectivePrice, commission service).
RUN docker-php-ext-install bcmath

WORKDIR /app
