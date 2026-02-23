#!/bin/bash

echo "=========================================="
echo "IDOR Lab - Deployment Script"
echo "=========================================="
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    echo "Visit: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    echo "Visit: https://docs.docker.com/compose/install/"
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"
echo ""

# Stop any existing containers
echo "🛑 Stopping existing containers..."
docker-compose down 2>/dev/null

echo ""
echo "🔨 Building Docker image..."
docker-compose build

if [ $? -ne 0 ]; then
    echo "❌ Build failed!"
    exit 1
fi

echo ""
echo "🚀 Starting the lab..."
docker-compose up -d

if [ $? -ne 0 ]; then
    echo "❌ Failed to start the lab!"
    exit 1
fi

echo ""
echo "=========================================="
echo "✅ Lab is now running!"
echo "=========================================="
echo ""
echo "📍 Access the lab at: http://localhost:8082"
echo ""
echo "🔑 Credentials:"
echo "   Username: student"
echo "   Password: password123"
echo ""
echo "📋 Commands:"
echo "   View logs:      docker-compose logs -f"
echo "   Stop lab:       docker-compose down"
echo "   Restart lab:    docker-compose restart"
echo ""
echo "🎯 Objective: Access admin profile (ID=0) and retrieve the FLAG!"
echo ""
echo "💡 Hint: After login, check the URL... /profile?user_id=?"
echo ""
echo "=========================================="
