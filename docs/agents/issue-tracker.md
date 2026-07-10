# Issue 追踪器：GitHub

本仓库的 Issue 和 PRD 使用 GitHub Issues 管理。所有操作均使用 `gh` CLI。

## 操作约定

- 创建：`gh issue create --title "..." --body "..."`
- 读取：`gh issue view <number> --comments`
- 列出：`gh issue list --state open`
- 评论：`gh issue comment <number> --body "..."`
- 标签：`gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- 关闭：`gh issue close <number> --comment "..."`

从 `git remote -v` 推断仓库；在此克隆目录中运行时，`gh` 会自动完成该操作。

## Pull Request 是否作为分诊入口

**Pull Request 不作为需求分诊入口。**

## 当技能要求“发布到 Issue 追踪器”时

创建 GitHub Issue。

## 当技能要求“获取相关工单”时

运行 `gh issue view <number> --comments`。
