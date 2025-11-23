.PHONY: run shell tmux start stop logs clean rebuild build help

help:
	@echo "🚀 Gemini-Project Docker Commands"
	@echo ""
	@echo "Common:"
	@echo "  make build     🔨  - Build Docker image"
	@echo "  make start     ▶️   - Start container (detached)"
	@echo "  make shell     🐚  - Connect to container (zsh)"
	@echo "  make tmux      📺  - Attach to tmux ai-dev session"
	@echo "  make stop      ⏹️   - Stop container (keep volumes)"
	@echo "  make logs      📋  - View container logs"
	@echo ""
	@echo "Maintenance:"
	@echo "  make restart   🔄  - Restart container"
	@echo "  make rebuild   🔨  - Clean + rebuild from scratch"
	@echo "  make clean     🧹  - Remove containers + volumes"
	@echo "  make prune     🗑️   - Deep clean (images + build cache)"
	@echo "  make fix-permissions 🔧  - Fix file ownership issues"
	@echo ""
	@echo "Development:"
	@echo "  make run       ⚡  - Temporary interactive run"
	@echo "  make status    📊  - Show container status"
	@echo "  make health    🏥  - Check container health"

# Build the Docker image
build:
	@echo "🔨 Building Docker image..."
	docker buildx build --load -t gemini-project .devcontainer/
	@echo "✓ Image built successfully"

# Start container (build if needed)
start: build
	@echo "🚀 Starting gemini-project-dev..."
	docker compose up -d
	@sleep 2
	@echo "🔧 Fixing permissions..."
	@docker compose exec -u root gemini-dev sh -c "chown -R bitnami:bitnami /gemini-project/node_modules 2>/dev/null || true"
	@echo "✓ Container ready"
	@docker compose ps

# Connect to running container with zsh
shell:
	@echo "🐚 Connecting to container (zsh)..."
	@docker compose exec gemini-dev zsh || \
		(echo "⚠️  Container not running. Starting..." && make start && docker compose exec gemini-dev zsh)

# Attach to tmux session for long Claude sessions
tmux:
	@echo "📺 Attaching to tmux ai-dev session..."
	@docker compose exec gemini-dev tmux attach -t ai-dev || \
		docker compose exec gemini-dev tmux new-session -s ai-dev

# Temporary interactive run (removed on exit)
run:
	@echo "⚡ Running temporary container..."
	docker compose run --rm gemini-dev zsh

# Stop container
stop:
	@echo "⏹️  Stopping gemini-project-dev..."
	docker compose down
	@echo "✓ Container stopped"

# Restart container
restart: stop start

# View logs
logs:
	@echo "📋 Tailing logs (Ctrl+C to exit)..."
	docker compose logs -f gemini-dev

# Show container status
status:
	@echo "📊 Container Status:"
	@docker compose ps
	@echo ""
	@echo "💾 Volumes:"
	@docker volume ls | grep gemini-project || echo "No volumes found"

# Check container health
health:
	@echo "🏥 Health Check:"
	@docker compose ps gemini-dev --format json | grep -q '"Health":"healthy"' && \
		echo "✅ Container is healthy" || \
		echo "⚠️  Container health check failed"
	@docker inspect gemini-project-dev --format='{{.State.Health.Status}}' 2>/dev/null || \
		echo "Container not running"

# Fix file permissions in workspace
fix-permissions:
	@echo "🔧 Fixing file permissions..."
	@docker compose exec -u root gemini-dev sh -c "find /gemini-project -type f ! -path '/gemini-project/.env' -exec chown bitnami:bitnami {} + 2>/dev/null || true"
	@docker compose exec -u root gemini-dev sh -c "find /gemini-project -type d ! -path '/gemini-project/.env' -exec chown bitnami:bitnami {} + 2>/dev/null || true"
	@echo "✓ Permissions fixed"

# Clean containers and volumes
clean:
	@echo "🧹 Cleaning containers and volumes..."
	docker compose down -v --remove-orphans
	@echo "✓ Cleanup complete"

# Deep clean (remove images and build cache too)
prune: clean
	@echo "🗑️  Deep cleaning (images + build cache)..."
	docker rmi gemini-project 2>/dev/null || true
	docker builder prune -f
	@echo "✓ Deep clean complete"

# Rebuild from scratch
rebuild: clean build start
	@echo "✓ Rebuild complete - container running"

# Quick test that everything works
test: build
	@echo "🧪 Testing container..."
	docker run --rm gemini-project zsh -c "echo '✅ Container test passed' && tmux -V && git --version && node --version"
	@echo "✓ All tests passed"
