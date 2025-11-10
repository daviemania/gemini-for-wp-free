# Gemini Context Guide - Dav3's Development Environment

## 🏗️ System Architecture & Infrastructure

### WordPress Stack Configuration

- **Platform**: Bitnami WordPress Stack (Highly Customized)
- **Web Server**: Nginx as reverse proxy + Apache backend via Bitnami
- **Operating System**: Ubuntu 24.04 LTS (WSL2)
- **Database**: MySQL/MariaDB (Bitnami managed)
- **PHP**: Custom PHP-FPM configuration
- **Caching**: Mixed setup - Nginx fastcgi_cache + Bitnami object cache

### Hybrid Setup Details

- **Nginx**: Installed natively on Ubuntu Linux
- **Bitnami Components**: WordPress core, database, Apache encapsulated
- **Reverse Proxy**: Nginx → Bitnami Apache backend
- **SSL/TLS**: Managed through Nginx reverse proxy
- **File Structure**: Mixed - some in Bitnami dirs, some in standard Linux paths

## 🎯 My Expertise & Content Focus

### WordPress Development Specialties

- Custom WordPress guides for creators and developers
- Performance optimization for high-traffic WordPress
- Security hardening for WordPress installations
- Custom plugin and theme development
- WordPress multisite configurations
- E-commerce integrations (WooCommerce)

### Infrastructure & DevOps

- Nginx configuration and optimization
- Bitnami stack customization and management
- Linux server administration (Ubuntu/CentOS)
- Reverse proxy setups and load balancing
- SSL/TLS certificate management
- Server security and hardening

### AI & Modern Development

- Python AI/ML development
- WordPress AI integration (custom plugins)
- API development and integration
- Automation scripts and workflows
- Machine learning model deployment
- AI-powered content generation

## 📝 Current Projects & Workflows

## 📊 Site Analysis Summary

### Overall Architecture

- **Front-end & Caching Layers:** Nginx (native Linux) acts as a reverse proxy, directing traffic to Varnish, which then passes it to Apache (Bitnami-encapsulated). Redis serves as the WordPress object cache. This creates a multi-layered caching system for high performance.
- **Web Server:** Apache (Bitnami-encapsulated) is the backend web server, serving WordPress files.
- **PHP Environment:** Custom-installed PHP 8.4 (PHP-FPM) is used for WordPress, running as a `systemctl` service.
- **Database:** MySQL/MariaDB (Bitnami-managed).
- **WordPress Setup:** A complex, feature-rich multisite installation.

### Key Components & Integrations

- **Nginx:** Handles SSL termination, HTTP/2, HTTP to HTTPS redirection, and sophisticated caching exclusion rules. It also reverse proxies to Kibana.
- **Varnish:** Acts as an intermediate caching layer between Nginx and Apache.
- **Apache:** The backend web server, configured for performance and security.
- **PHP 8.4 FPM:** A dedicated pool for WordPress, with Wordfence WAF integrated via `auto_prepend_file`.
- **Redis:** Active as the WordPress object cache, further reducing database load.
- **Elasticsearch:** Integrated via the ElasticPress plugin for fast and accurate search.
- **RabbitMQ:** Used for asynchronous tasks, improving responsiveness for long-running processes.
- **JNews Theme:** The site is built on the JNews theme and its extensive suite of bundled plugins.
- **WooCommerce:** E-commerce functionality is provided by WooCommerce.
- **Rank Math SEO:** The site uses Rank Math Pro for search engine optimization.
- **Monitoring & Management:** A comprehensive suite of monitoring tools is in place, including Filebeat, Metricbeat, New Relic, and Salt Stack.

### Security & Performance Highlights

- **Strong SSL:** Modern TLS protocols and ciphers are enforced.
- **Comprehensive Caching:** A multi-layered caching strategy (Nginx, Varnish, Redis, WP Super Cache, Autoptimize, Flying Pages/Scripts) is in place to ensure fast page loads.
- **Web Application Firewall (WAF):** Wordfence is integrated at both the Apache and PHP-FPM levels.
- **Plugin Management:** A large number of plugins are in use, but performance is managed through caching and optimization plugins.
- **Best Practices:** Debugging is disabled on production, a child theme is used for customizations, and pingbacks are disabled.

## 🌐 Site Content & Structure

### Multisite Network

- **Apex Domain:** `maniainc.com`
- **Child Sites:**
  - `mania.africa` (News)
  - `/sports`
  - `/lifestyle`
  - `/movies`
  - `/technology`

### Content Overview

