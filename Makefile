.PHONY: build run shell start stop clean rebuild help

# Default target
help:
	@echo "Available commands:"
	@echo "  make build    - Build the Docker image"
	@echo "  make start    - Start persistent container (for IDEs)"
	@echo "  make shell    - Open a shell in the container"
	@echo "  make run      - Run temporary container interactively"
	@echo "  make stop     - Stop running containers"
	@echo "  make clean    - Remove the image and container"
	@echo "  make rebuild  - Clean and rebuild"

# Build the Docker image using buildx with host networking
build:
	@echo "Building gemini-project image..."
	docker buildx build --load -t gemini-project .devcontainer/
	@echo "✓ Build complete!"

# Start a persistent container in the background
start: build
	@if docker ps -a | grep -q gemini-project-dev; then \
		echo "Container already exists. Starting..."; \
		docker start gemini-project-dev 2>/dev/null || true; \
	else \
		echo "Creating new persistent container..."; \
		docker run -d -v $(PWD):/gemini-project --name gemini-project-dev gemini-project sleep infinity; \
	fi
	@echo "✓ Container is running"

# Run the container interactively (temporary)
run: build
	docker run -it --rm -v $(PWD):/gemini-project --name gemini-project-temp gemini-project

# Open a shell in the persistent container
shell: start
	@docker exec -it gemini-project-dev /bin/zsh || true

# Stop all running gemini-project containers
stop:
	@docker stop gemini-project-dev 2>/dev/null || true
	@echo "✓ Container stopped"

# Remove the Docker image and container
clean:
	@docker stop gemini-project-dev 2>/dev/null || true
	@docker rm gemini-project-dev 2>/dev/null || true
	@docker rmi gemini-project 2>/dev/null || true
	@echo "✓ Image and container removed"

# Clean and rebuild
rebuild: clean build
