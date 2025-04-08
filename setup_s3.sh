#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Default values
BUCKET_NAME=""
REGION="us-east-1"

# Function to print usage
print_usage() {
    echo "Usage: $0 -b <bucket-name> [-r <region>]"
    echo "  -b    S3 bucket name (required)"
    echo "  -r    AWS region (default: us-east-1)"
    echo "Example: $0 -b my-pin-bucket -r us-west-2"
}

# Parse command line arguments
while getopts "b:r:h" opt; do
    case $opt in
        b) BUCKET_NAME="$OPTARG";;
        r) REGION="$OPTARG";;
        h) print_usage; exit 0;;
        ?) print_usage; exit 1;;
    esac
done

# Check if bucket name is provided
if [ -z "$BUCKET_NAME" ]; then
    echo -e "${RED}Error: Bucket name is required${NC}"
    print_usage
    exit 1
fi

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo -e "${RED}Error: AWS CLI is not installed${NC}"
    echo "Please install AWS CLI first:"
    echo "  macOS: brew install awscli"
    echo "  Linux: sudo apt-get install awscli"
    exit 1
fi

# Check if AWS credentials are configured
if ! aws sts get-caller-identity &> /dev/null; then
    echo -e "${RED}Error: AWS credentials not configured${NC}"
    echo "Please run 'aws configure' to set up your AWS credentials"
    exit 1
fi

echo -e "${YELLOW}Setting up S3 bucket for Pin...${NC}"

# Create CORS configuration file
cat > cors.json << EOL
{
    "CORSRules": [
        {
            "AllowedOrigins": ["*"],
            "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
            "AllowedHeaders": ["*"],
            "MaxAgeSeconds": 3000
        }
    ]
}
EOL

# Create bucket policy file
cat > bucket-policy.json << EOL
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::${BUCKET_NAME}/*"
        }
    ]
}
EOL

# Check if bucket exists
echo -e "${YELLOW}Checking if bucket ${BUCKET_NAME} exists...${NC}"
if aws s3api head-bucket --bucket "$BUCKET_NAME" 2>/dev/null; then
    echo -e "${GREEN}Bucket ${BUCKET_NAME} already exists${NC}"
else
    # Create the bucket
    echo -e "${YELLOW}Creating bucket ${BUCKET_NAME} in ${REGION}...${NC}"
    
    # Special handling for us-east-1 region
    if [ "$REGION" = "us-east-1" ]; then
        ERROR_OUTPUT=$(aws s3api create-bucket --bucket "$BUCKET_NAME" --region "$REGION" 2>&1)
    else
        ERROR_OUTPUT=$(aws s3api create-bucket --bucket "$BUCKET_NAME" --region "$REGION" --create-bucket-configuration LocationConstraint="$REGION" 2>&1)
    fi
    CREATE_RESULT=$?
    
    if [ $CREATE_RESULT -eq 0 ]; then
        echo -e "${GREEN}Bucket created successfully${NC}"
    else
        echo -e "${RED}Failed to create bucket ${BUCKET_NAME}${NC}"
        echo -e "${RED}Error: $ERROR_OUTPUT${NC}"
        echo -e "\n${YELLOW}Troubleshooting tips:${NC}"
        echo -e "1. ${YELLOW}Bucket name requirements:${NC}"
        echo -e "   - Must be globally unique across all AWS accounts"
        echo -e "   - Must be between 3 and 63 characters long"
        echo -e "   - Can only contain lowercase letters, numbers, dots (.), and hyphens (-)"
        echo -e "   - Must start and end with a letter or number"
        echo -e "   - Cannot be formatted as an IP address"
        echo -e "2. ${YELLOW}Permission issues:${NC}"
        echo -e "   - Ensure your AWS user has s3:CreateBucket permission"
        echo -e "   - Check if you have the necessary IAM roles"
        echo -e "3. ${YELLOW}Region issues:${NC}"
        echo -e "   - Verify the region is valid and accessible"
        echo -e "   - Some regions may have restrictions"
        echo -e "   - For us-east-1, no LocationConstraint is needed"
        echo -e "4. ${YELLOW}Try a different bucket name:${NC}"
        echo -e "   - Add a unique identifier to make it more likely to be available"
        echo -e "   - Example: ${BUCKET_NAME}-$(date +%s)"
        echo -e "\n${YELLOW}Please fix the issue and try again${NC}"
        rm -f cors.json bucket-policy.json
        exit 1
    fi
fi

# Configure CORS
echo -e "${YELLOW}Configuring CORS...${NC}"
if aws s3api put-bucket-cors --bucket "$BUCKET_NAME" --cors-configuration file://cors.json; then
    echo -e "${GREEN}CORS configured successfully${NC}"
else
    echo -e "${RED}Failed to configure CORS${NC}"
    echo -e "${YELLOW}Continuing with setup...${NC}"
fi

# Configure bucket policy for public read access
echo -e "${YELLOW}Configuring bucket policy...${NC}"
if aws s3api put-bucket-policy --bucket "$BUCKET_NAME" --policy file://bucket-policy.json; then
    echo -e "${GREEN}Bucket policy configured successfully${NC}"
else
    echo -e "${RED}Failed to configure bucket policy${NC}"
    echo -e "${YELLOW}Continuing with setup...${NC}"
fi

# Create directory structure
echo -e "${YELLOW}Creating directory structure...${NC}"
for dir in "dms" "chats" "pins" "audio" "images"; do
    if aws s3api put-object --bucket "$BUCKET_NAME" --key "$dir/" 2>/dev/null; then
        echo -e "${GREEN}Created directory: $dir${NC}"
    else
        echo -e "${YELLOW}Directory $dir might already exist, continuing...${NC}"
    fi
done
echo -e "${GREEN}Directory structure created successfully${NC}"

# Create initial JSON files
echo -e "${YELLOW}Creating initial JSON files...${NC}"
for file in "pins.json" "positions.json" "contexts.json" "last_seen.json" "banned.json"; do
    if echo "{}" | aws s3 cp - "s3://${BUCKET_NAME}/pins/$file" 2>/dev/null; then
        echo -e "${GREEN}Created file: $file${NC}"
    else
        echo -e "${YELLOW}File $file might already exist, continuing...${NC}"
    fi
done
echo -e "${GREEN}Initial JSON files created successfully${NC}"

# Clean up temporary files
rm -f cors.json bucket-policy.json

# Update config.php with the bucket information
echo -e "${YELLOW}Updating config.php...${NC}"
sed -i.bak "s/'bucket' => '.*'/'bucket' => '${BUCKET_NAME}'/" config.php
sed -i.bak "s/'region' => '.*'/'region' => '${REGION}'/" config.php
rm -f config.php.bak
echo -e "${GREEN}Configuration updated successfully${NC}"

echo -e "\n${GREEN}S3 bucket setup complete!${NC}"
echo -e "Bucket URL: https://${BUCKET_NAME}.s3.${REGION}.amazonaws.com"
echo -e "You can now run ./run_local.sh to start the application" 