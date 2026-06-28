<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IconSearchAPI — 文档</title>
<link href="webui/fonts/fonts.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --copper:#c8843a;--copper-light:#e6a85c;--copper-dim:#8a5c2a;
  --bg:#080b14;--bg2:#0e1220;--surface:#111828;--surface2:#1a2440;
  --border:#2a3450;--text:#c8d0e0;--text-dim:#6a7a9a;--text-bright:#e8ecf4;
  --radius:6px;--font-display:'Fraunces','Georgia',serif;
  --font-body:'Outfit',sans-serif;--font-mono:'Space Mono',monospace;
}
body{
  background:var(--bg);color:var(--text);font-family:var(--font-body);
  line-height:1.7;padding:0;max-width:960px;margin:0 auto;
}
header{
  padding:48px 24px 32px;text-align:center;border-bottom:1px solid var(--border);
  background:linear-gradient(180deg,var(--surface) 0%,transparent 100%);
}
header h1{font-family:var(--font-display);font-size:2.5em;color:var(--copper);font-weight:600}
header p{color:var(--text-dim);margin-top:8px;font-size:1.05em}
header .links{margin-top:16px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
header .links a{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 20px;border-radius:var(--radius);
  background:var(--surface2);color:var(--copper-light);
  text-decoration:none;font-size:.92em;border:1px solid var(--border);
  transition:all .15s;
}
header .links a:hover{background:var(--copper);color:#fff;border-color:var(--copper)}
nav{
  position:sticky;top:0;z-index:10;background:rgba(8,11,20,.92);backdrop-filter:blur(8px);
  border-bottom:1px solid var(--border);padding:8px 16px;overflow-x:auto;white-space:nowrap;
}
nav a{color:var(--text-dim);text-decoration:none;padding:6px 14px;font-size:.88em;display:inline-block;transition:color .15s}
nav a:hover{color:var(--copper-light)}
main{padding:24px}
section{margin-bottom:40px}
h2{font-family:var(--font-display);font-size:1.5em;color:var(--copper-light);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)}
h3{font-size:1.08em;color:var(--text-bright);margin:20px 0 10px}
h4{font-size:.96em;color:var(--copper);margin:14px 0 6px}
p,li{color:var(--text);font-size:.95em}
code{font-family:var(--font-mono);background:var(--surface2);padding:2px 6px;border-radius:3px;font-size:.88em;color:var(--copper-light)}
pre{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;overflow-x:auto;margin:12px 0;font-size:.85em;line-height:1.5;tab-size:2}
pre code{background:none;padding:0;color:var(--text);font-size:1em}
table{width:100%;border-collapse:collapse;margin:12px 0;font-size:.9em}
th,td{text-align:left;padding:8px 12px;border:1px solid var(--border)}
th{background:var(--surface2);color:var(--text-bright);font-weight:500;white-space:nowrap}
td{color:var(--text)}
tr:hover td{background:var(--surface)}
.tag{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.78em;font-weight:500;margin-right:4px}
.tag.get{background:#1a3a4a;color:#6abfdb}
.tag.post{background:#2a3a1a;color:#8fc86a}
.tag.getpost{background:#3a2a4a;color:#b88ad8}
.param{font-family:var(--font-mono);font-size:.88em}
.required{color:var(--danger,#c44040)}.optional{color:var(--text-dim)}
.badge{
  display:inline-block;padding:3px 10px;border-radius:4px;
  font-size:.78em;font-weight:500;margin:2px;
}
.badge.b200{background:#1a3a2a;color:#6ac88a}
.badge.b400{background:#3a2a1a;color:#d8a84a}
.badge.b401{background:#3a1a1a;color:#d86a6a}
.badge.b405{background:#2a2a3a;color:#8a8ac8}
.badge.b500{background:#3a1a2a;color:#d86a8a}
ul{list-style:none;padding:0}
ul li{padding:4px 0 4px 20px;position:relative}
ul li::before{content:"•";position:absolute;left:4px;color:var(--copper-dim)}
footer{
  text-align:center;padding:32px 24px;border-top:1px solid var(--border);
  color:var(--text-dim);font-size:.85em;
}
@media(max-width:640px){
  header h1{font-size:1.8em}
  table{font-size:.82em}th,td{padding:6px 8px}
  pre{padding:12px;font-size:.8em}
}
</style>
</head>
<body>

<header>
  <h1>IconSearchAPI</h1>
  <p>图标搜索 API — 纯原生 PHP 实现，零依赖，支持 RESTful 搜索、分页、多来源过滤</p>
  <div class="links">
    <a href="webui/">🖥️ 进入 WebUI →</a>
    <a href="https://github.com/wmy2981/iconsearchAPI" target="_blank">📦 GitHub</a>
  </div>
</header>

<nav>
  <a href="#overview">概览</a>
  <a href="#auth">认证</a>
  <a href="#search">搜索 API</a>
  <a href="#status">状态 API</a>
  <a href="#response">响应格式</a>
  <a href="#errors">错误码</a>
  <a href="#config">配置</a>
  <a href="#cli">CLI 工具</a>
</nav>

<main>

<!-- ─── 概览 ─── -->
<section id="overview">
<h2>📋 概览</h2>
<p>IconSearchAPI 是一个轻量级图标搜索引擎，基于图标名称进行宽松匹配（大小写不敏感），聚合多个来源的图标数据。</p>

<h3>端点一览</h3>
<table>
  <tr><th>方法</th><th>路径</th><th>说明</th><th>鉴权</th></tr>
  <tr>
    <td><span class="tag getpost">GET</span><span class="tag getpost">POST</span></td>
    <td><code>/search.php</code></td>
    <td>搜索图标</td>
    <td>视配置而定</td>
  </tr>
  <tr>
    <td><span class="tag get">GET</span></td>
    <td><code>/status.php</code></td>
    <td>服务状态与来源统计</td>
    <td>视配置而定</td>
  </tr>
</table>

<h3>技术栈</h3>
<p>PHP 8.3+ 原生构建，无框架、无 Composer 依赖、无数据库。数据存储在 JSON 文件中，配合序列化缓存提速。</p>

<h3>运行方式</h3>
<pre><code>php -S 127.0.0.1:8080</code></pre>
<p>所有端点支持 CORS（<code>Access-Control-Allow-Origin: *</code>），可直接被 WebUI 或跨域前端调用。</p>
</section>

<!-- ─── 认证 ─── -->
<section id="auth">
<h2>🔐 认证</h2>
<p>配置项 <code>skipAuth</code> 控制是否需要鉴权：</p>
<ul>
  <li><strong>skipAuth: true</strong> — 跳过认证，所有人可访问</li>
  <li><strong>skipAuth: false</strong> — 需要提供有效 Token</li>
</ul>

<h3>认证方式（优先级从高到低）</h3>
<table>
  <tr><th>方式</th><th>示例</th></tr>
  <tr><td><strong>Authorization Header</strong></td><td><code>Authorization: Bearer &lt;token&gt;</code></td></tr>
  <tr><td><strong>Query 参数</strong></td><td><code>?token=&lt;token&gt;</code>（仅 GET）</td></tr>
</table>

<h3>生成 Token 哈希</h3>
<pre><code>echo -n "your-secret-token" | sha256sum</code></pre>
<p>将输出的 SHA256 哈希写入 <code>config.json</code> 的 <code>auth</code> 字段。</p>
</section>

<!-- ─── 搜索 API ─── -->
<section id="search">
<h2>🔍 搜索 API</h2>
<p><code>/search.php</code> — 搜索图标，支持 GET 和 POST 两种方式。</p>

<h3>GET /search.php</h3>
<table>
  <tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr>
  <tr><td><code>query</code></td><td>string</td><td class="required">✅</td><td>搜索关键词，大小写不敏感</td></tr>
  <tr><td><code>num</code></td><td>int</td><td class="optional">❌</td><td>总结果数量限制（1–1000）</td></tr>
  <tr><td><code>page</code></td><td>int</td><td class="optional">❌</td><td>页码（需与 pageSize 配合）</td></tr>
  <tr><td><code>pageSize</code></td><td>int</td><td class="optional">❌</td><td>每页数量（默认 18，最大 100）</td></tr>
</table>

<div class="note">
  <p>不传 <code>page</code>/<code>pageSize</code> 时返回全部匹配结果。<br>
  GET 方式下 <code>type</code> 和 <code>sources</code> 参数<strong>静默忽略</strong>（不报错）。</p>
</div>

<h4>示例请求</h4>
<pre><code>GET /search.php?query=chrome&num=5&token=your-token-here</code></pre>

<pre><code>GET /search.php?query=drive&page=2&pageSize=20</code></pre>

<h3>POST /search.php</h3>
<p>支持 <code>application/json</code> 和 <code>application/x-www-form-urlencoded</code>。</p>

<table>
  <tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr>
  <tr><td><code>query</code></td><td>string</td><td class="required">✅</td><td>搜索关键词</td></tr>
  <tr><td><code>num</code></td><td>int</td><td class="optional">❌</td><td>总结果数量限制（1–1000）</td></tr>
  <tr><td><code>page</code></td><td>int</td><td class="optional">❌</td><td>页码</td></tr>
  <tr><td><code>pageSize</code></td><td>int</td><td class="optional">❌</td><td>每页数量（默认 18，最大 100）</td></tr>
  <tr><td><code>type</code></td><td>array / string</td><td class="optional">❌</td><td>文件扩展名过滤</td></tr>
  <tr><td><code>sources</code></td><td>array / string</td><td class="optional">❌</td><td>来源过滤</td></tr>
</table>

<h4>参数说明</h4>
<ul>
  <li><code>type</code> — 可以是数组 <code>[".png", ".svg"]</code> 或逗号分隔字符串 <code>".png,.svg"</code>，传 <code>"*"</code> 不过滤</li>
  <li><code>sources</code> — 可以是数组 <code>["homarr", "MacIcons"]</code> 或逗号分隔字符串 <code>"homarr,MacIcons"</code>，传 <code>"*"</code> 不过滤</li>
</ul>

<h4>示例请求</h4>
<pre><code>POST /search.php
Content-Type: application/json
Authorization: Bearer your-token-here

{
  "query": "chrome",
  "num": 100,
  "page": 2,
  "pageSize": 20,
  "type": [".png"],
  "sources": ["HDiconsV2", "homarr"]
}</code></pre>

<pre><code>POST /search.php
Content-Type: application/x-www-form-urlencoded

query=proton&num=3&sources=MacIcons</code></pre>
</section>

<!-- ─── 状态 API ─── -->
<section id="status">
<h2>📊 状态 API</h2>
<p><code>GET /status.php</code> — 返回服务状态信息。根据 <code>skipAuth</code> 决定是否需要鉴权。</p>

<h4>示例请求</h4>
<pre><code>GET /status.php</code></pre>

<h4>响应示例</h4>
<pre><code>{
  "code": 200,
  "message": "success",
  "data": {
    "serverName": "IconSearchAPI",
    "skipAuth": true,
    "logLevel": "INFO",
    "default": {
      "num": null,
      "type": "*",
      "sources": "*"
    },
    "linkBoost": 1,
    "sources": [
      { "name": "HDiconsV2", "iconCount": 1754 },
      { "name": "MacIcons", "iconCount": 26037 },
      { "name": "homarr", "iconCount": 2798 },
      { "name": "material-icon", "iconCount": 1052 }
    ],
    "totalSources": 4,
    "totalIcons": 31641
  }
}</code></pre>

<table>
  <tr><th>字段</th><th>类型</th><th>说明</th></tr>
  <tr><td><code>serverName</code></td><td>string</td><td>服务名称</td></tr>
  <tr><td><code>skipAuth</code></td><td>bool</td><td>是否跳过鉴权</td></tr>
  <tr><td><code>logLevel</code></td><td>string</td><td>当前日志级别</td></tr>
  <tr><td><code>default</code></td><td>object</td><td>默认搜索参数</td></tr>
  <tr><td><code>linkBoost</code></td><td>int</td><td>加速链接规则数量</td></tr>
  <tr><td><code>sources</code></td><td>array</td><td>来源列表（名称 + 图标数）</td></tr>
  <tr><td><code>totalSources</code></td><td>int</td><td>来源总数</td></tr>
  <tr><td><code>totalIcons</code></td><td>int</td><td>图标总数</td></tr>
</table>
</section>

<!-- ─── 响应格式 ─── -->
<section id="response">
<h2>📦 响应格式</h2>

<p>所有端点返回统一的 JSON 结构：</p>

<h3>基本结构</h3>
<pre><code>{
  "code": 200,
  "message": "success",
  "data": { ... }
}</code></pre>

<h3>搜索响应（无分页）</h3>
<pre><code>{
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
}</code></pre>

<h3>搜索响应（分页模式）</h3>
<p>传入 <code>page</code>/<code>pageSize</code> 时额外包含分页字段：</p>
<pre><code>{
  "code": 200,
  "message": "success",
  "data": {
    "serverName": "IconSearchAPI",
    "query": "chrome",
    "total": 150,
    "page": 2,
    "pageSize": 20,
    "totalPages": 8,
    "results": [ ... ]
  }
}</code></pre>

<table>
  <tr><th>字段</th><th>类型</th><th>说明</th></tr>
  <tr><td><code>serverName</code></td><td>string</td><td>服务名称（来自配置）</td></tr>
  <tr><td><code>query</code></td><td>string</td><td>搜索关键词</td></tr>
  <tr><td><code>total</code></td><td>int</td><td>匹配总数</td></tr>
  <tr><td><code>page</code></td><td>int</td><td>当前页码（分页模式）</td></tr>
  <tr><td><code>pageSize</code></td><td>int</td><td>每页数量（分页模式）</td></tr>
  <tr><td><code>totalPages</code></td><td>int</td><td>总页数（分页模式）</td></tr>
  <tr><td><code>results</code></td><td>array</td><td>图标结果列表</td></tr>
</table>

<h3>结果条目</h3>
<table>
  <tr><th>字段</th><th>类型</th><th>说明</th></tr>
  <tr><td><code>name</code></td><td>string</td><td>图标名称</td></tr>
  <tr><td><code>url</code></td><td>string</td><td>图标 URL（已应用加速链接转换，相对路径自动拼装）</td></tr>
  <tr><td><code>source</code></td><td>string</td><td>来源名称</td></tr>
</table>
</section>

<!-- ─── 错误码 ─── -->
<section id="errors">
<h2>⚠️ 错误码</h2>
<table>
  <tr><th>状态码</th><th>含义</th><th>说明</th></tr>
  <tr><td><span class="badge b200">200</span></td><td>成功</td><td>请求正常处理</td></tr>
  <tr><td><span class="badge b400">400</span></td><td>参数错误</td><td>缺 <code>query</code>、<code>num</code> 非整数或超范围（1–1000）</td></tr>
  <tr><td><span class="badge b401">401</span></td><td>认证失败</td><td>Token 缺失或无效</td></tr>
  <tr><td><span class="badge b405">405</span></td><td>方法不支持</td><td>仅 GET/POST（search.php）或 GET（status.php）</td></tr>
  <tr><td><span class="badge b500">500</span></td><td>服务器配置错误</td><td><code>config.json</code> 缺失、JSON 解析失败或缺少必要字段</td></tr>
</table>

<h3>错误响应示例</h3>
<pre><code>{
  "code": 400,
  "message": "Bad request: missing required parameter 'query'"
}</code></pre>

<pre><code>{
  "code": 401,
  "message": "Unauthorized: invalid or missing token"
}</code></pre>
</section>

<!-- ─── 配置 ─── -->
<section id="config">
<h2>⚙️ 配置</h2>

<p>配置文件 <code>config.json</code>，<strong>不提交到 Git</strong>（敏感信息）。<code>config.json.example</code> 提供模板参考。</p>

<table>
  <tr><th>字段</th><th>类型</th><th>默认值</th><th>说明</th></tr>
  <tr><td><code>severName</code></td><td>string</td><td>—（必填）</td><td>服务名称，显示在响应中</td></tr>
  <tr><td><code>auth</code></td><td>string</td><td>—（条件必填）</td><td>Token 的 SHA256 哈希（<code>skipAuth</code> 为 true 时可选）</td></tr>
  <tr><td><code>skipAuth</code></td><td>bool</td><td><code>false</code></td><td>跳过鉴权，允许任意请求</td></tr>
  <tr><td><code>logLevel</code></td><td>string</td><td><code>"DEBUG"</code></td><td>日志级别：DEBUG / INFO / WARN / ERROR</td></tr>
  <tr><td><code>linkBoost</code></td><td>array</td><td><code>[]</code></td><td>加速链接规则数组</td></tr>
  <tr><td><code>default.num</code></td><td>int / null</td><td><code>null</code></td><td>默认返回数量，null 不限制</td></tr>
  <tr><td><code>default.type</code></td><td>array / "*"</td><td><code>"*"</code></td><td>默认文件类型过滤</td></tr>
  <tr><td><code>default.sources</code></td><td>array / "*"</td><td><code>"*"</code></td><td>默认来源过滤</td></tr>
</table>

<h3>加速链接规则</h3>
<p><code>fast</code> 中的 <code>&lt;origin&gt;</code> 占位符会被替换为匹配的原始链接域名：</p>
<pre><code>{
  "fast": "https://ghfast.top/&lt;origin&gt;",
  "originDomain": "https://raw.githubusercontent.com"
}</code></pre>

<p>原始 URL <code>https://raw.githubusercontent.com/user/repo/icon.png</code> → <code>https://ghfast.top/https://raw.githubusercontent.com/user/repo/icon.png</code></p>

<h3>相对路径处理</h3>
<p>JSON 源文件中的 URL 如果以 <code>/</code> 开头（不以 <code>http://</code> 或 <code>https://</code> 开头），会自动拼接为请求服务器地址。</p>
<p>例如请求服务器为 <code>http://example.com:8080</code>，JSON 中路径为 <code>/icons/chrome.png</code>，则返回 <code>http://example.com:8080/icons/chrome.png</code>。</p>

<h3>完整配置示例</h3>
<pre><code>{
  "severName": "IconSearchAPI",
  "auth": "&lt;SHA256 hash&gt;",
  "skipAuth": false,
  "logLevel": "DEBUG",
  "linkBoost": [
    {
      "fast": "https://ghfast.top/&lt;origin&gt;",
      "originDomain": "https://raw.githubusercontent.com"
    }
  ],
  "default": {
    "num": null,
    "type": "*",
    "sources": "*"
  }
}</code></pre>
</section>

<!-- ─── CLI 工具 ─── -->
<section id="cli">
<h2>💻 CLI 客户端</h2>
<p>Python 命令行工具，支持表格和 JSON 输出，面向人类和 Agent。</p>

<h3>安装</h3>
<pre><code>cd cli
pip install -r requirements.txt</code></pre>

<h3>环境变量</h3>
<table>
  <tr><th>变量</th><th>说明</th><th>必填</th></tr>
  <tr><td><code>ICONSEARCH_API_URL</code></td><td>API 地址（如 <code>http://127.0.0.1:8080</code>）</td><td class="required">✅</td></tr>
  <tr><td><code>ICONSEARCH_TOKEN</code></td><td>认证令牌</td><td class="required">✅</td></tr>
</table>
<p>可通过 <code>--api-url</code> / <code>--token</code> 参数覆盖环境变量。</p>

<h3>命令</h3>
<pre><code># 搜索图标
iconsearch search &lt;query&gt; [options]

# 查看 API 状态
iconsearch status</code></pre>

<h4>搜索参数</h4>
<table>
  <tr><th>参数</th><th>短写</th><th>说明</th></tr>
  <tr><td><code>--num</code></td><td><code>-n</code></td><td>返回数量</td></tr>
  <tr><td><code>--type</code></td><td><code>-t</code></td><td>文件类型过滤，逗号分隔</td></tr>
  <tr><td><code>--sources</code></td><td><code>-s</code></td><td>来源过滤，逗号分隔</td></tr>
  <tr><td><code>--page</code></td><td><code>-p</code></td><td>页码（需与 --page-size 配合）</td></tr>
  <tr><td><code>--page-size</code></td><td></td><td>每页数量（默认 18，最大 100）</td></tr>
  <tr><td><code>--json</code></td><td></td><td>JSON 输出（默认表格，适合 Agent 解析）</td></tr>
</table>

<h4>示例</h4>
<pre><code># 基本搜索
iconsearch search chrome

# JSON 输出
iconsearch search chrome --json

# 查看状态
iconsearch status</code></pre>

<h3>索引构建工具</h3>
<p>扫描本地图标目录，生成 JSON 索引文件。<code>build_index.py</code> 位于 <code>cli/</code> 目录。</p>
<pre><code>python cli/build_index.py /path/to/icons -d https://example.com/icons -e .png,.svg -o my_icons.json</code></pre>
</section>

</main>

<footer>
  <p>IconSearchAPI — MIT License</p>
  <p style="margin-top:4px;font-size:.85em;color:var(--text-dim)">Made with PHP 8.3+ · Pure native, no frameworks</p>
</footer>

</body>
</html>
