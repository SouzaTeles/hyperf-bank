#!/bin/sh
set -e

# Start cron in background
crond -f -l 2 &

# Start Hyperf server
exec php /opt/www/bin/hyperf.php server:watch
