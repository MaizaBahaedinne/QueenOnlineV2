# QueenOnlineV2 - CyberPanel Quickstart (copier-coller)

Objectif: deploy rapide en mode production sur un VPS avec CyberPanel deja installe.

## 0) Variables a adapter

Dans ton terminal SSH:

export DOMAIN="ton-domaine.tld"
export SITE_USER="siteuser"
export REPO_URL="https://github.com/owner/repo.git"
export APP_PATH="/home/$SITE_USER/public_html"
export DB_NAME="quee_QueenBD"
export DB_USER="quee_QueenBD"
export DB_PASS="r0gOkJqdt+eH9EpD"

Note:

- Tu as indique "QueenBD", mais CyberPanel prefixe automatiquement avec le compte domaine.
- Les valeurs finales a utiliser sont donc bien `quee_QueenBD` pour le nom de base et l'utilisateur.

## 1) Installer outils

sudo apt update && sudo apt upgrade -y
sudo apt install -y git unzip mysql-server supervisor
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
command -v composer >/dev/null || (cd /tmp && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php && sudo mv composer.phar /usr/local/bin/composer)

## 2) Creer base MySQL

sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1'; FLUSH PRIVILEGES;"

## 3) Deploy application

sudo mkdir -p "$APP_PATH"
sudo chown -R "$SITE_USER":"$SITE_USER" "$APP_PATH"
sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && [ -d .git ] || git clone '$REPO_URL' ."
sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && composer install --no-dev --optimize-autoloader && npm install && npm run build"
sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && [ -f .env ] || cp .env.example .env && php artisan key:generate"

## 4) Ecrire .env production

sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && sed -i.bak \
-e 's|^APP_ENV=.*|APP_ENV=production|' \
-e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
-e 's|^APP_URL=.*|APP_URL=https://${DOMAIN}|' \
-e 's|^DB_CONNECTION=.*|DB_CONNECTION=mysql|' \
-e 's|^#\?DB_HOST=.*|DB_HOST=127.0.0.1|' \
-e 's|^#\?DB_PORT=.*|DB_PORT=3306|' \
-e 's|^#\?DB_DATABASE=.*|DB_DATABASE='"${DB_NAME}"'|' \
-e 's|^#\?DB_USERNAME=.*|DB_USERNAME='"${DB_USER}"'|' \
-e 's|^#\?DB_PASSWORD=.*|DB_PASSWORD='"${DB_PASS}"'|' .env"

## 5) Migrer et optimiser

sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && php artisan migrate --force && php artisan db:seed --force && php artisan storage:link"
sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"
sudo chown -R "$SITE_USER":"$SITE_USER" "$APP_PATH"
sudo chmod -R ug+rwx "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"

## 6) Activer scheduler

( crontab -l 2>/dev/null; echo "* * * * * cd $APP_PATH && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -

## 7) Activer queue worker

sudo tee /etc/supervisor/conf.d/queenonlinev2-worker.conf >/dev/null <<EOF
[program:queenonlinev2-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_PATH/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$SITE_USER
numprocs=1
redirect_stderr=true
stdout_logfile=$APP_PATH/storage/logs/worker.log
stopwaitsecs=3600
EOF
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start queenonlinev2-worker:*

## 8) Verifications finales

sudo -u "$SITE_USER" bash -lc "cd '$APP_PATH' && php artisan about"
sudo supervisorctl status

Dans CyberPanel:
1. Verify document root = /home/<site-user>/public_html/public
2. Issue SSL
3. Force HTTPS
4. Rewrite ON

## 9) Si erreur 500

- Lire: /home/<site-user>/public_html/storage/logs/laravel.log
- Verifier owner et permissions sur storage + bootstrap/cache
- Relancer build: npm run build
