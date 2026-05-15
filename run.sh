#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_HOST="${APP_HOST:-127.0.0.1}"
APP_PORT="${APP_PORT:-8000}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-risk_secure_db}"
DB_USER="${DB_USER:-root}"

usage() {
  cat <<'EOF'
RiskSecure Linux Launcher

Usage:
  ./run.sh start       Start PHP dev server (Laragon-like quick start)
  ./run.sh check-db    Test DB connection using config/db.php
  ./run.sh db-shell    Open MariaDB shell over TCP (avoids socket issues)

Optional environment variables:
  APP_HOST, APP_PORT
  DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

Examples:
  ./run.sh start
  DB_USER=risksecure DB_PASS='secret' ./run.sh db-shell
  DB_USER=risksecure DB_PASS='secret' DB_NAME=risk_secure_db ./run.sh db-shell
EOF
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1"
    exit 1
  fi
}

start_server() {
  require_cmd php
  cd "$ROOT_DIR"
  echo "Starting RiskSecure at http://${APP_HOST}:${APP_PORT}/index.php"
  exec php -S "${APP_HOST}:${APP_PORT}" -t "$ROOT_DIR"
}

check_db() {
  require_cmd php
  cd "$ROOT_DIR"
  php -r 'require __DIR__ . "/config/db.php"; db(); echo "DB connection OK\n";'
}

db_shell() {
  require_cmd mariadb

  local -a pass_args
  if [[ -n "${DB_PASS:-}" ]]; then
    pass_args=(-p"${DB_PASS}")
  else
    pass_args=(-p)
  fi

  echo "Connecting to ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"
  exec mariadb -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" "${pass_args[@]}" "${DB_NAME}"
}

main() {
  case "${1:-}" in
    start)
      start_server
      ;;
    check-db)
      check_db
      ;;
    db-shell)
      db_shell
      ;;
    -h|--help|help|"")
      usage
      ;;
    *)
      echo "Unknown command: $1"
      usage
      exit 1
      ;;
  esac
}

main "$@"
