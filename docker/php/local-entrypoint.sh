#!/bin/sh
# ============================================================
# Local Development Entrypoint
# Runs setup tasks before starting PHP-FPM
# ============================================================

set -e

cd /var/www/html

echo "🚀 Starting Email Validator PHP container..."

# Install dependencies if vendor/autoload.php doesn't exist
if [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Installing Composer dependencies..."
    COMPOSER_PROCESS_TIMEOUT=0 composer install --no-interaction --prefer-dist
    echo "✅ Composer install complete"
fi

# Generate app key if not set
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --no-interaction
fi

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL..."
until php -r "
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'connected';
" 2>/dev/null; do
    echo "   MySQL not ready yet, retrying in 2s..."
    sleep 2
done
echo "✅ MySQL is ready"

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force --no-interaction
echo "✅ Migrations complete"

# Run seeders (only if tables are empty)
USERS_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USERS_COUNT" = "0" ]; then
    echo "🌱 Running seeders..."
    php artisan db:seed --no-interaction
    echo "✅ Seeding complete"
fi

# Create storage link
php artisan storage:link --no-interaction 2>/dev/null || true

# Ensure required storage directories exist
mkdir -p storage/app/uploads storage/app/results storage/app/public
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Copy JS assets to public (no build step needed — pure vanilla JS with CDN Vue)
mkdir -p public/js
cp resources/js/app.js public/js/app.js 2>/dev/null || true

# Clear caches in local
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

echo "✅ Setup complete! Starting PHP-FPM..."

# Start PHP-FPM
exec "$@"
