#!/bin/zsh
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$(TZ=Asia/Karachi date +%Y%m%d-%H%M)"
OUT="$ROOT/dist/skyfragrances-update-$STAMP.zip"
STAGE="$ROOT/dist/.update-stage"
rm -rf "$STAGE" && mkdir -p "$STAGE"
rsync -a --exclude 'install.php' --exclude 'config.php' --exclude 'config.sample.php' --exclude 'dev/' --exclude 'storage/' --exclude 'uploads/' --exclude 'cj.txt' --exclude '.DS_Store' --exclude '*.log' "$ROOT/site/" "$STAGE/"
cat > "$STAGE/UPDATE-README.txt" <<'TXT'
Sky Fragrances - live update pack
1. hPanel -> Files -> File Manager -> open public_html.
2. Upload this ZIP into public_html, right-click it -> Extract -> keep the path as public_html -> Extract.
   If it asks about existing files, choose Replace / Overwrite.
3. Delete the ZIP and this UPDATE-README.txt afterwards.
It never touches config.php, install.php, the database, storage/ or your uploads/.
TXT
(cd "$STAGE" && zip -qr "$OUT" . -x '*.DS_Store')
rm -rf "$STAGE"
echo "$OUT"
unzip -l "$OUT" | tail -1
unzip -l "$OUT" | awk '{print $4}' | grep -E '^(install\.php|config\.php|storage/|uploads/|dev/)' | head -3 || true
