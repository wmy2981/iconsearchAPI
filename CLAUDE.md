# IconSearchAPI

PHP 图标搜索 API 服务 + Python CLI 客户端。

## 项目结构

```
IconSearchAPI/
├── index.php               # 首页，含 API 文档和 WebUI 入口链接
├── search.php              # 搜索 API 端点
├── status.php              # 服务状态 API 端点
├── config.json             # 配置文件（认证、加速链接、默认值、日志级别）
├── includes/
│   ├── Config.php          # 配置加载器（单例）
│   ├── Logger.php          # 日志系统（按日期轮转，支持级别过滤）
│   ├── Auth.php            # Token 鉴权（Bearer header 或 ?token= 参数）
│   ├── Searcher.php        # 搜索引擎（含文件缓存）
│   └── LinkBoost.php       # GitHub 加速链接转换器
├── sources/                # 图标 JSON 源文件（<来源名>.json）
│   ├── HDiconsV2.json      # 1754 个图标
│   ├── MacIcons.json       # 26037 个图标（UTF-8 BOM）
│   ├── homarr.json         # 2798 个图标
│   └── material-icon.json  # Material 图标
├── cache/                  # 自动生成的序列化缓存（基于源文件 mtime 失效）
├── logs/                   # 按日期轮转日志（YYYY-MM-DD.log）
├── webui/                  # 纯静态 Web UI
│   ├── index.html          # 单文件前端（搜索、预览、分页、主题切换）
│   └── fonts/              # 本地字体文件（woff2，无外部 CDN 依赖）
│       ├── fonts.css       # @font-face 声明
│       ├── fraunces-*.woff2
│       ├── outfit-*.woff2
│       └── spacemono-*.woff2
└── cli/                    # Python CLI 工具
    ├── iconsearch.py       # CLI 主程序（搜索 API）
    ├── build_index.py      # 索引构建工具（扫描目录生成 JSON）
    └── requirements.txt    # Python 依赖
```

## 技术栈

- PHP 8.3+（使用 `str_starts_with`、`match` 等新语法）
- Python 3.10+（CLI 客户端）
- 无框架，纯原生 PHP / Python
- 无数据库，纯文件读取
- PHP 无 Composer 依赖；Python 依赖 `requests`

## 运行方式

```bash
php -S 127.0.0.1:8080
```

## API 接口

所有端点支持 CORS（`Access-Control-Allow-Origin: *`），支持 GET/POST/OPTIONS。

### 认证方式

1. **Authorization header**: `Authorization: Bearer <token>`
2. **Query 参数**: `?token=<token>`（仅 GET 有效，优先级低于 header）

### GET /search.php

```
GET /search.php?query=<搜索词>&num=<数量>&page=<页码>&pageSize=<每页数量>&token=<令牌>
```

- `query` — 必填，宽松匹配（图标名包含搜索词，大小写不敏感）
- `num` — 可选，限制总结果数量（1–1000）
- `page` — 可选，页码（需与 `pageSize` 一起使用才生效）
- `pageSize` — 可选，每页数量（默认 18，最大 100）
- `type` 和 `sources` — **静默忽略**（不报错）
- **不传 `page`/`pageSize` 时返回所有结果**

### POST /search.php

支持 `application/json` 和 `application/x-www-form-urlencoded`。

```json
{
  "query": "chrome",
  "num": 100,
  "page": 2,
  "pageSize": 20,
  "type": [".png"],
  "sources": ["HDiconsV2", "homarr"]
}
```

- `num` — 可选，限制总结果数量（1–1000）
- `page` — 可选，页码（需与 `pageSize` 一起使用才生效）
- `pageSize` — 可选，每页数量（默认 18，最大 100）
- `type` — 可选，文件扩展名过滤（数组或逗号分隔字符串如 `".png,.jpg"`）
- `sources` — 可选，来源过滤（数组或逗号分隔字符串）
- `type: "*"` 或 `sources: "*"` 表示不过滤

### 响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "serverName": "IconSearchAPI",
    "query": "chrome",
    "total": 150,
    "results": [
      {
        "name": "chrome",
        "url": "https://ghfast.top/https://raw.githubusercontent.com/...",
        "source": "homarr"
      }
    ]
  }
}
```

传入 `page`/`pageSize` 时响应额外包含 `page`、`pageSize`、`totalPages` 字段。

### 错误码

| code | 含义 |
|------|------|
| 200 | 成功 |
| 400 | 参数错误（缺 query、num 非整数或超范围） |
| 401 | 认证失败 |
| 405 | 请求方法不支持 |
| 500 | 服务器配置错误 |

### GET /status.php

返回服务状态信息。根据 `skipAuth` 配置决定是否需要鉴权。

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "serverName": "IconSearchAPI",
    "skipAuth": true,
    "logLevel": "INFO",
    "default": { "num": null, "type": "*", "sources": "*" },
    "linkBoost": 1,
    "sources": [
      { "name": "HDiconsV2", "iconCount": 1754 },
      { "name": "MacIcons", "iconCount": 26037 }
    ],
    "totalSources": 2,
    "totalIcons": 27791
  }
}
```

