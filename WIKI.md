# Gemini for WP Project Wiki

## Overview
This repository integrates AI (Gemini, Ollama, OpenRouter, Claude) with WordPress via MCP (Model Context Protocol) relay. It provides hybrid AI chat, smart file organization, and WP automation tools.

## Architecture
```
User Query → AI Orchestrator (ai-manager-hybrid.js) → MCP Relay (mcp.js:3001) → WP MCP API (/wp-json/mcp/v1/sse)
                          ↓
                   Local Ollama / Cloud Models (Gemini fallback)
```

**Core Components:**
- **server.js**: Express API (port 3000) - Hybrid AI chat, Ollama management
- **mcp.js**: MCP proxy/relay to WP endpoint
- **ai-manager-hybrid.js**: AI routing (Ollama → Gemini/OpenRouter/Claude)
- **smart-folder-manager/**: AI-powered CLI for file organization/dupe detection
- **/gemini-for-wp/**: WP plugin development
- **/agents/**: AI agent configs

## Setup & Commands
See `CLAUDE.md` for npm scripts:
```bash
npm run dev              # Dev server
npm run chatwmcp         # Gemini + MCP chat
npm run organize:smart   # AI file organization
```

**WordPress Integration:**
- Path: `/opt/bitnami/wordpress/` (symlink: `/wordpress`)
- Auth: Bearer `${process.env.WP_MCP_TOKEN}`
- 37 MCP functions: See `wordpress_mcp_functions.md` (powered by [AI Engine WordPress Plugin](https://wordpress.org/plugins/ai-engine/)) + full [Gemini MCP Tools plugin](/gemini-for-wp/) included (requires AI Engine)
- JSON-RPC 2.0 required

## Key Directories
| Directory | Purpose |
|-----------|---------|
| `/projects/` | Project docs |
| `/tasks/` | Active tasks |
| `/logs/`, `/data/`, `/tmp/` | Runtime data |
| `/agents/` | AI configs |

## Related Files
- [acknowledgements.md](acknowledgements.md)
- [CHANGELOG.md](CHANGELOG.md)
- [CLAUDE.md](CLAUDE.md) - Claude Code instructions
- GitLab: https://gitlab.com/daviemania/gemini-project

## Contributing
1. Fork & clone
2. `npm install`
3. `npm run dev`
4. Commit with semantic messages
5. Push & create MR

**Recent:** [CHANGELOG.md](CHANGELOG.md) tracks evolution.
