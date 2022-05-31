FROM php:8.0.13-cli

RUN apt-get update \
    && apt-get install -y \
        zip \
        unzip \
        git \
        wget \
    && wget https://codeclimate.com/downloads/test-reporter/test-reporter-latest-linux-amd64 \
    && mv test-reporter-latest-linux-amd64 /usr/bin/cc-test-reporter  \
    && chmod +x /usr/bin/cc-test-reporter

ARG WITH_XDEBUG=false

RUN if [ $WITH_XDEBUG = "true" ] ; then \
        pecl install xdebug && \
        docker-php-ext-enable xdebug; \
fi;

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /opt/project

COPY composer.json composer.lock phpunit.xml.dist phpunit.coverage.xml.dist .php-cs-fixer.php psalm.xml ./
COPY src/ src/
COPY tests/ tests/
COPY out/ out/
COPY .git/ .git/

RUN composer install