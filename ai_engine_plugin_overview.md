# AI Engine Plugin MCP Functions Overview

This document provides an overview of the Media Control Protocol (MCP) functions exposed by the AI Engine plugin for WordPress. These functions allow for comprehensive interaction with various WordPress components and AI-powered features.

## REST API Endpoint and Authentication

*   **REST API Endpoint:** `/wp-json/mcp/v1/sse`
*   **Authentication:** Bearer Token `uX4484&B$k@c@6072&VdTJi#3`
*   **Protocol:** All requests must use the JSON-RPC 2.0 format.

## Available Functions (37 functions registered via MCP.Core)

### WordPress Core Functions

1.  **`wp_list_plugins`**
    *   **Description:** Lists installed plugins (returns array of {Name, Version}).
    *   **Arguments:** `search` (string, optional)
2.  **`wp_get_users`**
    *   **Description:** Retrieves users (fields: ID, user_login, display_name, roles). Returns 10 by default. `paged` ignored if `offset` is used.
    *   **Arguments:** `search` (string, optional), `role` (string, optional), `limit` (integer, optional), `offset` (integer, optional), `paged` (integer, optional)
3.  **`wp_create_user`**
    *   **Description:** Creates a user. Requires `user_login` and `user_email`. Optional: `user_pass` (random if omitted), `display_name`, `role`.
    *   **Arguments:** `user_login` (string, required), `user_email` (string, required), `user_pass` (string, optional), `display_name` (string, optional), `role` (string, optional)
4.  **`wp_update_user`**
    *   **Description:** Updates a user – pass ID plus a “fields” object (user_email, display_name, user_pass, role).
    *   **Arguments:** `ID` (integer, required), `fields` (object, required: `user_email` (string, optional), `display_name` (string, optional), `user_pass` (string, optional), `role` (string, optional))
5.  **`wp_get_comments`**
    *   **Description:** Retrieves comments (fields: comment_ID, comment_post_ID, comment_author, comment_content, comment_date, comment_approved). Returns 10 by default.
    *   **Arguments:** `post_id` (integer, optional), `status` (string, optional), `search` (string, optional), `limit` (integer, optional), `offset` (integer, optional), `paged` (integer, optional)
6.  **`wp_create_comment`**
    *   **Description:** Inserts a comment. Requires `post_id` and `comment_content`. Optional `author`, `author_email`, `author_url`.
    *   **Arguments:** `post_id` (integer, required), `comment_content` (string, required), `comment_author` (string, optional), `comment_author_email` (string, optional), `comment_author_url` (string, optional), `comment_approved` (string, optional)
7.  **`wp_update_comment`**
    *   **Description:** Updates a comment – pass `comment_ID` plus fields (comment_content, comment_approved).
    *   **Arguments:** `comment_ID` (integer, required), `fields` (object, required: `comment_content` (string, optional), `comment_approved` (string, optional))
8.  **`wp_delete_comment`**
    *   **Description:** Deletes a comment. `force` true bypasses trash.
    *   **Arguments:** `comment_ID` (integer, required), `force` (boolean, optional)
9.  **`wp_get_option`**
    *   **Description:** Gets a single WordPress option value (scalar or array) by key.
    *   **Arguments:** `key` (string, required)
10. **`wp_update_option`**
    *   **Description:** Creates or updates a WordPress option (JSON-serialised if necessary).
    *   **Arguments:** `key` (string, required), `value` (string, number, boolean, object, array, required)
11. **`wp_count_posts`**
    *   **Description:** Returns counts of posts by status. Optional `post_type` (default post).
    *   **Arguments:** `post_type` (string, optional)
12. **`wp_count_terms`**
    *   **Description:** Returns total number of terms in a taxonomy.
    *   **Arguments:** `taxonomy` (string, required)
13. **`wp_count_media`**
    *   **Description:** Returns number of attachments (optionally after/before date).
    *   **Arguments:** `after` (string, optional), `before` (string, optional)
14. **`wp_get_post_types`**
    *   **Description:** Lists public post types (key, label).
    *   **Arguments:** None.
15. **`wp_get_posts`**
    *   **Description:** Retrieves posts (fields: ID, title, status, excerpt, link). No full content. **If no limit is supplied it returns 10 posts by default.** `paged` is ignored if `offset` is used.
    *   **Arguments:** `post_type` (string, optional), `post_status` (string, optional), `search` (string, optional), `after` (string, optional), `before` (string, optional), `limit` (integer, optional), `offset` (integer, optional), `paged` (integer, optional)
16. **`wp_get_post`**
    *   **Description:** Gets basic post data by ID (title, content, status, dates). For complete data including all meta and terms, use `wp_get_post_snapshot` instead.
    *   **Arguments:** `ID` (integer, required)
17. **`wp_get_post_snapshot`**
    *   **Description:** Gets complete post data in ONE call: all post fields, all meta, all terms/taxonomies, featured image, and author. Use this for WooCommerce products, events, or any post type where you need full context. Reduces 10-20 API calls to just 1. Returns structured JSON with post, meta, terms, thumbnail, and author keys.
    *   **Arguments:** `ID` (integer, required), `include` (array of strings, optional: `meta`, `terms`, `thumbnail`, `author`)
