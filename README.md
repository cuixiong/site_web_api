

cd ~/projects/site_web_api

docker compose up -d --build  (PROJECT_NAME=site_web_api docker compose up -d)

docker exec site_web_api_php composer install

chmod -R 777 storage bootstrap/cache


docker exec -it site_web_api_php ping global_mysql


manticore的使用
# 1. 像连 MySQL 一样连进 Sphinx
docker exec -it global_sphinx mysql -P 9306 -h 127.0.0.1

# 2. 执行查询看条数
SHOW TABLES;                     # 看看有没有 products 这个索引
SELECT COUNT(*) FROM products;    # 看看里面有没有数据

# 2. 将普通索引的数据导入到实时索引
ATTACH INDEX products TO RTINDEX products_rt;


关闭搜索容器
cd /infra
docker compose -f docker_sphinx.yml down


cd /projects/site_web_api
docker compose down
