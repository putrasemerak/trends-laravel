#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DB_FILE="$ROOT_DIR/database/database.sqlite"
BACKUP_DIR="$ROOT_DIR/database/backups"

if [[ ! -f "$DB_FILE" ]]; then
  echo "Database file not found: $DB_FILE" >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"
TS="$(date +%Y%m%d_%H%M%S)"
DEST="$BACKUP_DIR/database_$TS.sqlite"
cp "$DB_FILE" "$DEST"

echo "Backup created: $DEST"
