# CLAUDE.template.md (Copy to CLAUDE.md)

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Development Tasks

```bash
# Server Management
npm start                # Production server (port 3000)
npm run dev              # Development server with nodemon
npm run chatwmcp         # Interactive Gemini + MCP chat
npm run ollamachat       # Ollama + MCP chat
npm run openrouterchat   # OpenRouter chat
npm run claude:code      # Launch Claude Code

# Smart Folder Management
npm run organize         # Basic organization
npm run organize:smart   # AI-powered organization
npm run organize:dupes   # Find duplicates
npm run organize:analyze # Detailed analysis
npm run organize:interactive  # Interactive mode
```

## High-Level Architecture

**Core Components:**
- **Main Server** (`server.js`): Express API on port 3000 serving hybrid AI chat, Ollama management, MCP relay
- **MCP Relay** (`mcp.js`): Port 3001 proxy for your WordPress MCP endpoint (e.g., `https://your-site.com/wp-json/mcp/v1/sse`)
- **AI Orchestrator** (`ai-manager-hybrid.js`): Routes between Ollama Cloud (primary), local models, Gemini fallback
- **Smart Folder Manager** (`smart-folder-manager/`): CLI for intelligent file organization with duplicate detection

**WordPress Integration Flow:**
```
User Query → AI (Gemini/Ollama) → MCP Tool Call → mcp.js Relay → WP REST API → Results
```

**Key Integration Points:**
- 37 WordPress MCP functions documented in `wordpress_mcp_functions.md`
- Authentication: Bearer `${process.env.WP_MCP_TOKEN}`
- JSON-RPC 2.0 format required for all MCP calls
- WordPress lives at your installation path (e.g., `/opt/bitnami/wordpress/` or symlink `/wordpress` – adjust in code)

**Critical Directories (Core):**
```
/gemini-for-wp/          # WP plugin development
/agents/                 # AI agent configurations (optional, user-created)
/smart-folder-manager/   # File organization CLI tool
```

**Optional/Personal (create as needed, gitignore'd):**
- `/projects/`, `/tasks/`: Docs/todos
- `/logs/`, `/data/`, `/tmp/`: Runtime