## 配置文件 config.json

```json
{
  "severName": "IconSearchAPI",
  "auth": "<SHA256 hash>",
  "skipAuth": false,
  "logLevel": "DEBUG",
  "linkBoost": [
    {
      "fast": "https://ghfast.top/<origin>",
      "originDomain": "https://raw.githubusercontent.com"
    }
  ],
  "default": {
    "num": null,
    "type": "*",
    "sources": "*"
  }
}
```

### 字段说明

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `severName` | string | 是 | 服务名称，出现在响应中 |
| `auth` | string | 条件 | Token 的 SHA256 哈希值（skipAuth 为 false 时必填） |
| `skipAuth` | bool | 否 | 跳过鉴权，设为 true 时允许任意请求（默认 false） |
| `logLevel` | string | 否 | 日志级别：DEBUG/INFO/WARN/ERROR，默认 DEBUG |
| `linkBoost` | array | 否 | 加速链接规则 |
| `default.num` | int/null | 否 | 默认返回数量，必须为正整数或 null，null 不限制 |
| `default.type` | array/"*" | 否 | 默认文件类型过滤，"*" 不限制 |
| `default.sources` | array/"*" | 否 | 默认来源过滤，"*" 不限制 |

### 加速链接规则

`fast` 中的 `<origin>` 占位符会被替换为匹配的原始链接域名。例如：
- 原始 URL: `https://raw.githubusercontent.com/user/repo/icon.png`
- 规则: `{ fast: "https://ghfast.top/<origin>", originDomain: "https://raw.githubusercontent.com" }`
- 结果: `https://ghfast.top/https://raw.githubusercontent.com/user/repo/icon.png`

### 相对路径处理

JSON 源文件中的 URL 如果是相对路径（不以 `http://` 或 `https://` 开头），会自动拼接为请求的完整地址。

例如，请求 `http://example.com:8080/search.php`，JSON 中的路径为 `/icons/chrome.png`，则返回的 URL 为 `http://example.com:8080/icons/chrome.png`。

### 生成 Token 哈希

```bash
echo -n "your-secret-token" | sha256sum
```

## 日志系统

### 级别层次

```
DEBUG(0) < INFO(1) < WARN(2) < ERROR(3)
```

`logLevel` 设置最低输出级别，低于该级别的日志不写入。

### 日志格式

```
[2026-06-22 14:30:00] [INFO] [125.3ms] message
```

`[125.3ms]` 是从请求开始到该条日志的耗时。

### 日志内容

| 级别 | 内容 |
|------|------|
| DEBUG | 请求参数、源文件加载详情、缓存状态、匹配统计 |
| INFO | 请求/响应摘要、认证成功、搜索完成 |
| WARN | 认证失败、源文件读取/解析错误 |
| ERROR | 目录扫描失败 |

### 安全

- Token 在日志中只显示前4后4字符：`[test...-123]`
- URI 中的 `?token=` 参数会被清除后再记录

## 搜索引擎

### 匹配规则

- 宽松匹配：`stripos(iconName, query)` — 图标名包含搜索词，大小写不敏感
- 例：搜索 `drive` 匹配 `115-drive-1`、`123-drive-2`

### 搜索流程

1. 扫描 `sources/` 目录获取所有 `*.json` 文件
2. 按 `sources` 过滤跳过不匹配的来源
3. 加载来源（优先从 `cache/` 读取，miss 时解析 JSON 并写缓存）
4. 逐个图标：名称匹配 → 类型过滤 → 加速链接转换
5. `search.php` 按 `num` 截断总量，传入 `page`/`pageSize` 时分页切片，否则返回全部结果

### 文件缓存

- 缓存路径: `cache/<md5(sourcePath)>.cache`
- 格式: PHP `serialize()` 序列化
- 失效: 源文件 `mtime` 更新时自动重建
- 用途: 避免每次请求重新解析大 JSON（MacIcons 4.3MB, 26037 图标）

### 已知问题

