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

### 首次部署（Passport 加密密钥 & 初始客户端）
Laravel Passport 需要一对私钥/公钥文件用于签名访问令牌。它们只在第一次部署时生成一次：

```bash
# 仅第一次（数据库已迁移且 oauth_* 表存在）
php artisan passport:install
```

该命令会：
1. 生成 `storage/oauth-private.key` 与 `storage/oauth-public.key`（不要提交到仓库，多机需同步）。
2. 创建 Personal Access Client（用于 `$user->createToken()`）。
3. 创建 Password Grant Client（如果暂不使用密码授权可以忽略）。

注意：
* 生成的两个 key 可以放在 /data 下面，Dockerfile 中定义了相关的脚本，在容器启动时会从 /data 下复制两个 key 到 storage 目录下。
* 多机部署需要将生成的两个 key 文件安全分发到所有运行实例。
* 之后不要在镜像构建阶段或每次发布重复执行 `passport:install`，否则会产生多余客户端记录，若覆盖密钥还会使旧 token 全部失效。
* 如果只缺少 Personal Access Client，可用命令：`php artisan passport:client --personal` 创建。
* 如果只缺少 oauth keys，可以执行 `php artisan passport:keys`

### 手动验证 Token 签发
首次安装后可快速验证：
```bash
php artisan tinker
>>> $u = \App\User::first();
>>> $token = $u->createToken('smoke')->accessToken;
>>> $token; # 得到字符串表示成功
```


### Front-end Watch & Build
```bash
# install dependencies
pnpm install
# dev watch
pnpm run watch
# build
pnpm run build
```

## Contribution

### Code Style
The [PHP CS Fixer](https://cs.symfony.com/) is located at tools/php-cs-fixer/vendor/bin.  
The style config file is located at root directory .php-cs-fixer.dist.php.  
See [Config PHP CS Fixer for PHPStorm](https://www.jetbrains.com/help/phpstorm/using-php-cs-fixer.html#installing-configuring-php-cs-fixer) and 
[php-cs-fixer-configurator](https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0).

### Domain Docs

- See `docs/author-names.md` for the relationship between primary names (`name_lang`) and aliases, and the update flow.
