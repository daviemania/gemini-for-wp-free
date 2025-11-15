/**
 * Hybrid AI Manager - Ollama Cloud (Free) + Local Tiny + Gemini Fallback
 * Perfect for constrained RAM with free cloud access
 */

const { Ollama } = require("ollama");
const { GoogleGenerativeAI } = require("@google/generative-ai");
const fs = require("fs").promises;
const path = require("path");
const { execSync } = require("child_process");

class HybridAIManager {
    constructor(config = {}) {
        // Ollama Local setup (for offline/simple tasks)
        this.ollamaLocal = new Ollama({
            host: config.ollamaHost || "http://localhost:11434",
        });

        // Ollama Cloud setup (PRIMARY - free powerful models)
        this.ollamaCloud = new Ollama({
            host: "https://ollama.com",
            headers: config.ollamaApiKey
                ? {
                      Authorization: `Bearer ${config.ollamaApiKey}`,
                  }
                : undefined,
        });

        // Gemini setup (FALLBACK - when cloud limits reached)
        this.geminiKey = config.geminiKey || process.env.GEMINI_API_KEY;
        this.gemini = this.geminiKey
            ? new GoogleGenerativeAI(this.geminiKey)
            : null;

        // Cloud models (FREE - no local RAM usage!)
        this.cloudModels = [
            "gpt-oss:120b-cloud", // Best open-source, 120B params
            "deepseek-v3.1:671b-cloud", // Huge reasoning model
            "qwen3-coder:480b-cloud", // Best for code
            "gpt-oss:20b-cloud", // Fast and capable
        ];

        // Local tiny models (OFFLINE - only if needed)
        this.tinyLocalModels = [
            "qwen2.5:0.5b", // ~400MB
            "tinyllama:1.1b", // ~637MB
        ];

        // Gemini models (EMERGENCY FALLBACK)
        this.geminiModels = [
            "gemini-2.5-flash",
            "gemini-2.5-lite",
            "gemini-2.5-pro",
        ];

        // Active models
        this.activeCloudModel = null;
        this.activeTinyModel = null;
        this.activeGeminiModel = null;

        // Usage tracking (for free tier management)
        this.usage = {
            cloud: {
                requests: 0,
                failures: 0,
                rateLimitHits: 0,
                lastRateLimitTime: null,
            },
            local: {
                requests: 0,
                failures: 0,
            },
            gemini: {
                requests: 0,
                failures: 0,
            },
        };

        // Learning context
        this.contextFile = path.join(
            __dirname,
            "data",
            "ai-context-hybrid.json",
        );
        this.context = {
            taskComplexity: {},
            providerPreference: {},
            successfulPatterns: [],
        };

        this.initialize();
    }

    async initialize() {
        await this.loadContext();

        // Check cloud availability
        await this.checkCloudModels();

        // Check local tiny models (optional)
        await this.checkLocalModels();

        // Check Gemini (fallback)
        if (this.gemini) {
            await this.selectGeminiModel();
        }

        console.log("🤖 Hybrid AI Manager initialized");
        console.log(
            `   ☁️  Cloud: ${this.activeCloudModel || "Not available"}`,
        );
        console.log(
            `   💻 Local: ${this.activeTinyModel || "None (optional)"}`,
        );
        console.log(
            `   🔄 Fallback: ${this.activeGeminiModel || "Not available"}`,
        );
    }

    async checkCloudModels() {
        try {
            // Try to list cloud models
            const models = await this.ollamaLocal.list();
            const cloudModels = models.models.filter((m) =>
                m.name.includes("-cloud"),
            );

            if (cloudModels.length === 0) {
                console.log("⚠️  No cloud models found. Pull with:");
                console.log("   ollama pull gpt-oss:120b-cloud");
                return;
            }

            // Use best available cloud model
            for (const preferred of this.cloudModels) {
                if (cloudModels.some((m) => m.name.startsWith(preferred))) {
                    this.activeCloudModel = cloudModels.find((m) =>
                        m.name.startsWith(preferred),
                    ).name;
                    console.log(
                        `✅ Cloud model ready: ${this.activeCloudModel} (FREE)`,
                    );
                    return;
                }
            }

            // Use first cloud model found
            if (cloudModels.length > 0) {
                this.activeCloudModel = cloudModels[0].name;
                console.log(`✅ Using cloud model: ${this.activeCloudModel}`);
            }
        } catch (error) {
            console.log("⚠️  Cloud models check failed:", error.message);
            console.log(
                "   Run: ollama signin && ollama pull gpt-oss:120b-cloud",
            );
        }
    }

