@echo off
REM Diagnostic script to find MySQL and test connection

echo ============================================
echo MySQL Diagnostic Check
echo ============================================
echo.

REM Common Laragon MySQL paths
set PATHS[0]=C:\laragon\bin\mysql\mysql-8.0-32\bin\mysql.exe
set PATHS[1]=C:\laragon\bin\mysql\mysql-8.0-64\bin\mysql.exe
set PATHS[2]=C:\laragon\bin\mysql\mysql-8.4-32\bin\mysql.exe
set PATHS[3]=C:\laragon\bin\mysql\mysql-8.4-64\bin\mysql.exe
set PATHS[4]=C:\laragon\bin\mysql\mysql-5.7\bin\mysql.exe

echo Checking for MySQL installations...
echo.

if exist "C:\laragon\bin\mysql\mysql-8.0-32\bin\mysql.exe" (
    echo Found: mysql-8.0-32
    set FOUND_MYSQL=C:\laragon\bin\mysql\mysql-8.0-32\bin\mysql.exe
)

if exist "C:\laragon\bin\mysql\mysql-8.0-64\bin\mysql.exe" (
    echo Found: mysql-8.0-64
    set FOUND_MYSQL=C:\laragon\bin\mysql\mysql-8.0-64\bin\mysql.exe
)

if exist "C:\laragon\bin\mysql\mysql-8.4-32\bin\mysql.exe" (
    echo Found: mysql-8.4-32
    set FOUND_MYSQL=C:\laragon\bin\mysql\mysql-8.4-32\bin\mysql.exe
)

if exist "C:\laragon\bin\mysql\mysql-8.4-64\bin\mysql.exe" (
    echo Found: mysql-8.4-64
    set FOUND_MYSQL=C:\laragon\bin\mysql\mysql-8.4-64\bin\mysql.exe
)

if exist "C:\laragon\bin\mysql\mysql-5.7\bin\mysql.exe" (
    echo Found: mysql-5.7
    set FOUND_MYSQL=C:\laragon\bin\mysql\mysql-5.7\bin\mysql.exe
)

echo.
echo Testing connection...
echo.

if defined FOUND_MYSQL (
    "%FOUND_MYSQL%" -h 127.0.0.1 -u root -e "SELECT VERSION();"
    if %ERRORLEVEL% EQU 0 (
        echo ✓ Connection successful!
        echo Using: %FOUND_MYSQL%
    ) else (
        echo ✗ Connection failed
    )
) else (
    echo ✗ No MySQL installation found in typical Laragon locations
)

echo.
echo Listing existing databases...
echo.

if defined FOUND_MYSQL (
    "%FOUND_MYSQL%" -h 127.0.0.1 -u root -e "SHOW DATABASES;"
)

echo.
pause
