#!/bin/bash

set -e # Exit immediately if a command exits with a non-zero status.

if [ -z "$1" ]; then
    echo "No argument provided. Please provide a zip file."
    exit 1
fi

zip_file="$1"

if [[ ! -f "$zip_file" ]]; then
    echo "The file '$zip_file' does not exist."
    exit 1
elif [[ "$zip_file" != *.zip ]]; then
    echo "The file '$zip_file' is not a zip file."
    exit 1
fi

echo "Running backup..."
php artisan backup:run || {
    echo "Backup failed. Aborting."
    exit 1
}

echo "Putting application into maintenance mode..."
if ! php artisan down; then
    echo "Failed to put application into maintenance mode. Attempting to bring it back up and aborting."
    php artisan up
    exit 1
fi

backup_folder="backup_$(date +"%Y%m%d_%H%M%S")"
mkdir -p "$backup_folder"

# Move directories to backup, ignoring if they don't exist
dirs_to_backup="api app bootstrap config database lang public resources routes"
for dir in $dirs_to_backup; do
    if [ -d "$dir" ]; then
        mv "$dir" "$backup_folder/"
    else
        echo "Warning: Directory '$dir' not found. Skipping backup."
    fi
done

# Move files to backup, ignoring if they don't exist
files_to_backup="artisan composer.json composer.lock package-lock.json package.json postcss.config.js tailwind.config.js tsconfig.json vite.config.js"
for file in $files_to_backup; do
    if [ -f "$file" ]; then
        mv "$file" "$backup_folder/"
    else
        echo "Warning: File '$file' not found. Skipping backup."
    fi
done

rollback() {
    echo "Attempting rollback..."
    # Remove potentially incomplete new files
    rm -rf api app bootstrap config database lang public resources routes artisan colors.js composer.json composer.lock package-lock.json package.json postcss.config.js tailwind.config.js tsconfig.json vite.config.js
    # Move old files back
    mv "$backup_folder"/* .
    # Clean up backup dir name (content is moved)
    rm -r "$backup_folder"
    echo "Rollback attempt finished. Bringing application back up."
    php artisan up
    exit 1
}

if ! unzip -q "$zip_file" -d temp; then
    echo "Failed to unzip the file. Aborting."
    rollback
fi

shopt -s extglob
# Find the actual application root inside the temp directory
APP_ROOT=$(find temp -maxdepth 1 -mindepth 1 -type d)
if [ -z "$APP_ROOT" ]; then
    echo "Could not find application root inside the zip. Aborting."
    rm -r temp
    rollback
fi

# Move new files, preserving .env and storage
echo "Moving new application files..."
rsync -av --exclude='.env' --exclude='storage' --exclude='.vscode' --exclude='tests' --exclude='deploy.sh' "$APP_ROOT/" .
rm -r temp

# Sync public assets
echo "Syncing public assets..."
if [ -f "storage/public/favicon.ico" ]; then
    rm -f public/favicon.ico
    cp "storage/public/favicon.ico" "public/favicon.ico"
else
    echo "Warning: Source file 'storage/public/favicon.ico' not found. Skipping public favicon.ico sync."
fi

echo "Installing dependencies..."
npm install
composer install --no-dev || {
    echo "Composer install failed. Aborting."
    rollback
}

npm run build || {
    echo "NPM build failed. Aborting."
    rollback
}

php artisan storage:link

echo "Clearing and optimizing caches..."
php artisan optimize
php artisan view:clear

echo "Running database migrations..."
php artisan migrate --force || {
    echo "Database migrations failed. Check logs. Site remains in maintenance mode."
    exit 1
}

echo "Bringing application out of maintenance mode..."
php artisan up
