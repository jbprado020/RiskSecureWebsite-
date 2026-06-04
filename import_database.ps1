# PowerShell script to import SQL files with auto-detection
# This is more robust than the batch script

Write-Host "================================" -ForegroundColor Green
Write-Host "RiskSecure Database Import Tool" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""

# Check if Laragon is installed
$laragonPath = "C:\laragon"
if (!(Test-Path $laragonPath)) {
    Write-Host "ERROR: Laragon not found at $laragonPath" -ForegroundColor Red
    Write-Host "Please install Laragon or check the path" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

# Find MySQL executable
Write-Host "Searching for MySQL installation..." -ForegroundColor Cyan
$mysqlPaths = @(
    "C:\laragon\bin\mysql\mysql-8.4-64\bin\mysql.exe",
    "C:\laragon\bin\mysql\mysql-8.4-32\bin\mysql.exe",
    "C:\laragon\bin\mysql\mysql-8.0-64\bin\mysql.exe",
    "C:\laragon\bin\mysql\mysql-8.0-32\bin\mysql.exe",
    "C:\laragon\bin\mysql\mysql-5.7\bin\mysql.exe"
)

$mysqlExe = $null
foreach ($path in $mysqlPaths) {
    if (Test-Path $path) {
        $mysqlExe = $path
        Write-Host "✓ Found: $path" -ForegroundColor Green
        break
    }
}

if (!$mysqlExe) {
    Write-Host "ERROR: MySQL not found in any standard Laragon location" -ForegroundColor Red
    Write-Host ""
    Write-Host "Checking Laragon\bin\mysql directory:" -ForegroundColor Yellow
    Get-ChildItem "C:\laragon\bin\mysql\" -Directory | ForEach-Object {
        Write-Host "  - $_"
    }
    Read-Host "Press Enter to exit"
    exit
}

# Test MySQL connection
Write-Host ""
Write-Host "Testing MySQL connection..." -ForegroundColor Cyan
$testResult = & $mysqlExe -h 127.0.0.1 -u root -e "SELECT VERSION();" 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ MySQL connection successful" -ForegroundColor Green
} else {
    Write-Host "✗ MySQL connection failed" -ForegroundColor Red
    Write-Host "Error: $testResult" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Make sure:" -ForegroundColor Yellow
    Write-Host "  1. Laragon is running (check system tray)" -ForegroundColor Yellow
    Write-Host "  2. MySQL service is started in Laragon" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

# Database setup
$dbName = "risk_secure"
$projectPath = Get-Location

Write-Host ""
Write-Host "================================" -ForegroundColor Green
Write-Host "Creating Database: $dbName" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green

# Create database
& $mysqlExe -h 127.0.0.1 -u root -e "DROP DATABASE IF EXISTS ``$dbName``; CREATE DATABASE ``$dbName``;"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Database created" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to create database" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit
}

# Import SQL files
$sqlFiles = @(
    @{ file = "database\risk_secure_db.sql"; name = "Main Schema" },
    @{ file = "database\add_customer_accounts.sql"; name = "Customer Accounts" },
    @{ file = "database\add_staff_accounts.sql"; name = "Staff Accounts" },
    @{ file = "database\add_process_tables.sql"; name = "Process Tables" },
    @{ file = "database\add_indexes.sql"; name = "Indexes" },
    @{ file = "database\seed.sql"; name = "Seed Data" },
    @{ file = "database\migrations\001_add_login_attempts_table.sql"; name = "Migration 001 - Login Attempts" },
    @{ file = "database\migrations\002_add_audit_logs_table.sql"; name = "Migration 002 - Audit Logs" },
    @{ file = "database\migrations\003_add_meeting_end_at.sql"; name = "Migration 003 - Meeting End At" }
)

Write-Host ""
Write-Host "================================" -ForegroundColor Green
Write-Host "Importing SQL Files" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""

$successCount = 0
$failCount = 0

foreach ($sqlFile in $sqlFiles) {
    $filePath = Join-Path $projectPath $sqlFile.file
    
    if (!(Test-Path $filePath)) {
        Write-Host "✗ File not found: $($sqlFile.file)" -ForegroundColor Red
        $failCount++
        continue
    }
    
    Write-Host "Importing: $($sqlFile.name)..." -ForegroundColor Cyan -NoNewline
    
    $result = Get-Content $filePath | & $mysqlExe -h 127.0.0.1 -u root $dbName 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host " ✓" -ForegroundColor Green
        $successCount++
    } else {
        Write-Host " ✗" -ForegroundColor Red
        Write-Host "  Error: $result" -ForegroundColor Yellow
        $failCount++
    }
}

# Final summary
Write-Host ""
Write-Host "================================" -ForegroundColor Green
Write-Host "Import Summary" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host "Successful: $successCount" -ForegroundColor Green
Write-Host "Failed: $failCount" -ForegroundColor $(if ($failCount -eq 0) { "Green" } else { "Red" })
Write-Host ""
Write-Host "Check phpMyAdmin: http://localhost/phpmyadmin" -ForegroundColor Cyan
Write-Host "Database name: $dbName" -ForegroundColor Cyan
Write-Host ""
Read-Host "Press Enter to exit"
