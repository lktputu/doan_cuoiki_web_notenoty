# Sử dụng PHP 8.2 – phiên bản yêu cầu bởi Laravel 12
FROM php:8.2-apache

# Cài đặt các phần mềm phụ trợ
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git

# Cài đặt các phần mềm phụ trợ
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git
RUN docker-php-ext-install pdo_mysql gd zip

# Bật tính năng điều hướng của Apache
RUN a2enmod rewrite

# Ép máy chủ trỏ thẳng vào thư mục public (Bảo mật tuyệt đối, không cần .htaccess ngoài)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy toàn bộ code của em vào máy chủ
COPY . /var/www/html

# Tự động tải thư mục Vendor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Cấp quyền đọc ghi cho hệ thống
# Tạo thư mục chứa ảnh và cấp quyền đọc ghi cho hệ thống
# Tạo thư mục, CẦU NỐI ẢNH và cấp quyền đọc ghi
RUN mkdir -p /var/www/html/public/uploads/avatars \
    && php artisan storage:link \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/uploads