# CLAUDE.md

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
- **MCP Relay** (`mcp.js`): Port 3001 proxy for WordPress MCP endpoint (`https://maniainc.com/wp-json/mcp/v1/sse`)
- **AI Orchestrator** (`ai-manager-hybrid.js`): Routes between Ollama Cloud (primary), local models, Gemini fallback
- **Smart Folder Manager** (`smart-folder-manager/`): CLI for intelligent file organization with duplicate detection

**WordPress Integration Flow:**
```
User Query → AI (Gemini/Ollama) → MCP Tool Call → mcp.js Relay → WP REST API → Results
```

**Key Integration Points:**
- 37 WordPress MCP functions documented in `wordpress_mcp_functions.md`
- Authentication: Bearer `uX484&B$k@c@6072&VdTJi#3`
- JSON-RPC 2.0 format required for all MCP calls
- WordPress lives at `/opt/bitnami/wordpress/` (symlink: `/wordpress`)

**Critical Directories:**
```
/gemini-for-wp/          # WP plugin development
/agents/                 # AI agent configurations
/smart-folder-manager/   # File organization CLI tool
/projects/               # Project documentation
/tasks/                  # Active development tasks
/logs/, /data/, /tmp/    # Runtime data
```