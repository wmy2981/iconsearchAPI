#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Icon Index Builder - Scan icon directory and build JSON index for IconSearchAPI

Usage:
    python build_index.py <workdir> --domain <domain> [--ext .png,.svg] [--output index.json]
"""

import argparse
import json
import io
import sys
from collections import defaultdict
from pathlib import Path

# Fix Windows console encoding for UTF-8
if sys.platform == "win32":
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding="utf-8", errors="replace")

__version__ = "1.0.0"

# 常见图片扩展名
DEFAULT_EXTENSIONS = {".png", ".jpg", ".jpeg", ".svg", ".gif", ".ico", ".webp", ".bmp"}


def build_url(domain: str, relative_path: str) -> str:
    """
    构建完整 URL：域名 + 相对路径

    处理两种域名格式：
    1. HTTP/HTTPS URL：https://example.com/icons
    2. 本地路径：/icons、C:/icons、./icons

    注意：在 Git Bash 中，/icons 会被转换为 C:/Program Files/Git/icons
    建议使用 //icons 或 ./icons 避免此问题
    """
    # 检测是否为 HTTP/HTTPS URL
    is_url = domain.startswith(("http://", "https://"))

    if is_url:
        # URL：处理末尾斜杠
        domain = domain.rstrip("/") + "/"
    else:
        # 本地路径：确保有分隔符
        if not domain.endswith(("/", "\\")):
            domain += "/"

    relative_path = relative_path.lstrip("/").lstrip("\\")
    return domain + relative_path


def scan_icons(workdir: str, extensions: set = None) -> list:
    """
    递归扫描工作目录下的图标文件

    返回 [(relative_path, filename_stem), ...]
    """
    workdir = Path(workdir).resolve()
    if not workdir.is_dir():
        print(f"Error: '{workdir}' is not a directory", file=sys.stderr)
        sys.exit(1)

    icons = []
    for file_path in workdir.rglob("*"):
        if not file_path.is_file():
            continue

        # 过滤扩展名
        if extensions:
            if file_path.suffix.lower() not in extensions:
                continue
        else:
            # 无指定扩展名时，只处理常见图片格式
            if file_path.suffix.lower() not in DEFAULT_EXTENSIONS:
                continue

        # 获取相对路径和文件名（去扩展名）
        relative_path = file_path.relative_to(workdir).as_posix()
        name = file_path.stem
        icons.append((relative_path, name))

    return icons


def build_index(icons: list, domain: str) -> dict:
    """
    构建索引 JSON

    处理同名冲突：第一次正常记录，后续冲突时改为相对路径（/ → _）
    """
    # 统计同名出现次数
    name_count = defaultdict(int)
    for _, name in icons:
        name_count[name] += 1

    result = []
    # 用于追踪已处理过的 name
    processed_names = set()

    for relative_path, name in icons:
        # 如果这个 name 出现多次，且不是第一次处理，使用相对路径
        if name_count[name] > 1:
            if name in processed_names:
                # 冲突：使用相对路径（/ → _）
                path_based_name = relative_path.rsplit(".", 1)[0].replace("/", "_")
                final_name = path_based_name
            else:
                # 第一次遇到同名，记录但暂时用文件名
                # 等遇到第二次时再修改之前的记录
                final_name = name
                # 回溯修改之前同名的记录
                for item in result:
                    if item["name"] == name:
                        # 修改之前记录的 name 为相对路径格式
                        old_relative = item["url"].split(domain.rstrip("/"))[-1].lstrip("/")
                        item["name"] = old_relative.rsplit(".", 1)[0].replace("/", "_")
                        break
        else:
            final_name = name

        processed_names.add(name)
        url = build_url(domain, relative_path)
        result.append({"name": final_name, "url": url})

    return {"icons": result}


def main():
    parser = argparse.ArgumentParser(
        prog="build_index",
        description="扫描图标目录，生成 IconSearchAPI 格式的 JSON 索引",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
示例:
  python build_index.py /path/to/icons -d https://example.com/icons
  python build_index.py /path/to/icons -d https://example.com/icons -e .png,.svg
  python build_index.py /path/to/icons -d /icons -o my_index.json
        """,
    )
    parser.add_argument("workdir", help="要扫描的工作目录")
    parser.add_argument(
        "-d", "--domain", required=True, help="图标服务器域名，如 https://example.com/icons"
    )
    parser.add_argument(
        "-e", "--ext", help="文件后缀过滤，逗号分隔（如 .png,.svg）；不指定则使用常见图片格式"
    )
    parser.add_argument(
        "-o", "--output", default="output.json", help="输出文件路径（默认 output.json）"
    )
    parser.add_argument("--version", action="version", version=f"%(prog)s {__version__}")

    args = parser.parse_args()

    # 解析扩展名
    extensions = None
    if args.ext:
        extensions = {ext.strip().lower() if ext.strip().startswith(".") else f".{ext.strip().lower()}" for ext in args.ext.split(",")}

    # 扫描图标
    print(f"Scanning: {args.workdir}", file=sys.stderr)
    icons = scan_icons(args.workdir, extensions)
    print(f"Found {len(icons)} icon(s)", file=sys.stderr)

    if not icons:
        print("Warning: No icons found", file=sys.stderr)
        sys.exit(0)

    # 构建索引
    index = build_index(icons, args.domain)

    # 输出
    output_path = Path(args.output)
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(index, f, ensure_ascii=False, indent=2)

    print(f"Output: {output_path.resolve()}", file=sys.stderr)
    print(f"Total: {len(index['icons'])} icon(s)", file=sys.stderr)


if __name__ == "__main__":
    main()
