#!/bin/bash

CRON_COMMAND="/usr/bin/php /var/www/t.mercadalibre.live/broadcast_driver.php"
CRON_COMMENT="# $CRON_COMMAND"

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 --start | --stop"
    exit 1
fi

if [ "$1" == "--start" ]; then
    if crontab -l | grep -q "$CRON_COMMENT"; then
        (crontab -l | sed "s|$CRON_COMMENT|$CRON_COMMAND|") | crontab -
        echo "Cron job started!"
    else
        echo "Cron job is already running or not set."
    fi
elif [ "$1" == "--stop" ]; then
    if crontab -l | grep -q "$CRON_COMMAND"; then
        (crontab -l | sed "s|$CRON_COMMAND|$CRON_COMMENT|") | crontab -
        echo "Cron job stopped!"
    else
        echo "Cron job is already stopped."
    fi
fi
