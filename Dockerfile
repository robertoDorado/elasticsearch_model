#  PHP Drivers
FROM php:7.4-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Definir o diretório de trabalho
WORKDIR /var/www/html/

# Copia os arquivos do projeto para o diretório de trabalho
COPY . /var/www/html

# Instalação das dependências necessárias
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    libpq-dev \
    dos2unix \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql mysqli soap \
    && rm -rf /var/lib/apt/lists/*

# Reiniciar o Apache
RUN service apache2 restart

# Definir permissões
RUN chown -R www-data:www-data /var/www/html/

# Ativar o módulo rewrite
RUN a2enmod rewrite

# Permissão de super usuário para o composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Intalação do Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer