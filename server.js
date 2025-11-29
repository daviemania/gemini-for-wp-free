/**
 * Complete Multi-AI Server with MCP Integration
 * Supports: Ollama Cloud, Ollama Local, Gemini, and WordPress MCP Tools
 */

const express = require("express");
const path = require("path");
const fs = require("fs").promises;

// AI Managers
const HybridAIManager = require("./ai-manager-hybrid");
const OllamaManager = require("./ollama-manager");

// MCP Client
const MCP_RELAY_URL = "http://localhost:3001";
const MCP_BEARER_TOKEN = `Bearer ${process.env.WP_MCP_TOKEN}`;

class MCPClient {
    constructor(relayUrl, bearerToken) {
        this.relayUrl = relayUrl;
        this.bearerToken = bearerToken;
    }

    async callTool(toolName, args = {}) {
        try {
            const response = await fetch(this.relayUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: this.bearerToken,
                },
                body: JSON.stringify({
                    tool: toolName,
                    args: args,
                }),
            });

            if (!response.ok) {
                throw new Error(
                    `MCP Error: ${response.status} ${response.statusText}`,
                );
            }

            return await response.json();
        } catch (error) {
            console.error(`MCP Tool Call Failed [${toolName}]:`, error.message);
            throw error;
        }
    }

    getToolDefinitions() {
        return [
            {
                name: "wp_list_plugins",
                description:
                    "List installed WordPress plugins with their versions",
                parameters: {
                    type: "object",
                    properties: {
                        search: {
                            type: "string",
                            description: "Search term for plugins",
                        },
                    },
                },
            },
            {
                name: "wp_get_posts",
                description: "Retrieve WordPress posts. Returns 10 by default",
                parameters: {
                    type: "object",
                    properties: {
                        post_type: {
                            type: "string",
                            description: "Post type (post, page, etc)",
                        },
                        post_status: {
                            type: "string",
                            description: "Post status (publish, draft, etc)",
                        },
                        search: { type: "string", description: "Search term" },
                        limit: {
                            type: "integer",
                            description: "Max posts to return",
                        },
                    },
                },
            },
            {
                name: "wp_get_post",
                description: "Get a specific post by ID with all its data",
                parameters: {
                    type: "object",
                    properties: {
                        ID: { type: "integer", description: "Post ID" },
                    },
                    required: ["ID"],
                },
            },
            {
                name: "wp_create_post",
                description: "Create a new WordPress post or page",
                parameters: {
                    type: "object",
                    properties: {
                        post_title: {
                            type: "string",
                            description: "Post title",
                        },
                        post_content: {
                            type: "string",
                            description: "Post content (Markdown supported)",
                        },
                        post_status: {
                            type: "string",
                            description: "Status: draft, publish, etc",
                        },
                        post_type: {
                            type: "string",
                            description: "Type: post, page, etc",
                        },
                    },
                    required: ["post_title"],
                },
            },
            {
                name: "wp_update_post",
                description: "Update an existing WordPress post",
                parameters: {
                    type: "object",
                    properties: {
                        ID: { type: "integer", description: "Post ID" },
                        fields: {
                            type: "object",
                            description: "Fields to update",
                        },
                    },
                    required: ["ID"],
                },
            },
            {
                name: "wp_get_users",
                description: "Get WordPress users. Returns 10 by default",
                parameters: {
                    type: "object",
                    properties: {
                        search: { type: "string", description: "Search term" },
                        role: { type: "string", description: "Filter by role" },
                        limit: {
                            type: "integer",
                            description: "Max users to return",
                        },
                    },
                },
            },
            {
                name: "wp_count_posts",
                description: "Count posts by status",
                parameters: {
                    type: "object",
                    properties: {
                        post_type: {
                            type: "string",
                            description: "Post type to count",
                        },
                    },
                },
            },
            {
                name: "wp_upload_media",
                description:
                    "Download image from URL and add to WordPress Media Library",
                parameters: {
                    type: "object",
                    properties: {
                        url: { type: "string", description: "Image URL" },
                        title: { type: "string", description: "Image title" },
                        alt: { type: "string", description: "Alt text" },
                    },
                    required: ["url"],
                },
            },
        ];
    }
}

