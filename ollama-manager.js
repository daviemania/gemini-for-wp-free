/**
 * Pure Ollama Manager - Cloud + Local (No Gemini)
 * Dedicated system for Ollama-only interactions
 */

const { Ollama } = require("ollama");
const { execSync } = require("child_process");

class OllamaManager {
    constructor(config = {}) {
        // Local Ollama instance
        this.ollamaLocal = new Ollama({
            host: config.host || "http://localhost:11434",
        });

        // Model categories
        this.models = {
            cloud: {
                huge: [
                    "deepseek-v3.1:671b-cloud", // 671B - Best reasoning
                    "qwen3-coder:480b-cloud", // 480B - Best coding
                    "kimi-k2:1t-cloud", // 1T - Experimental
                ],
                large: [
                    "gpt-oss:120b-cloud", // 120B - Best open-source
                    "glm-4.6:cloud", // Good general purpose
                    "minimax-m2:cloud", // Fast
                ],
                fast: [
                    "gpt-oss:20b-cloud", // 20B - Very fast
                ],
            },
            local: {
                tiny: [
                    "qwen2.5:0.5b", // ~400MB - Fastest
                    "tinyllama:1.1b", // ~637MB - Reliable
                ],
                small: [
                    "llama3.2:1b", // ~1.3GB - Good quality
                    "phi3:mini", // ~2GB - Microsoft
                ],
                medium: [
                    "llama3.2:3b", // ~2GB - Very capable
                    "qwen2.5:3b", // ~2GB - Smart
                    "phi3.5:latest", // ~2.5GB - Best small model
                ],
            },
        };

        // Available models (detected)
        this.available = {
            cloud: [],
            local: [],
        };

        // Active model selection
        this.activeModel = null;
        this.modelType = null; // 'cloud' or 'local'

        // Preferences
        this.preferCloud = true;
        this.autoFallback = true;

        // Statistics
        this.stats = {
            cloud: { requests: 0, failures: 0, avgTime: 0 },
            local: { requests: 0, failures: 0, avgTime: 0 },
        };

        this.initialize();
    }

    async initialize() {
        await this.detectModels();
        await this.selectBestModel();

        console.log("🦙 Ollama Manager initialized");
        console.log(`   Active Model: ${this.activeModel || "None"}`);
        console.log(`   Type: ${this.modelType || "Unknown"}`);
        console.log(`   Cloud Models: ${this.available.cloud.length}`);
        console.log(`   Local Models: ${this.available.local.length}`);
    }

    async detectModels() {
        try {
            const modelList = await this.ollamaLocal.list();

            // Separate cloud and local models
            for (const model of modelList.models) {
                if (model.name.includes("-cloud")) {
                    this.available.cloud.push(model.name);
                } else {
                    this.available.local.push(model.name);
                }
            }

            console.log(`✅ Found ${this.available.cloud.length} cloud models`);
            console.log(`✅ Found ${this.available.local.length} local models`);
        } catch (error) {
            console.log("⚠️  Could not detect models:", error.message);
        }
    }

    async selectBestModel() {
        // Try cloud models first (if preferred)
        if (this.preferCloud && this.available.cloud.length > 0) {
            // Check all cloud model tiers
            for (const tier of ["huge", "large", "fast"]) {
                for (const model of this.models.cloud[tier]) {
                    if (this.available.cloud.includes(model)) {
                        this.activeModel = model;
                        this.modelType = "cloud";
                        console.log(
                            `✅ Selected cloud model: ${model} (${tier})`,
                        );
                        return;
                    }
                }
            }

            // Use first available cloud model
            if (this.available.cloud.length > 0) {
                this.activeModel = this.available.cloud[0];
                this.modelType = "cloud";
                console.log(`✅ Using first cloud model: ${this.activeModel}`);
                return;
            }
        }

        // Fallback to local models
        if (this.available.local.length > 0) {
            const freeMem = this.getFreeMemory();

            // Select based on available memory
            for (const tier of ["medium", "small", "tiny"]) {
                for (const model of this.models.local[tier]) {
                    if (this.available.local.includes(model)) {
                        // Check if we have enough memory
                        const memNeeded = this.estimateModelMemory(model);
                        if (freeMem > memNeeded + 1000) {
                            // 1GB safety margin
                            this.activeModel = model;
                            this.modelType = "local";
                            console.log(
                                `✅ Selected local model: ${model} (${tier}, needs ~${memNeeded}MB)`,
                            );
                            return;
                        }
                    }
                }
            }

            // Use smallest available if memory constrained
            this.activeModel = this.available.local[0];
            this.modelType = "local";
            console.log(`⚠️  Using smallest local model: ${this.activeModel}`);
        }

        console.log("❌ No models available");
    }

