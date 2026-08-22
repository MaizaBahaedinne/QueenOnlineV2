# QueenOnlineV2 - Setup VPS (Ubuntu 22.04/24.04)

Ce guide deploie le projet sur un VPS avec Nginx, PHP-FPM, MySQL et Node.

Important:

- Si ton VPS utilise deja CyberPanel (OpenLiteSpeed), n'applique pas les sections Nginx de ce document.
- Utilise le guide dedie: SETUP_CYBERPANEL.md.

## 1) Prerequis VPS

- Ubuntu 22.04 ou 24.04
- Un nom de domaine pointe vers le VPS (optionnel mais recommande)
- Acces sudo

## 2) Installer les dependances systeme

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server unzip git curl software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

Verifier:

```bash
php -v
node -v
npm -v
nginx -v
mysql --version
```

## 3) Installer Composer

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## 4) Creer la base MySQL

```bash
sudo mysql
```

Puis dans MySQL:

```sql
CREATE DATABASE queenonline_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'queenuser'@'127.0.0.1' IDENTIFIED BY 'ChangeMeStrong123!';
GRANT ALL PRIVILEGES ON queenonline_v2.* TO 'queenuser'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

## 5) Deployer le code

Exemple dossier cible:

```bash
sudo mkdir -p /var/www/queenonlinev2
sudo chown -R $USER:$USER /var/www/queenonlinev2
cd /var/www/queenonlinev2
```

Puis clone/pull du projet:

```bash
git clone <URL_DU_REPO> .
```

## 6) Installer backend + frontend

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

## 7) Configurer .env pour le VPS

Ouvrir .env et ajuster au minimum:

```dotenv
APP_NAME=QueenOnlineV2
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ton-domaine.tld

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=queenonline_v2
DB_USERNAME=queenuser
DB_PASSWORD=ChangeMeStrong123!

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Puis:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8) Droits fichiers

```bash
sudo chown -R www-data:www-data /var/www/queenonlinev2
sudo find /var/www/queenonlinev2 -type f -exec chmod 644 {} \;
sudo find /var/www/queenonlinev2 -type d -exec chmod 755 {} \;
sudo chmod -R ug+rwx /var/www/queenonlinev2/storage /var/www/queenonlinev2/bootstrap/cache
```

## 9) Config Nginx

Creer le virtual host:

```bash
sudo nano /etc/nginx/sites-available/queenonlinev2
```

Contenu:

```nginx
server {
    listen 80;
    server_name ton-domaine.tld www.ton-domaine.tld;

    root /var/www/queenonlinev2/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activer le site:

```bash
sudo ln -s /etc/nginx/sites-available/queenonlinev2 /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 10) SSL LetsEncrypt (recommande)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ton-domaine.tld -d www.ton-domaine.tld
```

## 11) Laravel scheduler (cron)

```bash
crontab -e
```

Ajouter:

```cron
* * * * * cd /var/www/queenonlinev2 && php artisan schedule:run >> /dev/null 2>&1
```

## 12) Queue worker (systemd)

Creer service:

```bash
sudo nano /etc/systemd/system/queenonlinev2-worker.service
```

Contenu:

```ini
[Unit]
Description=QueenOnlineV2 Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/queenonlinev2/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/queenonlinev2
StandardOutput=append:/var/www/queenonlinev2/storage/logs/worker.log
StandardError=append:/var/www/queenonlinev2/storage/logs/worker-error.log

[Install]
WantedBy=multi-user.target
```

Activer:

```bash
sudo systemctl daemon-reload
sudo systemctl enable queenonlinev2-worker
sudo systemctl start queenonlinev2-worker
sudo systemctl status queenonlinev2-worker
```

## 13) Commandes de maintenance

```bash
cd /var/www/queenonlinev2
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm
```

## 14) Checklist rapide de debug

- Erreur 500: verifier `storage/logs/laravel.log`
- Page blanche: verifier `APP_DEBUG=false` et logs PHP-FPM/Nginx
- Erreur DB: verifier credentials dans `.env`
- Erreur permission: verifier droits sur `storage` et `bootstrap/cache`
- Vite assets absents: relancer `npm run build`
- Routes non prises en compte: `php artisan route:clear && php artisan route:cache`

## 15) Notes projet

- Le projet actuel contient la base technique Laravel, mais les vues metier restent a completer.
- Les routes resource existent, alors que certaines actions CRUD ne sont pas encore implementees dans les controllers.
- En production, il est recommande de limiter les routes exposees tant que l'UI et les actions ne sont pas finalisees.
