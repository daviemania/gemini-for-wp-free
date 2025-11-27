# Docker Containerization Guide

## Overview
This repo supports Docker/VSCode Dev Containers for replicable dev/prod WP AI CLI setup. Tested on Bitnami WP; adapt paths.

## .devcontainer/devcontainer.json + Dockerfile
- **devcontainer.json:** VSCode extension launches container with Node/Python/WP deps.
- **Dockerfile:** Multi-stage: Node 20 + Python 3.12 + WP CLI + MCP relay.

**Usage:**
1. VSCode → Reopen in Container (builds).
2. `docker build -t gemini-wp-cli .`
3. `docker run -p 3000:3000 -p 3001:3001 -v .env:/app/.env gemini-wp-cli`

## docker-compose.yml (Root)
```
version: '3.8'
services:
  relay:
    build: .
    ports:
      - '3000:3000'
      - '3001:3001'
    env_file: .env
    volumes:
      - .:/app
  ollama:
    image: ollama/ollama
    ports:
      - '11434:11434'
  wp:
    image: bitnami/wordpress:latest
    env_file: .env
```

**Run:** `docker compose up -d` (WP + relay + Ollama).

**Makefile Targets:**
```
dev: docker-compose up
build: docker build -t gemini-wp-cli .
clean: docker compose down -v
```

Adapt WP path/symlink in mcp.js for non-Bitnami.
