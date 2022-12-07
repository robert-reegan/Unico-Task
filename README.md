Steps:

git clone --branch main git@github.com:robert-reegan/Unico-Task.git

Need to create ".env" and database credentials

composer install

php artisan key:generate

php artisan migrate

php artisan serve