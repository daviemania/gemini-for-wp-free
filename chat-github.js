#!/usr/bin/env node

/**
 * GitHub Models Chat - Advanced Edition with MCP Integration
 * CommonJS version (no ES modules required)
 * Usage: npm run githubchat
 */

const ModelClient = require("@azure-rest/ai-inference").default;
const { isUnexpected } = require("@azure-rest/ai-inference");
const { AzureKeyCredential } = require("@azure/core-auth");
const inquirer = require("inquirer");
const chalk = require("chalk");
const fs = require("fs-extra");
const path = require("path");
const yaml = require("yaml");
const https = require("https");
const { URL } = require("url");

const token = process.env["GITHUB_MODELS_TOKEN"];
const endpoint = "https://models.github.ai/inference";

// Simple fetch implementation using https
function fetch(url, options = {}) {
    return new Promise((resolve, reject) => {
        const parsedUrl = new URL(url);

        const reqOptions = {
            hostname: parsedUrl.hostname,
            port: parsedUrl.port || 443,
            path: parsedUrl.pathname + parsedUrl.search,
            method: options.method || "GET",
            headers: options.headers || {},
        };

        const req = https.request(reqOptions, (res) => {
            let data = "";

            res.on("data", (chunk) => {
                data += chunk;
            });

            res.on("end", () => {
                resolve({
                    ok: res.statusCode >= 200 && res.statusCode < 300,
                    status: res.statusCode,
                    statusText: res.statusMessage,
                    json: async () => JSON.parse(data),
                    text: async () => data,
                });
            });
        });

        req.on("error", reject);

        if (options.body) {
            req.write(options.body);
        }

        req.end();
    });
}

// MCP Configuration
const MCP_ENDPOINT = "http://localhost:3001/mcp";
const MCP_WP_ENDPOINT = "https://maniainc.com/wp-json/mcp/v1/sse";
const MCP_AUTH_TOKEN = "uX484&B$k@c@6072&VdTJi#3";

// Load configuration from YAML
let config = {};
let AVAILABLE_MODELS = {};
const configPath = path.join(process.cwd(), "github-chat.prompt.yml");

let conversationHistory = [];
let currentModel = "gpt-4o-mini";
let systemPrompt = "You are a helpful assistant.";
let streamingEnabled = true;
let mcpEnabled = false;

// MCP Tool Call Function
async function callMCPTool(toolName, args = {}) {
    try {
        const response = await fetch(MCP_WP_ENDPOINT, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${MCP_AUTH_TOKEN}`,
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
            throw new Error(
                `MCP call failed: ${response.status} ${response.statusText}`,
            );
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error(chalk.red(`MCP Error: ${error.message}`));
        return { error: error.message };
    }
}

// Parse AI response for MCP tool calls
function extractMCPCalls(text) {
    const mcpPattern = /\[MCP:(\w+)\s*\((.*?)\)\]/g;
    const calls = [];
    let match;

    while ((match = mcpPattern.exec(text)) !== null) {
        const toolName = match[1];
        let args = {};

        try {
            if (match[2].trim()) {
                args = JSON.parse(`{${match[2]}}`);
            }
        } catch (e) {
            console.error(
                chalk.yellow(`Failed to parse MCP args: ${match[2]}`),
            );
        }

        calls.push({ toolName, args });
    }

    return calls;
}

// Format object for display with proper escaping
function formatObject(obj, indent = "") {
    if (obj === null) return chalk.gray("null");
    if (obj === undefined) return chalk.gray("undefined");

    if (typeof obj !== "object") {
        if (typeof obj === "string") {
            return chalk.white(
                `"${obj.replace(/"/g, '\\"').replace(/\n/g, "\\n")}"`,
            );
        }
        return chalk.white(String(obj));
    }

    if (Array.isArray(obj)) {
        if (obj.length === 0) return chalk.gray("[]");
        return `[${obj.map((item) => formatObject(item)).join(", ")}]`;
    }

    const keys = Object.keys(obj);
    if (keys.length === 0) return chalk.gray("{}");

    const entries = keys.map((key) => {
        const value = obj[key];
        const formattedKey = chalk.blue(key);

        if (typeof value === "object" && value !== null) {
            const formattedValue = formatObject(value, indent + "  ");
            return `${formattedKey}: ${formattedValue}`;
        }

        return `${formattedKey}: ${formatObject(value)}`;
    });

    if (indent === "" && entries.join(", ").length < 80) {
        return `{ ${entries.join(", ")} }`;
    }

    return entries.join(", ");
}

