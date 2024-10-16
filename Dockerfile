
FROM php:8.2.22-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    git \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*


RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install intl pdo pdo_mysql zip

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && chmod +x /usr/local/bin/symfony


WORKDIR /var/www/html


COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts


CMD ["symfony", "server:start", "--listen-ip=0.0.0.0", "--port=9000"]

