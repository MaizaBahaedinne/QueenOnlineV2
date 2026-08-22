# QueenOnlineV2 - Setup rapide (MAMP)

## Prerequis detectes

- PHP MAMP: `/Applications/MAMP/bin/php/php8.3.30/bin/php`
- Composer MAMP: `/Applications/MAMP/bin/php/composer`
- Node: `v22.16.0`
- npm: `10.9.2`

## Base MySQL

1. Demarrer MAMP (Apache + MySQL).
2. Creer la base:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h127.0.0.1 -P8889 -uroot -proot -e "CREATE DATABASE IF NOT EXISTS queenonline_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## Installation backend

Depuis `/Applications/MAMP/htdocs/QueenOnlineV2`:

```bash
/Applications/MAMP/bin/php/php8.3.30/bin/php /Applications/MAMP/bin/php/composer install
/Applications/MAMP/bin/php/php8.3.30/bin/php artisan migrate
```

## Installation frontend

```bash
npm install
npm run dev
```

## Lancer l'application

Terminal 1 (Laravel):

```bash
/Applications/MAMP/bin/php/php8.3.30/bin/php artisan serve --host=127.0.0.1 --port=8082
```

Terminal 2 (Vite):

```bash
npm run dev
```

Application: `http://127.0.0.1:8082`

## Notes

- Ce projet est separe de l'ancien (`QueenOnline`) et ne le modifie pas.
- Les variables metier visibles pourront etre stockees en base via une interface admin (approche recommandee pour ce contexte).