    async checkLocalModels() {
        try {
            const models = await this.ollamaLocal.list();
            const localModels = models.models.filter(
                (m) => !m.name.includes("-cloud"),
            );

            // Find smallest local model
            for (const tiny of this.tinyLocalModels) {
                if (localModels.some((m) => m.name.startsWith(tiny))) {
                    this.activeTinyModel = localModels.find((m) =>
                        m.name.startsWith(tiny),
                    ).name;
                    console.log(
                        `✅ Tiny local model (optional): ${this.activeTinyModel}`,
                    );
                    return;
                }
            }
        } catch (error) {
            console.log("ℹ️  No local models (offline mode unavailable)");
        }
    }

    async selectGeminiModel() {
        try {
            const models = await this.gemini.listModels();
            const availableModels = models
                .filter((m) =>
                    m.supportedGenerationMethods?.includes("generateContent"),
                )
                .map((m) => m.name.replace("models/", ""));

            for (const preferred of this.geminiModels) {
                if (availableModels.includes(preferred)) {
                    this.activeGeminiModel = preferred;
                    return;
                }
            }

            this.activeGeminiModel = "gemini-pro";
        } catch (error) {
            this.activeGeminiModel = "gemini-pro";
        }
    }

    isRateLimited() {
        // Check if we recently hit rate limit (wait 60 seconds)
        if (this.usage.cloud.lastRateLimitTime) {
            const timeSince = Date.now() - this.usage.cloud.lastRateLimitTime;
            if (timeSince < 60000) {
                // 1 minute
                return true;
            }
        }
        return false;
    }

    classifyTaskComplexity(message) {
        const messageLower = message.toLowerCase();
        const wordCount = message.split(/\s+/).length;

        // Simple task indicators
        const simplePatterns = [
            "hello",
            "hi",
            "thanks",
            "list",
            "count",
            "get",
            "what is",
            "who is",
            "define",
            "show me",
        ];

        // Complex task indicators
        const complexPatterns = [
            "create",
            "generate",
            "write",
            "analyze",
            "explain",
            "compare",
            "summarize",
            "code",
            "debug",
            "refactor",
        ];

        // Check patterns
        const isSimple =
            simplePatterns.some((p) => messageLower.includes(p)) &&
            wordCount < 15;
        const isComplex =
            complexPatterns.some((p) => messageLower.includes(p)) ||
            wordCount > 30;

        if (isComplex) return "complex";
        if (isSimple) return "simple";
        return "medium";
    }

    selectProvider(message, options = {}) {
        const complexity = this.classifyTaskComplexity(message);
        const { forceProvider, needsMCP } = options;

        // Forced provider
        if (forceProvider) {
            return forceProvider;
        }

        // MCP tools need cloud or Gemini (tiny models can't handle it)
        if (needsMCP) {
            if (this.activeCloudModel && !this.isRateLimited()) {
                return "cloud";
            }
            return "gemini";
        }

        // Strategy based on complexity
        switch (complexity) {
            case "simple":
                // Try local first (offline, free, fast)
                if (this.activeTinyModel) return "local";
            // Fall through to cloud

            case "medium":
            case "complex":
                // Use cloud for everything (it's free and powerful!)
                if (this.activeCloudModel && !this.isRateLimited()) {
                    return "cloud";
                }
                // Fallback to Gemini
                if (this.activeGeminiModel) {
                    return "gemini";
                }
                // Last resort: local
                if (this.activeTinyModel) {
                    return "local";
                }
                break;
        }

        return "none";
    }

