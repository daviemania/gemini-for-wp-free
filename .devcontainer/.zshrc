# ============================================
# Zsh Configuration for SSH Server
# ============================================

# Oh My Zsh Setup
export ZSH="/home/bitnami/.oh-my-zsh"
ZSH_THEME="robbyrussell"
plugins=(git docker docker-compose)
export ZSH_DISABLE_COMPFIX=true
source $ZSH/oh-my-zsh.sh

# Path Configuration
export PATH="$HOME/.local/bin:$HOME/.cargo/bin:$HOME/.npm-global/bin:$PATH"

# Editor Settings (for SSH environment)
export EDITOR="vim"
export VISUAL="vim"
export TERM="xterm-256color"

# ============================================
# CLI Tool Aliases
# ============================================
alias ccr='claude-code-router'
alias gemini='generative-ai-cli'
alias claude='claude-code'

echo 'export PATH="$HOME/.npm-global/bin:$PATH"' >>~/.zshrc

# ============================================
# Git Aliases
# ============================================
alias git-push-to-dev="git add . && git commit -m 'Server updates' && git push origin main"
alias git-status="git status -sb"
alias git-log="git log --oneline --graph --decorate --all"

# ============================================
# Docker Aliases & Functions
# ============================================
alias docker-build='docker buildx build --load'
alias docker-prune='docker system prune -af --volumes'
alias docker-stop-all='docker stop $(docker ps -aq)'

# Gemini Project Shortcuts
alias gp-build='docker buildx build --load -t gemini-project .devcontainer/'
alias gp-run='docker run -it --rm -v $(pwd):/gemini-project --name gemini-project-dev gemini-project'
alias gp-shell='docker run -it --rm -v $(pwd):/gemini-project --name gemini-project-dev gemini-project /bin/zsh'

# Docker helper function
docker-exec() {
  if [ -z "$1" ]; then
    echo "Usage: docker-exec <container-name> [command]"
    return 1
  fi
  local cmd="${2:-/bin/bash}"
  docker exec -it "$1" "$cmd"
}

# ============================================
# MCP Toolbox Configuration
# ============================================
alias start-toolbox="source ~/.gemini/extensions/mcp-toolbox/.env && cd ~/.gemini/extensions/mcp-toolbox && ./toolbox --tools-file tools.yaml --stdio"
alias toolbox='/home/bitnami/.gemini/extensions/mcp-toolbox/toolbox'

# ============================================
# Project Environment Variables
# ============================================
# Load project-specific environment variables
if [ -f /home/bitnami/gemini-project/.env ]; then
  set -a # Automatically export all variables
  source /home/bitnami/gemini-project/.env
  set +a
fi

# ============================================
# Docker Environment Detection
# ============================================
if [ -f /.dockerenv ]; then
  export IN_DOCKER=true
  export PS1="🐳 $PS1"
else
  export IN_DOCKER=false
fi

# ============================================
# SSH-Specific Optimizations
# ============================================
# Reduce startup time
skip_global_compinit=1

# Better completion for Docker commands
if command -v docker &>/dev/null; then
  fpath=(~/.zsh/completion $fpath)
fi

# ============================================
# Utility Functions
# ============================================

# Quick directory navigation for gemini-project
gpcd() {
  cd /home/bitnami/gemini-project
}

# Show Docker container status for current project
docker-ps-project() {
  docker ps --filter "name=gemini-project" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
}

# Tail Docker logs for gemini-project
docker-logs-project() {
  local container="${1:-gemini-project-dev}"
  docker logs -f "$container"
}

# Clean Docker build cache
docker-clean-build() {
  docker builder prune -af
  echo "✓ Docker build cache cleaned"
}

# ============================================
# Performance Tweaks
# ============================================
# Disable auto-update checks (faster SSH sessions)
DISABLE_AUTO_UPDATE="true"
DISABLE_UPDATE_PROMPT="true"

# Faster git status in prompt
DISABLE_UNTRACKED_FILES_DIRTY="true"

# ============================================
# Welcome Message
# ============================================
if [[ -o interactive ]]; then
  echo "🚀 Zed SSH Environment Ready"
  echo "Working Directory: $(pwd)"
  if [ "$IN_DOCKER" = "true" ]; then
    echo "Environment: Docker Container"
  fi
fi
