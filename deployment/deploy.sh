#!/bin/bash

# NMR Platform Deployment Script
# This script builds and deploys the Laravel + FrankenPHP application

set -e

echo "🚀 NMR Platform Deployment Script"
echo "=================================="

# Configuration
IMAGE_NAME="nfdi4chem/nmr-platform"
TAG="${1:-latest}"
FULL_IMAGE_NAME="${IMAGE_NAME}:${TAG}"

# Check if COMPOSER_AUTH_JSON is set
if [ -z "$COMPOSER_AUTH_JSON" ]; then
    echo "❌ Error: COMPOSER_AUTH_JSON environment variable is not set"
    echo "Please set it with your composer authentication credentials:"
    echo 'export COMPOSER_AUTH_JSON='"'"'{"http-basic":{"example.com":{"username":"user","password":"pass"}}}'"'"
    exit 1
fi

echo "📦 Building Docker image: $FULL_IMAGE_NAME"
docker build \
    --build-arg COMPOSER_AUTH="$COMPOSER_AUTH_JSON" \
    --build-arg WWWUSER=1000 \
    --build-arg WWWGROUP=1000 \
    --build-arg TZ=UTC \
    --tag "$FULL_IMAGE_NAME" \
    .

echo "✅ Build completed successfully!"

# Test the image
echo "🧪 Testing the built image..."
docker run --rm \
    -e APP_KEY=base64:dBLUaMuZz7Tte4CataxlQK+GgYNRqiJpXn2RFa1dpgQ= \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=/tmp/test.sqlite \
    "$FULL_IMAGE_NAME" \
    php --version

echo "✅ Image test passed!"

# Optionally push to registry
if [ "$2" = "--push" ]; then
    echo "📤 Pushing image to registry..."
    docker push "$FULL_IMAGE_NAME"
    echo "✅ Image pushed successfully!"
fi

echo ""
echo "🎉 Deployment completed!"
echo "📋 Image: $FULL_IMAGE_NAME"
echo "🔧 To run locally:"
echo "   docker run -p 8000:8000 -e APP_KEY=your-app-key $FULL_IMAGE_NAME"
echo "🚀 To deploy with docker-compose:"
echo "   docker-compose -f docker-compose.prod.yml up -d" 