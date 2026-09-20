#!/bin/bash
# Синхронизация рабочей копии с тест-площадкой icms1.test
# Запуск из корня рабочей копии:  bash scripts/sync-test.sh
set -e

SRC="$(cd "$(dirname "$0")/.." && pwd)"
DST="${1:-/Users/maxisoft/Sites/icms1.test}"

rsync -a \
    --exclude=".git" \
    --exclude="includes/config.inc.php" \
    --exclude="cache/*" \
    --exclude="upload/*" \
    "$SRC/" "$DST/"

echo "Синхронизировано: $SRC -> $DST"
echo "Не забудьте: кеш шаблонов сайта сбрасывается удалением файлов в cache/ (кроме .htaccess)."
