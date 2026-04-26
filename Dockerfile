FROM php:5.6-apache

RUN rm -f /etc/apt/sources.list \
 && echo "deb http://archive.debian.org/debian stretch main"          > /etc/apt/sources.list \
 && echo "deb http://archive.debian.org/debian-security stretch/updates main" >> /etc/apt/sources.list \
 && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99archive-no-check \
 && echo 'Acquire::AllowInsecureRepositories "true";' >> /etc/apt/apt.conf.d/99archive-no-check \
 && echo 'APT::Get::AllowUnauthenticated "true";'     >> /etc/apt/apt.conf.d/99archive-no-check

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libpng-dev \
      libjpeg-dev \
      libfreetype6-dev \
 && docker-php-ext-configure gd \
      --with-jpeg-dir=/usr/include/ \
      --with-freetype-dir=/usr/include/ \
 && docker-php-ext-install -j$(nproc) mysqli gd mbstring \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]