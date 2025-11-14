#!/usr/bin/env node

/**
 * Interactive Gemini AI Chat (Regular - No MCP Tools)
 * Usage: npm run chat
 */

const readline = require("readline");
const { GoogleGenerativeAI } = require("@google/generative-ai");

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

// Initialize model and chat
let chat;
let model;

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
        generationConfig: {
            maxOutputTokens: 2000,
            temperature: 0.7,
        },
    });

    // Chat session
    chat = model.startChat({
        history: [],
    });

    console.log(`
╔═══════════════════════════════════════════════════════════════╗
║              🤖 GEMINI AI INTERACTIVE CHAT                   ║
╠═══════════════════════════════════════════════════════════════╣
║  Model: ${selectedModel.padEnd(53)} ║
║  Mode: Regular Chat (No MCP Tools)                            ║
╠═══════════════════════════════════════════════════════════════╣
║  Commands:                                                    ║
║    /clear    - Clear chat history                             ║
║    /exit     - Exit chat                                      ║
║    /help     - Show help                                      ║
╚═══════════════════════════════════════════════════════════════╝

Type your message and press Enter to chat with Gemini AI
For WordPress integration, use: npm run chatwmcp
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
        chat = model.startChat({ history: [] });
        console.log("✅ Chat history cleared\n");
        rl.prompt();
        return;
    }

    if (userInput === "/help") {
        console.log(`
📖 Help:
  • Type any message to chat with Gemini AI
  • /clear - Start a new conversation
  • /exit  - Exit the chat
  • For WordPress MCP tools, use: npm run chatwmcp
`);
        rl.prompt();
        return;
    }

    try {
        console.log("\n🤖 Gemini: Thinking...\n");

        const result = await chat.sendMessage(userInput);
        const response = result.response;

        console.log(`🤖 Gemini: ${response.text()}\n`);
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
