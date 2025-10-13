#!/usr/bin/env bash

#Define backup procedure
term_handler() {
    echo "Container stopped, backup..."
    /usr/bin/mariadb-dump -hdatabase -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" $MARIADB_DATABASE > /tmp/db/dump.sql
    sleep 20
}

#Trap TERM
trap "term_handler" SIGTERM

#Wait
tail --pid=$$ -f /dev/null &
wait ${!}