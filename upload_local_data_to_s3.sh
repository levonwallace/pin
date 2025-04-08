#!/bin/bash

# Script to upload local data files from the pin/ directory to the configured S3 bucket.
# Run this script from within the pin/ directory.

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

CONFIG_FILE="config.php"

# --- Helper Functions ---

echo_info() {
    echo -e "${YELLOW}[INFO] $1${NC}"
}

echo_success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}"
}

echo_error() {
    echo -e "${RED}[ERROR] $1${NC}"
}

# --- Configuration Loading ---

echo_info "Loading configuration from ${CONFIG_FILE}..."

if [ ! -f "$CONFIG_FILE" ]; then
    echo_error "Configuration file (${CONFIG_FILE}) not found!"
    echo_error "Please ensure you are in the 'pin' directory and the file exists."
    exit 1
fi

# Extract bucket name and region using grep and sed
BUCKET_NAME=$(grep -o "'bucket' => '.*'" "$CONFIG_FILE" | sed -n "s/'bucket' => '\(.*\)'.*/\1/p")
REGION=$(grep -o "'region' => '.*'" "$CONFIG_FILE" | sed -n "s/'region' => '\(.*\)'.*/\1/p")

if [ -z "$BUCKET_NAME" ] || [ -z "$REGION" ]; then
    echo_error "Could not parse bucket name or region from ${CONFIG_FILE}."
    echo_error "Please ensure the file contains 'bucket' => 'your-bucket' and 'region' => 'your-region'."
    exit 1
fi

echo_info "Using Bucket: ${BUCKET_NAME}, Region: ${REGION}"

# --- Pre-checks ---

echo_info "Checking prerequisites..."

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo_error "AWS CLI is not installed."
    echo_error "Please install AWS CLI first: https://aws.amazon.com/cli/"
    exit 1
fi

# Check if AWS credentials are configured
if ! aws sts get-caller-identity &> /dev/null; then
    echo_error "AWS credentials not configured or invalid."
    echo_error "Please run 'aws configure' or ensure your environment (e.g., IAM role) is set up correctly."
    exit 1
fi

echo_success "Prerequisites met."

# --- File Upload Logic ---

echo_info "Starting data upload to S3 bucket: ${BUCKET_NAME}"

# Directories to sync
DIRS_TO_SYNC=("dms" "audio" "images")

for dir in "${DIRS_TO_SYNC[@]}"; do
    if [ -d "$dir" ]; then
        echo_info "Syncing directory '${dir}/' to s3://${BUCKET_NAME}/${dir}/ ..."
        if aws s3 sync "./${dir}" "s3://${BUCKET_NAME}/${dir}/" --region "${REGION}" --no-progress; then
            echo_success "Successfully synced '${dir}/'."
        else
            echo_error "Failed to sync '${dir}/'. Check AWS CLI output above for details."
        fi
    else
        echo_info "Local directory '${dir}/' not found, skipping sync."
    fi
done

# JSON files to upload to pins/ directory
JSON_FILES=("pins.json" "positions.json" "banned.json" "last_seen.json" "contexts.json")

for file in "${JSON_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo_info "Uploading file '${file}' to s3://${BUCKET_NAME}/pins/${file} ..."
        if aws s3 cp "./${file}" "s3://${BUCKET_NAME}/pins/${file}" --region "${REGION}"; then
            echo_success "Successfully uploaded '${file}'."
        else
            echo_error "Failed to upload '${file}'. Check AWS CLI output above for details."
        fi
    else
        echo_info "Local file '${file}' not found, skipping upload."
    fi
done

# Chat files to upload to chats/ directory
shopt -s nullglob # Prevent loop from running if no files match
CHAT_FILES=(chat_*.txt)
shopt -u nullglob # Turn off nullglob

if [ ${#CHAT_FILES[@]} -gt 0 ]; then
    echo_info "Uploading chat files (chat_*.txt) to s3://${BUCKET_NAME}/chats/ ..."
    UPLOAD_FAILURES=0
    for file in "${CHAT_FILES[@]}"; do
        if [ -f "$file" ]; then
            echo_info " -> Uploading '${file}' ..."
            if ! aws s3 cp "./${file}" "s3://${BUCKET_NAME}/chats/${file}" --region "${REGION}"; then
                echo_error "    Failed to upload '${file}'."
                ((UPLOAD_FAILURES++))
            fi
        fi
    done
    if [ $UPLOAD_FAILURES -eq 0 ]; then
        echo_success "Successfully uploaded all chat files found."
    else
        echo_error "${UPLOAD_FAILURES} chat file(s) failed to upload. Check AWS CLI output above for details."
    fi
else
    echo_info "No local chat files (chat_*.txt) found, skipping upload."
fi

echo_success "Data upload process finished." 