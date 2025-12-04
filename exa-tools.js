// exa-tools.js - Exa AI Tool Definitions for MCP

const EXA_API_KEY = process.env.EXA_API_KEY;

/**
 * Call Exa AI API
 */
async function callExaAPI(endpoint, params) {
    try {
        const response = await fetch(`https://api.exa.ai/${endpoint}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "x-api-key": EXA_API_KEY,
            },
            body: JSON.stringify(params),
        });

        if (!response.ok) {
            throw new Error(`Exa API Error: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        return { error: error.message };
    }
}

/**
 * Exa Tool Handler - Execute Exa tools
 */
async function callExaTool(toolName, args = {}) {
    switch (toolName) {
        case "exa_search":
            return await callExaAPI("search", {
                query: args.query,
                numResults: args.numResults || 10,
                type: args.type || "auto", // neural, keyword, or auto
                category: args.category,
                startPublishedDate: args.startPublishedDate,
                endPublishedDate: args.endPublishedDate,
            });

        case "exa_find_similar":
            return await callExaAPI("findSimilar", {
                url: args.url,
                numResults: args.numResults || 10,
                category: args.category,
            });

        case "exa_get_contents":
            return await callExaAPI("contents", {
                ids: args.ids,
                text: args.text !== false, // Get text content by default
                highlights: args.highlights,
                summary: args.summary,
            });

        default:
            return { error: `Unknown Exa tool: ${toolName}` };
    }
}

/**
 * Exa MCP Tool Definitions (for Gemini/Ollama)
 */
const EXA_TOOLS = [
    {
        type: "function",
        function: {
            name: "exa_search",
            description:
                "Search the web using Exa AI's neural semantic search. Returns high-quality, relevant results with full content.",
            parameters: {
                type: "object",
                properties: {
                    query: {
                        type: "string",
                        description:
                            "Natural language search query (supports semantic understanding)",
                    },
                    numResults: {
                        type: "integer",
                        description: "Number of results to return (1-10, default 10)",
                    },
                    type: {
                        type: "string",
                        enum: ["neural", "keyword", "auto"],
                        description:
                            "Search type: neural (semantic), keyword (exact), or auto (best match)",
                    },
                    category: {
                        type: "string",
                        description:
                            "Filter by category (e.g., company, research paper, news, github, tweet, etc.)",
                    },
                    startPublishedDate: {
                        type: "string",
                        description: "Filter results after this date (ISO 8601 format)",
                    },
                    endPublishedDate: {
                        type: "string",
                        description: "Filter results before this date (ISO 8601 format)",
                    },
                },
                required: ["query"],
            },
        },
    },
    {
        type: "function",
        function: {
            name: "exa_find_similar",
            description:
                "Find web pages similar to a given URL using Exa AI's semantic similarity.",
            parameters: {
                type: "object",
                properties: {
                    url: {
                        type: "string",
                        description: "URL to find similar content for",
                    },
                    numResults: {
                        type: "integer",
                        description: "Number of similar results (1-10, default 10)",
                    },
                    category: {
                        type: "string",
                        description: "Filter by content category",
                    },
                },
                required: ["url"],
            },
        },
    },
    {
        type: "function",
        function: {
            name: "exa_get_contents",
            description:
                "Get full content, highlights, or summaries for specific Exa search result IDs.",
            parameters: {
                type: "object",
                properties: {
                    ids: {
                        type: "array",
                        items: { type: "string" },
                        description: "Array of Exa result IDs to get content for",
                    },
                    text: {
                        type: "boolean",
                        description: "Get full text content (default true)",
                    },
                    highlights: {
                        type: "object",
                        description:
                            "Get highlights matching query: {query: string, numSentences: number}",
                    },
                    summary: {
                        type: "boolean",
                        description: "Get AI-generated summary",
                    },
                },
                required: ["ids"],
            },
        },
    },
];

module.exports = {
    callExaTool,
    EXA_TOOLS,
};