    async chat(message, options = {}) {
        const startTime = Date.now();
        const {
            temperature = 0.7,
            maxTokens = 2000,
            systemPrompt = null,
            mcpTools = null,
            forceProvider = null,
        } = options;

        const needsMCP = !!mcpTools;
        const provider = this.selectProvider(message, {
            forceProvider,
            needsMCP,
        });
        const complexity = this.classifyTaskComplexity(message);

        console.log(`🤖 Task: ${complexity} | Provider: ${provider}`);

        try {
            let response;
            let actualProvider = provider;

            switch (provider) {
                case "cloud":
                    try {
                        response = await this.chatWithCloud(message, {
                            temperature,
                            maxTokens,
                            systemPrompt,
                            mcpTools,
                        });
                        this.usage.cloud.requests++;
                    } catch (cloudError) {
                        // Check for rate limit
                        if (
                            cloudError.message.includes("rate limit") ||
                            cloudError.message.includes("429")
                        ) {
                            console.log("⚠️  Cloud rate limited, using Gemini");
                            this.usage.cloud.rateLimitHits++;
                            this.usage.cloud.lastRateLimitTime = Date.now();
                        } else {
                            console.log(
                                "⚠️  Cloud failed:",
                                cloudError.message,
                            );
                            this.usage.cloud.failures++;
                        }

                        // Fallback to Gemini
                        if (this.gemini) {
                            response = await this.chatWithGemini(message, {
                                temperature,
                                maxTokens,
                                systemPrompt,
                                mcpTools,
                            });
                            actualProvider = "gemini";
                            this.usage.gemini.requests++;
                        } else {
                            throw cloudError;
                        }
                    }
                    break;

                case "local":
                    try {
                        response = await this.chatWithLocal(message, {
                            temperature,
                            maxTokens: Math.min(maxTokens, 500),
                            systemPrompt,
                        });
                        actualProvider = "local";
                        this.usage.local.requests++;
                    } catch (localError) {
                        console.log("⚠️  Local failed, using cloud or Gemini");
                        this.usage.local.failures++;

                        // Try cloud, then Gemini
                        if (this.activeCloudModel && !this.isRateLimited()) {
                            response = await this.chatWithCloud(
                                message,
                                options,
                            );
                            actualProvider = "cloud";
                            this.usage.cloud.requests++;
                        } else if (this.gemini) {
                            response = await this.chatWithGemini(
                                message,
                                options,
                            );
                            actualProvider = "gemini";
                            this.usage.gemini.requests++;
                        } else {
                            throw localError;
                        }
                    }
                    break;

                case "gemini":
                    response = await this.chatWithGemini(message, {
                        temperature,
                        maxTokens,
                        systemPrompt,
                        mcpTools,
                    });
                    actualProvider = "gemini";
                    this.usage.gemini.requests++;
                    break;

                default:
                    throw new Error("No AI provider available");
            }

            const responseTime = Date.now() - startTime;

            // Learn from interaction
            await this.learnFromInteraction(
                message,
                response,
                actualProvider,
                complexity,
            );

            return {
                success: true,
                response: response.text,
                provider: actualProvider,
                model: this.getActiveModel(actualProvider),
                responseTime: responseTime,
                functionCalls: response.functionCalls || [],
                complexity: complexity,
            };
        } catch (error) {
            console.error("❌ All providers failed:", error);
            return {
                success: false,
                error: error.message,
                provider: "none",
            };
        }
    }

    async chatWithCloud(message, options) {
        const { systemPrompt, temperature, maxTokens, mcpTools } = options;

        const messages = [];
        if (systemPrompt) {
            messages.push({ role: "system", content: systemPrompt });
        }
        messages.push({ role: "user", content: message });

        const response = await this.ollamaLocal.chat({
            model: this.activeCloudModel,
            messages: messages,
            stream: false,
            options: {
                temperature: temperature,
                num_predict: maxTokens,
                num_ctx: 8192, // Cloud models can handle larger context
            },
            tools: mcpTools
                ? this.convertMCPToolsToOllama(mcpTools)
                : undefined,
        });

        return {
            text: response.message.content,
            functionCalls: this.convertOllamaToolCallsToMCP(
                response.message.tool_calls || [],
            ),
        };
    }

    convertMCPToolsToOllama(mcpTools) {
        // Convert from Gemini/MCP format to Ollama format
        return mcpTools.map((tool) => ({
            type: "function",
            function: {
                name: tool.name,
                description: tool.description,
                parameters: tool.parameters || tool.inputSchema,
            },
        }));
    }

    convertOllamaToolCallsToMCP(toolCalls) {
        // Convert Ollama tool calls to MCP format (compatible with Gemini format)
        return toolCalls.map((call) => ({
            name: call.function.name,
            args: call.function.arguments,
        }));
    }

    async chatWithLocal(message, options) {
        const { systemPrompt, temperature, maxTokens } = options;

        const messages = [];
        if (systemPrompt) {
            messages.push({ role: "system", content: systemPrompt });
        }
        messages.push({ role: "user", content: message });

        const response = await this.ollamaLocal.chat({
            model: this.activeTinyModel,
            messages: messages,
            stream: false,
            options: {
                temperature: temperature,
                num_predict: maxTokens,
                num_ctx: 2048, // Tiny models need small context
            },
        });

        return {
            text: response.message.content,
            functionCalls: [],
        };
    }

