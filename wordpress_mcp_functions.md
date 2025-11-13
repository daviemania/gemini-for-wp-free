# WordPress MCP Functions

This document outlines the 37 Media Control Protocol (MCP) functions available for interacting with a WordPress site. These functions cover various aspects of WordPress management, including posts, users, comments, media, taxonomies, and site options.

## API Endpoint and Authentication

*   **REST API Endpoint:** `/wp-json/mcp/v1/sse`
*   **Authentication:** Bearer Token `uX484&B$k@c@6072&VdTJi#3`

## Functions

### wp_list_plugins
*   **Description:** List installed plugins (returns array of {Name, Version}).
*   **Arguments:**
    *   `search` (string, optional): Search term for plugins.

### wp_get_users
*   **Description:** Retrieve users (fields: ID, user_login, display_name, roles). If no limit supplied, returns 10. `paged` ignored if `offset` is used.
*   **Arguments:**
    *   `search` (string, optional): Search term for users.
    *   `role` (string, optional): Filter by user role.
    *   `limit` (integer, optional): Maximum number of users to return.
    *   `offset` (integer, optional): Offset for pagination.
    *   `paged` (integer, optional): Page number for pagination (ignored if `offset` is used).

### wp_create_user
*   **Description:** Create a user. Requires `user_login` and `user_email`. Optional: `user_pass` (random if omitted), `display_name`, `role`.
*   **Arguments:**
    *   `user_login` (string, required): The user's login name.
    *   `user_email` (string, required): The user's email address.
    *   `user_pass` (string, optional): The user's password.
    *   `display_name` (string, optional): The user's display name.
    *   `role` (string, optional): The user's role.

### wp_update_user
*   **Description:** Update a user – pass ID plus a “fields” object (user_email, display_name, user_pass, role).
*   **Arguments:**
    *   `ID` (integer, required): The ID of the user to update.
    *   `fields` (object, required): An object containing fields to update:
        *   `user_email` (string, optional)
        *   `display_name` (string, optional)
        *   `user_pass` (string, optional)
        *   `role` (string, optional)

### wp_get_comments
*   **Description:** Retrieve comments (fields: comment_ID, comment_post_ID, comment_author, comment_content, comment_date, comment_approved). Returns 10 by default.
*   **Arguments:**
    *   `post_id` (integer, optional): Filter comments by post ID.
    *   `status` (string, optional): Filter comments by status (e.g., 'approved', 'pending', 'spam').
    *   `search` (string, optional): Search term for comments.
    *   `limit` (integer, optional): Maximum number of comments to return.
    *   `offset` (integer, optional): Offset for pagination.
    *   `paged` (integer, optional): Page number for pagination (ignored if `offset` is used).

### wp_create_comment
*   **Description:** Insert a comment. Requires `post_id` and `comment_content`. Optional `author`, `author_email`, `author_url`.
*   **Arguments:**
    *   `post_id` (integer, required): The ID of the post the comment belongs to.
    *   `comment_content` (string, required): The content of the comment.
    *   `comment_author` (string, optional): The author's name.
    *   `comment_author_email` (string, optional): The author's email.
    *   `comment_author_url` (string, optional): The author's URL.
    *   `comment_approved` (string, optional): Comment approval status (e.g., '1' for approved, '0' for pending).

### wp_update_comment
*   **Description:** Update a comment – pass `comment_ID` plus fields (comment_content, comment_approved).
*   **Arguments:**
    *   `comment_ID` (integer, required): The ID of the comment to update.
    *   `fields` (object, required): An object containing fields to update:
        *   `comment_content` (string, optional)
        *   `comment_approved` (string, optional)

### wp_delete_comment
*   **Description:** Delete a comment. `force` true bypasses trash.
*   **Arguments:**
    *   `comment_ID` (integer, required): The ID of the comment to delete.
    *   `force` (boolean, optional): Whether to bypass trash and permanently delete.

### wp_get_option
*   **Description:** Get a single WordPress option value (scalar or array) by key.
*   **Arguments:**
    *   `key` (string, required): The option key.

### wp_update_option
*   **Description:** Create or update a WordPress option (JSON-serialised if necessary).
*   **Arguments:**
    *   `key` (string, required): The option key.
    *   `value` (string, number, boolean, object, array, required): The option value.

### wp_count_posts
*   **Description:** Return counts of posts by status. Optional `post_type` (default 'post').
*   **Arguments:**
    *   `post_type` (string, optional): The post type to count (e.g., 'post', 'page').

