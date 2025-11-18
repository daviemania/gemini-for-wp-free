# /exa - Exa AI Semantic Web Search

Use Exa search for high-quality, semantic web results. Better than keywords—uses neural embeddings.

**Examples:**
- `/exa Search for latest WordPress MCP updates, type:neural, category:news`
- `/exa Find similar to https://exa.ai/blog, numResults:5`
- `/exa Get contents for IDs from previous search: ["id1", "id2"]`

**Auto-runs:** `node ./exa-tools.js exa_search --query "{{query}}" --type neural --numResults 5`

Provide query + optional params (numResults, type:neural/keyword/auto, category, dates).