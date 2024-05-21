#!/bin/bash

# Define variables for the repository and the desired branch
REPO_URL="https://github.com/woocommerce/woocommerce.git"
BRANCH="trunk"
RANDOM_DIR_NAME=$(date +%s)
DEST_DIR="generated/woocommerce/$RANDOM_DIR_NAME"

# Create a temporary directory for cloning
TEMP_DIR=$(mktemp -d)

# Clone the repository
git clone -b $BRANCH --depth 1 $REPO_URL $TEMP_DIR

# Copy the TypeScript files to the destination directory
mkdir -p $DEST_DIR
cp -R "$TEMP_DIR/plugins/woocommerce-blocks/assets/js/types/"* $DEST_DIR

# Remove the temporary directory
rm -rf $TEMP_DIR

# Echo success message
echo "WooCommerce types have been successfully fetched to $DEST_DIR"
