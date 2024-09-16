#!/bin/bash

CRON_COMMAND="/usr/bin/php /var/www/t.mercadalibre.live/broadcast_driver.php"

if [ "$1" == "--start" ]; then
    EXISTING_CRON_JOB=$(crontab -l | grep -E "$CRON_COMMAND")
    if [ -z "$EXISTING_CRON_JOB" ]; then
        (crontab -l; echo "$CRON_COMMAND") | crontab -
        echo "Cron job started!"
    else
        echo "Cron job is already running."
    fi
elif [ "$1" == "--stop" ]; then
    (crontab -l | grep -v "$CRON_COMMAND") | crontab -
    echo "Cron job stopped!"
fi