# InstantCMS

> Community Management System

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-yellow.svg)
![MySQL](https://img.shields.io/badge/MySQL-5-orange.svg)

**Версия:** 1.10.7 (2016-07-26)

## Требования

- Apache + mod_rewrite
- PHP 8.2+ или PHP 5.3-8.1 (GD, iconv, mbstrings)
- MySQL 5

## Установка

1. Распакуйте архив с системой в папку сайта
2. Создайте базу данных
3. Запустите скрипт `http://yoursite.ru/install`
4. После установки удалите папки `install` и `migrate`

### Права на папки

При установке на хостинг установите права **777** на директории:

```
/cache
/images
/includes (только на время установки)
/upload
```

и все вложенные в них. На файлы `.htaccess` права должны быть **644**.

> **Важно:** Убедитесь, что директория хранения сессий настроена для каждого сайта своя, если у вас shared-хостинг.

## Обновление с версии 1.10.6

1. Сделайте резервную копию сайта и дамп базы данных — **обязательно!**
2. Распакуйте архив в папку с сайтом, заменяя все имеющиеся файлы
3. Запустите скрипт `http://yoursite.ru/migrate`
4. После завершения удалите папки `install` и `migrate`
5. Очистите кеш сайта и браузера

## Структура каталогов

```
/admin/          — Панель управления
/components/     — Компоненты системы
/core/           — Ядро системы
/includes/       — Библиотеки (Smarty, jQuery, PHPMailer и др.)
/languages/      — Языковые файлы
/modules/        — Модули
/plugins/        — Плагины
/templates/      — Шаблоны
/upload/         — Загруженные файлы
```

## Лицензия

Система распространяется по принципу **"КАК ЕСТЬ"** и **БЕЗ ГАРАНТИЙНЫХ ОБЯЗАТЕЛЬСТВ**.

Вы можете свободно использовать и модифицировать систему, но исключительно на свой страх и риск. Вы обязаны сохранять копирайты в исходном коде.

Подробнее см. в файлах [license.txt](license.txt) и [license.rus.txt](license.rus.txt).

## Ссылки

- Сайт: https://www.instantcms.ru
- Документация: https://docs.instantcms.ru
