#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Setting up Pin locally...${NC}"

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    echo "On macOS, you can install it using: brew install php"
    exit 1
fi

# Create necessary directories if they don't exist
mkdir -p cache

# Set proper permissions for cache directory
chmod 755 cache

# Start PHP server
echo -e "${GREEN}Starting PHP server...${NC}"
echo -e "${YELLOW}The application will be available at: http://localhost:8000${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop the server${NC}"

# Start the PHP server in the current directory
php -S localhost:8000 