// Save conversation to file
async function saveConversation() {
    const timestamp = new Date().toISOString().replace(/:/g, "-").split(".")[0];
    const filename = `conversation-${timestamp}.json`;
    const conversationsDir = path.join(process.cwd(), "conversations");

    await fs.ensureDir(conversationsDir);

    const data = {
        model: currentModel,
        systemPrompt,
        timestamp: new Date().toISOString(),
        messages: conversationHistory,
    };

    await fs.writeJSON(path.join(conversationsDir, filename), data, {
        spaces: 2,
    });
    console.log(chalk.green(`\n💾 Conversation saved to: ${filename}\n`));
}

// Load conversation from file
async function loadConversation() {
    const conversationsDir = path.join(process.cwd(), "conversations");

    if (!(await fs.pathExists(conversationsDir))) {
        console.log(chalk.yellow("\n⚠️  No saved conversations found.\n"));
        return;
    }

    const files = (await fs.readdir(conversationsDir)).filter((f) =>
        f.endsWith(".json"),
    );

    if (files.length === 0) {
        console.log(chalk.yellow("\n⚠️  No saved conversations found.\n"));
        return;
    }

    const { selectedFile } = await inquirer.prompt([
        {
            type: "list",
            name: "selectedFile",
            message: "Select a conversation to load:",
            choices: files.map((f) => ({ name: f, value: f })),
        },
    ]);

    const data = await fs.readJSON(path.join(conversationsDir, selectedFile));
    conversationHistory = data.messages;
    currentModel = data.model || currentModel;
    systemPrompt = data.systemPrompt || systemPrompt;

    console.log(
        chalk.green(
            `\n✅ Loaded conversation with ${conversationHistory.length} messages\n`,
        ),
    );
}

// Stream chat response
async function chatWithStreaming(userMessage, customSystemPrompt = null) {
    const client = ModelClient(endpoint, new AzureKeyCredential(token));

    conversationHistory.push({ role: "user", content: userMessage });

    const response = await client
        .path("/chat/completions")
        .post({
            body: {
                messages: [
                    {
                        role: "system",
                        content: customSystemPrompt || systemPrompt,
                    },
                    ...conversationHistory,
                ],
                temperature: 0.7,
                top_p: 0.95,
                max_tokens: 2000,
                model: currentModel,
                stream: true,
            },
        })
        .asNodeStream();

    if (isUnexpected(response)) {
        throw response.body.error;
    }

    process.stdout.write(chalk.cyan("\nAssistant: "));

    let fullResponse = "";
    const stream = response.body;
    let buffer = "";

    for await (const chunk of stream) {
        buffer += chunk.toString();
        const lines = buffer.split("\n");
        buffer = lines.pop() || "";

        for (const line of lines) {
            const trimmed = line.trim();
            if (!trimmed || !trimmed.startsWith("data: ")) continue;

            const data = trimmed.slice(6);
            if (data === "[DONE]") continue;

            try {
                const parsed = JSON.parse(data);
                const content = parsed.choices?.[0]?.delta?.content || "";

                if (content) {
                    process.stdout.write(chalk.cyan(content));
                    fullResponse += content;
                }
            } catch (e) {
                // Skip parsing errors
            }
        }
    }

    console.log("\n");
    conversationHistory.push({ role: "assistant", content: fullResponse });
    return fullResponse;
}

// Non-streaming chat
async function chatWithoutStreaming(userMessage, customSystemPrompt = null) {
    const client = ModelClient(endpoint, new AzureKeyCredential(token));

    conversationHistory.push({ role: "user", content: userMessage });

    const response = await client.path("/chat/completions").post({
        body: {
            messages: [
                { role: "system", content: customSystemPrompt || systemPrompt },
                ...conversationHistory,
            ],
            temperature: 0.7,
            top_p: 0.95,
            max_tokens: 2000,
            model: currentModel,
        },
    });

    if (isUnexpected(response)) {
        throw response.body.error;
    }

    const assistantMessage = response.body.choices[0].message.content;
    conversationHistory.push({ role: "assistant", content: assistantMessage });

    return assistantMessage;
}

