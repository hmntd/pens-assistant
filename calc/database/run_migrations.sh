#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MIGRATIONS_DIR="${SCRIPT_DIR}/migrations"
FEEDERS_DIR="${SCRIPT_DIR}/feeders"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5433}"
DB_USER="${DB_USER:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-password}"
DB_NAME="${DB_NAME:-calc_db}"

RUN_FEEDERS=false

for arg in "$@"; do
    case $arg in
        --seed|-s|--feeders)
            RUN_FEEDERS=true
            ;;
        --help|-h)
            echo "Usage: $0 [--seed]"
            echo ""
            echo "Options:"
            echo "  --seed, -s, --feeders   Run database feeders/seeders after migrations"
            echo "  --help, -h              Display this help message"
            exit 0
            ;;
    esac
done

echo "========================================="
echo "Running calc_db Migrations"
echo "========================================="

run_sql() {
    local sql_file="$1"
    echo "--> Executing $(basename "$sql_file")..."
    if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' | grep -q "pens_calc_db"; then
        docker compose exec -T calc_db psql -U "$DB_USER" -d "$DB_NAME" < "$sql_file"
    else
        PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$sql_file"
    fi
}

echo "1. Applying Migrations..."
for f in "${MIGRATIONS_DIR}"/*.sql; do
    if [ -f "$f" ]; then
        run_sql "$f"
    fi
done

if [ "$RUN_FEEDERS" = true ]; then
    echo "2. Applying Feeders..."
    for f in "${FEEDERS_DIR}"/*.sql; do
        if [ -f "$f" ]; then
            run_sql "$f"
        fi
    done
else
    echo "Skipping feeders (use --seed or -s flag to run feeders)."
fi

echo "========================================="
echo "✅ calc_db migration completed successfully!"
echo "========================================="