    async chatWithGemini(message, options) {
        const { systemPrompt, temperature, maxTokens, mcpTools } = options;

        const model = this.gemini.getGenerativeModel({
            model: this.activeGeminiModel,
            systemInstruction: systemPrompt,
            generationConfig: {
                temperature: temperature,
                maxOutputTokens: maxTokens,
            },
            tools: mcpTools ? [{ functionDeclarations: mcpTools }] : undefined,
        });

        const result = await model.generateContent(message);
        const response = result.response;

        return {
            text: response.text(),
            functionCalls: response.functionCalls?.() || [],
        };
    }

    convertMCPToolsToOllama(mcpTools) {
        // Convert Gemini-style tool definitions to Ollama format
        return mcpTools.map((tool) => ({
            type: "function",
            function: {
                name: tool.name,
                description: tool.description,
                parameters: tool.parameters,
            },
        }));
    }

    getActiveModel(provider) {
        switch (provider) {
            case "cloud":
                return this.activeCloudModel;
            case "local":
                return this.activeTinyModel;
            case "gemini":
                return this.activeGeminiModel;
            default:
                return "unknown";
        }
    }

    async learnFromInteraction(message, response, provider, complexity) {
        // Track task complexity patterns
        this.context.taskComplexity[complexity] =
            (this.context.taskComplexity[complexity] || 0) + 1;

        // Track provider usage
        this.context.providerPreference[provider] =
            (this.context.providerPreference[provider] || 0) + 1;

        // Save periodically
        const totalRequests =
            this.usage.cloud.requests +
            this.usage.local.requests +
            this.usage.gemini.requests;
        if (totalRequests % 5 === 0) {
            await this.saveContext();
        }
    }

    getStats() {
        const totalRequests =
            this.usage.cloud.requests +
            this.usage.local.requests +
            this.usage.gemini.requests;

        return {
            providers: {
                cloud: {
                    ...this.usage.cloud,
                    model: this.activeCloudModel,
                    available: !!this.activeCloudModel,
                    percentage:
                        totalRequests > 0
                            ? (
                                  (this.usage.cloud.requests / totalRequests) *
                                  100
                              ).toFixed(1) + "%"
                            : "0%",
                    rateLimited: this.isRateLimited(),
                },
                local: {
                    ...this.usage.local,
                    model: this.activeTinyModel,
                    available: !!this.activeTinyModel,
                    percentage:
                        totalRequests > 0
                            ? (
                                  (this.usage.local.requests / totalRequests) *
                                  100
                              ).toFixed(1) + "%"
                            : "0%",
                },
                gemini: {
                    ...this.usage.gemini,
                    model: this.activeGeminiModel,
                    available: !!this.activeGeminiModel,
                    percentage:
                        totalRequests > 0
                            ? (
                                  (this.usage.gemini.requests / totalRequests) *
                                  100
                              ).toFixed(1) + "%"
                            : "0%",
                },
            },
            learning: this.context,
            recommendation: this.getRecommendation(),
        };
    }

    getRecommendation() {
        if (!this.activeCloudModel) {
            return "Set up Ollama Cloud for free powerful models: ollama signin && ollama pull gpt-oss:120b-cloud";
        }

        if (this.usage.cloud.rateLimitHits > 5) {
            return "Hitting cloud rate limits often - Gemini fallback is working well";
        }

        if (!this.activeTinyModel && this.usage.cloud.requests > 50) {
            return "Consider installing a tiny local model for simple offline tasks: ollama pull qwen2.5:0.5b";
        }

        return "System optimized - Cloud models handling most requests efficiently";
    }

    async loadContext() {
        try {
            const data = await fs.readFile(this.contextFile, "utf8");
            this.context = { ...this.context, ...JSON.parse(data) };
        } catch (error) {
            await fs.mkdir(path.dirname(this.contextFile), { recursive: true });
        }
    }

    async saveContext() {
        try {
            await fs.writeFile(
                this.contextFile,
                JSON.stringify(
                    {
                        ...this.context,
                        usage: this.usage,
                        lastUpdated: new Date().toISOString(),
                    },
                    null,
                    2,
                ),
                "utf8",
            );
        } catch (error) {
            // Silent fail
        }
    }
}

module.exports = HybridAIManager;
