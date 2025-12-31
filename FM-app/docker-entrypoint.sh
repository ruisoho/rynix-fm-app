#!/bin/bash
set -e

# Directory for persistent data
DATA_DIR="/var/www/html/data"
UPLOADS_DIR="/var/www/html/uploads"

# 1. Initialize Database
if [ ! -f "$DATA_DIR/database.sqlite" ]; then
    echo "Initializing database from seed..."
    cp /var/www/html/database_seed.sqlite "$DATA_DIR/database.sqlite"
    chown www-data:www-data "$DATA_DIR/database.sqlite"
    echo "Database initialized."
else
    echo "Using existing database."
fi

# 2. Initialize Uploads
# If uploads directory is empty, copy seed files
if [ -z "$(ls -A $UPLOADS_DIR)" ]; then
    echo "Initializing uploads from seed..."
    cp -r /var/www/html/uploads_seed/* $UPLOADS_DIR/
    chown -R www-data:www-data $UPLOADS_DIR
    echo "Uploads initialized."
else
    echo "Using existing uploads."
fi

# Ensure permissions
chown -R www-data:www-data $DATA_DIR
chown -R www-data:www-data $UPLOADS_DIR
chmod -R 775 $UPLOADS_DIR
chmod -R 775 $DATA_DIR

# Execute the main container command (apache)
exec "$@"
