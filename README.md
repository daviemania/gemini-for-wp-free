# Gemini for WordPress - AI-Powered MCP Tools

> Transform WordPress with AI automation. 37+ free tools + premium AI integrations for developers and content creators.

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://maniainc.com/gemini-mcp-tools-plugin)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![GitHub stars](https://img.shields.io/github/stars/daviemania/gemini-for-wp-free.svg)](https://github.com/daviemania/gemini-for-wp-free/stargazers)

## 🚀 Overview

A powerful CLI-based environment and WordPress plugin for interacting with and managing WordPress sites using AI-powered tools and a custom Model Context Protocol (MCP). This project provides comprehensive WordPress management through 37+ MCP functions, enabling developers and administrators to perform complex tasks, automate workflows, and leverage AI for content creation and site maintenance.

### Key Features

- ✅ **37+ WordPress CRUD Tools** - Complete control over posts, pages, users, comments, media, taxonomies, and options
- ✅ **AI-Powered Assistance** - Image generation, vision analysis, and intelligent automation
- ✅ **Custom MCP Toolset** - Robust set of functions providing granular control over WordPress
- ✅ **Full AI Engine Integration** - Native protocol support, seamless communication
- ✅ **Privacy-First Architecture** - All processing happens on your server
- ✅ **Multiple AI Providers** - Ollama (local), OpenRouter (50+ models), Claude, Exa AI, and more
- ✅ **Developer-Focused** - Streamlined workflow with VS Code Dev Containers
- ✅ **Multisite Compatible** - Works with WordPress multisite networks

## 💎 Pricing & Plans

Gemini MCP Tools follows a **freemium model** with generous free features and premium AI capabilities.

### Free Plan (Forever Free)

Perfect for getting started with AI-powered WordPress automation.

- ✅ **37+ WordPress CRUD Tools** - Complete control over posts, pages, users, comments, media, and more
- ✅ **Full AI Engine MCP Integration** - Seamless communication with AI Engine plugin
- ✅ **Community Support** - Access to community forums and documentation
- ✅ **Unlimited Sites** - Use on as many WordPress installations as you need
- ✅ **Open Source** - Full access to source code on GitHub

### Premium Plans

Unlock advanced AI capabilities with our premium plans:

#### 🌟 Premium - Perfect for Individuals

Starting at **$29/month** or **$275/year** (save 21%)

- ✅ All Free features
- ✅ **Unlimited Usage** (no rate limiting)
- ✅ **Ollama Integration** - Run local AI models on your server
- ✅ **OpenRouter Access** - 50+ AI model providers in one API
- ✅ **Exa Search** - Intelligent, context-aware web search
- ✅ **Claude Code** - AI-powered coding assistance from Anthropic
- ✅ **GitHub Chat** - Repository-aware AI conversations
- ✅ **Priority Email Support** - Get help when you need it

#### 🚀 Pro - Best for Teams

Starting at **$72.99/month** or **$695.88/year** (save 20%)

- ✅ All Premium features
- ✅ **Smart Folder Organize** - AI-powered content organization
- ✅ **Multiple Site Licenses** - Use on 5 sites
- ✅ **Priority Support** (48-hour response time)
- ✅ **Early Access** to new features and toolkits
- ✅ **Advanced Analytics Dashboard**

#### 💼 Agency - For Large Organizations

Starting at **$127.99/month** or **$1223/year** (save 20%)

- ✅ All Pro features
- ✅ **White-Glove Support** (24-hour response time)
- ✅ **Custom Tool Development** (1-hour consultation included)
- ✅ **Dedicated Bug Fix Priority**
- ✅ **Private Slack Channel** for direct communication
- ✅ **Unlimited Site Licenses**

### 🛒 Get Premium

