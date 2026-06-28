#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IconSearchAPI CLI - Search icons and get results

Usage:
    iconsearch search <query> [options]
    iconsearch status

Environment:
    ICONSEARCH_API_URL   API URL (e.g. http://127.0.0.1:8080)
    ICONSEARCH_TOKEN     Auth token
"""

import argparse
import json
import io
import os
import sys
from urllib.parse import urljoin

try:
    import requests
except ImportError:
    print("Error: 'requests' package required. Install: pip install requests", file=sys.stderr)
    sys.exit(1)

# Fix Windows console encoding for UTF-8
if sys.platform == "win32":
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding="utf-8", errors="replace")

__version__ = "1.0.0"


def get_config(args):
    """从参数或环境变量获取配置"""
    api_url = args.api_url or os.environ.get("ICONSEARCH_API_URL")
    token = args.token or os.environ.get("ICONSEARCH_TOKEN")

    if not api_url:
        print("Error: API URL required. Set ICONSEARCH_API_URL or use --api-url", file=sys.stderr)
        sys.exit(1)

    if not token:
        print("Error: Token required. Set ICONSEARCH_TOKEN or use --token", file=sys.stderr)
        sys.exit(1)

    # 去除末尾斜杠
    api_url = api_url.rstrip("/")
    return api_url, token


def make_request(api_url, token, endpoint, data):
    """发送 POST 请求到 API"""
    url = f"{api_url}/{endpoint.lstrip('/')}"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json",
    }

    try:
        resp = requests.post(url, json=data, headers=headers, timeout=30)
        resp.raise_for_status()
        return resp.json()
    except requests.exceptions.ConnectionError:
        print(f"Error: Cannot connect to {api_url}", file=sys.stderr)
        sys.exit(1)
    except requests.exceptions.Timeout:
        print("Error: Request timed out", file=sys.stderr)
        sys.exit(1)
    except requests.exceptions.HTTPError as e:
        try:
            err = resp.json()
            msg = err.get("message", str(e))
        except Exception:
            msg = str(e)
        print(f"Error [{resp.status_code}]: {msg}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)


def format_table(results, server_name=""):
    """将结果格式化为表格"""
    if not results.get("data", {}).get("results"):
        return "No results found."

    data = results["data"]
    items = data["results"]
    total = data["total"]
    query = data["query"]
    server = data.get("serverName", server_name)

    lines = []
    # Header with pagination info if present
    header = f"Server: {server} | Query: {query} | Total: {total}"
    if "page" in data and "totalPages" in data:
        header += f" | Page: {data['page']}/{data['totalPages']}"
    lines.append(header)
    lines.append("-" * 80)
    lines.append(f"{'#':<4} {'Name':<35} {'Source':<12} {'URL'}")
    lines.append("-" * 80)

    for i, item in enumerate(items, 1):
        name = item["name"]
        source = item["source"]
        url = item["url"]
        lines.append(f"{i:<4} {name:<35} {source:<12} {url}")

    if total > len(items):
        lines.append(f"... and {total - len(items)} more results")

    return "\n".join(lines)


def format_status(data):
    """将状态信息格式化为表格"""
    d = data.get("data", {})
    sources = d.get("sources", [])
    lines = []
    lines.append("Server Status")
    lines.append("-" * 50)
    lines.append(f"Server Name:   {d.get('serverName', 'N/A')}")
    lines.append(f"API Status:    OK ({data.get('code', '?')})")
    lines.append(f"Skip Auth:     {d.get('skipAuth', 'N/A')}")
    lines.append(f"Log Level:     {d.get('logLevel', 'N/A')}")
    lines.append(f"Total Icons:   {d.get('totalIcons', 0):,}")
    lines.append(f"Sources:       {d.get('totalSources', 0)}")
    lines.append("-" * 50)
    for s in sources:
        lines.append(f"  {s['name']:<20} {s.get('iconCount', 0):>6} icons")
    lines.append("-" * 50)
    return "\n".join(lines)


def cmd_search(args):
    """搜索图标"""
    api_url, token = get_config(args)

    data = {"query": args.query}

    if args.num is not None:
        data["num"] = args.num

    if args.type:
        data["type"] = args.type.split(",")

    if args.sources:
        data["sources"] = args.sources.split(",")

    # Pagination
    if args.page is not None or args.page_size is not None:
        if args.page is not None:
            data["page"] = args.page
        if args.page_size is not None:
            data["pageSize"] = args.page_size

    result = make_request(api_url, token, "search.php", data)

    if args.json:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print(format_table(result))


def cmd_status(args):
    """查看 API 状态"""
    api_url, token = get_config(args)

    url = f"{api_url}/status.php"
    headers = {"Authorization": f"Bearer {token}"}

    try:
        resp = requests.get(url, headers=headers, timeout=10)
        resp.raise_for_status()
        result = resp.json()
    except requests.exceptions.ConnectionError:
        print(f"Error: Cannot connect to {api_url}", file=sys.stderr)
        sys.exit(1)
    except requests.exceptions.Timeout:
        print("Error: Request timed out", file=sys.stderr)
        sys.exit(1)
    except requests.exceptions.HTTPError as e:
        try:
            err = resp.json()
            msg = err.get("message", str(e))
        except Exception:
            msg = str(e)
        print(f"Error [{resp.status_code}]: {msg}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)

    if args.json:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    else:
        print(format_status(result))


def main():
    parser = argparse.ArgumentParser(
        prog="iconsearch",
        description="IconSearchAPI CLI - 搜索图标并获取结果",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
环境变量:
  ICONSEARCH_API_URL   API 地址（如 http://127.0.0.1:8080）
  ICONSEARCH_TOKEN     认证令牌

全局选项（所有子命令共用）:
  --api-url URL        API 地址（覆盖环境变量）
  --token TOKEN        认证令牌（覆盖环境变量）

search 命令参数:
  query                搜索关键词（必填，宽松匹配，大小写不敏感）
  -n, --num NUM        返回结果总数（1-1000）
  -t, --type TYPES     文件类型过滤，逗号分隔（如 .png,.jpg,.svg）
  -s, --sources NAMES  来源过滤，逗号分隔（如 HDiconsV2,homarr）
  -p, --page PAGE      页码（需与 --page-size 一起使用）
  --page-size SIZE     每页数量（默认 18，最大 100）
  --json               输出 JSON 格式（默认表格）

status 命令参数:
  --json               输出 JSON 格式（默认表格）

示例:
  iconsearch search chrome
  iconsearch search drive --num 5
  iconsearch search proton --type .png --json
  iconsearch search home --sources homarr,HDiconsV2
  iconsearch search chrome --page 2 --page-size 20
  iconsearch search chrome --num 100 --type .png,.svg --sources homarr
  iconsearch status
  iconsearch status --json
        """
    )
    parser.add_argument("--version", action="version", version=f"%(prog)s {__version__}")
    parser.add_argument("--api-url", help="API 地址（覆盖环境变量）")
    parser.add_argument("--token", help="认证令牌（覆盖环境变量）")

    subparsers = parser.add_subparsers(dest="command", help="可用命令")

    # search 子命令
    search_parser = subparsers.add_parser(
        "search", help="搜索图标",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description="通过 POST 请求搜索图标，支持过滤和分页。",
        epilog="""
API 参数:
  query    搜索关键词，图标名包含该词即匹配（大小写不敏感）
  num      限制返回的总结果数（1-1000），不传则返回全部
  type     按文件扩展名过滤（数组），如 [".png", ".svg"]
  sources  按来源名称过滤（数组），如 ["HDiconsV2", "homarr"]
  page     页码，需与 pageSize 一起使用
  pageSize 每页数量，默认 18，最大 100

示例:
  iconsearch search chrome
  iconsearch search drive --num 5
  iconsearch search proton --type .png --json
  iconsearch search home --sources homarr,HDiconsV2
  iconsearch search chrome --page 2 --page-size 20
  iconsearch search chrome --num 100 --type .png,.svg --sources homarr
        """
    )
    search_parser.add_argument("query", help="搜索关键词（必填，宽松匹配，大小写不敏感）")
    search_parser.add_argument("-n", "--num", type=int, help="返回结果总数（1-1000）")
    search_parser.add_argument("-t", "--type", help="文件类型过滤，逗号分隔（如 .png,.jpg）")
    search_parser.add_argument("-s", "--sources", help="来源过滤，逗号分隔（如 HDiconsV2,homarr）")
    search_parser.add_argument("-p", "--page", type=int, help="页码（需与 --page-size 一起使用）")
    search_parser.add_argument("--page-size", type=int, help="每页数量（默认 18，最大 100）")
    search_parser.add_argument("--json", action="store_true", help="输出 JSON 格式（默认表格）")

    # status 子命令
    status_parser = subparsers.add_parser(
        "status", help="查看 API 状态",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description="请求 /status.php 获取服务状态信息。",
        epilog="""
返回信息:
  服务名称、鉴权状态、日志级别
  图标总数、各来源名称及图标数量
        """
    )
    status_parser.add_argument("--json", action="store_true", help="输出 JSON 格式（默认表格）")

    args = parser.parse_args()

    if args.command is None:
        parser.print_help()
        sys.exit(0)

    if args.command == "search":
        cmd_search(args)
    elif args.command == "status":
        cmd_status(args)


if __name__ == "__main__":
    main()
