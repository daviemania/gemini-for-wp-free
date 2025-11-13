/**
 * Gemini AI Project - Main Application Server
 * A comprehensive Express.js server with Gemini AI integration
 * Features: API routes, middleware, error handling, security, logging
 */

const express = require('express');
const { GoogleGenerativeAI } = require('@google/generative-ai');
const path = require('path');
const fs = require('fs').promises;

// Initialize Express app
const app = express();
const PORT = process.env.PORT || 3000;

// =============================================================================
// CONFIGURATION & CONSTANTS
// =============================================================================

const config = {
    app: {
        name: 'Gemini AI Project',
        version: '1.0.0',
        environment: process.env.NODE_ENV || 'development'
    },
    gemini: {
        model: 'gemini-pro',
        maxOutputTokens: 1000,
        temperature: 0.7
    },
    security: {
        rateLimit: {
            windowMs: 15 * 60 * 1000, // 15 minutes
            max: 100 // limit each IP to 100 requests per windowMs
        }
    }
};

// =============================================================================
// MIDDLEWARE SETUP
// =============================================================================

// Security middleware
app.use(require('helmet')());
app.use(require('cors')());

// Body parsing middleware
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Static files
app.use(express.static(path.join(__dirname, 'public')));

// Custom logging middleware
app.use((req, res, next) => {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${req.method} ${req.path} - IP: ${req.ip}`);
    next();
});

// Request timing middleware
app.use((req, res, next) => {
    const start = Date.now();
    res.on('finish', () => {
        const duration = Date.now() - start;
        console.log(`[${req.method}] ${req.path} - ${res.statusCode} - ${duration}ms`);
    });
    next();
});

// =============================================================================
// GEMINI AI SERVICE
// =============================================================================

class GeminiAIService {
    constructor() {
        this.genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY || 'your-api-key-here');
        this.model = this.genAI.getGenerativeModel({ 
            model: config.gemini.model,
            generationConfig: {
                maxOutputTokens: config.gemini.maxOutputTokens,
                temperature: config.gemini.temperature,
            }
        });
        this.isConfigured = !!process.env.GEMINI_API_KEY;
    }

    async generateContent(prompt, options = {}) {
        if (!this.isConfigured) {
            throw new Error('Gemini API key not configured. Set GEMINI_API_KEY environment variable.');
        }

        try {
            const result = await this.model.generateContent(prompt);
            const response = await result.response;
            
            return {
                success: true,
                text: response.text(),
                usage: {
                    promptTokens: response.usageMetadata?.promptTokenCount || 0,
                    candidatesTokens: response.usageMetadata?.candidatesTokenCount || 0,
                    totalTokens: response.usageMetadata?.totalTokenCount || 0
                },
                safetyRatings: response.candidates?.[0]?.safetyRatings || []
            };
        } catch (error) {
            console.error('Gemini AI Error:', error);
            return {
                success: false,
                error: error.message,
                code: error.code || 'UNKNOWN_ERROR'
            };
        }
    }

    async chat(messages) {
        if (!this.isConfigured) {
            throw new Error('Gemini API key not configured.');
        }

        const chat = this.model.startChat({
            history: messages.slice(0, -1), // All but the last message
            generationConfig: {
                maxOutputTokens: config.gemini.maxOutputTokens,
                temperature: config.gemini.temperature,
            }
        });

        const lastMessage = messages[messages.length - 1];
        const result = await chat.sendMessage(lastMessage);
        const response = await result.response;
        
        return response.text();
    }

    getStatus() {
        return {
            configured: this.isConfigured,
            model: config.gemini.model,
            maxTokens: config.gemini.maxOutputTokens,
            temperature: config.gemini.temperature
        };
    }
}

// Initialize Gemini service
const geminiService = new GeminiAIService();

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

const utils = {
    // Response formatter
    formatResponse: (success, data, message = '') => ({
        success,
        data,
        message,
        timestamp: new Date().toISOString(),
        version: config.app.version
    }),

    // Error handler
    handleError: (res, error, statusCode = 500) => {
        console.error('Server Error:', error);
        res.status(statusCode).json(
            utils.formatResponse(false, null, error.message)
        );
    },

    // Validation
    validatePrompt: (prompt) => {
        if (!prompt || typeof prompt !== 'string') {
            return 'Prompt must be a non-empty string';
        }
        if (prompt.length > 5000) {
            return 'Prompt too long (max 5000 characters)';
        }
        return null;
    }
};

// =============================================================================
// ROUTES
// =============================================================================

// Health check endpoint
app.get('/health', (req, res) => {
    res.json(utils.formatResponse(true, {
        status: 'healthy',
        environment: config.app.environment,
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        gemini: geminiService.getStatus()
    }, 'Server is running normally'));
});

// API status endpoint
app.get('/api/status', (req, res) => {
    res.json(utils.formatResponse(true, {
        app: config.app,
        server: {
            nodeVersion: process.version,
            platform: process.platform,
            uptime: Math.floor(process.uptime())
        },
        gemini: geminiService.getStatus()
    }));
});

// Main Gemini AI endpoint
app.post('/api/generate', async (req, res) => {
    try {
        const { prompt, options } = req.body;

        // Validate input
        const validationError = utils.validatePrompt(prompt);
        if (validationError) {
            return res.status(400).json(
                utils.formatResponse(false, null, validationError)
            );
        }

        console.log(`Generating content for prompt: "${prompt.substring(0, 100)}..."`);

        const result = await geminiService.generateContent(prompt, options);

        if (result.success) {
            res.json(utils.formatResponse(true, {
                generatedText: result.text,
                usage: result.usage,
                safety: result.safetyRatings
            }, 'Content generated successfully'));
        } else {
            res.status(500).json(
                utils.formatResponse(false, null, `AI Service Error: ${result.error}`)
            );
        }
    } catch (error) {
        utils.handleError(res, error);
    }
});

// Chat endpoint for conversational AI
app.post('/api/chat', async (req, res) => {
    try {
        const { messages } = req.body;

        if (!Array.isArray(messages) || messages.length === 0) {
            return res.status(400).json(
                utils.formatResponse(false, null, 'Messages array is required')
            );
        }

        const response = await geminiService.chat(messages);
        
        res.json(utils.formatResponse(true, {
            response,
            messageCount: messages.length + 1
        }, 'Chat response generated'));
    } catch (error) {
        utils.handleError(res, error);
    }
});

// Batch processing endpoint
app.post('/api/batch-generate', async (req, res) => {
    try {
        const { prompts } = req.body;

        if (!Array.isArray(prompts) || prompts.length === 0) {
            return res.status(400).json(
                utils.formatResponse(false, null, 'Prompts array is required')
            );
        }

        if (prompts.length > 10) {
            return res.status(400).json(
                utils.formatResponse(false, null, 'Maximum 10 prompts allowed per batch')
            );
        }

        const results = [];
        for (const prompt of prompts) {
            const result = await geminiService.generateContent(prompt);
            results.push({
                prompt,
                result
            });
        }

        res.json(utils.formatResponse(true, {
            batchId: Date.now(),
            totalPrompts: prompts.length,
            results
        }, 'Batch processing completed'));
    } catch (error) {
        utils.handleError(res, error);
    }
});

// Main page
app.get('/', (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${config.app.name}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                .container { max-width: 800px; margin: 0 auto; }
                .header { background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                .endpoint { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
                code { background: #eee; padding: 2px 6px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 ${config.app.name}</h1>
                    <p>Version ${config.app.version} | Environment: ${config.app.environment}</p>
                </div>
                
                <h2>API Endpoints</h2>
                
                <div class="endpoint">
                    <h3>GET <code>/health</code></h3>
                    <p>Server health check and status information</p>
                </div>
                
                <div class="endpoint">
                    <h3>GET <code>/api/status</code></h3>
                    <p>Detailed application and Gemini AI status</p>
                </div>
                
                <div class="endpoint">
                    <h3>POST <code>/api/generate</code></h3>
                    <p>Generate content using Gemini AI</p>
                    <p><strong>Body:</strong> <code>{"prompt": "Your prompt here"}</code></p>
                </div>
                
                <div class="endpoint">
                    <h3>POST <code>/api/chat</code></h3>
                    <p>Conversational chat with Gemini AI</p>
                    <p><strong>Body:</strong> <code>{"messages": [{"role": "user", "content": "Hello"}]}</code></p>
                </div>
                
                <div class="endpoint">
                    <h3>POST <code>/api/batch-generate</code></h3>
                    <p>Process multiple prompts in batch</p>
                    <p><strong>Body:</strong> <code>{"prompts": ["prompt1", "prompt2"]}</code></p>
                </div>
                
                <h2>Getting Started</h2>
                <p>Set your Gemini AI API key as environment variable: <code>GEMINI_API_KEY=your_key_here</code></p>
                
                <h2>Development</h2>
                <p>This server is running in a Docker Dev Container with hot reload enabled.</p>
            </div>
        </body>
        </html>
    `);
});