- MacIcons.json 有 UTF-8 BOM（`\xEF\xBB\xBF`），已做 strip 处理
- 每次请求重新扫描 `sources/` 目录（无目录缓存）
- 缓存使用 `serialize/unserialize`，非跨语言兼容

## 代码约定

- 所有类使用单例模式（Config、Logger）或直接实例化（Auth、Searcher、LinkBoost）
- 无命名空间，通过 `require_once` 加载
- 响应统一通过 `jsonResponse()` 函数输出并 `exit`
- 参数校验在入口文件完成，业务逻辑在各自类中
- 日志中不记录完整 token，只记录前4后4字符预览

## Web UI

纯静态单文件前端，位于 `webui/index.html`，通过浏览器直接打开或由 PHP 内置服务器提供。

### 功能

- 图标搜索（关键词 + 格式多选 + 来源多选）
- 来源列表从 `status.php` 动态加载
- 服务端分页，每页固定 18 个图标，CSS grid 自动按屏幕宽度分配列数
- 分页栏含页码跳转输入框
- 图标预览弹窗，点击「↗ 链接」跳转原图
- 深色/浅色/跟随系统三种主题模式（⊙ 自动 → ☾ 深色 → ☀ 浅色）
- 响应式布局，适配移动端
- 本地字体（woff2），无外部 CDN 依赖
- Token 不存储到 localStorage，API 地址默认填入当前页面 origin

### 使用

1. 在首页（`/`）点击「进入 WebUI →」或直接访问 `/webui/`
2. 点击右上角「⚙ 设置」填写 API 地址和 Token（可选）
3. 输入关键词搜索

## CLI 客户端

Python 命令行工具，面向人类和 agent，通过 POST 请求调用 API。

### 安装

```bash
cd cli
pip install -r requirements.txt
```

### 环境变量

| 变量 | 说明 | 必填 |
|------|------|------|
| `ICONSEARCH_API_URL` | API 地址（如 `http://127.0.0.1:8080`） | 是 |
| `ICONSEARCH_TOKEN` | 认证令牌 | 是 |

可通过 `--api-url` / `--token` 参数覆盖。

### 命令

```bash
# 搜索图标
iconsearch search <query> [options]

# 查看 API 状态
iconsearch status
```

### 搜索参数

| 参数 | 短写 | 说明 |
|------|------|------|
| `--num` | `-n` | 返回数量 |
| `--type` | `-t` | 文件类型过滤，逗号分隔（如 `.png,.jpg`） |
| `--sources` | `-s` | 来源过滤，逗号分隔（如 `HDiconsV2,homarr`） |
| `--page` | `-p` | 页码（需与 `--page-size` 一起使用） |
| `--page-size` | | 每页数量（默认 18，最大 100） |
| `--json` | | 输出 JSON 格式（默认表格） |

### 使用示例

```bash
# 设置环境变量
export ICONSEARCH_API_URL="http://127.0.0.1:8080"
export ICONSEARCH_TOKEN="your-token"

# 基本搜索（表格输出）
iconsearch search chrome

# 限制数量
iconsearch search drive --num 5

# 按类型过滤
iconsearch search proton --type .png

# 按来源过滤
iconsearch search home --sources homarr,HDiconsV2

# 分页查询
iconsearch search chrome --page 2 --page-size 20

# JSON 输出（适合 agent 解析）
iconsearch search chrome --json

# 查看状态
iconsearch status
```

### 输出格式

**表格模式**（默认，面向人类）:
```
Server: IconSearchAPI | Query: chrome | Total: 5
--------------------------------------------------------------------------------
#    Name                                Source       URL
--------------------------------------------------------------------------------
1    chrome-1                            HDiconsV2    https://ghfast.top/...
2    Google_Chrome__glass_               MacIcons     https://s3.macosicons.com/...
```

**JSON 模式**（`--json`，面向 agent）:
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "serverName": "IconSearchAPI",
    "query": "chrome",
    "total": 5,
    "results": [...]
  }
}
```

### Agent 集成

CLI 为 agent 设计：
- 环境变量配置，无需配置文件
- `--json` 输出结构化数据，方便解析
- 错误信息输出到 stderr，退出码非零
- 支持 `--api-url` / `--token` 参数覆盖环境变量

## 索引构建工具

扫描本地图标目录，生成 IconSearchAPI 格式的 JSON 索引文件，用于将图标仓库接入 API。

### 用法

```bash
python cli/build_index.py <工作目录> --domain <域名> [--ext .png,.svg] [--output index.json]
```

### 参数

| 参数 | 短写 | 说明 | 必填 |
|------|------|------|------|
| `workdir` | | 要扫描的工作目录 | 是 |
| `--domain` | `-d` | 图标服务器域名或本地路径 | 是 |
| `--ext` | `-e` | 文件后缀过滤，逗号分隔（如 `.png,.svg`） | 否 |
| `--output` | `-o` | 输出文件路径（默认 `output.json`） | 否 |

### 域名格式

| 格式 | 示例 | 说明 |
|------|------|------|
| HTTP/HTTPS URL | `https://example.com/icons` | 远程图标服务器 |
| 本地路径 | `C:/icons`、`./icons`、`//icons` | 本地文件系统 |

