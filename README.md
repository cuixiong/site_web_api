

cd ~/projects/site_web_api

docker compose up -d --build  (PROJECT_NAME=site_web_api docker compose up -d)

docker exec site_web_api_php composer install

chmod -R 777 storage bootstrap/cache


docker exec -it site_web_api_php ping global_mysql


