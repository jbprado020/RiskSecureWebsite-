#!/bin/bash
cd /home/Jbprado020/Documents/Coding\ Projects/RiskSecureWebsite-

echo "🚀 Starting RiskSecure..."

# Start MySQL with persistent volume
podman run -d --replace --name risksecure-mysql \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=risk_secure_db \
  -v risksecure-data:/var/lib/mysql \
  -p 3306:3306 mysql:8.4

sleep 15

echo "📊 Importing all database schemas..."
# Import in correct order
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/risk_secure_db.sql
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/add_customer_accounts.sql
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/add_staff_accounts.sql
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/add_process_tables.sql
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/seed.sql
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/migrations/001_add_login_attempts_table.sql
podman exec -i risksecure-mysql mysql -u root -proot risk_secure_db < database/migrations/002_add_audit_logs_table.sql

echo "🎨 Starting phpMyAdmin..."
podman run -d --replace --name risksecure-phpmyadmin -e PMA_HOST=host.containers.internal -e PMA_USER=root -e PMA_PASSWORD=root -p 8080:80 phpmyadmin:latest

echo ""
echo "✅ Services ready!"
echo "   App:        http://localhost:8000"
echo "   phpMyAdmin: http://localhost:8080"
echo ""
echo "🚀 Starting PHP server..."
php -S localhost:8000
