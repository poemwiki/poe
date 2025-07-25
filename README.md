## About poemwiki

poemwiki（诗歌维基）是一个跨语种的诗歌库系统，收集并记录世界上的诗作，组成一个共有、自治的诗歌社区。
该项目基于“读首诗再睡觉”发展而来，二者均致力于推荐优秀诗作、译作。

## Environment Requirements
MySQL 8.0+  
PHP 8.3+  
Nginx  
Redis  

## Tech Stack
Laravel 9  
Meilisearch  


## Start

### Docker Deployment
```bash
# Build and run with Docker
docker build -t poemwiki .
docker run -p 8080:8080 poemwiki

# Or use docker-compose for full stack
docker-compose up -d
```

### Initialize
Import all searchable data to meilisearch
```
php artisan scout:import "App\Models\Author"
php artisan scout:import "App\Models\Poem"
```

### Front-end Watch & Build
```bash
# install dependencies
pnpm install
# dev watch
pnpm run watch
# build
pnpm run prod
```

## Contribution

### Code Style
The [PHP CS Fixer](https://cs.symfony.com/) is located at tools/php-cs-fixer/vendor/bin.  
The style config file is located at root directory .php-cs-fixer.dist.php.  
See [Config PHP CS Fixer for PHPStorm](https://www.jetbrains.com/help/phpstorm/using-php-cs-fixer.html#installing-configuring-php-cs-fixer) and 
[php-cs-fixer-configurator](https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0).