#!/usr/bin/env node

/**
 * Ollama-Only Interactive Chat
 * Pure Ollama experience - Cloud + Local models
 * Usage: npm run ollamachat
 */

const readline = require('readline');
const OllamaManager = require('./ollama-manager');

// MCP Configuration
const MCP_RELAY_URL = 'http://localhost:3001';
const MCP_BEARER_TOKEN = 'Bearer uX484&B$k@c@6072&VdTJi#3';

// Simple MCP Client
class SimpleMCPClient {
    async callTool(toolName, args = {}) {
        try {
            const response = await fetch(MCP_RELAY_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': MCP_BEARER_TOKEN
                },
                body: JSON.stringify({ tool: toolName, args })
            });

            if (!response.ok) {
                throw new Error(`MCP Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            throw new Error(`MCP call failed: ${error.message}`);
        }
    }
}

// MCP Tool Definitions
const MCP_TOOLS = [
    {
        name: "wp_list_plugins",
        description: "List all installed WordPress plugins with their names and versions",
        parameters: {
            type: "object",
            properties: {
                search: { type: "string", description: "Optional search term to filter plugins" }
            }
        }
    },
    {
        name: "wp_get_posts",
        description: "Get WordPress posts. Returns 10 by default",
        parameters: {
            type: "object",
            properties: {
                post_type: { type: "string", description: "Post type (post, page, etc)" },
                post_status: { type: "string", description: "Status (publish, draft, etc)" },
                search: { type: "string", description: "Search term" },
                limit: { type: "integer", description: "Number of posts (default 10)" }
            }
        }
    },
    {
        name: "wp_create_post",
        description: "Create a new WordPress post or page",
        parameters: {
            type: "object",
            properties: {
                post_title: { type: "string", description: "Post title" },
                post_content: { type: "string", description: "Post content (Markdown supported)" },
                post_status: { type: "string", description: "Status (draft, publish)" },
                post_type: { type: "string", description: "Type (post, page)" }
            },
            required: ["post_title"]
        }
    },
    {
        name: "wp_get_users",
        description: "Get WordPress users. Returns 10 by default",
        parameters: {
            type: "object",
            properties: {
                search: { type: "string", description: "Search term" },
                role: { type: "string", description: "Filter by role" },
                limit: { type: "integer", description: "Number to return" }
            }
        }
    },
    {
        name: "wp_count_posts",
        description: "Count posts by status",
        parameters: {
            type: "object",
            properties: {
                post_type: { type: "string", description: "Post type to count" }
            }
        }
    },
    {
        name: "wp_update_post",
        description: "Update an existing WordPress post",
        parameters: {
            type: "object",
            properties: {
                ID: { type: "integer", description: "Post ID" },
                fields: { type: "object", description: "Fields to update" }
            },
            required: ["ID"]
        }
    },
    {
        name: "wp_upload_media",
        description: "Upload image from URL to WordPress Media Library",
        parameters: {
            type: "object",
            properties: {
                url: { type: "string", description: "Image URL" },
                title: { type: "string", description: "Image title" }
            },
            required: ["url"]
        }
    }
];

// Initialize
const ollama = new OllamaManager();
const mcpClient = new SimpleMCPClient();

// Terminal interface
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
    prompt: '\n🦙 You: '
});

// Wait for initialization
(async () => {
    await new Promise(resolve => setTimeout(resolve, 1000)); // Wait for initialization
    
    const stats = ollama.getStats();
    const models = await ollama.listModels();
    
    console.log(`
╔═══════════════════════════════════════════════════════════════╗
║              🦙 OLLAMA INTERACTIVE CHAT                      ║
╠═══════════════════════════════════════════════════════════════╣
║  Active Model: ${(stats.activeModel || 'None').padEnd(44)} ║
║  Type: ${(stats.modelType || 'Unknown').padEnd(52)} ║
║  Cloud Models: ${stats.cloud.available.toString().padEnd(44)} ║
║  Local Models: ${stats.local.available.toString().padEnd(44)} ║
║  Memory Free: ${stats.memory.free.padEnd(45)} ║
╠═══════════════════════════════════════════════════════════════╣
║  Commands:                                                    ║
║    /models   - List all available models                      ║
║    /switch   - Switch to different model                      ║
║    /stats    - Show usage statistics                          ║
║    /cloud    - Prefer cloud models                            ║
║    /local    - Prefer local models                            ║
║    /clear    - Clear conversation                             ║
║    /help     - Show help                                      ║
║    /exit     - Exit chat                                      ║
╚═══════════════════════════════════════════════════════════════╝

💡 Tip: Cloud models are FREE and use 0 RAM!
   Available: ${models.cloud.available.join(', ') || 'None - run: ollama pull gpt-oss:120b-cloud'}

${models.cloud.recommended ? `🌟 Recommended cloud: ${models.cloud.recommended.model}` : ''}
${models.local.recommended ? `💻 Recommended local: ${models.local.recommended.model}` : ''}
`);

    rl.prompt();
})();

// Conversation history
let conversationHistory = [];

rl.on('line', async (input) => {
    const userInput = input.trim();

    if (!userInput) {
        rl.prompt();
        return;
    }

    // Handle commands
    if (userInput === '/exit') {
        console.log('\n👋 Goodbye!\n');
        process.exit(0);
    }

    if (userInput === '/clear') {
        conversationHistory = [];
        console.log('✅ Conversation cleared\n');
        rl.prompt();
        return;
    }

    if (userInput === '/models') {
        const models = await ollama.listModels();
        console.log('\n📋 Available Models:\n');
        
        if (models.cloud.available.length > 0) {
            console.log('☁️  Cloud Models (FREE, 0 RAM):');
            models.cloud.available.forEach(m => {
                const active = m === ollama.activeModel ? ' ← ACTIVE' : '';
                console.log(`   • ${m}${active}`);
            });
            if (models.cloud.recommended) {
                console.log(`   🌟 Recommended: ${models.cloud.recommended.model}`);
                console.log(`      ${models.cloud.recommended.reason}`);
            }
        } else {
            console.log('☁️  No cloud models. Install with:');
            console.log('   ollama pull gpt-oss:120b-cloud');
        }
        
        console.log('');
        
        if (models.local.available.length > 0) {
            console.log('💻 Local Models:');
            models.local.available.forEach(m => {
                const active = m === ollama.activeModel ? ' ← ACTIVE' : '';
                const memEst = ollama.estimateModelMemory(m);
                console.log(`   • ${m} (~${memEst}MB)${active}`);
            });
            if (models.local.recommended) {
                console.log(`   🌟 Recommended: ${models.local.recommended.model}`);
                console.log(`      ${models.local.recommended.reason}`);
            }
        } else {
            console.log('💻 No local models. Install with:');
            console.log('   ollama pull qwen2.5:0.5b  (tiny, 400MB)');
        }
        
        console.log('');
        rl.prompt();
        return;
    }

    if (userInput === '/switch') {
        const models = await ollama.listModels();
        const allModels = [...models.cloud.available, ...models.local.available];
        
        if (allModels.length === 0) {
            console.log('❌ No models available\n');
            rl.prompt();
            return;
        }
        
        console.log('\n📋 Available models:');
        allModels.forEach((m, i) => {
            const type = m.includes('-cloud') ? '☁️ ' : '💻';
            const active = m === ollama.activeModel ? ' ← ACTIVE' : '';
            console.log(`   ${i + 1}. ${type} ${m}${active}`);
        });
        
        rl.question('\nEnter model number or name: ', (answer) => {
            const num = parseInt(answer);
            let modelName;
            
            if (!isNaN(num) && num > 0 && num <= allModels.length) {
                modelName = allModels[num - 1];
            } else {
                modelName = answer.trim();
            }
            
            const result = ollama.switchModel(modelName);
            
            if (result.success) {
                console.log(`✅ ${result.message}\n`);
            } else {
                console.log(`❌ ${result.error}\n`);
            }
            
            rl.prompt();
        });
        return;
    }

    if (userInput === '/stats') {
        const stats = ollama.getStats();
        console.log('\n📊 Usage Statistics:\n');
        console.log(`Total Requests: ${stats.totalRequests}`);
        console.log('');
        console.log('☁️  Cloud Models:');
        console.log(`   Requests: ${stats.cloud.requests} (${stats.cloud.percentage})`);
        console.log(`   Failures: ${stats.cloud.failures}`);
        console.log(`   Avg Time: ${stats.cloud.avgTime.toFixed(0)}ms`);
        console.log(`   Available: ${stats.cloud.available}`);
        console.log('');
        console.log('💻 Local Models:');
        console.log(`   Requests: ${stats.local.requests} (${stats.local.percentage})`);
        console.log(`   Failures: ${stats.local.failures}`);
        console.log(`   Avg Time: ${stats.local.avgTime.toFixed(0)}ms`);
        console.log(`   Available: ${stats.local.available}`);
        console.log('');
        console.log(`💾 Memory: ${stats.memory.free}`);
        console.log(`   ${stats.memory.recommendation}`);
        console.log('');
        rl.prompt();
        return;
    }

    if (userInput === '/cloud') {
        ollama.setPreferences({ preferCloud: true });
        await ollama.selectBestModel();
        console.log(`✅ Switched to cloud preference\n`);
        console.log(`   Active model: ${ollama.activeModel}\n`);
        rl.prompt();
        return;
    }

    if (userInput === '/local') {
        ollama.setPreferences({ preferCloud: false });
        await ollama.selectBestModel();
        console.log(`✅ Switched to local preference\n`);
        console.log(`   Active model: ${ollama.activeModel}\n`);
        rl.prompt();
        return;
    }

    if (userInput === '/help') {
        console.log(`
📖 Ollama Chat Help:

Commands:
  /models  - Show all available models with details
  /switch  - Switch to a different model
  /stats   - Show usage statistics and performance
  /cloud   - Prefer cloud models (FREE, no RAM usage)
  /local   - Prefer local models (offline capable)
  /clear   - Clear conversation history
  /help    - Show this help
  /exit    - Exit chat

Tips:
  • Cloud models are FREE and use 0 local RAM
  • Local models work offline but use RAM
  • Cloud models auto-fallback to local if rate limited
  • Use /stats to see which models work best

Install Cloud Models (Recommended):
  ollama signin
  ollama pull gpt-oss:120b-cloud     # 120B params, 0 RAM!
  ollama pull gpt-oss:20b-cloud      # Fast version

Install Local Models (Optional):
  ollama pull qwen2.5:0.5b           # 400MB - Fastest
  ollama pull llama3.2:1b            # 1.3GB - Good quality
`);
        rl.prompt();
        return;
    }

    // Regular chat
    try {
        console.log(`\n🦙 ${ollama.activeModel || 'Ollama'}: Thinking...\n`);
        
        // Build conversation context
        const systemPrompt = conversationHistory.length > 0 
            ? 'Continue the conversation naturally.' 
            : null;
        
        const result = await ollama.chat(userInput, { systemPrompt });

        if (result.success) {
            console.log(`🦙 ${ollama.activeModel}: ${result.response}\n`);
            console.log(`   [${result.modelType} • ${result.responseTime}ms • ${result.tokenCount} tokens]\n`);
            
            // Add to conversation history
            conversationHistory.push(
                { role: 'user', content: userInput },
                { role: 'assistant', content: result.response }
            );
            
            // Keep only last 10 messages to save memory
            if (conversationHistory.length > 20) {
                conversationHistory = conversationHistory.slice(-20);
            }
        } else {
            console.log(`❌ Error: ${result.error}\n`);
            console.log('Try /models to see available models or /switch to change model\n');
        }

    } catch (error) {
        console.error(`\n❌ Error: ${error.message}\n`);
    }

    rl.prompt();
});

rl.on('close', () => {
    console.log('\n👋 Goodbye!\n');
    process.exit(0);
});

// Handle errors
process.on('unhandledRejection', (error) => {
    console.error('\n❌ Unhandled error:', error.message);
    rl.prompt();
});