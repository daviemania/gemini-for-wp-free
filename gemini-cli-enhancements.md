# Gemini CLI Enhancements

This document details the improvements made to the `gemini-cli.js` script to enhance its performance, precision, and context-awareness.

## Key Improvements

### 1. System Prompt Integration

*   **Problem:** The previous version of the script operated without any predefined context, leading to generic and less precise responses.
*   **Solution:** The new script now reads the `GEMINI.md` file at startup and uses its content as a system prompt. This provides me with a consistent personality, a better understanding of the user's technical environment, and improved response structure.

### 2. Conversation History

*   **Problem:** The old script was stateless and had no memory of previous interactions.
*   **Solution:** The enhanced script now maintains an in-memory conversation history. Each new prompt is sent to the API along with the preceding conversation, allowing me to retain context throughout a session.

### 3. Configurable Parameters

*   **Problem:** The model, temperature, and max output tokens were hardcoded, limiting flexibility and control over the output.
*   **Solution:** The new script accepts the following command-line arguments:
    *   `--model <string>`: Sets the model to use (e.g., `gemini-1.5-flash`).
    *   `-t, --temperature <number>`: Sets the temperature for generation (e.g., `0.2` for more deterministic results).
    *   `-m, --max-tokens <number>`: Sets the maximum number of output tokens.

### 4. Optimized Model Selection

*   **Problem:** The previous script tested all available models on every run, which was inefficient.
*   **Solution:** The model selection logic has been optimized. The script now tests the specified (or default) model first. If that fails, it iterates through the other available models. This test is only performed once per session.

## How to Use the Enhanced CLI

The script is now named `gemini-cli.js`. You can run it interactively as before:

```bash
./gemini-cli.js
```

You can also use the new command-line options to customize its behavior:

```bash
./gemini-cli.js --model gemini-1.5-pro --temperature 0.2 --max-tokens 4096
```
