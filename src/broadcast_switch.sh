#!/bin/bash

CRON_JOB="/usr/bin/php /var/www/t.mercadalibre.live/broadcast_driver.php"

case "$1" in
    --start)
        if crontab -l | grep -q "# $CRON_JOB"; then
            (crontab -l | sed "s|# $CRON_JOB|$CRON_JOB|") | crontab -
            echo "Cron job started!"
        else
            echo "Cron job is already running or not set."
        fi
        ;;
    --stop)
        if crontab -l | grep -q "$CRON_JOB"; then
            (crontab -l | sed "s|$CRON_JOB|# $CRON_JOB|") | crontab -
            echo "Cron job stopped!"
        else
            echo "Cron job is already stopped."
        fi
        ;;
esac