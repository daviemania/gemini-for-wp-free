# Use Alpine Linux - smaller and more reliable mirrors
FROM alpine:latest
# [1] Set the correct UID and GID for the 'bitnami' user
ARG USER_UID=1000
ARG USER_GID=1000
# [2] Install common Dev Container utilities and features
RUN apk update \
    && apk add --no-cache \
    git \
    curl \
    ca-certificates \
    bash \
    zsh \
    tmux \
    sudo \
    shadow \
    npm \
    nodejs \
    nano \
    # Create user with matching host UID/GID
    && addgroup -g $USER_GID bitnami \
    && adduser -D -u $USER_UID -G bitnami -s /bin/zsh bitnami \
    && echo "bitnami ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/bitnami \
    && chmod 0440 /etc/sudoers.d/bitnami \
    # Set up a basic zsh configuration
    && echo 'export PS1="%F{green}%n@%m%f:%F{blue}%~%f$ "' > /home/bitnami/.zshrc \
    && echo 'export LANG=C.UTF-8' >> /home/bitnami/.zshrc \
    && echo 'export LC_ALL=C.UTF-8' >> /home/bitnami/.zshrc \
    && chown bitnami:bitnami /home/bitnami/.zshrc \
    # Clean up
    && rm -rf /var/cache/apk/*
# [3] Set the correct working directory and user for subsequent commands
WORKDIR /gemini-project
USER bitnami