### wp_count_terms
*   **Description:** Return total number of terms in a taxonomy.
*   **Arguments:**
    *   `taxonomy` (string, required): The taxonomy to count terms for (e.g., 'category', 'post_tag').

### wp_count_media
*   **Description:** Return number of attachments (optionally after/before date).
*   **Arguments:**
    *   `after` (string, optional): Date string to count media after.
    *   `before` (string, optional): Date string to count media before.

### wp_get_post_types
*   **Description:** List public post types (key, label).
*   **Arguments:** None.

### wp_get_posts
*   **Description:** Retrieve posts (fields: ID, title, status, excerpt, link). No full content. **If no limit is supplied it returns 10 posts by default.** `paged` is ignored if `offset` is used.
*   **Arguments:**
    *   `post_type` (string, optional): Filter by post type.
    *   `post_status` (string, optional): Filter by post status.
    *   `search` (string, optional): Search term for posts.
    *   `after` (string, optional): Date string to retrieve posts published after.
    *   `before` (string, optional): Date string to retrieve posts published before.
    *   `limit` (integer, optional): Maximum number of posts to return.
    *   `offset` (integer, optional): Offset for pagination.
    *   `paged` (integer, optional): Page number for pagination (ignored if `offset` is used).

### wp_get_post
*   **Description:** Get basic post data by ID (title, content, status, dates). For complete data including all meta and terms, use `wp_get_post_snapshot` instead.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post to retrieve.

### wp_get_post_snapshot
*   **Description:** Get complete post data in ONE call: all post fields, all meta, all terms/taxonomies, featured image, and author. Use this for WooCommerce products, events, or any post type where you need full context. Reduces 10-20 API calls to just 1. Returns structured JSON with post, meta, terms, thumbnail, and author keys.
*   **Arguments:**
    *   `ID` (integer, required): Post ID.
    *   `include` (array of strings, optional): Optional: fields to include (default: all). Options: `meta`, `terms`, `thumbnail`, `author`.

### wp_create_post
*   **Description:** Create a post or page – `post_title` required; Markdown accepted in `post_content`; defaults to draft `post_status` and 'post' `post_type`; set categories later with `wp_add_post_terms`; `meta_input` is an associative array of custom-field key/value pairs.
*   **Arguments:**
    *   `post_title` (string, required): The title of the post.
    *   `post_content` (string, optional): The content of the post (Markdown accepted).
    *   `post_excerpt` (string, optional): The post excerpt.
    *   `post_status` (string, optional): The status of the post (e.g., 'publish', 'draft').
    *   `post_type` (string, optional): The type of post (e.g., 'post', 'page').
    *   `post_name` (string, optional): The post slug.
    *   `meta_input` (object, optional): Associative array of custom fields.

### wp_update_post
*   **Description:** Update post fields and/or meta in ONE call. Pass ID + "fields" object (post_title, post_content, post_status, etc.) and/or "meta_input" object for custom fields. Efficient for WooCommerce products: update title, price + stock together. Note: `post_category` REPLACES categories; use `wp_add_post_terms` to append instead.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post to update.
    *   `fields` (object, optional): An object containing fields to update:
        *   `post_title` (string, optional)
        *   `post_content` (string, optional)
        *   `post_status` (string, optional)
        *   `post_name` (string, optional)
        *   `post_excerpt` (string, optional)
        *   `post_category` (array of integers, optional): Replaces existing categories.
    *   `meta_input` (object, optional): Associative array of custom fields.

### wp_delete_post
*   **Description:** Delete/trash a post.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post to delete.
    *   `force` (boolean, optional): Whether to bypass trash and permanently delete.

### wp_get_post_meta
*   **Description:** Get specific post meta field(s). Provide "key" to fetch a single value; omit to fetch all custom fields. If you need ALL meta along with post data and terms, use `wp_get_post_snapshot` instead for efficiency.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post.
    *   `key` (string, optional): The meta key to retrieve.

### wp_update_post_meta
*   **Description:** Update post meta efficiently. Use "meta" object to update MULTIPLE fields at once (e.g., `_price: "19.99", _stock: "50", _sku: "WIDGET"`), or use "key"+"value" for a single field. Essential for WooCommerce products and custom post types.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post.
    *   `meta` (object, optional): Key/value pairs to set. Alternative: provide "key" + "value".
    *   `key` (string, optional): The meta key to update.
    *   `value` (string, number, boolean, optional): The meta value.