**[View All Plans & Pricing →](https://maniainc.com/gemini-mcp-tools-plugin)**

**Try Premium Risk-Free:**
- ✅ 3-day trial available
- ✅ 30-day money-back guarantee
- ✅ No credit card required to start

---

## 🔧 Installation

### Quick Install (Recommended)

**This toolkit is a companion to the [Gemini MCP Tools](https://maniainc.com/gemini-mcp-tools-plugin) WordPress plugin.**

```bash
cd /path/to/wordpress/wp-content/
git clone https://github.com/daviemania/gemini-for-wp-free.git gemini-ai-toolkit
cd gemini-ai-toolkit
npm install
```

⚠️ **Important:** The folder must be named `gemini-ai-toolkit` when cloning.

### Manual Install

1. Download this repository
2. Rename the folder to `gemini-ai-toolkit`
3. Place it in `/wp-content/gemini-ai-toolkit/`
4. Run `npm install` inside the directory

### WordPress Plugin Installation

#### Free Version

1. Download the latest release from [Releases](https://github.com/daviemania/gemini-for-wp-free/releases)
2. Upload to WordPress via **Plugins → Add New → Upload Plugin**
3. Activate the plugin
4. Install this AI Toolkit (see Quick Install above)

#### Premium Version

1. Purchase a license at [maniainc.com](https://maniainc.com/gemini-mcp-tools-plugin)
2. Download the premium version from your Freemius account
3. Upload and activate in WordPress
4. Enter your license key under **Plugins → Gemini MCP Tools**

### Development Container Setup

1. **Clone the repository**
2. **Rebuild the Dev Container:** Open the project in VS Code. You will be prompted to "Reopen in Container". This will build the Docker container defined in `.devcontainer/devcontainer.json`, which includes all necessary dependencies.
3. **Install Dependencies:**
    - Node.js dependencies should install automatically. If not:
      ```bash
      npm install
      ```
    - Python dependencies:
      ```bash
      pip install -r requirements.txt
      ```

---

## ⚙️ Environment Configuration

Copy `.env.example` to `.env` and fill in your values:

```bash
cp .env.example .env
# Edit .env with your keys (git-ignored)
```

**Required Environment Variables:**
- `WP_MCP_TOKEN`: WordPress MCP Bearer token
- `GEMINI_API_KEY`: Google Gemini API
- `GITHUB_TOKEN`: GitHub PAT (for MCP)
- `EXA_API_KEY`: Exa AI search (optional, premium feature)

**Security Note:** `.env` is git-ignored. Never commit secrets!

---

## 📋 Requirements

- Node.js 18+
- npm or yarn
- WordPress 6.0+ with Gemini MCP Tools plugin
- PHP 7.4+
- Docker (for development container)
- VS Code with "Dev Containers" extension (recommended)
- SSH agent configured on host machine

---

## 🎯 Usage

This toolkit is automatically detected and used by the Gemini MCP Tools plugin once installed in the correct location (`wp-content/gemini-ai-toolkit/`).

### CLI Interaction

Interaction with the WordPress site is primarily handled through the Gemini CLI and the custom MCP functions.

```bash
# Start interactive chat with MCP
npm run chatwmcp
```

### MCP Functions

A complete list of available functions, their parameters, and usage examples can be found in:
- [wordpress_mcp_functions.md](wordpress_mcp_functions.md) - 37+ WordPress CRUD functions
- [ai_engine_mcp_functions.md](ai_engine_mcp_functions.md) - AI Engine specific functions

### Custom Scripts

The project contains various scripts for specific tasks:
- `get_posts.php` - Retrieve a list of posts
- `propose_post_update.php` - Update post content
- `create_new_post.php` - Create new posts

---

## 🔍 Exa AI Integration

**New: Semantic Web Search with Exa AI** (enabled via `EXA_API_KEY`)

### Available Tools:
- **`exa_search`** - Neural/keyword web search with categories (news, github, papers), date filters
- **`exa_find_similar`** - Find pages similar to a URL
- **`exa_get_contents`** - Fetch full text, highlights, summaries for result IDs

### Usage Example:

```bash
💬 You: Search Exa for "latest Claude AI features" (numResults:5, type:neural)
🔍 Calling: exa_search({"query":"latest Claude AI features","numResults":5,"type":"neural"})
✓ Found 5 results
🤖 Gemini: Here are the top results... [AI summary + WP integration possible]
```

**Tested & Ready:** Returns real results (e.g., Claude Sonnet 4.5 articles from Nov 2025).

---

## 📚 Documentation

### Free WordPress CRUD Tools (37+)

Access these tools immediately after installation:

**Posts & Pages:**
- `wp_list_posts` - List all posts with filters
- `wp_create_post` - Create new posts
- `wp_update_post` - Update existing posts
- `wp_delete_post` - Delete posts
- `wp_get_post` - Get single post details
- Plus pages, custom post types, and more...

**Users:**
- `wp_list_users`, `wp_create_user`, `wp_update_user`, `wp_delete_user`

**Media:**
- `wp_list_media`, `wp_upload_media`, `wp_delete_media`, `wp_get_media`

**Comments:**
- `wp_list_comments`, `wp_create_comment`, `wp_update_comment`
- `wp_approve_comment`, `wp_spam_comment`, etc.

**Taxonomies:**
- `wp_list_categories`, `wp_list_tags`
- `wp_create_category`, `wp_create_tag`
- `wp_update_category`, `wp_update_tag`

### Premium AI Tools

Available with Premium plans:

- **`ollama_chat`** - Local AI model conversations
- **`exa_search`** - Intelligent web search
- **`openrouter_call`** - Access 50+ AI models
- **`smart_folder_organize`** - AI content organization (Pro+)
- **`composed_exploring_dolphin`** - Advanced automation
- **`claude_code`** - AI coding assistance
- **`github_chat`** - Repository-aware AI help

---

## 🗂️ Project Structure

```
.
├── GEMINI.md                      # Main context file for AI
├── wordpress_mcp_functions.md     # Documentation for 37 WordPress MCP functions
├── ai_engine_mcp_functions.md     # AI Engine specific functions
├── projects/                      # Sub-project documentation
├── setups/                        # Development environment setup guides
├── tasks/                         # Current and completed tasks
├── gemini-mcp-tools/             # WordPress plugin code
├── .devcontainer/                # VS Code Dev Container configuration
├── .env.example                  # Environment variables template
└── package.json                  # Node.js dependencies and scripts
```

---

## 💻 Technologies Used

- **Backend:** Node.js, Python 3.12+
- **WordPress:** Custom Bitnami Stack with Nginx, Apache, PHP-FPM
- **Database:** MySQL/MariaDB
- **Caching:** Redis, Varnish, Nginx fastcgi_cache
- **Development:** VS Code Dev Containers, Docker
- **AI Services:** Google Gemini, Ollama, OpenRouter, Claude, Exa AI

---

## 🎯 Use Cases

### Content Creators
- Bulk create/update posts with AI assistance
- Organize content with smart folders
- Generate SEO-optimized content
- Automate publishing workflows

### Developers
- Code assistance with Claude Code
- GitHub integration for repository management
- Automated testing and deployment
- Custom tool development

### Agencies
- Multi-site management
- Client content organization
- Automated reporting
- White-label solutions

### Site Administrators
- Bulk user management
- Media library organization
- Comment moderation
- Site maintenance automation

---

## 🤝 Support

### Free Users
- 📖 [Documentation](https://maniainc.com/docs/gemini-mcp-tools)
- 💬 [GitHub Issues](https://github.com/daviemania/gemini-for-wp-free/issues)
- 🌐 [Community Forum](https://github.com/daviemania/gemini-for-wp-free/discussions)

### Premium Users
- ✉️ Priority email support via [Freemius Dashboard](https://dashboard.freemius.com)
- 📞 Pro/Agency: Enhanced support with guaranteed response times
- 💬 Agency: Private Slack channel access

---

## ⭐ Support This Project

If you find Gemini MCP Tools helpful:

- ⭐ **Star this repository** to show your support
- 🐛 **Report bugs** via [GitHub Issues](https://github.com/daviemania/gemini-for-wp-free/issues)
- 💡 **Suggest features** you'd like to see
- 📝 **Write a review** on WordPress.org
- 🚀 **Upgrade to Premium** to unlock advanced AI features

---

## 🔐 Privacy & Security

- **Privacy-First:** Your data never leaves your server
- **No Tracking:** We don't collect or analyze your content
- **Secure:** All communications encrypted
- **GPL Licensed:** Open source and transparent

---

## 🛠️ Development

### Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines (if available).

### Development Setup

```bash
# Clone repository
git clone https://github.com/daviemania/gemini-for-wp-free.git
cd gemini-for-wp-free

# Install dependencies
npm install
pip install -r requirements.txt

# Copy environment template
cp .env.example .env
# Edit .env with your configuration

# Start development
npm run dev
```

---

## 📜 License

**Free Version:** GPL v2 or later - [License Details](https://www.gnu.org/licenses/gpl-2.0.html)

**Premium Version:** Proprietary license - Commercial use allowed with valid license

---

## 🔗 Links

- **Plugin Page:** [https://maniainc.com/gemini-mcp-tools-plugin](https://maniainc.com/gemini-mcp-tools-plugin)
- **Pricing:** [https://maniainc.com/gemini-mcp-tools-plugin#pricing](https://maniainc.com/gemini-mcp-tools-plugin#pricing)
- **Documentation:** [https://maniainc.com/docs/gemini-mcp-tools](https://maniainc.com/docs/gemini-mcp-tools)
- **Support:** [https://maniainc.com/support](https://maniainc.com/support)
- **Author:** [https://maniainc.com](https://maniainc.com)

---

## 📊 Stats

- **37+** Free WordPress Tools
- **7** Premium AI Integrations
- **50+** AI Models via OpenRouter
- **100%** MCP Protocol Compatible
- **0** Data Collection

---

## 👨‍💻 About

Created by [@daviemania](https://github.com/daviemania) – WP blogger, musician, YouTuber. I created this tool for my own multisite WordPress workflow at Mania Africa, but saw its utility for all WP users. It's been contributed to majorly by numerous AI tools and LLMs.

- **Site:** [maniainc.com](https://maniainc.com)
- **Links:** [linktr.ee/davidmania](https://linktr.ee/davidmania)
- **Mania Africa:** [About](https://maniainc.com/about-us) | [Contact](https://maniainc.com/contact-us)
- **Email:** mail@davidmania.com (PGP hardened: /.well-known/security.txt)

---

## 📄 Legal

[Privacy Policy](./privacy-policy.md) | [Terms & Conditions](./terms-and-conditions.md)

---

Made with ❤️ for the WordPress community

**[Get Started Now →](https://maniainc.com/gemini-mcp-tools-plugin)**

### Free Plan (Forever Free)

Perfect for getting started with AI-powered WordPress automation.

- ✅ **37+ WordPress CRUD Tools** - Complete control over posts, pages, users, comments, media, taxonomies, and options
- ✅ **Full AI Engine MCP Integration** - Seamless communication with AI Engine plugin
- ✅ **Community Support** - Access to community forums and documentation
- ✅ **Unlimited Sites** - Use on as many WordPress installations as you need
- ✅ **Open Source** - Full access to source code on GitHub

### Premium Plans

Unlock advanced AI capabilities with our premium plans:

#### 🌟 Premium - Perfect for Individuals

Starting at **$29/month** or **$275/year** (save 21%)

- ✅ All Free features
- ✅ **Unlimited Usage** (no rate limiting)
- ✅ **Ollama Integration** - Run local AI models on your server
- ✅ **OpenRouter Access** - 50+ AI model providers in one API
- ✅ **Exa Search** - Intelligent, context-aware web search
- ✅ **Claude Code** - AI-powered coding assistance from Anthropic
- ✅ **GitHub Chat** - Repository-aware AI conversations
- ✅ **Priority Email Support** - Get help when you need it

#### 🚀 Pro - Best for Teams

Starting at **$49/month** or **$470/year** (save 20%)

- ✅ All Premium features
- ✅ **Smart Folder Organize** - AI-powered content organization
- ✅ **Multiple Site Licenses** - Use on 5 sites
- ✅ **Priority Support** (48-hour response time)
- ✅ **Early Access** to new features and toolkits
- ✅ **Advanced Analytics Dashboard**

#### 💼 Agency - For Large Organizations

Starting at **$99/month** or **$950/year** (save 20%)

- ✅ All Pro features
- ✅ **White-Glove Support** (24-hour response time)
- ✅ **Custom Tool Development** (1-hour consultation included)
- ✅ **Dedicated Bug Fix Priority**
- ✅ **Private Slack Channel** for direct communication
- ✅ **Unlimited Site Licenses**

### 🛒 Get Premium

**[View All Plans & Pricing →](https://maniainc.com/gemini-mcp-tools-plugin)**

**Try Premium Risk-Free:**
- ✅ 3-day trial available
- ✅ 30-day money-back guarantee
- ✅ No credit card required to start

---

## 🔧 Installation

### Quick Start (Free Version)

1. **Download** the latest release from [Releases](https://github.com/daviemania/gemini-for-wp-free/releases)
2. **Upload** to WordPress via Plugins → Add New → Upload Plugin
3. **Activate** the plugin
4. Start using 37+ free WordPress CRUD tools immediately!

### Premium Version Setup

1. **Purchase** a license at [maniainc.com](https://maniainc.com/gemini-mcp-tools-plugin)
2. **Download** the premium version from your Freemius account
3. **Upload and activate** in WordPress
4. **Enter your license key** under Plugins → Gemini MCP Tools
5. **Install the AI Toolkit** (see below)

### Gemini AI Toolkit Setup (Required for Premium Features)

Premium AI features require the companion Gemini AI Toolkit:

```bash
# Navigate to WordPress content directory
cd /path/to/wordpress/wp-content/

# Clone the toolkit (must be named 'gemini-ai-toolkit')
git clone https://github.com/daviemania/gemini-for-wp-free.git gemini-ai-toolkit

# Install dependencies
cd gemini-ai-toolkit
npm install
```

**Requirements:**
- Node.js 18+
- npm or yarn
- Git

The toolkit must be installed at `wp-content/gemini-ai-toolkit/`

---

## 📚 Documentation

### Free WordPress CRUD Tools (37+)

Access these tools immediately after installation:

**Posts & Pages:**
- `wp_list_posts` - List all posts with filters
- `wp_create_post` - Create new posts
- `wp_update_post` - Update existing posts
- `wp_delete_post` - Delete posts
- `wp_get_post` - Get single post details
- Plus pages, custom post types, and more...

**Users:**
- `wp_list_users` - List all users
- `wp_create_user` - Create new users
- `wp_update_user` - Update user details
- `wp_delete_user` - Delete users

**Media:**
- `wp_list_media` - List media library items
- `wp_upload_media` - Upload new media
- `wp_delete_media` - Delete media files
- `wp_get_media` - Get media details

**Comments:**
- `wp_list_comments` - List comments
- `wp_create_comment` - Add comments
- `wp_update_comment` - Modify comments
- `wp_approve_comment`, `wp_spam_comment`, etc.

**Taxonomies:**
- `wp_list_categories`, `wp_list_tags`
- `wp_create_category`, `wp_create_tag`
- `wp_update_category`, `wp_update_tag`
- And more...

### Premium AI Tools

Available with Premium plans:

- **`ollama_chat`** - Local AI model conversations
- **`exa_search`** - Intelligent web search
- **`openrouter_call`** - Access 50+ AI models
- **`smart_folder_organize`** - AI content organization (Pro+)
- **`composed_exploring_dolphin`** - Advanced automation
- **`claude_code`** - AI coding assistance
- **`github_chat`** - Repository-aware AI help

---

## 🎯 Use Cases

### Content Creators
- Bulk create/update posts with AI assistance
- Organize content with smart folders
- Generate SEO-optimized content
- Automate publishing workflows

### Developers
- Code assistance with Claude Code
- GitHub integration for repository management
- Automated testing and deployment
- Custom tool development

### Agencies
- Multi-site management
- Client content organization
- Automated reporting
- White-label solutions

### Site Administrators
- Bulk user management
- Media library organization
- Comment moderation
- Site maintenance automation

---

## 🤝 Support

### Free Users
- 📖 [Documentation](https://github.com/daviemania/gemini-for-wp-free/wiki)
- 💬 [GitHub Issues](https://github.com/daviemania/gemini-for-wp-free/issues)
- 🌐 [Community Forum](https://github.com/daviemania/gemini-for-wp-free/discussions)

### Premium Users
- ✉️ Priority email support via [Freemius Dashboard](https://dashboard.freemius.com)
- 📞 Pro/Agency: Enhanced support with guaranteed response times
- 💬 Agency: Private Slack channel access

---

## ⭐ Support This Project

If you find Gemini MCP Tools helpful:

- ⭐ **Star this repository** to show your support
- 🐛 **Report bugs** via [GitHub Issues](https://github.com/daviemania/gemini-for-wp-free/issues)
- 💡 **Suggest features** you'd like to see
- 📝 **Write a review** on WordPress.org
- 🚀 **Upgrade to Premium** to unlock advanced AI features

---

## 🔐 Privacy & Security

- **Privacy-First:** Your data never leaves your server
- **No Tracking:** We don't collect or analyze your content
- **Secure:** All communications encrypted
- **GPL Licensed:** Open source and transparent

---

## 🛠️ Development

### Requirements
- WordPress 6.0+
- PHP 7.4+
- Node.js 18+ (for AI Toolkit)
- Composer (for dependencies)

### Setup Development Environment

```bash
# Clone repository
git clone https://github.com/daviemania/gemini-for-wp-free.git
cd gemini-for-wp-free

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Build for development
npm run dev
```

### Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

---

## 📜 License

**Free Version:** GPL v2 or later - [License Details](https://www.gnu.org/licenses/gpl-2.0.html)

**Premium Version:** Proprietary license - Commercial use allowed with valid license

---

## 🔗 Links

- **Plugin Page:** [https://maniainc.com/gemini-mcp-tools-plugin](https://maniainc.com/gemini-mcp-tools-plugin)
- **Pricing:** [https://maniainc.com/gemini-mcp-tools-plugin#pricing](https://maniainc.com/gemini-mcp-tools-plugin#Pricing)
- **Documentation:** [https://maniainc.com/docs/gemini-mcp-tools](https://github.com/daviemania/gemini-for-wp-free/wiki)
- **Support:** [https://maniainc.com/support](https://maniainc.com/contact-us)
- **Author:** [https://maniainc.com](https://maniainc.com)

---

## 📊 Stats

- **37+** Free WordPress Tools
- **7** Premium AI Integrations
- **50+** AI Models via OpenRouter
- **100%** MCP Protocol Compatible
- **0** Data Collection

---

Made with ❤️ by [David Mania](https://maniainc.com)

**[Get Started Now →](https://maniainc.com/gemini-mcp-tools-plugin)**
