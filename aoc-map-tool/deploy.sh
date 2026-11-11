#!/bin/bash
set -e

DATE_FORMAT="%Y-%m-%d"
CURRENT_DATE=$(date "+$DATE_FORMAT")
LOG_DIR="storage/logs/deployment"
LOG_FILE="$LOG_DIR/deployment_$CURRENT_DATE.log"
TIME_FORMAT="%H:%M:%S"
CURRENT_TIME=$(date "+$TIME_FORMAT")

# Create the log directory if it doesn't exist
mkdir -p "$LOG_DIR"

echo "Please wait while deployment"

echo "$CURRENT_DATE $CURRENT_TIME - Deployment started ========================================================" >> "$LOG_FILE"

# Function to log errors
log_error() {
    local error_message="$1"
    echo "$CURRENT_DATE $CURRENT_TIME - ERROR: $error_message" >> "$LOG_FILE"
}

# Function to execute commands and log errors if any
execute_command() {
    local command="$1"
    local error_message="$2"
    local output

    output=$(eval "$command" 2>&1) || { log_error "$error_message"; echo "$output"; exit 1; }
    echo "$output" >> "$LOG_FILE"
    echo >> "$LOG_FILE"  # Add a blank line
}

# Enter maintenance mode or return true
# if already in maintenance mode
execute_command "php artisan down" "Failed to enter maintenance mode."

# Install composer dependencies
#execute_command "export COMPOSER_MEMORY_LIMIT=-1 && composer update" "Failed to install composer dependencies."

# Clear the old cache
execute_command "php artisan clear-compiled" "Failed to clear compiled files."

# Run database migrations
execute_command "php artisan migrate --force" "Failed to run database migrations."

# Run database migrations
# execute_command "php artisan db:seed" "Failed to run database seed."

# Processing the Permission Module
execute_command "php artisan process:permissions" "Failed to process permissions."

# Processing the Permission Module
execute_command "php artisan process:featureflags" "Failed to process feature flags."

# Node Module Installation
#execute_command "npm install" "Failed to install Node modules."

# Node Module Installation
#execute_command "npm audit fix" "Failed to fix Node module vulnerabilities."

# Node Module Installation
#execute_command "npm install mix" "Failed to install Mix."

# Aliasing the mix
#alias npm-exec='PATH=$(npm exec):$PATH'

# Run production mode Compilation
execute_command "npm exec mix --production" "Failed to run Mix in production mode."

# Copying the compiled CSS, JS, and Images from public to out
execute_command "cp -R public/js ." "Failed to copy JS files."
execute_command "cp -R public/css ." "Failed to copy CSS files."
execute_command "cp -R public/images ." "Failed to copy image files."
execute_command "cp -R resources/images ." "Failed to copy resource image files."
execute_command "php artisan cache:clear" "Failed to clear cache."
# Exit maintenance mode
execute_command "php artisan up" "Failed to exit maintenance mode."

echo >> "$LOG_FILE"
echo "$CURRENT_DATE $CURRENT_TIME - Deployment finished!========================================================" >> "$LOG_FILE"
echo >> "$LOG_FILE"
echo >> "$LOG_FILE"

echo "Deployment finished successfully. You can check the log in $LOG_FILE"
