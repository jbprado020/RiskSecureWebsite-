@echo off
REM Import all SQL files into MySQL via Laragon
REM This script assumes Laragon's MySQL is running

echo Starting SQL import process...
echo.

REM Set database name (you can modify this)
set DB_NAME=risk_secure
set MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.0-32\bin\mysql
set MYSQL_HOST=127.0.0.1
set MYSQL_USER=root
set MYSQL_PASS=

REM Check if MySQL path exists
if not exist "%MYSQL_PATH%.exe" (
    echo Error: MySQL not found at %MYSQL_PATH%
    echo Please update the MYSQL_PATH variable in this script
    pause
    exit /b 1
)

echo ============================================
echo Creating/Dropping Database
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% -e "DROP DATABASE IF EXISTS %DB_NAME%; CREATE DATABASE %DB_NAME%;"

if %ERRORLEVEL% NEQ 0 (
    echo Error creating database. Check your MySQL credentials.
    pause
    exit /b 1
)

echo.
echo ============================================
echo Importing main database schema
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\risk_secure_db.sql
if %ERRORLEVEL% NEQ 0 echo Error importing risk_secure_db.sql

echo.
echo ============================================
echo Importing customer accounts
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\add_customer_accounts.sql
if %ERRORLEVEL% NEQ 0 echo Error importing add_customer_accounts.sql

echo.
echo ============================================
echo Importing staff accounts
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\add_staff_accounts.sql
if %ERRORLEVEL% NEQ 0 echo Error importing add_staff_accounts.sql

echo.
echo ============================================
echo Importing process tables
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\add_process_tables.sql
if %ERRORLEVEL% NEQ 0 echo Error importing add_process_tables.sql

echo.
echo ============================================
echo Adding indexes
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\add_indexes.sql
if %ERRORLEVEL% NEQ 0 echo Error importing add_indexes.sql

echo.
echo ============================================
echo Seeding database
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\seed.sql
if %ERRORLEVEL% NEQ 0 echo Error importing seed.sql

echo.
echo ============================================
echo Running migrations
echo ============================================
%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\migrations\001_add_login_attempts_table.sql
if %ERRORLEVEL% NEQ 0 echo Error importing migration 001

%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\migrations\002_add_audit_logs_table.sql
if %ERRORLEVEL% NEQ 0 echo Error importing migration 002

%MYSQL_PATH% -h %MYSQL_HOST% -u %MYSQL_USER% %DB_NAME% < database\migrations\003_add_meeting_end_at.sql
if %ERRORLEVEL% NEQ 0 echo Error importing migration 003

echo.
echo ============================================
echo SQL import process completed!
echo ============================================
pause
