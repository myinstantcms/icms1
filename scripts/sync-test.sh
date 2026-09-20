#!/bin/bash
# Синхронизация рабочей копии с тест-площадкой icms1.test
# Запуск из корня рабочей копии:  bash scripts/sync-test.sh
#
# Если площадка уже установлена (есть includes/config.inc.php), папки
# install/ и migrate/ автоматически переименовываются в _install/_migrate —
# иначе CMS показывает страницу «Установка InstantCMS» вместо сайта.
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

# уже установленный сайт: отключаем установщик переименованием папок
if [ -f "$DST/includes/config.inc.php" ]; then
    for pair in "install:_install" "migrate:_migrate"; do
        from="${pair%%:*}"
        to="${pair##*:}"
        if [ -d "$DST/$from" ]; then
            rm -rf "$DST/$to"
            mv "$DST/$from" "$DST/$to"
            echo "Переименовано (сайт установлен): $from -> $to"
        fi
    done
    # восстановить папки для теста установщика: bash scripts/sync-test.sh --with-installer
    if [ "$1" = "--with-installer" ]; then
        echo "(режим --with-installer оставлен как есть)"
    fi
fi

echo "Не забудьте: кеш шаблонов сайта сбрасывается удалением файлов в cache/ (кроме .htaccess)."