**注意**：在 Git Bash 中使用 `/icons` 会被转换为 `C:/Program Files/Git/icons`，建议用 `//icons` 或 `./icons` 避免此问题。

### 名称规则

- 默认使用文件名（去扩展名），如 `chrome.png` → `name: "chrome"`
- 同名冲突时自动转换为相对路径格式（`/` → `_`），如：
  - `icons/chrome.png` → `name: "chrome"`
  - `sub/icons/chrome.png` → `name: "sub_icons_chrome"`

### 输出格式

```json
{
  "icons": [
    {"name": "chrome", "url": "https://example.com/icons/chrome.png"},
    {"name": "sub_firefox", "url": "https://example.com/icons/sub/firefox.png"}
  ]
}
```

### 示例

```bash
# 基本用法
python cli/build_index.py /path/to/icon-repo -d https://example.com/icons

# 只索引 .png 文件
python cli/build_index.py /path/to/icon-repo -d https://example.com/icons -e .png

# 指定输出文件
python cli/build_index.py /path/to/icon-repo -d https://example.com/icons -o my_icons.json

# 使用本地路径
python cli/build_index.py /path/to/icon-repo -d /icons
```

## 版本管理

### 版本号规则（SemVer）

遵循语义化版本（`MAJOR.MINOR.PATCH`）：

| 变更类型 | 版本升级 | 说明 |
|---------|---------|------|
| API 不兼容变更 | MAJOR +1 | 请求/响应格式、认证方式等破坏性改动 |
| 功能新增（向后兼容） | MINOR +1 | 新 API 端点、新来源格式、新 CLI 命令 |
| Bug 修复（向后兼容） | PATCH +1 | 逻辑修复、性能优化、文档修正 |

- 当前版本：**v1.0.0**（首次稳定发布）
- 版本号记录在项目根目录的 `VERSION` 文件中
- Git tag 以 `v` 开头（如 `v1.0.0`）
- 每次发布需同时：更新 `VERSION` 文件 → 生成 changelog → 打 tag
- 预发布版本号示例：`v1.1.0-alpha.1`、`v2.0.0-rc.1`

### 发布流程

```bash
# 1. 更新 VERSION 文件
echo -n "1.1.0" > VERSION

# 2. 提交
git add VERSION
git commit -m "chore: bump version to 1.1.0"

# 3. 打标签
git tag -a v1.1.0 -m "v1.1.0"

# 4. 推送
git push origin main
git push origin v1.1.0
```

## 仓库信息

- **远程仓库**: `git@github.com:wmy2981/iconsearchAPI.git`
- **默认分支**: `main`
- **开源许可**: MIT（`LICENSE` 文件）
- **自述文件**: `README.md`

## Git 工作流

### 分支策略

- `main` — 稳定发布分支，始终保持可部署状态
- 功能开发直接在 `main` 上进行（单人项目），复杂功能可临时创建 feat/* 分支
- Tag 标记所有正式版本

### 提交信息规范

采用 [Conventional Commits](https://www.conventionalcommits.org/) 风格：

```
<类型>: <简短描述>

<详细说明（可选）>
```

| 类型 | 使用场景 |
|------|---------|
| `feat` | 新功能 |
| `fix` | Bug 修复 |
| `docs` | 文档变更 |
| `chore` | 构建/配置/工具变更 |
| `refactor` | 重构（非功能、非修复） |
| `perf` | 性能优化 |
| `style` | 代码风格（格式化、缩进等） |
| `test` | 测试相关 |

### 免跟踪文件（.gitignore）

| 路径 | 原因 |
|------|------|
| `config.json` | 包含 auth 哈希等敏感信息，提交 `config.json.example` 作为模板 |
| `cache/` | 运行时自动生成的文件缓存 |
| `logs/` | 运行时自动生成的日志文件 |
| `__pycache__/` | Python 字节码缓存 |
| `.DS_Store` / `Thumbs.db` | 操作系统无关文件 |
