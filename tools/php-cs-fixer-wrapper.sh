#!/bin/bash
# Wrapper script to run php-cs-fixer with PHP version ignore flag
export PHP_CS_FIXER_IGNORE_ENV=1

# Get the directory of this script to find the config file
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$PROJECT_ROOT/.php-cs-fixer.dist.php"

# Check if config argument is already provided
HAS_CONFIG=false
for arg in "$@"; do
    if [[ $arg == --config* ]]; then
        HAS_CONFIG=true
        break
    fi
done

# Add config if not provided
if [ "$HAS_CONFIG" = false ] && [ -f "$CONFIG_FILE" ]; then
    exec "$PROJECT_ROOT/tools/php-cs-fixer/vendor/bin/php-cs-fixer" "$@"
else
    exec "$PROJECT_ROOT/tools/php-cs-fixer/vendor/bin/php-cs-fixer" "$@"
fi