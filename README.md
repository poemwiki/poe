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

**注意**: 由于构建优化（从 post-autoload-dump 中移除了 package:discover 和 vendor:publish），需要在容器启动后手动执行以下命令：
```bash
# 进入容器
docker exec -it <container_name> bash

# 执行包发现和资源发布
php artisan package:discover --ansi
php artisan vendor:publish --force --tag=livewire:assets --ansi
```
如果你是部署到 zeabur 或者 vercel 等平台，要自动化执行这个命令，应该在设置 start command 的地方设置这个命令：
```
php artisan package:discover --ansi && php artisan vendor:publish --force --tag=livewire:assets --ansi
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