- **Primary Focus:** The site is a media-rich blog with a strong focus on sports (especially football), news, and movies.
- **Secondary Topics:** It also covers a wide range of other topics, including politics, technology (with a focus on AI and WordPress), and lifestyle (including history, espionage, and personal development).
- **Content Style:** The content is a mix of news, "how-to" guides, reviews, and social commentary.

### Popular Content

- **Evergreen Content:** The most popular posts are "how-to" guides and reviews, which likely attract consistent search traffic.
- **Engaging Commentary:** Social and political commentary pieces are also popular, suggesting an engaged audience.
- **Examples:** Popular topics include micro-job sites, filing tax returns, secure communication apps, and social commentary on movies and current events.

### Custom Tools

- **The Geekline Feed (`/the-geekline-feed`):** A live feed of tech-related posts from the Fediverse.
- **Geekline Archives (`/geekline-archives`):** Archives for the Geekline feed.

## 📝 Current Projects & Workflows

### Active WordPress Sites

- Primary site: Mania Africa - Creator-focused WordPress guides
- Content focus: Tutorials, development guides, creator resources
- Audience: Content creators, WordPress developers, technical users

### Gemini AI Assistant Plugin Project

- **Objective**: Develop a custom WordPress plugin to enable secure AI collaboration for development and content creation.
- **Status**: Initial plugin scaffolding created locally.
- **Development Approach**: Local development on WSL2, manual deployment to AWS Lightsail for testing.

### Development Workflow

- Local development: WSL2 + Bitnami stack
- Version control: Git
- Code editor: VS Code with WSL integration
- Testing: Staging environment on same stack
- Deployment: Manual (considering automation)

## 🛠️ Technical Specifications

### Bitnami Customizations

- Custom Nginx configurations in `/etc/nginx/sites-available/`
- Modified Bitnami service paths and ports
- Mixed logging (Nginx logs + Bitnami Apache logs)
- Custom SSL certificates managed outside Bitnami

### Development Tools Stack

- **Python**: 3.12+ for AI development
- **Node.js**: 20.x for modern web development
- **Git**: Version control
- **VS Code**: Primary editor with WSL remote extension
- **MySQL**: Database management
- **Redis**: Object caching (if applicable)

### AI Development Environment

- Python virtual environments (venv/conda)
- Jupyter notebooks for experimentation
- API development with FastAPI/Flask
- WordPress REST API integration
- Custom AI plugin development

### WordPress Interaction

- `get_posts.php`: A custom script to retrieve a list of all posts from the WordPress database.
- `get_post_content.php`: A custom script to retrieve the content of a specific post by its ID.
- `propose_post_update.php`: A custom script to update a post with new content and set its status to "pending review".
- `create_new_post.php`: A custom script to create a new post on a specified child site.
- `format_post_content.py`: A python script to format plain text content into HTML.

## 📋 Context Management System

### File Structure

/gemini-project/
├── GEMINI.md (this file)
├── projects/
│ ├── ai-editor.md
│ ├── ai-developer-assistant.md
│ ├── miscellaneous.md
│ └── tool-creator-enhancer/
│ └── geekline-feed-plugin.md
├── tasks/
│ ├── current-task.md
│ └── completed/
└── setups/
├── bitnami-nginx.md
├── python-ai.md
└── wordpress-dev.md

### Usage Protocol

1. **Reference main context**: Always consider this base configuration
2. **Load project context**: Use relevant project files for specific work
3. **Update task context**: Maintain current-task.md for ongoing work
4. **Regular updates**: Review and update context files monthly

### Communication Preferences

- Provide step-by-step solutions for complex configurations
- Include both Bitnami-standard and custom approaches
- Consider security implications in all recommendations
- Suggest monitoring and validation steps
- Offer progressive enhancement paths

### User Preferences

- The user prefers that I use `sudo` and prompt for confirmation before executing commands that modify system files or configurations.
- **Note:** While I cannot directly use `sudo` or interactive prompts due to environment limitations, I will always explain commands that modify the system and rely on the user to confirm their execution.

### Reminders

- Keep `GEMINI.md` and child `.md` context files updated with our progress.

## 🔧 Common Commands & Paths

### Bitnami Management

```bash
# Bitnami service management
sudo /opt/bitnami/ctlscript.sh restart apache
sudo /opt/bitnami/ctlscript.sh status all

# Bitnami paths
/opt/bitnami/wordpress/  # WordPress installation
/opt/bitnami/mysql/      # Database
/opt/bitnami/apache2/    # Apache configuration

Nginx Management
bash

# Nginx service (native Linux)
sudo systemctl restart nginx
sudo nginx -t  # Test configuration

# Nginx paths
/etc/nginx/nginx.conf
/etc/nginx/sites-available/
/etc/nginx/sites-enabled/
```
