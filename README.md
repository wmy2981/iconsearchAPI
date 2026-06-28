<div align="center">

# 🗄️ IconSearchAPI

**零依赖 PHP 图标搜索引擎** — 搜索 3 万+ 图标，支持 WebUI、CLI、RESTful API

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?logo=python&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)
![零依赖](https://img.shields.io/badge/Composer-无-lightgrey)
![无数据库](https://img.shields.io/badge/数据库-无-lightgrey)

[⚡ 快速开始](#-快速开始) · [🔍 API 文档](#-api-文档) · [🖥️ WebUI](#️-webui) · [📦 CLI 工具](#-cli-客户端) · [🏗️ 架构](#️-项目结构)

</div>

---

## 📸 预览

| WebUI 搜索 | 图标预览弹窗 | CLI 命令行 |
|---|---|---|
| 搜索、分页、多来源 | 大图预览、一键跳转 | 表格/JSON 输出 |
| 深色/浅色主题 | — | Agent 友好 |

## ✨ 特性

- **⚡ 纯原生 PHP** — 无框架、无 Composer 依赖、无数据库，一行命令启动
- **🔎 多来源搜索** — 聚合 4 个图标来源（3 万+ 图标），支持来源过滤
- **🖥️ WebUI** — 单文件纯静态前端，深色/浅色/跟随系统主题，本地字体无 CDN
- **📦 Python CLI** — 搜索 & 状态查询，表格/JSON 双模式输出，Agent 友好
- **🔐 Token 鉴权** — Bearer header 或 query 参数，灵活配置跳过认证
- **⚡ 加速链接** — 可配置 GitHub 资源加速规则，国内访问友好
- **📝 日志系统** — 按日期轮转，支持级别过滤，Token 自动脱敏
- **🐳 Docker 部署** — Docker Compose 一键启动，卷映射持久化配置与数据
- **📐 索引构建** — 扫描本地图标目录生成 JSON 索引，轻松扩展新来源

---

## ⚡ 快速开始

### 启动服务器

```bash
php -S 127.0.0.1:8080
```

### 搜索图标

```bash
# 浏览器访问
http://127.0.0.1:8080/search.php?query=chrome&num=5

# 或使用 WebUI（浏览器打开）
http://127.0.0.1:8080/
```

> **零配置默认跳过认证**，开箱即用。`config.json` 中 `skipAuth: true`。

### 使用 CLI

```bash
cd cli
pip install -r requirements.txt

# 设置环境变量
export ICONSEARCH_API_URL="http://127.0.0.1:8080"
export ICONSEARCH_TOKEN="your-token"

# 搜索
iconsearch search chrome --num 5

# 表格输出
iconsearch search drive

# JSON 输出（Agent 友好）
iconsearch search chrome --json

# 查看状态
iconsearch status
```

### Docker 一键部署

```bash
docker compose build    # 构建镜像 iconsearchAPI
docker compose up -d    # 启动容器（端口 8080）
```

容器化运行，配置、数据、日志通过卷持久化，修改 `config.json` 后重启容器即可生效。

---

## 🔍 API 文档

### 认证方式

| 方式 | 示例 |
|------|------|
| **Authorization Header** | `Authorization: Bearer <token>` |
| **Query 参数** | `?token=<token>` |

### `GET /search.php`

```
GET /search.php?query=<搜索词>&num=<数量>&page=<页码>&pageSize=<每页数量>
```

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `query` | string | ✅ | 搜索关键词（大小写不敏感） |
| `num` | int | ❌ | 总结果数量限制（1-1000） |
| `page` | int | ❌ | 页码（需与 pageSize 配合） |
| `pageSize` | int | ❌ | 每页数量（默认 18，最大 100） |

> 不传 `page`/`pageSize` 时返回全部匹配结果。

### `POST /search.php`

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

| 参数 | 类型 | 说明 |
|------|------|------|
| `type` | array/string | 文件扩展名过滤（如 `".png,.jpg"`，`"*"` 不过滤） |
| `sources` | array/string | 来源过滤（如 `"HDiconsV2,homarr"`，`"*"` 不过滤） |

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

分页模式下额外返回 `page`、`pageSize`、`totalPages`。

### 错误码

| 状态码 | 含义 |
|--------|------|
| 200 | ✅ 成功 |
| 400 | ❌ 参数错误（缺 query、num 超范围） |
| 401 | 🔒 认证失败 |
| 405 | 🚫 请求方法不支持 |
| 500 | 💥 服务器配置错误 |

### `GET /status.php`

返回服务状态信息（来源列表、图标统计、配置），`skipAuth` 决定是否需要鉴权。

---

## 🖥️ WebUI

单文件纯静态前端，位于 `webui/index.html`。

### 功能

- 🔍 关键词搜索 + 格式多选 + 来源多选
- 📄 服务端分页，CSS grid 自适应布局
- 🖼️ 图标预览弹窗，一键跳转原图
- 🌓 深色 / 浅色 / 跟随系统三种主题
- 📱 响应式布局，适配移动端
- 🔤 本地字体（Fraunces + Outfit + Space Mono），零外部 CDN
- 🔑 Token 不存 localStorage，API 地址默认自动填充

---

## 📦 CLI 客户端

### 环境变量

```bash
export ICONSEARCH_API_URL="http://127.0.0.1:8080"
export ICONSEARCH_TOKEN="your-token"
```

可通过 `--api-url` / `--token` 参数覆盖。

### 命令参考

```bash
iconsearch search <query> [options]
iconsearch status
```

| 参数 | 短写 | 说明 |
|------|------|------|
| `--num` | `-n` | 返回数量 |
| `--type` | `-t` | 文件类型过滤，逗号分隔 |
| `--sources` | `-s` | 来源过滤，逗号分隔 |
| `--page` | `-p` | 页码（需与 page-size 配合） |
| `--page-size` | | 每页数量（默认 18，最大 100） |
| `--json` | | JSON 输出（默认表格） |

### Agent 设计

CLI 专门为 Agent 使用场景优化：

- 环境变量配置，无需配置文件
- `--json` 输出结构化数据，直接 `jq` 解析
- 错误信息输出到 stderr，退出码非零
- 支持参数覆盖环境变量

---

## 🏗️ 项目结构

```
IconSearchAPI/
├── index.php               # 首页（API 文档入口）
├── search.php              # 搜索 API 端点
├── status.php              # 服务状态 API 端点
├── config.json             # 配置文件
├── includes/
│   ├── Config.php          # 配置加载器（单例）
│   ├── Logger.php          # 日志系统（日期轮转）
│   ├── Auth.php            # Token 鉴权
│   ├── Searcher.php        # 搜索引擎（含文件缓存）
│   └── LinkBoost.php       # GitHub 加速链接转换器
├── sources/                # 图标 JSON 源文件（4 来源，3 万+ 图标）
├── cache/                  # 自动生成的序列化缓存
├── logs/                   # 日志文件（YYYY-MM-DD.log）
├── webui/                  # 纯静态 Web UI
│   ├── index.html          # 单文件前端
│   └── fonts/              # 本地字体
├── cli/                    # Python CLI 工具
│   ├── iconsearch.py       # CLI 主程序
│   ├── build_index.py      # 索引构建工具
│   └── requirements.txt
├── Dockerfile              # Docker 镜像构建文件
├── docker-compose.yml      # Docker Compose 编排配置
├── .dockerignore           # Docker 构建排除规则
├── LICENSE                 # MIT License
└── README.md
```

---

## ⚙️ 配置

`config.json`：

| 字段 | 类型 | 说明 |
|------|------|------|
| `severName` | string | 服务名称 |
| `auth` | string | Token 的 SHA256 哈希（skipAuth 为 false 时必填） |
| `skipAuth` | bool | 跳过鉴权（默认 false） |
| `logLevel` | string | 日志级别：DEBUG / INFO / WARN / ERROR |
| `linkBoost` | array | 加速链接规则 |
| `default.num` | int / null | 默认返回数量（null 不限制） |
| `default.type` | array / `"*"` | 默认类型过滤 |
| `default.sources` | array / `"*"` | 默认来源过滤 |

### 加速链接规则

```json
{
  "fast": "https://ghfast.top/<origin>",
  "originDomain": "https://raw.githubusercontent.com"
}
```

原始 URL `https://raw.githubusercontent.com/user/repo/icon.png` → `https://ghfast.top/https://raw.githubusercontent.com/user/repo/icon.png`

### 生成 Token 哈希

```bash
echo -n "your-secret-token" | sha256sum
```

---

## 📐 扩展来源

### 方法一：添加 JSON 源文件

1. 准备图标索引 JSON（格式见 `sources/` 目录下示例）
2. 放入 `sources/` 目录
3. 重启服务器即可生效

### 方法二：使用索引构建工具

```bash
python cli/build_index.py <图标目录> --domain <域名> [--ext .png,.svg] [--output my_icons.json]
```

- `--domain` 支持 HTTP/HTTPS URL 和本地路径两种格式
- 同名冲突自动转为相对路径格式（如 `sub/chrome.png` → `sub_chrome`）

---

## 🔧 技术栈

| 层级 | 技术 |
|------|------|
| 后端 | **PHP 8.3+**（`str_starts_with`、`match` 等新语法） |
| 前端 | 纯 **HTML + CSS + JS**，单文件，无框架 |
| CLI | **Python 3.10+**，仅依赖 `requests` |
| 部署 | **Docker**（Alpine 镜像，Compose 编排） |
| 存储 | 文件系统（JSON + PHP serialize 缓存） |
| 数据库 | ❌ 无 |

---

## 📜 许可证

[MIT](./LICENSE) © 2026 wmy2981

---

<div align="center">

**IconSearchAPI** — 让图标搜索变得简单

[报告 Bug](https://github.com/wmy2981/IconSearchAPI/issues) · [功能建议](https://github.com/wmy2981/IconSearchAPI/issues)

</div>
