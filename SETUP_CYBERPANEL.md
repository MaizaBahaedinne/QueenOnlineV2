# QueenOnlineV2 - Setup avec CyberPanel

Ce guide est pour un VPS qui a deja CyberPanel (OpenLiteSpeed) installe.

## 1) Ce qui change avec CyberPanel

- Pas de configuration Nginx manuelle.
- Le vhost, SSL et PHP se gerent depuis CyberPanel.
- Le webroot du site est generalement: `/home/<site-user>/public_html`.

## 2) Creer le site dans CyberPanel

Depuis CyberPanel:

1. Websites > Create Website
2. Renseigner domaine, email, package, PHP 8.3
3. Activer SSL (issue SSL)

Note: garde le chemin du site (document root) pour les commandes SSH.

## 3) Installer prerequis en SSH

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git unzip mysql-server
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

Installer Composer (si absent):

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## 4) Base MySQL

```bash
sudo mysql
```

Puis:

```sql
CREATE DATABASE queenonline_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'queenuser'@'127.0.0.1' IDENTIFIED BY 'ChangeMeStrong123!';
GRANT ALL PRIVILEGES ON queenonline_v2.* TO 'queenuser'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

## 5) Deploy du projet

Exemple de chemin (a adapter):

```bash
cd /home/<site-user>/public_html
git clone <URL_DU_REPO> .
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

## 6) Config .env production

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

## 7) Permissions (important)

CyberPanel/OpenLiteSpeed utilise souvent un utilisateur de site dedie.

Identifier le user:

```bash
ls -ld /home/<site-user>/public_html
```

Puis appliquer:

```bash
sudo chown -R <site-user>:<site-user> /home/<site-user>/public_html
sudo find /home/<site-user>/public_html -type f -exec chmod 644 {} \;
sudo find /home/<site-user>/public_html -type d -exec chmod 755 {} \;
sudo chmod -R ug+rwx /home/<site-user>/public_html/storage /home/<site-user>/public_html/bootstrap/cache
```

## 8) Config OpenLiteSpeed pour Laravel

Dans CyberPanel:

1. Websites > List Websites > Manage
2. Verifier que le document root pointe vers le dossier public Laravel
3. Rewriterules: activer Rewrite

Regle Rewrite minimale (si necessaire):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [L,QSA]
```

## 9) SSL

Depuis CyberPanel:

- Manage SSL > Issue SSL
- Verifier redirection HTTPS active

## 10) Cron Laravel scheduler

Depuis CyberPanel (Cron Jobs) ou crontab:

```cron
* * * * * cd /home/<site-user>/public_html && php artisan schedule:run >> /dev/null 2>&1
```

## 11) Queue worker

Option simple via Supervisor (recommande):

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/queenonlinev2-worker.conf
```

Contenu:

```ini
[program:queenonlinev2-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/<site-user>/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=<site-user>
numprocs=1
redirect_stderr=true
stdout_logfile=/home/<site-user>/public_html/storage/logs/worker.log
stopwaitsecs=3600
```

Activer:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start queenonlinev2-worker:*
sudo supervisorctl status
```

## 12) Maintenance deploy

```bash
cd /home/<site-user>/public_html
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 13) Debug rapide

- Erreur 500: `storage/logs/laravel.log`
- Erreur permissions: verifier owner sur `storage` et `bootstrap/cache`
- Asset manquants: `npm run build`
- DB ko: verifier credentials `.env`
- Rewrite ko: verifier regles Rewrite OpenLiteSpeed

## 14) Notes

- Ce projet est techniquement deployable, mais les vues metier sont encore des placeholders.
- Plusieurs routes CRUD existent avec actions controller partielles: termine ces parties avant exposition publique complete.
