# Use Alpine Linux - smaller and more reliable
FROM alpine:latest

# Arguments for user matching
ARG USER_UID=1000
ARG USER_GID=1000

# Environment variables
ENV TERM=xterm-256color \
    COLORTERM=truecolor \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    SHELL=/bin/zsh

# Install all dependencies in one layer
RUN apk update && apk add --no-cache \
    # Core utilities
    git \
    git-lfs \
    curl \
    wget \
    ca-certificates \
    openssl \
    # Shells
    bash \
    zsh \
    fish \
    # Terminal tools
    tmux \
    vim \
    nano \
    # System utilities
    sudo \
    shadow \
    # Node.js ecosystem (v20+)
    nodejs \
    npm \
    # Additional tools
    openssh-client \
    tar \
    gzip \
    make \
    # For building native modules
    python3 \
    g++ \
    # Clean up in same layer
    && rm -rf /var/cache/apk/*

# Create user with matching host UID/GID
RUN addgroup -g $USER_GID bitnami \
    && adduser -D -u $USER_UID -G bitnami -s /bin/zsh bitnami \
    && echo "bitnami ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/bitnami \
    && chmod 0440 /etc/sudoers.d/bitnami

# Install Oh My Zsh for bitnami user
RUN su - bitnami -c 'sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)" "" --unattended'

# Install NVM (Node Version Manager) for bitnami user
RUN su - bitnami -c 'curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash'

# Configure NVM in .zshrc
RUN echo 'export NVM_DIR="$HOME/.nvm"' >> /home/bitnami/.zshrc \
    && echo '[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm' >> /home/bitnami/.zshrc \
    && echo '[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion' >> /home/bitnami/.zshrc

# Update PATH for bitnami user
RUN echo 'export PATH="$HOME/.npm-global/bin:$PATH"' >> /home/bitnami/.zshrc

# Add CCR alias to .zshrc
RUN echo 'alias ccr="node $HOME/.npm-global/lib/node_modules/@musistudio/claude-code-router/dist/cli.js"' >> /home/bitnami/.zshrc

# Initialize Git LFS for bitnami user
RUN su - bitnami -c 'git lfs install'

# Install CLI tools as bitnami user
RUN su - bitnami -c ' \
    # Configure npm for user installation
    npm config set prefix "/home/bitnami/.npm-global" && \
    # Install Gemini CLI
    npm install -g @google/gemini-cli && \
    # Install Claude Code Router
    npm install -g @musistudio/claude-code-router && \
    # Install Express
    npm install -g express && \
    # Install Cors
    npm install -g cors && \
    # Install dotenv
    npm install -g dotenv && \
    # Install other Dependencies
    npm install -g simple-git && \
    npm install -g fs-extra && \
    npm install -g node-cron && \
    npm install -g archiver && \
    # Install Claude Code
    npm install -g @anthropic-ai/claude-code && \
'

# Create necessary directories
RUN mkdir -p /home/bitnami/.config/fish \
    && mkdir -p /home/bitnami/.local/bin \
    && mkdir -p /home/bitnami/.cargo/bin \
    && mkdir -p /home/bitnami/.npm-global \
    && mkdir -p /home/bitnami/.gemini \
    && mkdir -p /home/bitnami/.claude \
    && chown -R bitnami:bitnami /home/bitnami

# Set working directory
WORKDIR /gemini-project

# Switch to bitnami user
USER bitnami

# Default command
CMD ["/bin/zsh"]