    getFreeMemory() {
        try {
            return parseInt(
                execSync("free -m | awk 'NR==2{print $7}'").toString().trim(),
            );
        } catch (error) {
            return 2000; // Assume 2GB if can't detect
        }
    }

    estimateModelMemory(modelName) {
        // Rough memory estimates in MB
        if (modelName.includes("0.5b")) return 400;
        if (modelName.includes("1.1b") || modelName.includes("1b")) return 1000;
        if (modelName.includes("3b")) return 2000;
        if (modelName.includes("7b") || modelName.includes("8b")) return 5000;
        return 1500; // Default
    }

    async chat(message, options = {}) {
        const startTime = Date.now();
        const {
            model = this.activeModel,
            temperature = 0.7,
            maxTokens = 2000,
            systemPrompt = null,
            stream = false,
            tools = null,
            mcpClient = null,
        } = options;

        // Determine if using cloud or local
        const isCloud = model.includes("-cloud");
        const modelType = isCloud ? "cloud" : "local";

        try {
            const messages = [];
            if (systemPrompt) {
                messages.push({ role: "system", content: systemPrompt });
            }
            messages.push({ role: "user", content: message });

            // Prepare tools for Ollama format
            const ollamaTools = tools
                ? this.convertToolsToOllamaFormat(tools)
                : undefined;

            const response = await this.ollamaLocal.chat({
                model: model,
                messages: messages,
                stream: stream,
                options: {
                    temperature: temperature,
                    num_predict: maxTokens,
                    num_ctx: isCloud ? 8192 : 2048, // Cloud can handle larger context
                },
                tools: ollamaTools,
            });

            const responseTime = Date.now() - startTime;

            // Update stats
            this.stats[modelType].requests++;
            this.stats[modelType].avgTime =
                (this.stats[modelType].avgTime *
                    (this.stats[modelType].requests - 1) +
                    responseTime) /
                this.stats[modelType].requests;

            // Handle tool calls if present
            const toolCalls = response.message.tool_calls || [];
            let executedTools = [];

            if (toolCalls.length > 0 && mcpClient) {
                console.log(`🔧 Executing ${toolCalls.length} MCP tools...`);
                executedTools = await this.executeToolCalls(
                    toolCalls,
                    mcpClient,
                );
            }

            return {
                success: true,
                response: response.message.content,
                model: model,
                modelType: modelType,
                responseTime: responseTime,
                tokenCount: response.eval_count || 0,
                toolCalls: toolCalls,
                toolResults: executedTools,
            };
        } catch (error) {
            console.error(`❌ ${modelType} model error:`, error.message);
            this.stats[modelType].failures++;

            // Try fallback if enabled
            if (this.autoFallback) {
                return await this.tryFallback(message, options, modelType);
            }

            return {
                success: false,
                error: error.message,
                model: model,
                modelType: modelType,
            };
        }
    }

    convertToolsToOllamaFormat(tools) {
        // Convert from Gemini/MCP format to Ollama format
        return tools.map((tool) => ({
            type: "function",
            function: {
                name: tool.name,
                description: tool.description,
                parameters: tool.parameters || tool.inputSchema,
            },
        }));
    }

    async executeToolCalls(toolCalls, mcpClient) {
        const results = [];

        for (const call of toolCalls) {
            try {
                console.log(
                    `   → ${call.function.name}(${JSON.stringify(call.function.arguments).substring(0, 50)}...)`,
                );

                const toolResult = await mcpClient.callTool(
                    call.function.name,
                    call.function.arguments,
                );

                results.push({
                    tool: call.function.name,
                    arguments: call.function.arguments,
                    result: toolResult,
                    success: true,
                });

                // Show result summary
                if (toolResult.data) {
                    if (Array.isArray(toolResult.data)) {
                        console.log(
                            `   ✓ Returned ${toolResult.data.length} items`,
                        );
                    } else {
                        console.log(`   ✓ Success`);
                    }
                }
            } catch (error) {
                console.log(`   ✗ Error: ${error.message}`);
                results.push({
                    tool: call.function.name,
                    arguments: call.function.arguments,
                    error: error.message,
                    success: false,
                });
            }
        }

        return results;
    }

