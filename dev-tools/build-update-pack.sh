#!/bin/zsh
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$(TZ=Asia/Karachi date +%Y%m%d-%H%M)"
OUT="$ROOT/dist/skyfragrances-update-$STAMP.zip"
STAGE="$ROOT/dist/.update-stage"
rm -rf "$STAGE" && mkdir -p "$STAGE/public_html"
rsync -a --exclude 'install.php' --exclude 'config.php' --exclude 'config.sample.php' --exclude 'dev/' --exclude 'storage/' --exclude 'uploads/' --exclude 'cj.txt' --exclude '.DS_Store' --exclude '*.log' "$ROOT/site/" "$STAGE/public_html/"
(cd "$STAGE" && zip -qr "$OUT" public_html -x '*.DS_Store')
rm -rf "$STAGE"
echo "$OUT"
unzip -l "$OUT" | tail -1
unzip -l "$OUT" | awk '{print $4}' | grep -E 'public_html/(install\.php|config\.php|storage/|uploads/|dev/)' | head -3 || true
