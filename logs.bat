@echo off
cd /d "%~dp0"
docker compose -f docker-compose.local.yml logs -f --tail=100
