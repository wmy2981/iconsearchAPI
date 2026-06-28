<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IconSearchAPI</title>
</head>

<body>
    <h1>IconSearchAPI</h1>
    <p>图标搜索 API 服务</p>
    <p><a href="/webui/">进入 WebUI →</a></p>

    <h2>API 端点</h2>
    <p><code>GET /search.php?query=搜索词&token=令牌</code></p>

    <h2>认证方式</h2>
    <ol>
        <li><strong>Authorization header</strong>: <code>Authorization: Bearer &lt;token&gt;</code></li>
        <li><strong>Query 参数</strong>: <code>?token=&lt;token&gt;</code></li>
    </ol>

    <h2>参数说明</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>参数</th>
                <th>类型</th>
                <th>必填</th>
                <th>说明</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>query</code></td>
                <td>string</td>
                <td>是</td>
                <td>搜索关键词（大小写不敏感）</td>
            </tr>
            <tr>
                <td><code>num</code></td>
                <td>int</td>
                <td>否</td>
                <td>返回数量（覆盖默认值）</td>
            </tr>
            <tr>
                <td><code>type</code></td>
                <td>string/array</td>
                <td>否</td>
                <td>文件类型过滤（仅 POST）</td>
            </tr>
            <tr>
                <td><code>sources</code></td>
                <td>string/array</td>
                <td>否</td>
                <td>来源过滤（仅 POST）</td>
            </tr>
        </tbody>
    </table>

    <h2>示例请求</h2>
    <h3>GET 请求</h3>
    <pre>GET /search.php?query=chrome&num=5&token=your-token-here</pre>

    <h3>POST 请求 (JSON)</h3>
    <pre>POST /search.php
Content-Type: application/json

{
  "query": "chrome",
  "num": 10,
  "type": [".png"],
  "sources": ["HDiconsV2", "homarr"]
}</pre>

    <h2>响应格式</h2>
    <pre>{
  "code": 200,
  "message": "success",
  "data": {
    "serverName": "IconSearchAPI",
    "query": "chrome",
    "total": 10,
    "results": [
      {
        "name": "chrome",
        "url": "https://ghfast.top/https://raw.githubusercontent.com/...",
        "source": "homarr"
      }
    ]
  }
}</pre>

    <h2>错误码</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>code</th>
                <th>含义</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>200</td>
                <td>成功</td>
            </tr>
            <tr>
                <td>400</td>
                <td>参数错误（缺 query、num 超范围）</td>
            </tr>
            <tr>
                <td>401</td>
                <td>认证失败</td>
            </tr>
            <tr>
                <td>405</td>
                <td>请求方法不支持</td>
            </tr>
            <tr>
                <td>500</td>
                <td>服务器配置错误</td>
            </tr>
        </tbody>
    </table>
</body>

</html>