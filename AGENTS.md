# Repository Guidelines

## 项目结构与模块

PoemWiki 是一个 Laravel 9 的跨语种诗歌库。业务代码位于 `app/`：HTTP 控制器在 `app/Http/Controllers/`，请求校验在 `app/Http/Requests/`，领域模型在 `app/Models/`，查询与数据访问逻辑分别在 `app/Query/` 和 `app/Repositories/`。路由按入口分布在 `routes/web.php`、`routes/api.php` 和 `routes/admin.php`。

Blade 模板、Vue 组件、样式和翻译资源位于 `resources/`；编译产物和静态媒体位于 `public/`。数据库 schema 变更、工厂和种子分别放入 `database/migrations/`、`database/factories/`、`database/seeds/`。领域和接口说明维护在 `docs/`。

## 构建、测试与本地开发

先复制 `.env.example` 为 `.env`，配置 MySQL、Redis 和所需密钥，再安装依赖：

```bash
composer install                 # 安装 PHP/Laravel 依赖
pnpm install                     # 安装前端依赖（Node 18+）
php artisan migrate              # 应用数据库迁移
composer dev                     # 在 0.0.0.0:8881 启动开发服务
pnpm run watch                   # 监听并重编译前端资源
pnpm run build                   # 生成生产前端资源
./vendor/bin/phpunit             # 运行完整 PHPUnit 测试套件
tools/php-cs-fixer-wrapper.sh fix # 格式化 PHP 代码
```

Docker 部署可参考 `README.md` 中的 `docker build` 与 `docker run` 命令。首次部署 Passport 时执行一次 `php artisan passport:install`；不要提交生成的 OAuth 私钥。

## 代码风格与命名

遵循 `.editorconfig`：PHP 使用 4 空格，Blade、JS、YAML 使用 2 空格，文件使用 UTF-8 与 LF。PHP 采用 PSR-4（例如 `App\\Services\\PoemService`），类使用 PascalCase，方法与变量使用 camelCase，迁移文件保留 Laravel 时间戳加 snake_case 描述。提交前运行 PHP CS Fixer；其规则要求短数组语法、有序且无未使用的 `use`，简单字符串优先单引号。

## 测试准则

测试文件以 `Test.php` 结尾。单元测试放在 `tests/Unit/`，HTTP/API 与浏览器流程测试放在 `tests/Feature/` 和 `tests/APIs/`；公共构造辅助放在 `tests/Concerns/`。测试环境读取 `.env.testing`。覆盖业务规则、状态转换和异常输入，断言可观察的行为与响应，而非内部实现或易变文案。涉及用户交互的流程应同时覆盖成功和失败路径。

## 开发工作流

本工作流基于 [Matt Pocock Engineering Skills](https://github.com/mattpocock/skills/tree/main/skills/engineering) 中的几个 skill，如果当前环境中还没有这些 skill，需要先把这些 skill 安装到项目目录。

默认采用轻量闭环：`grill-with-docs → to-spec → TDD → Review → PR`。先通过 `grill-with-docs` 与 agent 澄清需求、术语和边界，必要时沉淀 `CONTEXT.md` 或 ADR；再使用 `to-spec` 将已讨论内容整理为规格并创建 GitHub Issue。实现时优先以可观察行为为边界，按 TDD 的小步循环推进。

仅在规格确实需要拆分为多个可独立验证、依赖关系明确的工作项时，才使用 `to-tickets`。无依赖的工作项可以交由 subagent 并行处理；简单需求直接依据规格实现，不为流程而拆分。

### Review 流程

[Matt Pocock Engineering Skills] 中的 code review 技能不好用, 所以特别规定此项目的 Review 流程。  
进入正式 review 后，至少完成一轮“`review → 修改/回复 → 复核`”才可提交 PR。

1. 使用 review subagent 审查当前 `git diff` 及相关文件，并在 `docs/reviews/` 新建 Markdown 文档，将至少六条意见按重要程度排序。审查应参考 Linus Torvalds 和 John Carmack 的编程原则，并检查 SSOT、DRY、多余的兜底兼容、过度设计、缺少说明“为什么”的注释、无必要注释、hardcoded numbers，以及常见的 vibe coding 测试问题（如 `constantA = literalA` 或 `mockA = A`）。
2. Coding agent 必须逐条阅读意见：认同的直接修改；不认同的在同一条下说明理由。
3. 完成修改和回复后，review subagent 必须在同一份文档中逐条复核，并补充新增意见。
4. 仅当每条意见均已处理且复核，或有明确、可复核的不采纳理由时，才可正式提交 PR。

## 提交与 Pull Request

历史采用 Conventional Commits 风格：`feat(api): add author search`、`fix(import): preserve aliases`、`docs: update guide`。每次提交聚焦单一变更，必要时附迁移与测试。PR 应说明目的、风险与验证命令，关联 issue；影响界面时附截图，影响 API 或数据格式时同步更新 `docs/`。

## Agent 技能

### Issue 追踪器

事项通过 GitHub Issues 管理（`star8ks/poe`）。参见 `docs/agents/issue-tracker.md`。

### 领域文档

采用单一上下文布局：根目录 `CONTEXT.md` 与 `docs/adr/`。参见 `docs/agents/domain.md`。
