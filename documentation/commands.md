# comands

git config --global user.email "grgsdev@gmail.com"
git config --global user.name "grgsdev"
composer require laravellegends/pt-br-validator

composer install &&
php artisan migrate:fresh --seed &&
composer dump-autoload &&
php artisan config:clear &&
php artisan cache:clear &&
php artisan permission:cache-reset &&
php artisan route:clear &&
php artisan view:clear &&
php artisan config:cache &&
php artisan modelCache:clear &&
php artisan event:clear &&
php artisan scribe:generate &&
php artisan event:cache &&
php artisan queue:flush &&
php artisan config:cache &&
php artisan queue:restart

php artisan queue:work

composer install &&
php artisan migrate:fresh --seed &&
chmod -R 755 storage bootstrap/cache &&
php artisan seed:log-subject-types
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=PermissionActivityLogSeeder
php artisan db:seed --class=UserSeeder
chmod -R 755 storage bootstrap/cache

ssh -i "modern-cms.pem" ubuntu@18.228.90.246
docker compose exec php-worker supervisorctl status &&
docker compose exec php-worker supervisorctl restart all &&
docker compose exec php-worker supervisorctl start all
docker compose exec php-worker supervisorctl stop all
docker compose exec php-worker supervisorctl reread
docker compose exec php-worker supervisorctl update

sudo git reset --hard HEAD
sudo git pull
cat queue-worker.log
tail -f /var/www/clubelupa-back/storage/logs/queue-worker.log
cat schedule-worker.log
tail -f /var/www/clubelupa-back/storage/logs/schedule-worker.log
sudo systemctl restart php8.3-fpm
sudo systemctl restart apache2

php artisan modelCache:clear &&
php artisan event:clear &&
php artisan config:clear &&
php artisan cache:clear &&
php artisan config:cache &&
php artisan event:cache &&
php artisan queue:restart &&
php artisan queue:flush && php artisan config:cache && php artisan queue:restart
tail -f /var/www/clubelupa-back/storage/logs/queue-worker.log

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
sudo supervisorctl status

agora temos que analisar, na área do aluno, o controle de avanços e retrocessos de vídeos

aluno só pode avançar e retroceder para áreas úteis do vídeo, e gostaria de limitar avanços e retrocessos de 10s

aluno não pode avançar ou retroceder durante uma interação, qualquer que seja

durante um avanço de vídeo não pode ultrapassar um ponto de interação

Análise como outros produtos semelhantes ao nosso procedem, e vamos analisar uma maneira de implementarmos esse controle

SEVIDOR
cd /var/www/avamovie/current
git checkout resources/views/scribe/index.blade.php
git pull
npm install
export NODE_OPTIONS="--max-old-space-size=4096"
npm run build
php artisan migrate
php artisan storage:link
sudo chmod -R 777 storage bootstrap/cache

composer dump-autoload && php artisan config:clear && php artisan cache:clear && php artisan permission:cache-reset && php artisan route:clear && php artisan view:clear && php artisan config:cache && php artisan modelCache:clear && php artisan event:clear && php artisan scribe:generate && php artisan event:cache && php artisan queue:flush && php artisan config:cache && php artisan queue:restart

sudo chown -Rf ubuntu:www-data ../current/
sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
php artisan optimize:clear && php artisan config:clear && php artisan cache:clear
