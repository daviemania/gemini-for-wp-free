# VS Code Dev Container Setup

This document outlines the configuration for the VS Code Dev Container in this project, with a focus on secure credential storage for SSH and GitHub.

## Objective

The goal of this configuration is to create a seamless and secure development experience within the VS Code Dev Container by:

- Eliminating the need to repeatedly enter SSH passphrases.
- Resolving keyring storage errors.
- Securely storing GitHub credentials.

## Configuration

### `devcontainer.json`

The following is the `devcontainer.json` configuration that enables these features:

```json
{
  "name": "Gemini Project",
  "dockerComposeFile": "../docker-compose.yml",
  "service": "app",
  "workspaceFolder": "/workspace",
  "forwardPorts": [3000],
  "remoteUser": "root",
  "shutdownAction": "stopContainer",
  "mounts": [
    "source=${localEnv:SSH_AUTH_SOCK},target=/tmp/ssh-agent.sock,type=bind"
  ],
  "postCreateCommand": "bash -c \"source /root/.bashrc && apt-get update && apt-get install -y gnupg software-properties-common curl && curl -fsSL https://apt.releases.hashicorp.com/gpg | apt-key add - && apt-add-repository 'deb [arch=amd64] https://apt.releases.hashicorp.com $(lsb_release -cs) main' && apt-get update && apt-get install -y terraform && apt-get install -y gnome-keyring libsecret-1-0 libsecret-1-dev && git config --global credential.helper /usr/share/doc/git/contrib/credential/libsecret/git-credential-libsecret\"",
  "customizations": {
    "vscode": {
      "extensions": [
        "ms-vscode.php-debug",
        "bmewburn.vscode-intelephense-client",
        "esbenp.prettier-vscode",
        "dbaeumer.vscode-eslint",
        "vscode-icons-team.vscode-icons",
        "ms-azuretools.vscode-docker",
        "redhat.vscode-yaml",
        "johnbillion.wordpress-vscode"
      ],
      "settings": {
        "debug.node.autoAttach": "disabled"
      }
    }
  }
}
```

### Key Configurations

- **`mounts`**: This property is used to mount the SSH agent socket from the host machine into the container. This allows the container to use the host's SSH agent for authentication, eliminating the need to enter SSH passphrases within the container.

- **`postCreateCommand`**: This command is executed after the container is created. It performs the following actions:
  - Installs `gnome-keyring` and `libsecret`, which are used to securely store credentials.
  - Configures Git to use `libsecret` as its credential helper.

## Setup Instructions

### 1. Rebuild the Dev Container

For the changes in `devcontainer.json` to take effect, you need to rebuild the Dev Container. This can be done from the VS Code Command Palette with the command `Dev Containers: Rebuild Container`.

### 2. Configure SSH Agent on Host

On your local machine, you need to ensure that your SSH agent is running and your SSH key is added to it.

```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa
```

### 3. Configure GitHub Credentials

The first time you perform a Git operation that requires authentication from within the container, you will be prompted for your GitHub username and password. For the password, use a GitHub Personal Access Token (PAT).

The credential helper will securely store your PAT in the keyring, so you won't have to enter it again.

```
```
