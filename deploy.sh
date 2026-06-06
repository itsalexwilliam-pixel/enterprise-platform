#!/usr/bin/env bash
# ============================================================
# Enterprise Email Validation Platform
# Production Deployment Script
# ============================================================
# Usage: sudo bash deploy.sh [production|staging]
# ============================================================

set -euo pipefail

ENVIRONMENT=${1:-production}
APP_DIR="/var/www/email-validator"
LOG_FILE="/var/log/ev-deploy-$(date +%Y%m%d_%H%M%S).log"

log() { echo -e "\033[0;32m[$(date '+%H:%M:%S')] $1\033[0m" | tee -a "$LOG_FILE"; }
warn() { echo -e "\033[0;33m[WARN] $1\033[0m" | tee -a "$LOG_FILE"; }
error() { echo -e "\033[0;31m[ERROR] $1\033[0m" | tee -a "$LOG_FILE"; exit 1; }

log "============================================================"
log "  Enterprise Email Validator - Deployment Script"
log "  Environment: ${ENVIRONMENT}"
log "  Date: $(date)"
log "============================================================"

# ============================================================
# STEP 1: System Requirements Check
# ============================================================
log "STEP 1: Checking system requirements..."

command -v docker >/dev/null 2>&1       || error "Docker is not installed"
command -v docker-compose >/dev/null 2>&1 || error "Docker Compose is not installed"
command -v git >/dev/null 2>&1          || error "Git is not installed"

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "0")
if [[ "$PHP_VER" < "8.3" ]]; then
    warn "PHP 8.3+ recommended. Found: $PHP_VER"
fi

log "✅ System requirements met"

# ============================================================
# STEP 2: Setup .env File
# ============================================================
log "STEP 2: Configuring environment..."

if [ ! -f ".env" ]; then
    cp .env.example .env
    log "Created .env from .env.example"
    log "⚠️  Please edit .env with your credentials before continuing"
    log "    nano .env"
    read -p "Press ENTER when .env is configured..." -r
else
    log "✅ .env file exists"
fi

# Validate required env vars
required_vars=("APP_KEY" "DB_PASSWORD" "REDIS_PASSWORD" "RABBITMQ_PASSWORD" "STRIPE_SECRET")
for var in "${required_vars[@]}"; do
    if ! grep -q "^${var}=.\+" .env 2>/dev/null; then
        error "${var} is not set in .env file"
    fi
done

log "✅ Environment configured"

# ============================================================
# STEP 3: Build Docker Images
# ============================================================
log "STEP 3: Building Docker images..."

docker-compose build --no-cache api_server_1 2>&1 | tee -a "$LOG_FILE"
docker-compose build api_server_2 2>&1 | tee -a "$LOG_FILE"
docker-compose build smtp_worker_1 2>&1 | tee -a "$LOG_FILE"

log "✅ Docker images built"

# ============================================================
# STEP 4: Start Infrastructure Services
# ============================================================
log "STEP 4: Starting infrastructure services..."

# Start databases first
docker-compose up -d mysql_master mysql_replica redis rabbitmq
log "Waiting for databases to be ready..."
sleep 15

# Verify MySQL is ready
MYSQL_READY=false
for i in $(seq 1 30); do
    if docker-compose exec mysql_master mysqladmin ping -u root -p"${DB_ROOT_PASSWORD:-root}" 2>/dev/null; then
        MYSQL_READY=true
        break
    fi
    sleep 2
done

$MYSQL_READY || error "MySQL master failed to start after 60 seconds"
log "✅ Infrastructure services started"

# ============================================================
# STEP 5: Run Migrations & Seeders
# ============================================================
log "STEP 5: Running database migrations..."

docker-compose run --rm api_server_1 php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"
log "✅ Migrations complete"

log "STEP 5b: Running database seeders..."
docker-compose run --rm api_server_1 php artisan db:seed --force 2>&1 | tee -a "$LOG_FILE"
log "✅ Seeders complete"

# ============================================================
# STEP 6: Generate Application Key
# ============================================================
log "STEP 6: Generating application key..."

if ! grep -q "^APP_KEY=base64:" .env; then
    docker-compose run --rm api_server_1 php artisan key:generate --force
    log "✅ Application key generated"
else
    log "✅ Application key already set"
fi

# ============================================================
# STEP 7: Laravel Optimizations (Production)
# ============================================================
if [ "$ENVIRONMENT" = "production" ]; then
    log "STEP 7: Applying production optimizations..."

    docker-compose run --rm api_server_1 bash -c "
        php artisan config:cache &&
        php artisan route:cache &&
        php artisan view:cache &&
        php artisan event:cache &&
        php artisan storage:link
    " 2>&1 | tee -a "$LOG_FILE"

    log "✅ Production optimizations applied"
fi

# ============================================================
# STEP 8: Start All Services
# ============================================================
log "STEP 8: Starting all application services..."

docker-compose up -d 2>&1 | tee -a "$LOG_FILE"
log "Waiting for services to stabilize..."
sleep 10

log "✅ All services started"

# ============================================================
# STEP 9: Health Checks
# ============================================================
log "STEP 9: Running health checks..."

sleep 5

# Check API health
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    log "✅ API health check passed (HTTP $HTTP_CODE)"
else
    warn "API health check returned HTTP $HTTP_CODE"
fi

# Check RabbitMQ Management UI
RABBITMQ_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:15672 || echo "000")
log "RabbitMQ Management UI: HTTP $RABBITMQ_CODE"

# Check Grafana
GRAFANA_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000 || echo "000")
log "Grafana: HTTP $GRAFANA_CODE"

# ============================================================
# STEP 10: Show Running Containers
# ============================================================
log "STEP 10: Service Status"
docker-compose ps 2>&1 | tee -a "$LOG_FILE"

# ============================================================
# DEPLOYMENT COMPLETE
# ============================================================
log ""
log "============================================================"
log "  ✅ DEPLOYMENT COMPLETE!"
log "============================================================"
log ""
log "  Access URLs:"
log "  📧 Email Validator:        http://your-server-ip"
log "  🔧 Admin Panel:            http://your-server-ip/admin"
log "  🐰 RabbitMQ Management:    http://your-server-ip:15672"
log "  📊 Grafana Dashboard:      http://your-server-ip:3000"
log "  🔍 Prometheus Metrics:     http://your-server-ip:9090"
log ""
log "  Default Admin Credentials:"
log "  Email:    admin@yourdomain.com"
log "  Password: (set in .env as ADMIN_PASSWORD)"
log ""
log "  Next Steps:"
log "  1. Configure SSL/TLS certificate (Let's Encrypt)"
log "  2. Update DNS A records to point to this server"
log "  3. Configure Stripe webhook endpoint"
log "  4. Test API: curl -X POST http://your-ip/api/v1/validate"
log "              -H 'X-API-Key: your_key'"
log "              -d '{\"email\":\"test@example.com\"}'"
log ""
log "  Log file: $LOG_FILE"
log "============================================================"
