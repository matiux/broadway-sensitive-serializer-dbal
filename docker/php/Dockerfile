FROM matiux/php:fpm-7.4-bullseye-dev

USER root

###> Dipendenze sviluppo
RUN apt-get update \
    && apt-get -y upgrade \
    && apt-get install -y --no-install-recommends

RUN /usr/local/bin/composer self-update
###> Fine dipendenze sviluppo

COPY ./docker/php/conf/xdebug-starter.sh /usr/local/bin/xdebug-starter
RUN chmod +x /usr/local/bin/xdebug-starter
RUN /usr/local/bin/xdebug-starter

USER utente

###> Bashrc con alias
COPY ./docker/php/conf/shell-custom.rc /tmp/shell-custom.rc
RUN cat /tmp/shell-custom.rc >> /home/utente/.zshrc \
    && cat /tmp/shell-custom.rc >> /home/utente/.bashrc
###> Fine bashrc con alias

USER www-data