#!/usr/bin/env node

/**
 * Interactive Gemini AI Chat with WordPress MCP Tools + Exa AI
 * Usage: npm run chatwmcp
 */
require("dotenv").config();

const readline = require("readline");
const { GoogleGenerativeAI } = require("@google/generative-ai");
const { callExaTool, EXA_TOOLS } = require("./exa-tools");

const MCP_RELAY_URL = "https://maniainc.com/wp-json/mcp/v1/sse";
const decodedToken = Buffer.from(process.env.WP_MCP_TOKEN_BASE64 || '', 'base64').toString('utf8');
const MCP_BEARER_TOKEN = `Bearer ${decodedToken}`;

// MCP Tools Client
async function callMCPTool(toolName, args = {}) {
    try {

        const response = await fetch(MCP_RELAY_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: MCP_BEARER_TOKEN,
            },
            body: JSON.stringify({
                jsonrpc: "2.0",
                id: Date.now(),
                method: "tools/call",
                params: {
                    name: toolName,
                    arguments: args,
                },
            }),
        });

        if (!response.ok) {
            throw new Error(`MCP Error: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        return { error: error.message };
    }
}

// Unified tool caller - handles both MCP and Exa tools
async function callTool(toolName, args = {}) {
    // Check if it's an Exa tool
    if (toolName.startsWith("exa_")) {
        return await callExaTool(toolName, args);
    }
    // Otherwise it's a WordPress MCP tool
    return await callMCPTool(toolName, args);
}

// MCP Tool Definitions for Gemini format
const mcpTools = [
    {
        name: "wp_list_plugins",
        description:
            "List all installed WordPress plugins with their names and versions",
        parameters: {
            type: "object",
            properties: {
                search: {
                    type: "string",
                    description: "Optional search term to filter plugins",
                },
            },
        },
    },
    {
        name: "wp_get_posts",
        description: "Get WordPress posts with filtering options",
        parameters: {
            type: "object",
            properties: {
                post_type: {
                    type: "string",
                    description: "Post type (post, page, etc)",
                },
                post_status: {
                    type: "string",
                    description: "Status (publish, draft, etc)",
                },
                search: { type: "string", description: "Search term" },
                limit: {
                    type: "integer",
                    description: "Number of posts to return (default 10)",
                },
            },
        },
    },
    {
        name: "wp_get_post",
        description: "Get a specific WordPress post by ID",
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
        description: "Create a new WordPress post",
        parameters: {
            type: "object",
            properties: {
                post_title: { type: "string", description: "Post title" },
                post_content: {
                    type: "string",
                    description: "Post content (Markdown supported)",
                },
                post_status: {
                    type: "string",
                    description: "Status (draft, publish)",
                },
                post_type: { type: "string", description: "Type (post, page)" },
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
                ID: { type: "integer", description: "Post ID to update" },
                fields: { type: "object", description: "Fields to update" },
            },
            required: ["ID"],
        },
    },
    {
        name: "wp_get_users",
        description: "Get WordPress users",
        parameters: {
            type: "object",
            properties: {
                search: { type: "string", description: "Search term" },
                role: { type: "string", description: "Filter by role" },
                limit: {
                    type: "integer",
                    description: "Number to return (default 10)",
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
        description: "Upload image from URL to WordPress Media Library",
        parameters: {
            type: "object",
            properties: {
                url: { type: "string", description: "Image URL to download" },
                title: { type: "string", description: "Media title" },
                alt: { type: "string", description: "Alt text" },
            },
            required: ["url"],
        },
    },
    {
        name: "mwai_vision",
        description: "Analyze an image using AI vision",
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
        description: "Generate an image using AI and save to Media Library",
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

// Combine WordPress MCP tools with Exa tools
const allTools = [...mcpTools, ...EXA_TOOLS.map((t) => t.function)];

// Initialize Gemini
const apiKey = process.env.GEMINI_API_KEY;
if (!apiKey) {
    console.error("❌ Error: GEMINI_API_KEY environment variable not set");
    console.log("Set it with: export GEMINI_API_KEY=your_key_here");
    process.exit(1);
}

// Check for Exa API key
const exaEnabled = !!process.env.EXA_API_KEY;
if (!exaEnabled) {
    console.log("⚠️  EXA_API_KEY not set - Exa search tools disabled");
    console.log("   Get one at: https://exa.ai");
}

const genAI = new GoogleGenerativeAI(apiKey);

// Model selection with fallback
const PREFERRED_MODELS = [
    "gemini-2.5-pro",
    "gemini-2.5-flash",
    "gemini-2.5-lite",
];
let selectedModel = "gemini-2.5-flash"; // default fallback

async function selectBestModel() {
    try {
        const models = await genAI.listModels();
        const availableModels = models
            .filter((m) =>
                m.supportedGenerationMethods?.includes("generateContent"),
            )
            .map((m) => m.name.replace("models/", ""));

        // Try to find the best available model
        for (const preferred of PREFERRED_MODELS) {
            if (availableModels.includes(preferred)) {
                selectedModel = preferred;
                console.log(`✅ Using model: ${selectedModel}`);
                return selectedModel;
            }
        }

        // Use first available if none of the preferred are found
        if (availableModels.length > 0) {
            selectedModel = availableModels[0];
            console.log(`✅ Using first available model: ${selectedModel}`);
        } else {
            console.log(`⚠️  Using default model: ${selectedModel}`);
        }
    } catch (error) {
        console.log(
            `⚠️  Could not list models, using default: ${selectedModel}`,
        );
    }
    return selectedModel;
}

// Initialize model (will be set after selection)
let model;
let chat;

// Terminal interface
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
    prompt: "\n💬 You: ",
});

(async () => {
    await selectBestModel();

    // Only include enabled tools
    const enabledTools = exaEnabled ? allTools : mcpTools;

    model = genAI.getGenerativeModel({
        model: selectedModel,
        tools: [{ functionDeclarations: enabledTools }],
    });

    // Chat session
    chat = model.startChat({
        history: [],
        generationConfig: {
            maxOutputTokens: 2000,
            temperature: 0.7,
        },
    });

    const toolCount = enabledTools.length;
    const exaStatus = exaEnabled ? "✅" : "❌";

    console.log(`
╔═══════════════════════════════════════════════════════════════╗
║     🤖 GEMINI AI CHAT WITH MCP + EXA TOOLS                   ║
╠═══════════════════════════════════════════════════════════════╣
║  Model: ${selectedModel.padEnd(45)} ║
║  WordPress MCP: ${MCP_RELAY_URL}                      ║
║  Exa AI Search: ${exaStatus} ${exaEnabled ? "ENABLED" : "DISABLED (set EXA_API_KEY)"}                      ║
║  Total Tools: ${toolCount.toString().padEnd(47)} ║
╠═══════════════════════════════════════════════════════════════╣
║  Commands:                                                    ║
║    /tools    - List available tools                           ║
║    /clear    - Clear chat history                             ║
║    /exit     - Exit chat                                      ║
╚═══════════════════════════════════════════════════════════════╝

WordPress Examples:
  • "List all my WordPress plugins"
  • "Show me the latest 5 posts"
  • "Create a draft post about AI"

${
    exaEnabled
        ? `Exa Search Examples:
  • "Search for recent AI news using Exa"
  • "Find articles similar to https://example.com"
  • "Search for research papers about quantum computing"
`
        : ""
}
`);

    rl.prompt();
})();

rl.on("line", async (input) => {
    const userInput = input.trim();

    if (!userInput) {
        rl.prompt();
        return;
    }

    // Handle commands
    if (userInput === "/exit") {
        console.log("\n👋 Goodbye!\n");
        process.exit(0);
    }

    if (userInput === "/clear") {
        const enabledTools = exaEnabled ? allTools : mcpTools;
        chat = model.startChat({
            history: [],
            generationConfig: {
                maxOutputTokens: 2000,
                temperature: 0.7,
            },
        });
        console.log("✅ Chat history cleared\n");
        rl.prompt();
        return;
    }

    if (userInput === "/tools") {
        console.log("\n📋 Available Tools:\n");

        console.log("🔧 WordPress MCP Tools:");
        mcpTools.forEach((tool, i) => {
            console.log(`   ${i + 1}. ${tool.name}`);
            console.log(`      ${tool.description}\n`);
        });

        if (exaEnabled) {
            console.log("🔍 Exa AI Search Tools:");
            EXA_TOOLS.forEach((tool, i) => {
                console.log(`   ${i + 1}. ${tool.function.name}`);
                console.log(`      ${tool.function.description}\n`);
            });
        } else {
            console.log("🔍 Exa AI Search: ❌ DISABLED");
            console.log("   Set EXA_API_KEY to enable web search\n");
        }

        rl.prompt();
        return;
    }

    try {
        // Send message to Gemini
        console.log("\n🤖 Gemini: Thinking...\n");

        const result = await chat.sendMessage(userInput);
        const response = result.response;

        // Check for function calls
        const functionCalls = response.functionCalls();

        if (functionCalls && functionCalls.length > 0) {
            console.log("🔧 Executing tools...\n");

            // Execute all function calls
            const functionResults = [];
            for (const call of functionCalls) {
                const toolIcon = call.name.startsWith("exa_") ? "🔍" : "🔧";
                console.log(
                    `   ${toolIcon} Calling: ${call.name}(${JSON.stringify(call.args)})`,
                );

                const toolResult = await callTool(call.name, call.args);

                functionResults.push({
                    functionResponse: {
                        name: call.name,
                        response: toolResult,
                    },
                });

                // Show summary of result
                if (toolResult.data) {
                    if (Array.isArray(toolResult.data)) {
                        console.log(
                            `   ✓ Returned ${toolResult.data.length} items`,
                        );
                    } else {
                        console.log(`   ✓ Success`);
                    }
                } else if (toolResult.results) {
                    // Exa search results
                    console.log(
                        `   ✓ Found ${toolResult.results.length} results`,
                    );
                } else if (toolResult.error) {
                    console.log(`   ✗ Error: ${toolResult.error}`);
                } else {
                    console.log(`   ✓ Success`);
                }
            }

            // Send function results back to Gemini
            console.log("\n🤖 Gemini: Processing results...\n");
            const result2 = await chat.sendMessage(functionResults);
            const finalResponse = result2.response;

            console.log(`🤖 Gemini: ${finalResponse.text()}\n`);
        } else {
            // Regular text response
            console.log(`🤖 Gemini: ${response.text()}\n`);
        }
    } catch (error) {
        console.error(`\n❌ Error: ${error.message}\n`);
    }

    rl.prompt();
});

rl.on("close", () => {
    console.log("\n👋 Goodbye!\n");
    process.exit(0);
});

// Handle errors
process.on("unhandledRejection", (error) => {
    console.error("\n❌ Unhandled error:", error.message);
    rl.prompt();
});