// Initialize Express app
const app = express();
const PORT = process.env.PORT || 3000;

// Configuration
const config = {
    app: {
        name: "Multi-AI Server with MCP",
        version: "3.0.0",
        environment: process.env.NODE_ENV || "development",
    },
    mcp: {
        relayUrl: MCP_RELAY_URL,
        bearerToken: MCP_BEARER_TOKEN,
    },
};

// Middleware
app.use(express.json({ limit: "10mb" }));
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, "public")));

// Logging middleware
app.use((req, res, next) => {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${req.method} ${req.path} - IP: ${req.ip}`);
    next();
});

// Initialize AI Managers
const aiManager = new HybridAIManager({
    ollamaHost: "http://localhost:11434",
    geminiKey: process.env.GEMINI_API_KEY,
});

const ollamaOnly = new OllamaManager();

const mcpClient = new MCPClient(MCP_RELAY_URL, MCP_BEARER_TOKEN);

// Utility functions
const utils = {
    formatResponse: (success, data, message = "") => ({
        success,
        data,
        message,
        timestamp: new Date().toISOString(),
        version: config.app.version,
    }),

    handleError: (res, error, statusCode = 500) => {
        console.error("Server Error:", error);
        res.status(statusCode).json(
            utils.formatResponse(false, null, error.message),
        );
    },

    validatePrompt: (prompt) => {
        if (!prompt || typeof prompt !== "string") {
            return "Prompt must be a non-empty string";
        }
        if (prompt.length > 10000) {
            return "Prompt too long (max 10000 characters)";
        }
        return null;
    },
};

// =============================================================================
// ROUTES
// =============================================================================

// Health check
app.get("/health", async (req, res) => {
    try {
        const hybridStatus = await aiManager.getSystemStatus();
        const ollamaStatus = ollamaOnly.getStats();

        res.json(
            utils.formatResponse(
                true,
                {
                    status: "healthy",
                    environment: config.app.environment,
                    uptime: process.uptime(),
                    ai: {
                        hybrid: {
                            cloud: hybridStatus.providers.cloud.available,
                            gemini: hybridStatus.providers.gemini.available,
                            memory: hybridStatus.memory,
                        },
                        ollama: {
                            active: ollamaStatus.activeModel,
                            cloud: ollamaStatus.cloud.available,
                            local: ollamaStatus.local.available,
                        },
                    },
                    mcp: {
                        connected: true,
                        endpoint: config.mcp.relayUrl,
                        toolsAvailable: mcpClient.getToolDefinitions().length,
                    },
                },
                "Server is running",
            ),
        );
    } catch (error) {
        console.error("Health check error:", error);
        res.json(
            utils.formatResponse(true, {
                status: "initializing",
                message: "AI managers still initializing",
            }),
        );
    }
});

// API status
app.get("/api/status", async (req, res) => {
    try {
        const hybridStatus = await aiManager.getSystemStatus();
        const ollamaStatus = ollamaOnly.getStats();

        res.json(
            utils.formatResponse(true, {
                app: config.app,
                server: {
                    nodeVersion: process.version,
                    platform: process.platform,
                    uptime: Math.floor(process.uptime()),
                },
                ai: {
                    hybrid: hybridStatus,
                    ollama: ollamaStatus,
                },
                mcp: {
                    relayUrl: config.mcp.relayUrl,
                    toolsAvailable: mcpClient.getToolDefinitions().length,
                },
            }),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

// =============================================================================
// HYBRID AI ENDPOINTS (Ollama Cloud + Gemini)
// =============================================================================

app.post("/api/chat-with-tools", async (req, res) => {
    try {
        const { messages, preferOllama = true, provider = "auto" } = req.body;

        if (!Array.isArray(messages) || messages.length === 0) {
            return res
                .status(400)
                .json(
                    utils.formatResponse(
                        false,
                        null,
                        "Messages array required",
                    ),
                );
        }

        const lastMessage = messages[messages.length - 1];
        const messageContent = lastMessage.content || lastMessage;

        const tools = mcpClient.getToolDefinitions();

        const result = await aiManager.chat(messageContent, {
            preferOllama: preferOllama,
            mcpTools: tools,
            systemPrompt:
                "You are a helpful assistant with access to WordPress management tools. When users ask about WordPress content, use the available tools to get accurate information.",
            forceProvider: provider !== "auto" ? provider : null,
        });

        if (!result.success) {
            return res
                .status(500)
                .json(utils.formatResponse(false, null, result.error));
        }

        // Execute function calls if any
        const functionResults = [];
        if (result.functionCalls && result.functionCalls.length > 0) {
            console.log(
                `🔧 Executing ${result.functionCalls.length} MCP tools...`,
            );

            for (const call of result.functionCalls) {
                try {
                    const toolResult = await mcpClient.callTool(
                        call.name,
                        call.args || call.arguments,
                    );

                    functionResults.push({
                        tool: call.name,
                        args: call.args || call.arguments,
                        result: toolResult,
                    });
                } catch (error) {
                    functionResults.push({
                        tool: call.name,
                        args: call.args || call.arguments,
                        error: error.message,
                    });
                }
            }
        }

        res.json(
            utils.formatResponse(
                true,
                {
                    response: result.response,
                    provider: result.provider,
                    model: result.model,
                    responseTime: result.responseTime,
                    complexity: result.complexity,
                    functionCalls: functionResults,
                },
                "Chat response with MCP tools",
            ),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

app.get("/api/ai/status", async (req, res) => {
    try {
        const status = await aiManager.getSystemStatus();
        res.json(utils.formatResponse(true, status, "Hybrid AI system status"));
    } catch (error) {
        utils.handleError(res, error);
    }
});

app.get("/api/ai/stats", (req, res) => {
    try {
        const stats = aiManager.getStats();
        res.json(utils.formatResponse(true, stats, "AI usage statistics"));
    } catch (error) {
        utils.handleError(res, error);
    }
});

// =============================================================================
// OLLAMA-ONLY ENDPOINTS
// =============================================================================

app.get("/api/ollama/status", async (req, res) => {
    try {
        const stats = ollamaOnly.getStats();
        const models = await ollamaOnly.listModels();

        res.json(
            utils.formatResponse(
                true,
                {
                    stats: stats,
                    models: models,
                },
                "Ollama system status",
            ),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

app.post("/api/ollama/chat", async (req, res) => {
    try {
        const {
            message,
            model,
            temperature,
            maxTokens,
            enableMCP = false,
        } = req.body;

        if (!message) {
            return res
                .status(400)
                .json(utils.formatResponse(false, null, "Message is required"));
        }

        const tools = enableMCP ? mcpClient.getToolDefinitions() : null;
        const systemPrompt = enableMCP
            ? "You are a helpful assistant with access to WordPress management tools. Use them when users ask about WordPress."
            : null;

        const result = await ollamaOnly.chat(message, {
            model: model,
            temperature: temperature,
            maxTokens: maxTokens,
            tools: tools,
            mcpClient: enableMCP ? mcpClient : null,
            systemPrompt: systemPrompt,
        });

        res.json(
            utils.formatResponse(
                result.success,
                result,
                "Ollama chat response",
            ),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

app.post("/api/ollama/switch", async (req, res) => {
    try {
        const { model } = req.body;

        if (!model) {
            return res
                .status(400)
                .json(
                    utils.formatResponse(false, null, "Model name is required"),
                );
        }

        const result = ollamaOnly.switchModel(model);

        res.json(
            utils.formatResponse(
                result.success,
                result,
                result.success ? "Model switched" : "Switch failed",
            ),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

app.get("/api/ollama/models", async (req, res) => {
    try {
        const models = await ollamaOnly.listModels();
        res.json(utils.formatResponse(true, models, "Available Ollama models"));
    } catch (error) {
        utils.handleError(res, error);
    }
});

// =============================================================================
// MCP ENDPOINTS
// =============================================================================

app.post("/api/mcp/call", async (req, res) => {
    try {
        const { tool, args } = req.body;

        if (!tool) {
            return res
                .status(400)
                .json(utils.formatResponse(false, null, "Tool name required"));
        }

        const result = await mcpClient.callTool(tool, args || {});

        res.json(
            utils.formatResponse(true, result, `MCP tool ${tool} executed`),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

app.get("/api/mcp/tools", (req, res) => {
    const tools = mcpClient.getToolDefinitions();
    res.json(
        utils.formatResponse(
            true,
            {
                count: tools.length,
                tools: tools,
            },
            "Available MCP tools",
        ),
    );
});

// =============================================================================
// MAIN PAGE
// =============================================================================

app.get("/", (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${config.app.name}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; background: #f5f5f5; }
                .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; }
                .badge { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.9em; margin: 5px; display: inline-block; }
                .section { margin: 30px 0; }
                .endpoint { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; border-radius: 4px; }
                .endpoint h4 { margin: 0 0 10px 0; color: #333; }
                .endpoint code { background: #e8e8e8; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
                .endpoint p { margin: 5px 0; color: #666; font-size: 0.95em; }
                .cli { background: #1e1e1e; color: #e8e8e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .cli code { color: #10b981; background: transparent; }
                h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 ${config.app.name}</h1>
                    <p>Version ${config.app.version} | ${config.app.environment}</p>
                    <div>
                        <span class="badge">☁️ Ollama Cloud</span>
                        <span class="badge">💻 Ollama Local</span>
                        <span class="badge">🤖 Gemini AI</span>
                        <span class="badge">🔧 ${mcpClient.getToolDefinitions().length} MCP Tools</span>
                    </div>
                </div>

                <div class="section">
                    <h2>🤖 AI Chat Endpoints</h2>

                    <div class="endpoint">
                        <h4>POST <code>/api/chat-with-tools</code></h4>
                        <p>Hybrid AI chat with WordPress MCP tools (Ollama Cloud + Gemini fallback)</p>
                        <p><strong>Body:</strong> <code>{"messages": [{"content": "List my plugins"}], "provider": "cloud"}</code></p>
                    </div>

                    <div class="endpoint">
                        <h4>POST <code>/api/ollama/chat</code></h4>
                        <p>Ollama-only chat (cloud or local models)</p>
                        <p><strong>Body:</strong> <code>{"message": "Hello", "enableMCP": true}</code></p>
                    </div>
                </div>

                <div class="section">
                    <h2>🔧 WordPress MCP Tools</h2>

                    <div class="endpoint">
                        <h4>GET <code>/api/mcp/tools</code></h4>
                        <p>List all ${mcpClient.getToolDefinitions().length} available WordPress tools</p>
                    </div>

                    <div class="endpoint">
                        <h4>POST <code>/api/mcp/call</code></h4>
                        <p>Direct call to WordPress MCP tools</p>
                        <p><strong>Body:</strong> <code>{"tool": "wp_list_plugins", "args": {}}</code></p>
                    </div>
                </div>

                <div class="section">
                    <h2>📊 Status Endpoints</h2>

                    <div class="endpoint">
                        <h4>GET <code>/health</code></h4>
                        <p>Server health check with AI system status</p>
                    </div>

                    <div class="endpoint">
                        <h4>GET <code>/api/status</code></h4>
                        <p>Complete system status (AI providers, MCP, memory)</p>
                    </div>

                    <div class="endpoint">
                        <h4>GET <code>/api/ai/stats</code></h4>
                        <p>AI usage statistics and performance metrics</p>
                    </div>

                    <div class="endpoint">
                        <h4>GET <code>/api/ollama/models</code></h4>
                        <p>List available Ollama models (cloud and local)</p>
                    </div>
                </div>

                <div class="section">
                    <h2>💻 CLI Commands</h2>
                    <div class="cli">
                        <p><code>npm run chat</code> - Regular Gemini chat</p>
                        <p><code>npm run chatwmcp</code> - Gemini chat with MCP tools</p>
                        <p><code>npm run ollamachat</code> - Ollama-only chat with MCP</p>
                    </div>
                </div>

                <div class="section">
                    <h2>🦙 Available MCP Tools</h2>
                    <ul>
                        <li><strong>wp_list_plugins</strong> - List WordPress plugins</li>
                        <li><strong>wp_get_posts</strong> - Get WordPress posts (10 default)</li>
                        <li><strong>wp_get_post</strong> - Get specific post by ID</li>
                        <li><strong>wp_create_post</strong> - Create new post/page</li>
                        <li><strong>wp_update_post</strong> - Update existing post</li>
                        <li><strong>wp_get_users</strong> - Get WordPress users</li>
                        <li><strong>wp_count_posts</strong> - Count posts by status</li>
                        <li><strong>wp_upload_media</strong> - Upload media from URL</li>
                    </ul>
                </div>
            </div>
        </body>
        </html>
    `);
});

