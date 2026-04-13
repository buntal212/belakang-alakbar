#!/bin/bash
set -e  # Exit kalau error

# Tunggu dependencies ready (opsional, kalau compose healthcheck sudah handle)
echo "Waiting for dependencies..."
until nc -z mysql 3306; do
  echo "MySQL not ready, waiting..."
  sleep 2
done
until nc -z redis 6379; do
  echo "Redis not ready, waiting..."
  sleep 2
done

# Cache config & routes (selalu run, aman dan cepat)
echo "Caching Laravel config and routes..."
php artisan config:cache
php artisan route:cache
php artisan cache:clear  # Bonus: Cache clear

# Jalankan Supervisor (FPM dll.)
echo "Starting Supervisor..."
exec "$@"