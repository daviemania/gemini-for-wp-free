# Current Task

- Continue with the AI Editor role, focusing on improving WordPress posts. The current sub-task is to work through the posts, starting with the oldest.

## Custom Tools

### `create_new_post.php`

-   **Purpose:** Create new WordPress posts.
-   **Input:** Post title, content, status.
-   **Output:** Post ID, status message.
-   **Security:** Sanitize all inputs.
-   **Error Handling:** Implement robust error checks.
-   **Dependencies:** WordPress core functions.
-   **Usage:** Via direct script execution or API endpoint.

### `format_post_content.py`

-   **Purpose:** Formats plain text content into HTML.
-   **Input:** Path to the input plain text file.
-   **Output:** Path to the output HTML file.
-   **Usage:** `python3 format_post_content.py <input_file> <output_file>`