#!/usr/bin/env node

/**
 * Interactive Gemini AI Chat with WordPress MCP Tools
 * Usage: npm run chatwmcp
 */

const readline = require("readline");
const { GoogleGenerativeAI } = require("@google/generative-ai");

const MCP_RELAY_URL = "http://localhost:3001";
const MCP_BEARER_TOKEN = "Bearer uX484&B$k@c@6072&VdTJi#3";

// MCP Tools Client
async function callMCPTool(toolName, args = {}) {
    try {
        const response = await fetch(MCP_RELAY_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: MCP_BEARER_TOKEN,
            },
            body: JSON.stringify({ tool: toolName, args }),
        });

        if (!response.ok) {
            throw new Error(`MCP Error: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        return { error: error.message };
    }
}

// MCP Tool Definitions
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

// Initialize Gemini
const apiKey = process.env.GEMINI_API_KEY;
if (!apiKey) {
    console.error("❌ Error: GEMINI_API_KEY environment variable not set");
    console.log("Set it with: export GEMINI_API_KEY=your_key_here");
    process.exit(1);
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

    model = genAI.getGenerativeModel({
        model: selectedModel,
        tools: [{ functionDeclarations: mcpTools }],
    });

    // Chat session
    chat = model.startChat({
        history: [],
        generationConfig: {
            maxOutputTokens: 2000,
            temperature: 0.7,
        },
    });

    console.log(`
╔═══════════════════════════════════════════════════════════════╗
║     🤖 GEMINI AI CHAT WITH WORDPRESS MCP TOOLS               ║
╠═══════════════════════════════════════════════════════════════╣
║  Model: ${selectedModel.padEnd(45)} ║
║  Connected to: ${MCP_RELAY_URL}                      ║
║  MCP Tools: ${mcpTools.length} WordPress tools available               ║
╠═══════════════════════════════════════════════════════════════╣
║  Commands:                                                    ║
║    /tools    - List available MCP tools                       ║
║    /clear    - Clear chat history                             ║
║    /exit     - Exit chat                                      ║
╚═══════════════════════════════════════════════════════════════╝

Try asking:
  • "List all my WordPress plugins"
  • "Show me the latest 5 posts"
  • "Create a draft post about AI"
  • "How many posts do I have?"
  • "Get user info for user ID 1"
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
        console.log("\n📋 Available MCP Tools:\n");
        mcpTools.forEach((tool, i) => {
            console.log(`${i + 1}. ${tool.name}`);
            console.log(`   ${tool.description}\n`);
        });
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
            console.log("🔧 Executing MCP tools...\n");

            // Execute all function calls
            const functionResults = [];
            for (const call of functionCalls) {
                console.log(
                    `   → Calling: ${call.name}(${JSON.stringify(call.args)})`,
                );

                const toolResult = await callMCPTool(call.name, call.args);

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
                } else if (toolResult.error) {
                    console.log(`   ✗ Error: ${toolResult.error}`);
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
