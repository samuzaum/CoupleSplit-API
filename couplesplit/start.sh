#!/bin/bash
set -e

echo "==> Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Instalando dependências Node..."
npm ci

echo "==> Compilando assets..."
npm run build

echo "==> Configurando caches Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Rodando migrations..."
php artisan migrate --force

echo "==> Iniciando servidor..."
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
