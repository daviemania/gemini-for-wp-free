const http = require("http");
const { default: fetch } = require("node-fetch");

const WORDPRESS_MCP_ENDPOINT = "https://maniainc.com/wp-json/mcp/v1/sse";
const BEARER_TOKEN = process.env.WP_MCP_TOKEN;
const RELAY_PORT = 3001;

const server = http.createServer(async (req, res) => {
    // Add CORS headers
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
    res.setHeader(
        "Access-Control-Allow-Headers",
        "Content-Type, Authorization",
    );
    res.setHeader("Access-Control-Allow-Credentials", "true");

    // Handle preflight requests
    if (req.method === "OPTIONS") {
        res.statusCode = 200;
        res.end();
        return;
    }

    if (req.method === "POST" && req.url === "/") {
        let body = "";

        req.on("data", (chunk) => {
            body += chunk.toString();
        });

        req.on("end", async () => {
            try {
                console.log("Forwarding request to:", WORDPRESS_MCP_ENDPOINT);

                // Parse the incoming simple JSON
                const incomingData = JSON.parse(body);

                console.log("Request Headers:", {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${BEARER_TOKEN}`,
                });
                console.log(
                    "Request Body:",
                    JSON.stringify(incomingData, null, 2),
                );

                // Construct the JSON-RPC 2.0 payload
                const jsonRpcPayload = {
                    jsonrpc: "2.0",
                    id: Date.now(), // Use timestamp for unique IDs
                    method: "tools/call",
                    params: {
                        name: incomingData.tool,
                        arguments: incomingData.args || {},
                    },
                };

                console.log(
                    "JSON-RPC Payload:",
                    JSON.stringify(jsonRpcPayload, null, 2),
                );

                const response = await fetch(WORDPRESS_MCP_ENDPOINT, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${BEARER_TOKEN}`,
                    },
                    body: JSON.stringify(jsonRpcPayload),
                });

                console.log("Response Status:", response.status);

                // Get response as text first to handle both JSON and streaming
                const responseText = await response.text();

                console.log("Response Body:", responseText.substring(0, 500));

                // Forward response with appropriate headers
                res.statusCode = response.status;
                res.setHeader("Content-Type", "application/json");

                // Try to parse and extract the actual result
                try {
                    const jsonRpcResponse = JSON.parse(responseText);

                    // If it's a JSON-RPC response with result, extract it
                    if (jsonRpcResponse.result) {
                        res.end(JSON.stringify(jsonRpcResponse.result));
                    } else {
                        res.end(responseText);
                    }
                } catch (parseError) {
                    // If not valid JSON, return as is
                    res.end(responseText);
                }
            } catch (error) {
                console.error("Relay error:", error);
                res.statusCode = 500;
                res.setHeader("Content-Type", "application/json");
                res.end(
                    JSON.stringify({
                        success: false,
                        error: "Relay failed to connect to WordPress MCP",
                        details: error.message,
                    }),
                );
            }
        });
    } else if (req.method === "GET" && req.url === "/") {
        // Health check endpoint
        res.statusCode = 200;
        res.setHeader("Content-Type", "application/json");
        res.end(
            JSON.stringify({
                status: "online",
                service: "MCP Relay",
                endpoint: WORDPRESS_MCP_ENDPOINT,
                port: RELAY_PORT,
            }),
        );
    } else {
        res.statusCode = 404;
        res.setHeader("Content-Type", "text/plain");
        res.end("Not Found");
    }
});

server.listen(RELAY_PORT, () => {
    console.log(`MCP Relay listening on port ${RELAY_PORT}`);
    console.log(`Forwarding requests to: ${WORDPRESS_MCP_ENDPOINT}`);
});

// Graceful shutdown
process.on("SIGTERM", () => {
    console.log("SIGTERM received, shutting down gracefully");
    server.close(() => {
        console.log("Server closed");
        process.exit(0);
    });
});

process.on("SIGINT", () => {
    console.log("SIGINT received, shutting down gracefully");
    server.close(() => {
        console.log("Server closed");
        process.exit(0);
    });
});