// Error handlers
app.use((req, res) => {
    res.status(404).json(
        utils.formatResponse(
            false,
            null,
            `Route ${req.method} ${req.path} not found`,
        ),
    );
});

app.use((error, req, res, next) => {
    console.error("Unhandled Error:", error);
    res.status(500).json(
        utils.formatResponse(false, null, "Internal server error"),
    );
});

// =============================================================================
// SERVER STARTUP
// =============================================================================

async function initializeServer() {
    try {
        await fs.mkdir("./logs", { recursive: true });
        await fs.mkdir("./data", { recursive: true });

        console.log("🔄 Initializing AI managers...");
        await new Promise((resolve) => setTimeout(resolve, 2000));

        app.listen(PORT, () => {
            console.log(`
╔══════════════════════════════════════════════════════════════╗
║          🚀 MULTI-AI SERVER WITH MCP TOOLS                  ║
╠══════════════════════════════════════════════════════════════╣
║  Server:     http://localhost:${PORT}                          ║
║  MCP Relay:  ${config.mcp.relayUrl}                    ║
║  Environment: ${config.app.environment.padEnd(30)} ║
║  MCP Tools:  ✅ ${mcpClient.getToolDefinitions().length} WordPress tools available         ║
╠══════════════════════════════════════════════════════════════╣
║  📋 Endpoints:                                               ║
║     • GET    /health                                         ║
║     • GET    /api/status                                     ║
║     • POST   /api/chat-with-tools                            ║
║     • POST   /api/ollama/chat                                ║
║     • POST   /api/mcp/call                                   ║
║     • GET    /api/mcp/tools                                  ║
╠══════════════════════════════════════════════════════════════╣
║  🤖 AI Providers:                                            ║
║     • Ollama Cloud (FREE, 0 RAM)                             ║
║     • Ollama Local (offline capable)                         ║
║     • Google Gemini (fallback)                               ║
╠══════════════════════════════════════════════════════════════╣
║  💻 CLI Commands:                                            ║
║     • npm run chat        - Gemini chat                      ║
║     • npm run chatwmcp    - Gemini + MCP                     ║
║     • npm run ollamachat  - Ollama + MCP                     ║
╚══════════════════════════════════════════════════════════════╝

✅ Server ready! Visit http://localhost:${PORT}
            `);
        });
    } catch (error) {
        console.error("Server initialization failed:", error);
        process.exit(1);
    }
}

process.on("SIGTERM", () => {
    console.log("SIGTERM received, shutting down gracefully");
    process.exit(0);
});

process.on("SIGINT", () => {
    console.log("SIGINT received, shutting down gracefully");
    process.exit(0);
});

initializeServer();

module.exports = app;
