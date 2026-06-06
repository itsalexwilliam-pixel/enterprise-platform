@echo off
title Email Validator Pro - Stopping
cd /d "%~dp0"
echo Stopping Email Validator Pro...
docker compose -f docker-compose.local.yml down
echo Done.
pause
