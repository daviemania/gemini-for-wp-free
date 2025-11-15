module.exports = {
    apps: [
        {
            name: "gemini-mcp-relay",
            script: "./mcp.js",
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: "200M",
            max_restarts: 5, // Add restart limit
            min_uptime: "30s", // Add minimum uptime
            restart_delay: 3000, // Add restart delay
            env: {
                NODE_ENV: "production",
            },
            error_file: "./logs/mcp-error.log",
            out_file: "./logs/mcp-out.log",
            log_date_format: "YYYY-MM-DD HH:mm:ss Z",
            merge_logs: true,
        },
        {
            name: "gemini-server",
            script: "./server.js",
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: "500M",
            max_restarts: 5, // Add restart limit
            min_uptime: "30s", // Add minimum uptime
            restart_delay: 3000, // Add restart delay
            env: {
                NODE_ENV: "production",
                PORT: 3000,
            },
            error_file: "./logs/server-error.log",
            out_file: "./logs/server-out.log",
            log_date_format: "YYYY-MM-DD HH:mm:ss Z",
            merge_logs: true,
        },
        {
            name: "git-backup-service",
            script: "./backup-service.js",
            instances: 1,
            exec_mode: "fork",
            autorestart: false, // Critical: no auto-restart for scheduled service
            watch: false,
            max_memory_restart: "100M",
            env: {
                NODE_ENV: "production",
            },
            error_file: "./logs/backup-error.log",
            out_file: "./logs/backup-out.log",
            log_date_format: "YYYY-MM-DD HH:mm:ss Z",
            merge_logs: true,
        },
    ],
};
