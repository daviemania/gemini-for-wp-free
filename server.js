/**
 * Gemini AI Project - Main Application Server with MCP Integration
 * A comprehensive Express.js server with Gemini AI and WordPress MCP tools
 * Features: API routes, middleware, error handling, security, logging, MCP tools
 */

const express = require("express");
const { GoogleGenerativeAI } = require("@google/generative-ai");
const path = require("path");
const fs = require("fs").promises;

// Initialize Express app
const app = express();
const PORT = process.env.PORT || 3000;

// =============================================================================
// CONFIGURATION & CONSTANTS
// =============================================================================

const config = {
    app: {
        name: "Gemini AI Project with MCP",
        version: "2.0.0",
        environment: process.env.NODE_ENV || "development",
    },
    gemini: {
        defaultModel: "gemini-pro",
        fallbackModels: ["gemini-1.5-pro", "gemini-1.5-flash", "gemini-pro"],
        maxOutputTokens: 2000,
        temperature: 0.7,
    },
    mcp: {
        relayUrl: "http://localhost:3001",
        bearerToken: "Bearer uX484&B$k@c@6072&VdTJi#3",
    },
    security: {
        rateLimit: {
            windowMs: 15 * 60 * 1000,
            max: 100,
        },
    },
};

// =============================================================================
// MIDDLEWARE SETUP
// =============================================================================

app.use(express.json({ limit: "10mb" }));
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, "public")));

