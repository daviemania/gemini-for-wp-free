# Gemini for WordPress

@daviemania – WP blogger, musician, YouTuber, upcoming musician. Created this tool for my own multisite WordPress workflow at Mania Africa, but saw its utility for all WP users.

- Site: [maniainc.com](https://maniainc.com)
- [linktr.ee/davidmania](https://linktr.ee/davidmania)
- Mania Africa: [About](https://maniainc.com/about-us) | [Contact](https://maniainc.com/contact-us)

Official repo email: mail@davidmania.com (PGP hardened: /.well-known/security.txt).

[Privacy Policy](./privacy-policy.md) | [Terms](./terms-and-conditions.md)


A CLI-based environment for interacting with and managing a WordPress site using AI-powered tools and a custom Media Control Protocol (MCP).

## Overview

This project provides a powerful command-line interface to manage a complex, multisite WordPress installation. It leverages a custom set of 37 Media Control Protocol (MCP) functions to interact with various aspects of WordPress, from content and user management to site options and media.

The environment is designed for developers and administrators to perform complex tasks, automate workflows, and assist with content creation and site maintenance through AI-powered features.

## Key Features

- **Comprehensive WordPress Management:** Interact with posts, pages, users, comments, media, taxonomies, and site options directly from the CLI.
- **AI-Powered Assistance:**
    - **Image Generation:** Create images with AI and upload them to the Media Library.
    - **Vision:** Analyze images using AI.
- **Custom MCP Toolset:** A robust set of 37 functions providing granular control over the WordPress site.
- **Developer-Focused:** Designed for a streamlined development workflow with tools like VS Code Dev Containers.
- **Multisite Compatible:** Scripts and tools are designed to work with a WordPress multisite network.

## Technologies Used

- **Backend:** Node.js, Python 3.12+
- **WordPress:** Custom Bitnami Stack with Nginx, Apache, PHP-FPM
- **Database:** MySQL/MariaDB
- **Caching:** Redis, Varnish, Nginx fastcgi_cache
- **Development:** VS Code Dev Containers, Docker

## Environment Setup

Copy `.env.example` to `.env` and fill in your values:

```bash
cp .env.example .env
# Edit .env with your keys (git-ignored)
```

**Required Vars:**
- `WP_MCP_TOKEN`: WordPress MCP Bearer token
- `GEMINI_API_KEY`: Google Gemini API
- `GITHUB_TOKEN`: GitHub PAT (for MCP)
- `EXA_API_KEY`: Exa AI search (optional)

**Security Note:** `.env` is git-ignored. Never commit secrets!

## Getting Started

### Prerequisites

- Docker
- VS Code with the "Dev Containers" extension
- An SSH agent configured on your host machine

### Installation

1.  **Clone the repository.**
2.  **Rebuild the Dev Container:** Open the project in VS Code. You will be prompted to "Reopen in Container". This will build the Docker container defined in `.devcontainer/devcontainer.json`, which includes all necessary dependencies.
3.  **Install Dependencies:**
    - The Node.js dependencies should be installed automatically. If not, run:
      ```bash
      npm install
      ```
    - The Python dependencies can be installed via:
      ```bash
      pip install -r requirements.txt
      ```

## Exa AI Integration

**New: Semantic Web Search with Exa AI** (enabled via `EXA_API_KEY`)

- **Tools Available:**
  - `exa_search`: Neural/keyword web search with categories (news, github, papers), date filters.
  - `exa_find_similar`: Find pages similar to a URL.
  - `exa_get_contents`: Fetch full text, highlights, summaries for result IDs.

- **Usage in Gemini Chat (`npm run chatwmcp`):**
  ```
  💬 You: Search Exa for "latest Claude AI features" (numResults:5, type:neural)
  🔍 Calling: exa_search({"query":"latest Claude AI features","numResults":5,"type":"neural"})
  ✓ Found 5 results
  🤖 Gemini: Here are the top results... [AI summary + WP integration possible]
  ```

**Tested & Ready:** Returns real results (e.g., Claude Sonnet 4.5 articles from Nov 2025).

## Usage

Interaction with the WordPress site is primarily handled through the Gemini CLI and the custom MCP functions.

- **MCP Functions:** A complete list of available functions, their parameters, and usage examples can be found in [wordpress_mcp_functions.md](wordpress_mcp_functions.md).
- **Custom Scripts:** The project contains various scripts for specific tasks, such as:
    - `get_posts.php`: Retrieve a list of posts.
    - `propose_post_update.php`: Update a post's content.
    - `create_new_post.php`: Create a new post.

## Project Structure

- `GEMINI.md`: The main context file for the AI, detailing the system architecture and project goals.
- `wordpress_mcp_functions.md`: Detailed documentation for all 37 WordPress MCP functions.
- `ai_engine_mcp_functions.md`: Documentation for the AI Engine specific functions.
- `projects/`: Contains documentation for sub-projects like the AI Editor and AI Developer Assistant.
- `setups/`: Contains setup guides for the development environment.
- `tasks/`: Tracks current and completed tasks.
- `gemini-for-wp/`: Contains the WordPress plugin code.
