# Test Plan: WordPress MCP Functionality Verification

This plan outlines the steps to manually verify the newly documented WordPress MCP integration.

## 1. Verify API Endpoint Accessibility
- **Objective:** Confirm that the WordPress MCP REST API endpoint is live and accessible.
- **Action:** Use `curl` to send a basic request to the `/wp-json/mcp/v1/sse` endpoint.
- **Expected Outcome:** The server should respond with a status indicating it is reachable, even if it's an error message about missing authentication, rather than a connection refused error.

## 2. Verify Core Function Execution
- **Objective:** Confirm that a simple, read-only MCP function can be executed successfully with the provided bearer token.
- **Action:**
    1. Select the `wp_list_plugins` function.
    2. Construct a `curl` command including the `Authorization: Bearer uX484&B$k@c@6072&VdTJi#3` header and the appropriate JSON payload for the function.
    3. Execute the command.
- **Expected Outcome:** The command should return a `200 OK` status and a JSON response containing a list of installed WordPress plugins, confirming that authentication and the MCP function handling are working correctly.

## 3. Log Test Results
- **Objective:** Maintain a clear record of the testing process and its outcomes.
- **Action:**
    1. Create a new file named `TEST.md`.
    2. For each step above, document the exact command executed.
    3. Record the full output from the command.
    4. State whether the outcome PASSED or FAILED based on the expected result.