// Custom logging middleware
app.use((req, res, next) => {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${req.method} ${req.path} - IP: ${req.ip}`);
    next();
});

// =============================================================================
// MCP TOOLS CLIENT
// =============================================================================

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

    // Tool definitions for function calling
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
            {
                name: "mwai_vision",
                description: "Analyze an image using AI Engine Vision",
                parameters: {
                    type: "object",
                    properties: {
                        message: {
                            type: "string",
                            description: "Question about the image",
                        },
                        url: { type: "string", description: "Image URL" },
                    },
                    required: ["message"],
                },
            },
            {
                name: "mwai_image",
                description:
                    "Generate an image using AI and save to Media Library",
                parameters: {
                    type: "object",
                    properties: {
                        message: {
                            type: "string",
                            description: "Image generation prompt",
                        },
                        title: { type: "string", description: "Image title" },
                    },
                    required: ["message"],
                },
            },
        ];
    }
}

// Initialize MCP client
const mcpClient = new MCPClient(config.mcp.relayUrl, config.mcp.bearerToken);

// =============================================================================
// GEMINI AI SERVICE WITH FUNCTION CALLING
// =============================================================================

class GeminiAIService {
    constructor() {
        this.genAI = new GoogleGenerativeAI(
            process.env.GEMINI_API_KEY || "your-api-key-here",
        );
        this.isConfigured = !!process.env.GEMINI_API_KEY;
        this.currentModel = null;
        this.availableModels = [];

        if (this.isConfigured) {
            this.initializeModel();
        }
    }

    async initializeModel() {
        try {
            // Try to list available models
            const models = await this.genAI.listModels();
            this.availableModels = models
                .filter((m) =>
                    m.supportedGenerationMethods?.includes("generateContent"),
                )
                .map((m) => m.name.replace("models/", ""));

            console.log(
                `✅ Found ${this.availableModels.length} available Gemini models`,
            );

            // Try to find the best available model
            for (const modelName of config.gemini.fallbackModels) {
                if (this.availableModels.includes(modelName)) {
                    this.currentModel = modelName;
                    console.log(`✅ Using Gemini model: ${this.currentModel}`);
                    break;
                }
            }

            // If no preferred model found, use the first available
            if (!this.currentModel && this.availableModels.length > 0) {
                this.currentModel = this.availableModels[0];
                console.log(
                    `✅ Using first available model: ${this.currentModel}`,
                );
            }

            if (!this.currentModel) {
                this.currentModel = config.gemini.defaultModel;
                console.log(
                    `⚠️  No models detected, using default: ${this.currentModel}`,
                );
            }
        } catch (error) {
            console.log(`⚠️  Could not list models: ${error.message}`);
            console.log(
                `⚠️  Using default model: ${config.gemini.defaultModel}`,
            );
            this.currentModel = config.gemini.defaultModel;
        }
    }

    getModel() {
        if (!this.currentModel) {
            this.currentModel = config.gemini.defaultModel;
        }

        return this.genAI.getGenerativeModel({
            model: this.currentModel,
            generationConfig: {
                maxOutputTokens: config.gemini.maxOutputTokens,
                temperature: config.gemini.temperature,
            },
        });
    }

    async generateContent(prompt, options = {}) {
        if (!this.isConfigured) {
            throw new Error(
                "Gemini API key not configured. Set GEMINI_API_KEY environment variable.",
            );
        }

        try {
            const model = this.getModel();
            const result = await model.generateContent(prompt);
            const response = await result.response;

            return {
                success: true,
                text: response.text(),
                model: this.currentModel,
                usage: {
                    promptTokens: response.usageMetadata?.promptTokenCount || 0,
                    candidatesTokens:
                        response.usageMetadata?.candidatesTokenCount || 0,
                    totalTokens: response.usageMetadata?.totalTokenCount || 0,
                },
            };
        } catch (error) {
            console.error("Gemini AI Error:", error);
            return {
                success: false,
                error: error.message,
                code: error.code || "UNKNOWN_ERROR",
            };
        }
    }

    async chatWithTools(messages, tools) {
        if (!this.isConfigured) {
            throw new Error("Gemini API key not configured.");
        }

        const modelWithTools = this.genAI.getGenerativeModel({
            model: this.currentModel || config.gemini.defaultModel,
            tools: [{ functionDeclarations: tools }],
        });

        const chat = modelWithTools.startChat({
            history: messages.slice(0, -1),
        });

        const lastMessage = messages[messages.length - 1];
        const result = await chat.sendMessage(
            lastMessage.content || lastMessage,
        );
        const response = await result.response;

        // Check if function calls were made
        const functionCalls = response.functionCalls();

        return {
            text: response.text(),
            functionCalls: functionCalls || [],
            response: response,
            model: this.currentModel,
        };
    }

    getStatus() {
        return {
            configured: this.isConfigured,
            model: this.currentModel || config.gemini.defaultModel,
            availableModels: this.availableModels.length,
            maxTokens: config.gemini.maxOutputTokens,
            temperature: config.gemini.temperature,
        };
    }
}

// Initialize Gemini service
const geminiService = new GeminiAIService();

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

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
app.get("/health", (req, res) => {
    res.json(
        utils.formatResponse(
            true,
            {
                status: "healthy",
                environment: config.app.environment,
                uptime: process.uptime(),
                gemini: geminiService.getStatus(),
                mcp: { connected: true, endpoint: config.mcp.relayUrl },
            },
            "Server is running normally",
        ),
    );
});

// API status
app.get("/api/status", (req, res) => {
    res.json(
        utils.formatResponse(true, {
            app: config.app,
            server: {
                nodeVersion: process.version,
                platform: process.platform,
                uptime: Math.floor(process.uptime()),
            },
            gemini: geminiService.getStatus(),
            mcp: {
                relayUrl: config.mcp.relayUrl,
                toolsAvailable: mcpClient.getToolDefinitions().length,
            },
        }),
    );
});

// Main Gemini AI endpoint
app.post("/api/generate", async (req, res) => {
    try {
        const { prompt, options } = req.body;
        const validationError = utils.validatePrompt(prompt);
        if (validationError) {
            return res
                .status(400)
                .json(utils.formatResponse(false, null, validationError));
        }

        const result = await geminiService.generateContent(prompt, options);

        if (result.success) {
            res.json(
                utils.formatResponse(
                    true,
                    {
                        generatedText: result.text,
                        usage: result.usage,
                    },
                    "Content generated successfully",
                ),
            );
        } else {
            res.status(500).json(
                utils.formatResponse(false, null, `AI Error: ${result.error}`),
            );
        }
    } catch (error) {
        utils.handleError(res, error);
    }
});

// Chat with MCP function calling
app.post("/api/chat-with-tools", async (req, res) => {
    try {
        const { messages } = req.body;

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

        const tools = mcpClient.getToolDefinitions();
        const result = await geminiService.chatWithTools(messages, tools);

        // Execute function calls if any
        const functionResults = [];
        if (result.functionCalls && result.functionCalls.length > 0) {
            for (const call of result.functionCalls) {
                console.log(`Executing MCP tool: ${call.name}`);
                try {
                    const toolResult = await mcpClient.callTool(
                        call.name,
                        call.args,
                    );
                    functionResults.push({
                        tool: call.name,
                        args: call.args,
                        result: toolResult,
                    });
                } catch (error) {
                    functionResults.push({
                        tool: call.name,
                        args: call.args,
                        error: error.message,
                    });
                }
            }
        }

        res.json(
            utils.formatResponse(
                true,
                {
                    response: result.text,
                    functionCalls: functionResults,
                    messageCount: messages.length + 1,
                },
                "Chat response with tools generated",
            ),
        );
    } catch (error) {
        utils.handleError(res, error);
    }
});

// Direct MCP tool call
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

// List available MCP tools
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

// Main page
app.get("/", (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${config.app.name}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                .container { max-width: 900px; margin: 0 auto; }
                .header { background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                .endpoint { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
                .mcp-tools { background: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #0066cc; }
                code { background: #eee; padding: 2px 6px; border-radius: 3px; }
                .badge { background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 ${config.app.name}</h1>
                    <p>Version ${config.app.version} | Environment: ${config.app.environment}</p>
                    <p><span class="badge">MCP ENABLED</span> ${mcpClient.getToolDefinitions().length} WordPress tools available</p>
                </div>

                <h2>Gemini AI Endpoints</h2>

                <div class="endpoint">
                    <h3>POST <code>/api/generate</code></h3>
                    <p>Generate content using Gemini AI</p>
                    <p><strong>Body:</strong> <code>{"prompt": "Your prompt here"}</code></p>
                </div>

                <div class="endpoint">
                    <h3>POST <code>/api/chat-with-tools</code> <span class="badge">NEW</span></h3>
                    <p>Chat with Gemini AI that can call WordPress MCP tools</p>
                    <p><strong>Body:</strong> <code>{"messages": [{"role": "user", "content": "List my plugins"}]}</code></p>
                </div>

                <h2>MCP WordPress Tools</h2>

                <div class="mcp-tools">
                    <h3>POST <code>/api/mcp/call</code></h3>
                    <p>Direct call to any WordPress MCP tool</p>
                    <p><strong>Body:</strong> <code>{"tool": "wp_list_plugins", "args": {}}</code></p>
                </div>

                <div class="mcp-tools">
                    <h3>GET <code>/api/mcp/tools</code></h3>
                    <p>List all available MCP tools with descriptions</p>
                </div>

                <h2>Available MCP Tools</h2>
                <ul>
                    <li><code>wp_list_plugins</code> - List WordPress plugins</li>
                    <li><code>wp_get_posts</code> - Get WordPress posts</li>
                    <li><code>wp_create_post</code> - Create new post</li>
                    <li><code>wp_update_post</code> - Update existing post</li>
                    <li><code>wp_get_users</code> - Get WordPress users</li>
                    <li><code>wp_upload_media</code> - Upload media from URL</li>
                    <li><code>mwai_vision</code> - Analyze images with AI</li>
                    <li><code>mwai_image</code> - Generate images with AI</li>
                    <li>...and ${mcpClient.getToolDefinitions().length - 8} more!</li>
                </ul>

                <h2>CLI Usage</h2>
                <p>Start interactive chat with MCP tools: <code>npm run chatwmcp</code></p>
                <p>Regular chat without tools: <code>npm run chat</code></p>
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

        app.listen(PORT, () => {
            console.log(`
╔══════════════════════════════════════════════════════════════╗
║          🚀 GEMINI AI SERVER WITH MCP TOOLS                 ║
╠══════════════════════════════════════════════════════════════╣
║  Server:     http://localhost:${PORT}                          ║
║  MCP Relay:  ${config.mcp.relayUrl}                    ║
║  Environment: ${config.app.environment.padEnd(30)} ║
║  Gemini AI:  ${geminiService.isConfigured ? "✅ Configured" : "⚠️  API Key Needed"}${geminiService.isConfigured ? "".padEnd(23) : "".padEnd(22)}║
║  MCP Tools:  ✅ ${mcpClient.getToolDefinitions().length} tools available${" ".padEnd(19)}║
╚══════════════════════════════════════════════════════════════╝

📋 API Endpoints:
   • GET    /health                  - Health check
   • POST   /api/generate            - AI content generation
   • POST   /api/chat-with-tools     - Chat with MCP function calling
   • POST   /api/mcp/call            - Direct MCP tool execution
   • GET    /api/mcp/tools           - List available tools

🔧 CLI Commands:
   • npm run chat                   - Regular Gemini chat
   • npm run chatwmcp               - Chat with MCP tools enabled

${!geminiService.isConfigured ? "⚠️  REMINDER: Set GEMINI_API_KEY environment variable" : "✅ Ready to process AI requests with WordPress integration!"}
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