// Settings menu
async function showSettings() {
    console.log(chalk.blue.bold("\n⚙️  Settings\n"));

    const { action } = await inquirer.prompt([
        {
            type: "list",
            name: "action",
            message: "What would you like to do?",
            choices: [
                {
                    name: `Change Model (current: ${currentModel})`,
                    value: "model",
                },
                {
                    name: `Toggle Streaming (current: ${streamingEnabled ? "ON" : "OFF"})`,
                    value: "streaming",
                },
                {
                    name: `Toggle MCP WordPress Tools (current: ${mcpEnabled ? "ON" : "OFF"})`,
                    value: "mcp",
                },
                { name: "Change System Prompt", value: "system" },
                { name: "View Conversation Stats", value: "stats" },
                { name: "Clear Conversation History", value: "clear" },
                { name: "Save Conversation", value: "save" },
                { name: "Load Conversation", value: "load" },
                { name: "Back to Chat", value: "back" },
            ],
        },
    ]);

    switch (action) {
        case "model":
            const modelChoices = Array.isArray(AVAILABLE_MODELS)
                ? AVAILABLE_MODELS
                : Object.entries(AVAILABLE_MODELS).map(([name, value]) => ({
                      name: `${name} (${value})`,
                      value,
                  }));

            const { selectedModel } = await inquirer.prompt([
                {
                    type: "list",
                    name: "selectedModel",
                    message: "Select a model:",
                    choices: modelChoices,
                },
            ]);
            currentModel = selectedModel;
            console.log(
                chalk.green(`\n✅ Model changed to: ${currentModel}\n`),
            );
            break;

        case "streaming":
            streamingEnabled = !streamingEnabled;
            console.log(
                chalk.green(
                    `\n✅ Streaming ${streamingEnabled ? "enabled" : "disabled"}\n`,
                ),
            );
            break;

        case "mcp":
            mcpEnabled = !mcpEnabled;
            console.log(
                chalk.green(
                    `\n✅ MCP WordPress Tools ${mcpEnabled ? "enabled" : "disabled"}\n`,
                ),
            );
            if (mcpEnabled) {
                console.log(
                    chalk.gray(
                        "💡 You can now ask about WordPress content (posts, plugins, users, etc.)",
                    ),
                );
                console.log(
                    chalk.gray("   Example: 'List all my WordPress plugins'\n"),
                );
            }
            break;

        case "system":
            const { newPrompt } = await inquirer.prompt([
                {
                    type: "input",
                    name: "newPrompt",
                    message: "Enter new system prompt:",
                    default: systemPrompt,
                },
            ]);
            systemPrompt = newPrompt;
            console.log(chalk.green("\n✅ System prompt updated\n"));
            break;

        case "stats":
            const userMsgs = conversationHistory.filter(
                (m) => m.role === "user",
            ).length;
            const assistantMsgs = conversationHistory.filter(
                (m) => m.role === "assistant",
            ).length;
            const totalChars = conversationHistory.reduce(
                (sum, m) => sum + m.content.length,
                0,
            );

            console.log(chalk.blue("\n📊 Conversation Statistics:"));
            console.log(chalk.gray(`   Model: ${currentModel}`));
            console.log(chalk.gray(`   User messages: ${userMsgs}`));
            console.log(chalk.gray(`   Assistant messages: ${assistantMsgs}`));
            console.log(chalk.gray(`   Total characters: ${totalChars}`));
            console.log(
                chalk.gray(`   Streaming: ${streamingEnabled ? "ON" : "OFF"}`),
            );
            console.log(
                chalk.gray(`   MCP Tools: ${mcpEnabled ? "ON" : "OFF"}\n`),
            );
            break;

        case "clear":
            const { confirm } = await inquirer.prompt([
                {
                    type: "confirm",
                    name: "confirm",
                    message:
                        "Are you sure you want to clear the conversation history?",
                    default: false,
                },
            ]);

            if (confirm) {
                conversationHistory = [];
                console.log(chalk.green("\n✅ Conversation history cleared\n"));
            }
            break;

        case "save":
            await saveConversation();
            break;

        case "load":
            await loadConversation();
            break;

        case "back":
            return;
    }

    await showSettings();
}

