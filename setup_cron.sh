#!/bin/bash

if [ "$#" -ne 1 ]; then
    echo "Please follow: $0 'see rontab.guru for syntax'"
    exit 1
fi

NEW_CRON_EXPRESSION=$1
CRON_JOB="$NEW_CRON_EXPRESSION /usr/bin/php /var/www/t.mercadalibre.live/broadcast_driver.php"

EXISTING_CRON_JOB=$(crontab -l | grep -E "/usr/bin/php /var/www/t.mercadalibre.live/broadcast_driver.php")

if [ -z "$EXISTING_CRON_JOB" ]; then
    (crontab -l; echo "$CRON_JOB") | crontab -
    echo "Cron job added!"
    echo "$CRON_JOB"
else
    (crontab -l | grep -v "/usr/bin/php /var/www/t.mercadalibre.live/broadcast_driver.php"; echo "$CRON_JOB") | crontab -
    echo "Cron job changed!"
    echo "$CRON_JOB"
fi