18. **`wp_create_post`**
    *   **Description:** Creates a post or page – `post_title` required; Markdown accepted in `post_content`; defaults to `draft` `post_status` and `post` `post_type`; set categories later with `wp_add_post_terms`; `meta_input` is an associative array of custom-field key/value pairs.
    *   **Arguments:** `post_title` (string, required), `post_content` (string, optional), `post_excerpt` (string, optional), `post_status` (string, optional), `post_type` (string, optional), `post_name` (string, optional), `meta_input` (object, optional)
19. **`wp_update_post`**
    *   **Description:** Updates post fields and/or meta in ONE call. Pass ID + "fields" object (post_title, post_content, post_status, etc.) and/or "meta_input" object for custom fields. Efficient for WooCommerce products: update title, price + stock together. Note: `post_category` REPLACES categories; use `wp_add_post_terms` to append instead.
    *   **Arguments:** `ID` (integer, required), `fields` (object, optional: `post_title` (string, optional), `post_content` (string, optional), `post_status` (string, optional), `post_name` (string, optional), `post_excerpt` (string, optional), `post_category` (array of integers, optional)), `meta_input` (object, optional)
20. **`wp_delete_post`**
    *   **Description:** Deletes/trashes a post.
    *   **Arguments:** `ID` (integer, required), `force` (boolean, optional)
21. **`wp_get_post_meta`**
    *   **Description:** Gets specific post meta field(s). Provide "key" to fetch a single value; omit to fetch all custom fields. If you need ALL meta along with post data and terms, use `wp_get_post_snapshot` instead for efficiency.
    *   **Arguments:** `ID` (integer, required), `key` (string, optional)
22. **`wp_update_post_meta`**
    *   **Description:** Updates post meta efficiently. Use "meta" object to update MULTIPLE fields at once (e.g., `{_price: "19.99", _stock: "50", _sku: "WIDGET"}`), or use "key"+"value" for a single field. Essential for WooCommerce products and custom post types.
    *   **Arguments:** `ID` (integer, required), `meta` (object, optional), `key` (string, optional), `value` (string, number, boolean, optional)
23. **`wp_delete_post_meta`**
    *   **Description:** Deletes custom field(s) from a post. Provide value to remove a single row; omit value to delete all rows for the key.
    *   **Arguments:** `ID` (integer, required), `key` (string, required), `value` (string, number, boolean, optional)
24. **`wp_set_featured_image`**
    *   **Description:** Attaches or removes a featured image (thumbnail) for a post/page. Provide `media_id` to attach, omit or null to remove.
    *   **Arguments:** `post_id` (integer, required), `media_id` (integer, optional)
25. **`wp_get_taxonomies`**
    *   **Description:** Lists taxonomies for a post type.
    *   **Arguments:** `post_type` (string, optional)
26. **`wp_get_terms`**
    *   **Description:** Lists terms of a taxonomy.
    *   **Arguments:** `taxonomy` (string, required), `search` (string, optional), `parent` (integer, optional), `limit` (integer, optional)
27. **`wp_create_term`**
    *   **Description:** Creates a term.
    *   **Arguments:** `taxonomy` (string, required), `term_name` (string, required), `slug` (string, optional), `description` (string, optional), `parent` (integer, optional)
28. **`wp_update_term`**
    *   **Description:** Updates a term.
    *   **Arguments:** `term_id` (integer, required), `taxonomy` (string, required), `name` (string, optional), `slug` (string, optional), `description` (string, optional), `parent` (integer, optional)
29. **`wp_delete_term`**
    *   **Description:** Deletes a term.
    *   **Arguments:** `term_id` (integer, required), `taxonomy` (string, required)
30. **`wp_get_post_terms`**
    *   **Description:** Gets terms attached to a post.
    *   **Arguments:** `ID` (integer, required), `taxonomy` (string, optional)
31. **`wp_add_post_terms`**
    *   **Description:** Attaches or replaces terms for a post. Set "append=true" to ADD terms to existing ones, or "append=false" (default) to REPLACE all terms. Use for categories, tags, or WooCommerce attributes (pa_color, pa_size, etc.).
    *   **Arguments:** `ID` (integer, required), `taxonomy` (string, required), `terms` (array of integers, required), `append` (boolean, optional)
32. **`wp_get_media`**
    *   **Description:** Lists media items.
    *   **Arguments:** `search` (string, optional), `after` (string, optional), `before` (string, optional), `limit` (integer, optional)
33. **`wp_upload_media`**
    *   **Description:** Downloads file from URL and add to Media Library.
    *   **Arguments:** `url` (string, required), `title` (string, optional), `description` (string, optional), `alt` (string, optional)
34. **`wp_update_media`**
    *   **Description:** Updates attachment meta.
    *   **Arguments:** `ID` (integer, required), `title` (string, optional), `caption` (string, optional), `description` (string, optional), `alt` (string, optional)
35. **`wp_delete_media`**
    *   **Description:** Deletes/trashes an attachment.
    *   **Arguments:** `ID` (integer, required), `force` (boolean, optional)

### AI Engine Specific Functions

36. **`mwai_vision`**
    *   **Description:** Analyzes an image via AI Engine Vision.
    *   **Arguments:** `message` (string, required), `url` (string, optional), `path` (string, optional)
37. **`mwai_image`**
    *   **Description:** Generates an image with AI Engine and stores it in the Media Library. Optional: title, caption, description, alt. Returns `{ id, url, title, caption, alt }`.
    *   **Arguments:** `message` (string, required), `postId` (integer, optional), `title` (string, optional), `caption` (string, optional), `description` (string, optional), `alt` (string, optional)
