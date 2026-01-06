FROM php:8.2-fpm-alpine

# 1. 换阿里源，解决你刚才安装慢的问题
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# 2. 安装扩展需要的底层依赖库
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    libpng-dev \
    git \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    zlib-dev

# 3. 安装并配置 GD 库（带 webp/jpeg 支持）
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# 4. 安装其他核心扩展
RUN docker-php-ext-install -j$(nproc) pdo_mysql bcmath zip pcntl opcache

# 5. 通过 pecl 安装 redis 并启用
RUN pecl install redis && docker-php-ext-enable redis

# 6. 安装composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


# 设置工作目录
WORKDIR /var/www/html

# docker compose up -d --build         启动命令
# docker exec my_app_php php -m  查看扩展