// =============================================================================
// ERROR HANDLING MIDDLEWARE
// =============================================================================

// 404 handler
app.use((req, res) => {
    res.status(404).json(
        utils.formatResponse(false, null, `Route ${req.method} ${req.path} not found`)
    );
});

// Global error handler
app.use((error, req, res, next) => {
    console.error('Unhandled Error:', error);
    res.status(500).json(
        utils.formatResponse(false, null, 'Internal server error')
    );
});

// =============================================================================
// SERVER STARTUP
// =============================================================================

async function initializeServer() {
    try {
        // Create logs directory if it doesn't exist
        await fs.mkdir('./logs', { recursive: true });
        
        // Start the server
        app.listen(PORT, () => {
            console.log(`
╔══════════════════════════════════════════════════════════════╗
║                   🚀 GEMINI AI SERVER                       ║
╠══════════════════════════════════════════════════════════════╣
║  Server:     http://localhost:${PORT}                          ║
║  Environment: ${config.app.environment.padEnd(30)} ║
║  Node.js:    ${process.version.padEnd(30)} ║
║  Gemini AI:  ${geminiService.isConfigured ? '✅ Configured' : '⚠️  API Key Needed'}${geminiService.isConfigured ? ''.padEnd(23) : ''.padEnd(22)}║
╚══════════════════════════════════════════════════════════════╝

📋 Available Endpoints:
   • GET    /health           - Health check
   • GET    /api/status       - Detailed status
   • POST   /api/generate     - AI content generation
   • POST   /api/chat         - Conversational AI
   • POST   /api/batch-generate - Batch processing

🔧 Development: 
   • Hot reload: nodemon enabled
   • Dev Container: VS Code integrated
   • Logs: Request timing and error logging

${!geminiService.isConfigured ? '⚠️  REMINDER: Set GEMINI_API_KEY environment variable to use AI features' : '✅ Ready to process AI requests!'}
            `);
        });

    } catch (error) {
        console.error('Server initialization failed:', error);
        process.exit(1);
    }
}

// Handle graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM received, shutting down gracefully');
    process.exit(0);
});

process.on('SIGINT', () => {
    console.log('SIGINT received, shutting down gracefully');
    process.exit(0);
});

// Start the server
initializeServer();

module.exports = app; // For testing
