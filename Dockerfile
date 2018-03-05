FROM php:7.1-cli

MAINTAINER fitz@michaelfitzpatrick.co.uk
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y apt-transport-https \
                       ca-certificates \
                       curl \
                       git \
                       lsb-release \
                       wget

RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list.d/dotdeb.org.list && \
    echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list.d/dotdeb.org.list && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury.org.list && \
    wget -O- http://www.dotdeb.org/dotdeb.gpg | apt-key add - && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg

RUN apt-get update

# install zip
#RUN apt-get install -y php-zip libzip4 zlib1g-dev && \
#    docker-php-ext-install zip

# install composer
RUN cd /tmp && \
    php -r "readfile('https://getcomposer.org/installer');" | php && \
    mv composer.phar /usr/local/bin/composer

# install xdebug
RUN pecl install xdebug-2.5.0 && \
    docker-php-ext-enable xdebug

COPY . /var/www/html
#RUN COMPOSER_ALLOW_SUPERUSER=1 composer install

VOLUME /var/www/html

