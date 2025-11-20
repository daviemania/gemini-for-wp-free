.PHONY: run shell start stop clean rebuild help

help:
	@echo "Gemini-Project Docker Commands (via docker-compose):"
	@echo "  make shell     🐚  - Connect to running container (zsh)"
	@echo "  make start     🚀  - Start detached"
	@echo "  make stop      ⏹️   - Stop (keep vols)"
	@echo "  make clean     🧹  - Nuke containers/volumes"
	@echo "  make rebuild   🔨  - Clean + rebuild"
	@echo "  make run       ⚡  - Temp interactive run"

start:
	@echo "🚀 Starting..."
	docker compose up -d
	@echo "✓ Ready: docker compose ps"

shell: start
	@echo "🐚 Shelling in (zsh/tmux/fish ready)..."
	docker compose exec app zsh

run:
	docker compose run --rm app

stop:
	@echo "⏹️ Stopping..."
	docker compose down
	@echo "✓ Stopped"

clean:
	@echo "🧹 Cleaning..."
	docker compose down -v --remove-orphans || true
	docker volume prune -f || true
	@echo "✓ Volumes pruned"

rebuild: clean
	@echo "🔨 Rebuilding..."
	docker compose up --build -d
	@echo "✓ Fresh build running"
