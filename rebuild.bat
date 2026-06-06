@echo off
setlocal EnableDelayedExpansion
title Email Validator Pro - Rebuild
cd /d "%~dp0"

echo.
echo  ========================================================
echo   Email Validator Pro - Force Rebuild
echo  ========================================================
echo.

:: Check Docker
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Docker is not running. Start Docker Desktop first.
    pause
    exit /b 1
)

echo  [1] Stopping and removing old containers...
docker compose -f docker-compose.local.yml down --remove-orphans 2>&1
echo  [OK] Containers stopped.

echo.
echo  [2] Rebuilding PHP image (applying all fixes)...
echo      This may take 5-15 minutes on first build...
docker compose -f docker-compose.local.yml build --no-cache php worker 2>&1
if %errorlevel% neq 0 (
    echo.
    echo  [ERROR] Build failed! See error above.
    pause
    exit /b 1
)
echo  [OK] Images rebuilt.

echo.
echo  [3] Starting MySQL, Redis, Mailpit...
docker compose -f docker-compose.local.yml up -d mysql redis mailpit rabbitmq
echo  [OK] Infrastructure started.

echo.
echo  [4] Waiting for MySQL to be ready (up to 60 seconds)...
set /a attempts=0
:wait_mysql
set /a attempts+=1
docker compose -f docker-compose.local.yml exec -T mysql mysqladmin ping -h 127.0.0.1 -u root -proot_password --silent >nul 2>&1
if %errorlevel% neq 0 (
    if !attempts! lss 30 (
        echo      MySQL not ready yet... attempt !attempts!/30
        timeout /t 2 /nobreak >nul
        goto wait_mysql
    ) else (
        echo  [WARN] MySQL taking too long. Continuing anyway...
    )
) else (
    echo  [OK] MySQL is ready.
)

echo.
echo  [5] Starting PHP, Nginx, Worker...
docker compose -f docker-compose.local.yml up -d php nginx worker phpmyadmin
echo  [OK] Application containers started.

echo.
echo  [6] Waiting for PHP setup (composer install + migrations)...
echo      Checking logs... press Ctrl+C to stop watching, app may take 2-5 min.
echo.
timeout /t 5 /nobreak >nul

echo  [7] Generating app key and running migrations...
docker compose -f docker-compose.local.yml exec -T php php artisan key:generate --force >nul 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan migrate --force --no-interaction 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan db:seed --force --no-interaction 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan config:clear >nul 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan route:clear >nul 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan view:clear >nul 2>&1
echo  [OK] Setup complete.

echo.
echo  ========================================================
echo   Application is ready!
echo  ========================================================
echo.
echo   App        : http://localhost:8005
echo   Admin      : http://localhost:8005/admin
echo   PHPMyAdmin : http://localhost:8080
echo   Mailpit    : http://localhost:8025
echo.
echo   Login: admin@example.com / password
echo   Login: user@example.com  / password
echo.
echo  If app not loading, run: logs.bat  to see errors
echo.
pause
