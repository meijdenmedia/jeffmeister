FROM richarvey/nginx-php-fpm:1.8.2
MAINTAINER Innovato <info@innovato.nl>

COPY ./default.conf /etc/nginx/sites-available/
