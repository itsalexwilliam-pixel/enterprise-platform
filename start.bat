@echo off
title Email Validator Pro - Starting
cd /d "%~dp0"
echo Starting Email Validator Pro...
docker compose -f docker-compose.local.yml up -d
echo.
echo  Application  : http://localhost:8005
echo  PHPMyAdmin   : http://localhost:8080
echo  Mailpit UI   : http://localhost:8025
echo  RabbitMQ UI  : http://localhost:15672
echo.
pause
