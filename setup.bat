@echo off
setlocal EnableDelayedExpansion
title Email Validator Pro - Local Setup

echo.
echo  ========================================================
echo   Email Validator Pro - Local Development Setup
echo  ========================================================
echo.

:: ── Check Docker ────────────────────────────────────────────
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Docker is not installed or not running.
    echo          Please install Docker Desktop from https://docker.com
    pause
    exit /b 1
)

docker compose version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Docker Compose not found.
    echo          Please update Docker Desktop to a version that includes Compose V2.
    pause
    exit /b 1
)

echo  [OK] Docker is available.

:: ── Set project root ────────────────────────────────────────
set "PROJECT_DIR=%~dp0"
cd /d "%PROJECT_DIR%"

:: ── Generate APP_KEY if .env has empty one ───────────────────
echo.
echo  [1/7] Checking .env configuration...
if not exist ".env" (
    echo        .env not found — creating from .env.example
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
    ) else (
        echo  [ERROR] .env.example not found. Cannot create .env.
        pause
        exit /b 1
    )
)

:: Check if APP_KEY is empty
findstr /C:"APP_KEY=" .env | findstr /V "APP_KEY=base64:" >nul 2>&1
if %errorlevel% equ 0 (
    echo        APP_KEY is empty — will be generated after container starts.
)
echo  [OK] .env is ready.

:: ── Pull / Build images ─────────────────────────────────────
echo.
echo  [2/7] Building Docker images (first time: 10-20 min, please wait)...
docker compose -f docker-compose.local.yml build
if %errorlevel% neq 0 (
    echo  [ERROR] Docker build failed. Check output above.
    pause
    exit /b 1
)
echo  [OK] Images built.

:: ── Start infrastructure services first ─────────────────────
echo.
echo  [3/7] Starting infrastructure (MySQL, Redis, Mailpit)...
docker compose -f docker-compose.local.yml up -d mysql redis mailpit
if %errorlevel% neq 0 (
    echo  [ERROR] Failed to start infrastructure services.
    pause
    exit /b 1
)

:: Wait for MySQL to be ready
echo        Waiting for MySQL to be ready...
set /a attempts=0
:wait_mysql
set /a attempts+=1
docker compose -f docker-compose.local.yml exec -T mysql mysqladmin ping -h 127.0.0.1 -u root -proot_password --silent >nul 2>&1
if %errorlevel% neq 0 (
    if !attempts! lss 30 (
        timeout /t 2 /nobreak >nul
        goto wait_mysql
    ) else (
        echo  [WARN] MySQL may not be ready yet, continuing anyway...
    )
) else (
    echo  [OK] MySQL is ready.
)

:: ── Start application containers ────────────────────────────
echo.
echo  [4/7] Starting application (PHP, Nginx, Worker)...
docker compose -f docker-compose.local.yml up -d php nginx worker phpmyadmin
if %errorlevel% neq 0 (
    echo  [ERROR] Failed to start application containers.
    pause
    exit /b 1
)

:: Give PHP container time to run entrypoint (composer install, migrate, etc.)
echo        Waiting for application setup to complete...
timeout /t 5 /nobreak >nul

:: ── Generate APP_KEY if needed ───────────────────────────────
echo.
echo  [5/7] Generating application key...
docker compose -f docker-compose.local.yml exec -T php php artisan key:generate --no-interaction >nul 2>&1
echo  [OK] Application key set.

:: ── Run migrations and seeders ───────────────────────────────
echo.
echo  [6/7] Running database migrations and seeders...
docker compose -f docker-compose.local.yml exec -T php php artisan migrate --no-interaction --force
if %errorlevel% neq 0 (
    echo  [WARN] Migrations may have had warnings. Continuing...
)
docker compose -f docker-compose.local.yml exec -T php php artisan db:seed --no-interaction --force >nul 2>&1
if %errorlevel% neq 0 (
    echo  [WARN] Seeding had issues. Continuing...
) else (
    echo  [OK] Database ready.
)

:: ── Create storage link ──────────────────────────────────────
docker compose -f docker-compose.local.yml exec -T php php artisan storage:link --no-interaction >nul 2>&1

:: ── Clear and warm caches ────────────────────────────────────
echo.
echo  [7/7] Optimising application...
docker compose -f docker-compose.local.yml exec -T php php artisan config:clear >nul 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan route:clear >nul 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan view:clear >nul 2>&1
docker compose -f docker-compose.local.yml exec -T php php artisan config:cache >nul 2>&1
echo  [OK] Caches warmed.

:: ── Done ─────────────────────────────────────────────────────
echo.
echo  ========================================================
echo   Setup complete! Your application is running:
echo  ========================================================
echo.
echo   Application  : http://localhost:8005
echo   Admin Panel  : http://localhost:8005/admin
echo   PHPMyAdmin   : http://localhost:8080
echo   Mailpit UI   : http://localhost:8025
echo   RabbitMQ UI  : http://localhost:15672  (guest / guest)
echo.
echo   Default admin account (after seeding):
echo     Email    : admin@example.com
echo     Password : password
echo.
echo   Default user account:
echo     Email    : user@example.com
echo     Password : password
echo.
echo  ========================================================
echo.
echo  Useful commands:
echo    start.bat          - Start all containers
echo    stop.bat           - Stop all containers
echo    logs.bat           - View live logs
echo    artisan.bat ^<cmd^>  - Run artisan commands
echo.
pause