### wp_delete_post_meta
*   **Description:** Delete custom field(s) from a post. Provide value to remove a single row; omit value to delete all rows for the key.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post.
    *   `key` (string, required): The meta key to delete.
    *   `value` (string, number, boolean, optional): The specific meta value to delete.

### wp_set_featured_image
*   **Description:** Attach or remove a featured image (thumbnail) for a post/page. Provide `media_id` to attach, omit or null to remove.
*   **Arguments:**
    *   `post_id` (integer, required): The ID of the post.
    *   `media_id` (integer, optional): The ID of the media item to set as featured image.

### wp_get_taxonomies
*   **Description:** List taxonomies for a post type.
*   **Arguments:**
    *   `post_type` (string, optional): The post type to retrieve taxonomies for.

### wp_get_terms
*   **Description:** List terms of a taxonomy.
*   **Arguments:**
    *   `taxonomy` (string, required): The taxonomy to retrieve terms from.
    *   `search` (string, optional): Search term for terms.
    *   `parent` (integer, optional): Filter by parent term ID.
    *   `limit` (integer, optional): Maximum number of terms to return.

### wp_create_term
*   **Description:** Create a term.
*   **Arguments:**
    *   `taxonomy` (string, required): The taxonomy the term belongs to.
    *   `term_name` (string, required): The name of the term.
    *   `slug` (string, optional): The slug of the term.
    *   `description` (string, optional): The description of the term.
    *   `parent` (integer, optional): The parent term ID.

### wp_update_term
*   **Description:** Update a term.
*   **Arguments:**
    *   `term_id` (integer, required): The ID of the term to update.
    *   `taxonomy` (string, required): The taxonomy the term belongs to.
    *   `name` (string, optional): The new name of the term.
    *   `slug` (string, optional): The new slug of the term.
    *   `description` (string, optional): The new description of the term.
    *   `parent` (integer, optional): The new parent term ID.

### wp_delete_term
*   **Description:** Delete a term.
*   **Arguments:**
    *   `term_id` (integer, required): The ID of the term to delete.
    *   `taxonomy` (string, required): The taxonomy the term belongs to.

### wp_get_post_terms
*   **Description:** Get terms attached to a post.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post.
    *   `taxonomy` (string, optional): The taxonomy to retrieve terms from.

### wp_add_post_terms
*   **Description:** Attach or replace terms for a post. Set "append=true" to ADD terms to existing ones, or "append=false" (default) to REPLACE all terms. Use for categories, tags, or WooCommerce attributes (pa_color, pa_size, etc.).
*   **Arguments:**
    *   `ID` (integer, required): The ID of the post.
    *   `taxonomy` (string, required): The taxonomy to add terms to.
    *   `terms` (array of integers, required): An array of term IDs.
    *   `append` (boolean, optional): Whether to append terms (true) or replace them (false).

### wp_get_media
*   **Description:** List media items.
*   **Arguments:**
    *   `search` (string, optional): Search term for media items.
    *   `after` (string, optional): Date string to retrieve media uploaded after.
    *   `before` (string, optional): Date string to retrieve media uploaded before.
    *   `limit` (integer, optional): Maximum number of media items to return.

### wp_upload_media
*   **Description:** Download file from URL and add to Media Library.
*   **Arguments:**
    *   `url` (string, required): The URL of the file to upload.
    *   `title` (string, optional): The title of the media item.
    *   `description` (string, optional): The description of the media item.
    *   `alt` (string, optional): The alt text for the media item.

### wp_update_media
*   **Description:** Update attachment meta.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the media item to update.
    *   `title` (string, optional): The new title.
    *   `caption` (string, optional): The new caption.
    *   `description` (string, optional): The new description.
    *   `alt` (string, optional): The new alt text.

### wp_delete_media
*   **Description:** Delete/trash an attachment.
*   **Arguments:**
    *   `ID` (integer, required): The ID of the media item to delete.
    *   `force` (boolean, optional): Whether to bypass trash and permanently delete.

### mwai_vision
*   **Description:** Analyze an image via AI Engine Vision.
*   **Arguments:**
    *   `message` (string, required): The message/prompt for image analysis.
    *   `url` (string, optional): The URL of the image to analyze.
    *   `path` (string, optional): The local path to the image to analyze.

### mwai_image
*   **Description:** Generate an image with AI Engine and store it in the Media Library. Optional: title, caption, description, alt. Returns { id, url, title, caption, alt }.
*   **Arguments:**
    *   `message` (string, required): Prompt describing the desired image.
    *   `postId` (integer, optional): Optional post ID to attach the image to.