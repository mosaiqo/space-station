FROM mysql:5.7

LABEL maintainer="Boudy de Geer"
LABEL project="space-station"

ARG MYSQL_ROOT_PASSWORD

RUN apt-get update && apt-get --no-install-recommends --no-install-suggests -y install expect

ADD mysql_config.sh /root/mysql_config.sh
RUN cd /root && ./mysql_config.sh $MYSQL_ROOT_PASSWORD