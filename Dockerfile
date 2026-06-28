# IconSearchAPI — Docker 镜像
# 基于 PHP 8.3 CLI（Alpine），使用内置服务器

FROM php:8.3-cli-alpine

LABEL org.opencontainers.image.title="IconSearchAPI"
LABEL org.opencontainers.image.description="PHP icon search engine"
LABEL org.opencontainers.image.licenses="MIT"

WORKDIR /app

# 复制项目核心代码
COPY includes/ includes/
COPY webui/ webui/
COPY index.php search.php status.php config.json.example ./

# 创建运行时可写目录
RUN mkdir -p cache logs

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