// Main chat loop
async function main() {
    // Load config at startup
    try {
        if (await fs.pathExists(configPath)) {
            const configFile = await fs.readFile(configPath, "utf8");
            config = yaml.parse(configFile);

            // Build AVAILABLE_MODELS from config
            if (config.models) {
                AVAILABLE_MODELS = Object.values(config.models).flatMap(
                    (category) =>
                        category.map((m) => ({
                            name: `${m.name} (${m.id})`,
                            value: m.id,
                            description: m.best_for,
                        })),
                );
            }
        }
    } catch (error) {
        console.log(
            chalk.yellow(
                "⚠️  Could not load github-chat.prompt.yml, using defaults",
            ),
        );
    }

    // Set default models if config didn't load
    if (!AVAILABLE_MODELS || Object.keys(AVAILABLE_MODELS).length === 0) {
        AVAILABLE_MODELS = {
            "DeepSeek V3": "deepseek/DeepSeek-V3-0324",
            "GPT-4o": "gpt-4o",
            "GPT-4o Mini": "gpt-4o-mini",
            "o1 Preview": "o1-preview",
            "o1 Mini": "o1-mini",
            "Phi-4": "Phi-4",
            "Llama 3.1 405B": "Meta-Llama-3.1-405B-Instruct",
            "Llama 3.1 70B": "Meta-Llama-3.1-70B-Instruct",
            "Llama 3.3 70B": "Meta-Llama-3.3-70B-Instruct",
            "Mistral Large": "Mistral-large-2411",
            "Mistral Small": "Mistral-small",
            "Mistral Nemo": "Mistral-Nemo",
            "Cohere Command R": "cohere-command-r",
            "Cohere Command R+": "cohere-command-r-plus",
            "AI21 Jamba 1.5 Large": "AI21-Jamba-1.5-Large",
            "AI21 Jamba 1.5 Mini": "AI21-Jamba-1.5-Mini",
        };
    }

    if (!token) {
        console.error(
            chalk.red(
                "\n❌ Error: GITHUB_MODELS_TOKEN environment variable not set!",
            ),
        );
        console.log(chalk.yellow("\nPlease set your GitHub Models token:"));
        console.log(
            chalk.gray('  export GITHUB_MODELS_TOKEN="your-token-here"\n'),
        );
        process.exit(1);
    }

    console.log(
        chalk.blue.bold("\n🤖 GitHub Models Chat - Advanced Edition\n"),
    );

    if (config.project) {
        console.log(chalk.gray(`📄 Config: github-chat.prompt.yml loaded`));
    }

    console.log(chalk.gray(`Model: ${currentModel}`));
    console.log(chalk.gray(`Streaming: ${streamingEnabled ? "ON" : "OFF"}`));
    console.log(chalk.gray(`MCP Tools: ${mcpEnabled ? "ON" : "OFF"}`));
    console.log(chalk.gray("\nCommands:"));
    console.log(chalk.gray("  /settings - Open settings menu"));
    console.log(chalk.gray("  /mcp      - Toggle WordPress MCP tools"));
    console.log(chalk.gray("  /save     - Save conversation"));
    console.log(chalk.gray("  /load     - Load conversation"));
    console.log(chalk.gray("  /clear    - Clear conversation"));
    console.log(
        chalk.gray("  /help     - Show help and model recommendations"),
    );
    console.log(chalk.gray("  /exit     - Exit chat\n"));

    while (true) {
        const { message } = await inquirer.prompt([
            {
                type: "input",
                name: "message",
                message: chalk.green("You:"),
                prefix: "💬",
            },
        ]);

        const cmd = message.toLowerCase().trim();

        if (cmd === "/exit" || cmd === "exit" || cmd === "quit") {
            console.log(chalk.yellow("\n👋 Goodbye!\n"));
            break;
        }

        if (cmd === "/settings") {
            await showSettings();
            continue;
        }

        if (cmd === "/save") {
            await saveConversation();
            continue;
        }

        if (cmd === "/load") {
            await loadConversation();
            continue;
        }

        if (cmd === "/clear") {
            conversationHistory = [];
            console.log(chalk.green("\n✅ Conversation cleared\n"));
            continue;
        }

        if (cmd === "/help") {
            console.log(chalk.blue.bold("\n📚 GitHub Models Chat Help\n"));

            if (config.use_cases) {
                console.log(
                    chalk.yellow("🎯 Recommended Models by Use Case:\n"),
                );

                Object.entries(config.use_cases).forEach(
                    ([useCase, details]) => {
                        const caseName = useCase
                            .replace(/_/g, " ")
                            .toUpperCase();
                        console.log(chalk.cyan(`${caseName}:`));
                        if (details.recommended_models) {
                            details.recommended_models.forEach((model) => {
                                console.log(chalk.gray(`  • ${model}`));
                            });
                        }
                        if (details.system_prompt) {
                            console.log(
                                chalk.gray(
                                    `  System prompt: "${details.system_prompt}"`,
                                ),
                            );
                        }
                        console.log();
                    },
                );
            }

            console.log(chalk.yellow("💡 Quick Tips:"));
            console.log(
                chalk.gray("  • Use /settings to change models anytime"),
            );
            console.log(
                chalk.gray("  • Save important conversations with /save"),
            );
            console.log(
                chalk.gray("  • Try different models for the same task"),
            );
            console.log(
                chalk.gray(
                    "  • Use custom system prompts for specialized tasks",
                ),
            );
            console.log(
                chalk.gray("  • Enable MCP tools to interact with WordPress\n"),
            );
            continue;
        }

        if (cmd === "/mcp") {
            mcpEnabled = !mcpEnabled;
            console.log(
                chalk.green(
                    `\n✅ MCP WordPress Tools ${mcpEnabled ? "enabled" : "disabled"}\n`,
                ),
            );
            if (mcpEnabled) {
                console.log(
                    chalk.gray("💡 You can now ask about WordPress content:"),
                );
                console.log(chalk.gray("   • 'List all my WordPress plugins'"));
                console.log(chalk.gray("   • 'Show me my recent posts'"));
                console.log(chalk.gray("   • 'Get all users'"));
                console.log(chalk.gray("   • 'Create a new post about AI'\n"));
            }
            continue;
        }

        if (!message.trim()) {
            continue;
        }

        try {
            // Enhanced system prompt for MCP if enabled
            let enhancedSystemPrompt = systemPrompt;
            if (mcpEnabled) {
                enhancedSystemPrompt += `\n\nYou have access to WordPress MCP functions. When the user asks about WordPress content (posts, plugins, users, comments, etc.), you should identify which MCP function to call and respond with the tool name and arguments in this format: [MCP:function_name("arg1": "value1", "arg2": value2)]

Available MCP functions:
- wp_list_plugins() - List installed plugins (returns Name, Version)
- wp_get_posts(limit: 10) - Get recent posts (returns ID, title, status, excerpt, link) - NO FULL CONTENT
- wp_get_post(ID: number) - Get a SPECIFIC post with FULL CONTENT by ID
- wp_get_post_snapshot(ID: number) - Get COMPLETE post data including meta, terms, thumbnail, author
- wp_get_users(limit: 10) - Get users
- wp_get_comments(limit: 10) - Get comments
- wp_create_post(post_title: string, post_content: string, post_status: string) - Create a post
- wp_update_post(ID: number, fields: object) - Update a post
- wp_get_option(key: string) - Get a WordPress option
- And 28 more functions for complete WordPress management

IMPORTANT:
- wp_get_posts() returns only basic info (ID, title, excerpt, link) - NOT full content
- To get full post content, ALWAYS use wp_get_post(ID: number) after getting the post ID
- For multi-step requests: First get post list, then get specific post content

Examples:
- "List my plugins" → [MCP:wp_list_plugins()]
- "Show recent posts" → [MCP:wp_get_posts("limit": 5)]
- "Get content of post 123" → [MCP:wp_get_post("ID": 123)]
- "Summarize latest post" → First [MCP:wp_get_posts("limit": 1)], then use the returned ID in [MCP:wp_get_post("ID": <id>)]`;
            }

            if (streamingEnabled) {
                await chatWithStreaming(message, enhancedSystemPrompt);
            } else {
                const response = await chatWithoutStreaming(
                    message,
                    enhancedSystemPrompt,
                );
                console.log(chalk.cyan("\nAssistant:"), response, "\n");
            }

            // Check for MCP calls in the response
            if (mcpEnabled) {
                const lastResponse =
                    conversationHistory[conversationHistory.length - 1].content;
                const mcpCalls = extractMCPCalls(lastResponse);

                if (mcpCalls.length > 0) {
                    console.log(chalk.blue("\n🔧 Executing MCP tools...\n"));

                    const functionResults = [];

                    for (const { toolName, args } of mcpCalls) {
                        const toolIcon = "🔧";
                        console.log(
                            chalk.gray(
                                `   ${toolIcon} Calling: ${toolName}(${JSON.stringify(args)})`,
                            ),
                        );

                        const toolResult = await callMCPTool(toolName, args);

                        functionResults.push({
                            name: toolName,
                            result: toolResult,
                        });

                        // Show summary of result
                        if (toolResult.error) {
                            console.log(
                                chalk.red(`   ✗ Error: ${toolResult.error}`),
                            );
                        } else if (toolResult.result) {
                            const data = toolResult.result;
                            if (Array.isArray(data)) {
                                console.log(
                                    chalk.green(
                                        `   ✓ Returned ${data.length} items`,
                                    ),
                                );
                            } else {
                                console.log(chalk.green(`   ✓ Success`));
                            }
                        } else {
                            console.log(chalk.green(`   ✓ Success`));
                        }
                    }

                    // Format results for the AI
                    const resultsText = functionResults
                        .map((fr) => {
                            if (fr.result.error) {
                                return `Tool ${fr.name} failed: ${fr.result.error}`;
                            }
                            return `Tool ${fr.name} returned:\n${JSON.stringify(fr.result.result || fr.result, null, 2)}`;
                        })
                        .join("\n\n");

                    // Send results back to AI for processing
                    console.log(chalk.blue("\n🤖 Processing results...\n"));

                    conversationHistory.push({
                        role: "user",
                        content: `Here are the tool results:\n\n${resultsText}\n\nPlease interpret and present these results in a natural, user-friendly way.`,
                    });

                    // Get AI's interpretation
                    const client = ModelClient(
                        endpoint,
                        new AzureKeyCredential(token),
                    );

                    try {
                        if (streamingEnabled) {
                            const response = await client
                                .path("/chat/completions")
                                .post({
                                    body: {
                                        messages: [
                                            {
                                                role: "system",
                                                content: enhancedSystemPrompt,
                                            },
                                            ...conversationHistory,
                                        ],
                                        temperature: 0.7,
                                        top_p: 0.95,
                                        max_tokens: 2000,
                                        model: currentModel,
                                        stream: true,
                                    },
                                })
                                .asNodeStream();

                            if (isUnexpected(response)) {
                                throw response.body.error;
                            }

                            process.stdout.write(chalk.cyan("Assistant: "));

                            let fullResponse = "";
                            const stream = response.body;
                            let buffer = "";

                            for await (const chunk of stream) {
                                buffer += chunk.toString();
                                const lines = buffer.split("\n");
                                buffer = lines.pop() || "";

                                for (const line of lines) {
                                    const trimmed = line.trim();
                                    if (
                                        !trimmed ||
                                        !trimmed.startsWith("data: ")
                                    )
                                        continue;

                                    const data = trimmed.slice(6);
                                    if (data === "[DONE]") continue;

                                    try {
                                        const parsed = JSON.parse(data);
                                        const content =
                                            parsed.choices?.[0]?.delta
                                                ?.content || "";

                                        if (content) {
                                            process.stdout.write(
                                                chalk.cyan(content),
                                            );
                                            fullResponse += content;
                                        }
                                    } catch (e) {
                                        // Skip parsing errors
                                    }
                                }
                            }

                            console.log("\n");
                            conversationHistory.push({
                                role: "assistant",
                                content: fullResponse,
                            });
                        } else {
                            const response = await client
                                .path("/chat/completions")
                                .post({
                                    body: {
                                        messages: [
                                            {
                                                role: "system",
                                                content: enhancedSystemPrompt,
                                            },
                                            ...conversationHistory,
                                        ],
                                        temperature: 0.7,
                                        top_p: 0.95,
                                        max_tokens: 2000,
                                        model: currentModel,
                                    },
                                });

                            if (isUnexpected(response)) {
                                throw response.body.error;
                            }

                            const assistantMessage =
                                response.body.choices[0].message.content;
                            conversationHistory.push({
                                role: "assistant",
                                content: assistantMessage,
                            });
                            console.log(
                                chalk.cyan("Assistant:"),
                                assistantMessage,
                                "\n",
                            );
                        }
                    } catch (innerError) {
                        console.error(
                            chalk.red("\n❌ Error processing MCP results:"),
                            innerError.message || innerError,
                        );
                        console.log(
                            chalk.yellow(
                                "💡 The response may be too large. Try asking for specific parts or a summary.\n",
                            ),
                        );

                        // Remove the last user message that caused the error
                        conversationHistory.pop();
                    }
                }
            }
        } catch (error) {
            console.error(
                chalk.red("\n❌ Error:"),
                error.message || error,
                "\n",
            );

            if (
                error.message &&
                (error.message.includes("401") ||
                    error.message.includes("unauthorized"))
            ) {
                console.log(
                    chalk.yellow(
                        "💡 Tip: Make sure your GITHUB_MODELS_TOKEN has 'models:read' permission\n",
                    ),
                );
            }
        }
    }
}

main().catch((err) => {
    console.error(chalk.red("\n❌ Fatal error:"), err.message || err);
    process.exit(1);
});