    async tryFallback(message, options, failedType) {
        console.log(`🔄 Attempting fallback from ${failedType}...`);

        // Try opposite type
        const fallbackType = failedType === "cloud" ? "local" : "cloud";
        const fallbackModels = this.available[fallbackType];

        if (fallbackModels.length === 0) {
            return {
                success: false,
                error: `No ${fallbackType} models available for fallback`,
                modelType: failedType,
            };
        }

        // Use first available fallback model
        const fallbackModel = fallbackModels[0];
        console.log(`   → Using ${fallbackType} model: ${fallbackModel}`);

        return await this.chat(message, { ...options, model: fallbackModel });
    }

    async listModels() {
        return {
            cloud: {
                available: this.available.cloud,
                recommended: this.getRecommendedCloudModel(),
            },
            local: {
                available: this.available.local,
                recommended: this.getRecommendedLocalModel(),
            },
            active: {
                model: this.activeModel,
                type: this.modelType,
            },
        };
    }

    getRecommendedCloudModel() {
        // Recommend based on usage patterns
        const cloudAvailable = this.available.cloud;

        if (cloudAvailable.length === 0) return null;

        // Prefer 120B for general use
        const gpt120 = cloudAvailable.find((m) => m.startsWith("gpt-oss:120b"));
        if (gpt120)
            return {
                model: gpt120,
                reason: "Best balance of speed and quality",
            };

        // Next: DeepSeek for reasoning
        const deepseek = cloudAvailable.find((m) => m.startsWith("deepseek"));
        if (deepseek)
            return { model: deepseek, reason: "Best for complex reasoning" };

        return {
            model: cloudAvailable[0],
            reason: "First available cloud model",
        };
    }

    getRecommendedLocalModel() {
        const freeMem = this.getFreeMemory();
        const localAvailable = this.available.local;

        if (localAvailable.length === 0) return null;

        // Recommend based on available memory
        if (freeMem > 3000) {
            const medium = localAvailable.find(
                (m) => m.includes("3b") || m.includes("phi3.5"),
            );
            if (medium)
                return {
                    model: medium,
                    reason: "Good quality, fits in memory",
                };
        }

        if (freeMem > 1500) {
            const small = localAvailable.find((m) => m.includes("1b"));
            if (small)
                return { model: small, reason: "Fast and memory-efficient" };
        }

        // Fallback to smallest
        const tiny = localAvailable.find(
            (m) => m.includes("0.5b") || m.includes("tiny"),
        );
        if (tiny) return { model: tiny, reason: "Minimal memory usage" };

        return { model: localAvailable[0], reason: "First available" };
    }

    switchModel(modelName) {
        const isCloud = modelName.includes("-cloud");
        const modelType = isCloud ? "cloud" : "local";

        if (!this.available[modelType].includes(modelName)) {
            return {
                success: false,
                error: `Model ${modelName} not available`,
                available: this.available[modelType],
            };
        }

        this.activeModel = modelName;
        this.modelType = modelType;

        return {
            success: true,
            model: modelName,
            type: modelType,
            message: `Switched to ${modelType} model: ${modelName}`,
        };
    }

    getStats() {
        const totalRequests =
            this.stats.cloud.requests + this.stats.local.requests;

        return {
            activeModel: this.activeModel,
            modelType: this.modelType,
            totalRequests: totalRequests,
            cloud: {
                ...this.stats.cloud,
                percentage:
                    totalRequests > 0
                        ? (
                              (this.stats.cloud.requests / totalRequests) *
                              100
                          ).toFixed(1) + "%"
                        : "0%",
                available: this.available.cloud.length,
            },
            local: {
                ...this.stats.local,
                percentage:
                    totalRequests > 0
                        ? (
                              (this.stats.local.requests / totalRequests) *
                              100
                          ).toFixed(1) + "%"
                        : "0%",
                available: this.available.local.length,
            },
            memory: {
                free: this.getFreeMemory() + "MB",
                recommendation: this.getMemoryRecommendation(),
            },
        };
    }

    getMemoryRecommendation() {
        const freeMem = this.getFreeMemory();

        if (freeMem < 1500) {
            return "Critical: Use cloud models only";
        } else if (freeMem < 2500) {
            return "Low: Stick to tiny local models (0.5B-1B) or cloud";
        } else if (freeMem < 3500) {
            return "Moderate: Can use small local models (1B-3B) or cloud";
        } else {
            return "Good: Can use medium local models (3B-7B) or cloud";
        }
    }

    setPreferences(preferences = {}) {
        if (preferences.preferCloud !== undefined) {
            this.preferCloud = preferences.preferCloud;
        }
        if (preferences.autoFallback !== undefined) {
            this.autoFallback = preferences.autoFallback;
        }

        return {
            preferCloud: this.preferCloud,
            autoFallback: this.autoFallback,
        };
    }
}

module.exports = OllamaManager;
