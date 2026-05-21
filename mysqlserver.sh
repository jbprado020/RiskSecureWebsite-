#!/bin/bash
set -e

echo "🚀 Starting MySQL..."
podman run -d --name risksecure-mysql \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=risk_secure_db \
  -p 3306:3306 mysql:8.4

echo "⏳ Waiting for MySQL to be ready..."
sleep 10

echo "📦 Importing database schema..."
podman exec risksecure-mysql mysql -u root -proot risk_secure_db < \
  database/risk_secure_db.sql

echo "🔐 Running rate limiting migration..."
podman exec risksecure-mysql mysql -u root -proot risk_secure_db < \
  database/migrations/001_add_login_attempts_table.sql

echo "📊 Starting phpMyAdmin..."
podman run -d --name risksecure-phpmyadmin \
  -e PMA_HOST=risksecure-mysql \
  -e PMA_USER=root \
  -e PMA_PASSWORD=root \
  -p 8080:80 phpmyadmin:latest

echo "✅ Setup complete!"
echo "   phpMyAdmin: http://localhost:8080"
echo "   MySQL: localhost:3306"
echo ""
echo "Start PHP server in another terminal:"
echo "   php -S localhost:8000"