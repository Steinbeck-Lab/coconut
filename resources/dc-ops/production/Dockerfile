FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

ARG POSTGRES_VERSION=15

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    libgd-dev \
    libpq-dev \
    gnupg \
    gosu \
    curl \
    ca-certificates \
    supervisor \
    sqlite3 \
    libcap2-bin \
    dnsutils \
    librsvg2-bin \
    fswatch \
    software-properties-common \
    libmagickwand-dev

# Install Redis extension using PECL
RUN pecl install redis && docker-php-ext-enable redis

# Install Node.js and Yarn
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm pnpm bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /etc/apt/keyrings/yarn.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    && apt-get update && apt-get install -y yarn

# Install PostgreSQL client
RUN curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /etc/apt/keyrings/pgdg.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt jammy-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y postgresql-client-$POSTGRES_VERSION

# Install and configure PHP extensions
RUN docker-php-ext-configure pgsql --with-pgsql=/usr/include/postgresql/ \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-external-gd && docker-php-ext-install gd \
    && docker-php-ext-configure intl && docker-php-ext-install intl \
    && docker-php-ext-install bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user for laravel application
RUN groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www

# Set proper permissions
RUN chown -R www:www /var/www

# Switch to the www user
USER www

# Install PHP dependencies using Composer
RUN composer install --optimize-autoloader --no-scripts --prefer-dist

# Install Node.js dependencies and build assets
RUN npm ci && npm run build

# Expose port 9000 and start php-fpm server
EXPOSE 9000

CMD ["php-fpm"]
