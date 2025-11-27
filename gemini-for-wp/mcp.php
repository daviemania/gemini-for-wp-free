<?php

class Gemini_MCP_Tools_MCP {
  
  public function __construct() {
    add_action( 'init', array( $this, 'init' ), 20 );
  }
  
  public function init() {
    global $mwai;
    if ( isset( $mwai ) ) {
      add_filter( 'mwai_mcp_tools', array( $this, 'register_tools' ) );
      add_filter( 'mwai_mcp_callback', array( $this, 'handle_tool_execution' ), 10, 4 );
    }
  }
  
  public function register_tools( $tools ) {
    $gemini_tools = [
      [
        'name' => 'wp_list_plugins',
        'description' => 'Lists installed plugins (returns array of {Name, Version}).',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'search' => ['type' => 'string', 'description' => 'Search term for plugins.']
          ]
        ]
      ],
      [
        'name' => 'wp_get_users',
        'description' => 'Retrieves users (fields: ID, user_login, display_name, roles). Returns 10 by default.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'search' => ['type' => 'string', 'description' => 'Search term for users.'],
            'role' => ['type' => 'string', 'description' => 'Filter users by role.'],
            'limit' => ['type' => 'integer', 'description' => 'Maximum number of users to retrieve.'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination.'],
            'paged' => ['type' => 'integer', 'description' => 'Page number for pagination.']
          ]
        ]
      ],
      [
        'name' => 'wp_create_user',
        'description' => 'Creates a user. Requires user_login and user_email.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'user_login' => ['type' => 'string', 'description' => 'The user\'s login name.'],
            'user_email' => ['type' => 'string', 'description' => 'The user\'s email address.'],
            'user_pass' => ['type' => 'string', 'description' => 'The user\'s password.'],
            'display_name' => ['type' => 'string', 'description' => 'The user\'s display name.'],
            'role' => ['type' => 'string', 'description' => 'The user\'s role.']
          ],
          'required' => ['user_login', 'user_email']
        ]
      ],
      [
        'name' => 'wp_update_user',
        'description' => 'Updates a user. Pass ID plus a "fields" object.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the user to update.'],
            'fields' => ['type' => 'object', 'description' => 'An object containing fields to update.']
          ],
          'required' => ['ID', 'fields']
        ]
      ],
      [
        'name' => 'wp_get_comments',
        'description' => 'Retrieves comments. Returns 10 by default.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_id' => ['type' => 'integer', 'description' => 'Filter comments by post ID.'],
            'status' => ['type' => 'string', 'description' => 'Filter comments by status.'],
            'search' => ['type' => 'string', 'description' => 'Search term for comments.'],
            'limit' => ['type' => 'integer', 'description' => 'Maximum number of comments to retrieve.'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination.'],
            'paged' => ['type' => 'integer', 'description' => 'Page number for pagination.']
          ]
        ]
      ],
      [
        'name' => 'wp_create_comment',
        'description' => 'Inserts a comment. Requires post_id and comment_content.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_id' => ['type' => 'integer', 'description' => 'The ID of the post the comment belongs to.'],
            'comment_content' => ['type' => 'string', 'description' => 'The content of the comment.'],
            'comment_author' => ['type' => 'string', 'description' => 'The author\'s name.'],
            'comment_author_email' => ['type' => 'string', 'description' => 'The author\'s email.'],
            'comment_author_url' => ['type' => 'string', 'description' => 'The author\'s URL.'],
            'comment_approved' => ['type' => 'string', 'description' => 'Comment approval status.']
          ],
          'required' => ['post_id', 'comment_content']
        ]
      ],
      [
        'name' => 'wp_update_comment',
        'description' => 'Updates a comment. Pass comment_ID plus fields.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'comment_ID' => ['type' => 'integer', 'description' => 'The ID of the comment to update.'],
            'fields' => ['type' => 'object', 'description' => 'An object containing fields to update.']
          ],
          'required' => ['comment_ID', 'fields']
        ]
      ],
      [
        'name' => 'wp_delete_comment',
        'description' => 'Deletes a comment. `force` true bypasses trash.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'comment_ID' => ['type' => 'integer', 'description' => 'The ID of the comment to delete.'],
            'force' => ['type' => 'boolean', 'description' => 'Whether to bypass trash and permanently delete.']
          ],
          'required' => ['comment_ID']
        ]
      ],
      [
        'name' => 'wp_get_option',
        'description' => 'Gets a single WordPress option value by key.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'key' => ['type' => 'string', 'description' => 'The option key.']
          ],
          'required' => ['key']
        ]
      ],
      [
        'name' => 'wp_update_option',
        'description' => 'Creates or updates a WordPress option.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'key' => ['type' => 'string', 'description' => 'The option key.'],
            'value' => ['type' => 'string', 'description' => 'The option value. Can be a string, number, boolean, or JSON.']
          ],
          'required' => ['key', 'value']
        ]
      ],
      [
        'name' => 'wp_count_posts',
        'description' => 'Returns counts of posts by status.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_type' => ['type' => 'string', 'description' => 'The post type to count.']
          ]
        ]
      ],
      [
        'name' => 'wp_count_terms',
        'description' => 'Returns total number of terms in a taxonomy.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy to count terms for.']
          ],
          'required' => ['taxonomy']
        ]
      ],
      [
        'name' => 'wp_count_media',
        'description' => 'Returns number of attachments.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'after' => ['type' => 'string', 'description' => 'Date string to count media after.'],
            'before' => ['type' => 'string', 'description' => 'Date string to count media before.']
          ]
        ]
      ],
      [
        'name' => 'wp_get_post_types',
        'description' => 'Lists public post types (key, label).',
        'category' => 'WordPress Core',
        'inputSchema' => ['type' => 'object']
      ],
      [
        'name' => 'wp_get_posts',
        'description' => 'Retrieves posts. Returns 10 by default.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_type' => ['type' => 'string', 'description' => 'Filter by post type.'],
            'post_status' => ['type' => 'string', 'description' => 'Filter by post status.'],
            'search' => ['type' => 'string', 'description' => 'Search term for posts.'],
            'after' => ['type' => 'string', 'description' => 'Date string to retrieve posts after.'],
            'before' => ['type' => 'string', 'description' => 'Date string to retrieve posts before.'],
            'limit' => ['type' => 'integer', 'description' => 'Maximum number of posts to retrieve.'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination.'],
            'paged' => ['type' => 'integer', 'description' => 'Page number for pagination.']
          ]
        ]
      ],
      [
        'name' => 'wp_get_post',
        'description' => 'Gets basic post data by ID.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post to retrieve.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_get_post_snapshot',
        'description' => 'Gets complete post data in ONE call.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'Post ID.'],
            'include' => ['type' => 'array', 'description' => 'Optional: fields to include (meta, terms, thumbnail, author).']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_create_post',
        'description' => 'Creates a post or page.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_title' => ['type' => 'string', 'description' => 'The title of the post.'],
            'post_content' => ['type' => 'string', 'description' => 'The content of the post (Markdown accepted).'],
            'post_excerpt' => ['type' => 'string', 'description' => 'The post excerpt.'],
            'post_status' => ['type' => 'string', 'description' => 'The status of the post (e.g., \'publish\', \'draft\').'],
            'post_type' => ['type' => 'string', 'description' => 'The type of post (e.g., \'post\', \'page\').'],
            'post_name' => ['type' => 'string', 'description' => 'The post slug.'],
            'meta_input' => ['type' => 'object', 'description' => 'Associative array of custom fields.']
          ],
          'required' => ['post_title']
        ]
      ],
      [
        'name' => 'wp_update_post',
        'description' => 'Updates post fields and/or meta in ONE call.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post to update.'],
            'fields' => ['type' => 'object', 'description' => 'An object containing fields to update.'],
            'meta_input' => ['type' => 'object', 'description' => 'Associative array of custom fields.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_delete_post',
        'description' => 'Deletes/trashes a post.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post to delete.'],
            'force' => ['type' => 'boolean', 'description' => 'Whether to bypass trash and permanently delete.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_get_post_meta',
        'description' => 'Gets specific post meta field(s).',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post.'],
            'key' => ['type' => 'string', 'description' => 'The meta key to retrieve.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_update_post_meta',
        'description' => 'Updates post meta efficiently.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post.'],
            'meta' => ['type' => 'object', 'description' => 'Key/value pairs to set.'],
            'key' => ['type' => 'string', 'description' => 'The meta key to update.'],
            'value' => ['type' => 'string', 'description' => 'The meta value.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_delete_post_meta',
        'description' => 'Deletes custom field(s) from a post.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post.'],
            'key' => ['type' => 'string', 'description' => 'The meta key to delete.'],
            'value' => ['type' => 'string', 'description' => 'The meta value to match for deletion.']
          ],
          'required' => ['ID', 'key']
        ]
      ],
      [
        'name' => 'wp_set_featured_image',
        'description' => 'Attaches or removes a featured image for a post/page.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_id' => ['type' => 'integer', 'description' => 'The ID of the post.'],
            'media_id' => ['type' => 'integer', 'description' => 'The ID of the media item to set as featured image.']
          ],
          'required' => ['post_id']
        ]
      ],
      [
        'name' => 'wp_get_taxonomies',
        'description' => 'Lists taxonomies for a post type.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'post_type' => ['type' => 'string', 'description' => 'The post type to retrieve taxonomies for.']
          ]
        ]
      ],
      [
        'name' => 'wp_get_terms',
        'description' => 'Lists terms of a taxonomy.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy to retrieve terms from.'],
            'search' => ['type' => 'string', 'description' => 'Search term for terms.'],
            'parent' => ['type' => 'integer', 'description' => 'Filter by parent term ID.'],
            'limit' => ['type' => 'integer', 'description' => 'Maximum number of terms to retrieve.']
          ],
          'required' => ['taxonomy']
        ]
      ],
      [
        'name' => 'wp_create_term',
        'description' => 'Creates a term.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy the term belongs to.'],
            'term_name' => ['type' => 'string', 'description' => 'The name of the term.'],
            'slug' => ['type' => 'string', 'description' => 'The slug for the term.'],
            'description' => ['type' => 'string', 'description' => 'The description of the term.'],
            'parent' => ['type' => 'integer', 'description' => 'The parent term ID.']
          ],
          'required' => ['taxonomy', 'term_name']
        ]
      ],
      [
        'name' => 'wp_update_term',
        'description' => 'Updates a term.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'term_id' => ['type' => 'integer', 'description' => 'The ID of the term to update.'],
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy the term belongs to.'],
            'name' => ['type' => 'string', 'description' => 'The new name of the term.'],
            'slug' => ['type' => 'string', 'description' => 'The new slug for the term.'],
            'description' => ['type' => 'string', 'description' => 'The new description of the term.'],
            'parent' => ['type' => 'integer', 'description' => 'The new parent term ID.']
          ],
          'required' => ['term_id', 'taxonomy']
        ]
      ],
      [
        'name' => 'wp_delete_term',
        'description' => 'Deletes a term.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'term_id' => ['type' => 'integer', 'description' => 'The ID of the term to delete.'],
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy the term belongs to.']
          ],
          'required' => ['term_id', 'taxonomy']
        ]
      ],
      [
        'name' => 'wp_get_post_terms',
        'description' => 'Gets terms attached to a post.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post.'],
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy to retrieve terms from.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_add_post_terms',
        'description' => 'Attaches or replaces terms for a post.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the post.'],
            'taxonomy' => ['type' => 'string', 'description' => 'The taxonomy to add terms to.'],
            'terms' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'An array of term IDs.'],
            'append' => ['type' => 'boolean', 'description' => 'Whether to append terms (true) or replace them (false).']
          ],
          'required' => ['ID', 'taxonomy', 'terms']
        ]
      ],
      [
        'name' => 'wp_get_media',
        'description' => 'Lists media items.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'search' => ['type' => 'string', 'description' => 'Search term for media.'],
            'after' => ['type' => 'string', 'description' => 'Date string to retrieve media after.'],
            'before' => ['type' => 'string', 'description' => 'Date string to retrieve media before.'],
            'limit' => ['type' => 'integer', 'description' => 'Maximum number of media items to retrieve.']
          ]
        ]
      ],
      [
        'name' => 'wp_upload_media',
        'description' => 'Downloads file from URL and add to Media Library.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'url' => ['type' => 'string', 'description' => 'The URL of the file to upload.'],
            'title' => ['type' => 'string', 'description' => 'The title of the media item.'],
            'description' => ['type' => 'string', 'description' => 'The description of the media item.'],
            'alt' => ['type' => 'string', 'description' => 'The alt text for the media item.']
          ],
          'required' => ['url']
        ]
      ],
      [
        'name' => 'wp_update_media',
        'description' => 'Updates attachment meta.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the media item to update.'],
            'title' => ['type' => 'string', 'description' => 'The new title.'],
            'caption' => ['type' => 'string', 'description' => 'The new caption.'],
            'description' => ['type' => 'string', 'description' => 'The new description.'],
            'alt' => ['type' => 'string', 'description' => 'The new alt text.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'wp_delete_media',
        'description' => 'Deletes/trashes an attachment.',
        'category' => 'WordPress Core',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'ID' => ['type' => 'integer', 'description' => 'The ID of the media item to delete.'],
            'force' => ['type' => 'boolean', 'description' => 'Whether to bypass trash and permanently delete.']
          ],
          'required' => ['ID']
        ]
      ],
      [
        'name' => 'mwai_vision',
        'description' => 'Analyzes an image via AI Engine Vision.',
        'category' => 'AI Engine',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'message' => ['type' => 'string', 'description' => 'The message/prompt for the vision analysis.'],
            'url' => ['type' => 'string', 'description' => 'The URL of the image to analyze.'],
            'path' => ['type' => 'string', 'description' => 'The local path to the image to analyze.']
          ],
          'required' => ['message']
        ]
      ],
      [
        'name' => 'mwai_image',
        'description' => 'Generates an image with AI Engine and stores it in the Media Library.',
        'category' => 'AI Engine',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'message' => ['type' => 'string', 'description' => 'Prompt describing the desired image.'],
            'postId' => ['type' => 'integer', 'description' => 'Optional post ID to attach the image to.'],
            'title' => ['type' => 'string', 'description' => 'The title of the generated image.'],
            'caption' => ['type' => 'string', 'description' => 'The caption for the generated image.'],
            'description' => ['type' => 'string', 'description' => 'The description for the generated image.'],
            'alt' => ['type' => 'string', 'description' => 'The alt text for the generated image.']
          ],
          'required' => ['message']
        ]
      ]
    ];

    return array_merge($tools, $gemini_tools);
  }
  
  public function handle_tool_execution( $result, $tool, $args, $id ) {
    if ( strpos( $tool, 'wp_' ) !== 0 && strpos( $tool, 'mwai_' ) !== 0 ) {
      return $result;
    }
    
    try {
      switch ( $tool ) {
        case 'wp_list_plugins':
          if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
          }
          $all_plugins = get_plugins();
          $search = isset($args['search']) ? $args['search'] : '';
          $filtered_plugins = [];
          foreach ($all_plugins as $path => $plugin) {
            if (empty($search) || stripos($plugin['Name'], $search) !== false) {
                $filtered_plugins[] = ['Name' => $plugin['Name'], 'Version' => $plugin['Version']];
            }
          }
          return ['success' => true, 'data' => $filtered_plugins];

        case 'wp_get_users':
          $query_args = ['fields' => ['ID', 'user_login', 'display_name', 'roles']];
          if (isset($args['search'])) $query_args['search'] = '*' . $args['search'] . '*';
          if (isset($args['role'])) $query_args['role'] = $args['role'];
          if (isset($args['limit'])) $query_args['number'] = $args['limit'];
          if (isset($args['offset'])) $query_args['offset'] = $args['offset'];
          if (isset($args['paged'])) $query_args['paged'] = $args['paged'];
          $users = get_users($query_args);
          return ['success' => true, 'data' => $users];

        case 'wp_create_user':
          $user_id = wp_create_user($args['user_login'], isset($args['user_pass']) ? $args['user_pass'] : wp_generate_password(), $args['user_email']);
          if (is_wp_error($user_id)) return ['success' => false, 'error' => $user_id->get_error_message()];
          $update_args = ['ID' => $user_id];
          if (isset($args['display_name'])) $update_args['display_name'] = $args['display_name'];
          if (isset($args['role'])) $update_args['role'] = $args['role'];
          wp_update_user($update_args);
          return ['success' => true, 'data' => ['ID' => $user_id]];

        case 'wp_update_user':
          $update_args = ['ID' => $args['ID']];
          $update_args = array_merge($update_args, $args['fields']);
          $user_id = wp_update_user($update_args);
          if (is_wp_error($user_id)) return ['success' => false, 'error' => $user_id->get_error_message()];
          return ['success' => true, 'data' => ['ID' => $user_id]];

        case 'wp_get_comments':
          $query_args = [];
          if (isset($args['post_id'])) $query_args['post_id'] = $args['post_id'];
          if (isset($args['status'])) $query_args['status'] = $args['status'];
          if (isset($args['search'])) $query_args['search'] = $args['search'];
          if (isset($args['limit'])) $query_args['number'] = $args['limit'];
          if (isset($args['offset'])) $query_args['offset'] = $args['offset'];
          if (isset($args['paged'])) $query_args['paged'] = $args['paged'];
          $comments = get_comments($query_args);
          return ['success' => true, 'data' => $comments];

        case 'wp_create_comment':
          $comment_data = [
              'comment_post_ID' => $args['post_id'],
              'comment_content' => $args['comment_content'],
              'comment_author' => isset($args['comment_author']) ? $args['comment_author'] : null,
              'comment_author_email' => isset($args['comment_author_email']) ? $args['comment_author_email'] : null,
              'comment_author_url' => isset($args['comment_author_url']) ? $args['comment_author_url'] : null,
              'comment_approved' => isset($args['comment_approved']) ? $args['comment_approved'] : 1,
          ];
          $comment_id = wp_insert_comment($comment_data);
          if (!$comment_id) return ['success' => false, 'error' => 'Failed to create comment.'];
          return ['success' => true, 'data' => ['comment_ID' => $comment_id]];

        case 'wp_update_comment':
          $update_args = ['comment_ID' => $args['comment_ID']];
          $update_args = array_merge($update_args, $args['fields']);
          $result = wp_update_comment($update_args);
          return ['success' => $result === 1, 'data' => ['updated' => $result === 1]];

        case 'wp_delete_comment':
          $force = isset($args['force']) ? $args['force'] : false;
          $result = wp_delete_comment($args['comment_ID'], $force);
          return ['success' => $result, 'data' => ['deleted' => $result]];

        case 'wp_get_option':
          $value = get_option($args['key']);
          return ['success' => true, 'data' => $value];

        case 'wp_update_option':
          $value = is_string($args['value']) ? json_decode($args['value'], true) : $args['value'];
          if (json_last_error() !== JSON_ERROR_NONE) $value = $args['value'];
          $result = update_option($args['key'], $value);
          return ['success' => $result, 'data' => ['updated' => $result]];

        case 'wp_count_posts':
          $post_type = isset($args['post_type']) ? $args['post_type'] : 'post';
          $counts = wp_count_posts($post_type);
          return ['success' => true, 'data' => $counts];

        case 'wp_count_terms':
          $count = wp_count_terms($args['taxonomy']);
          return ['success' => true, 'data' => ['count' => $count]];

        case 'wp_count_media':
          // This is a simplified version. A full implementation would require more complex date queries.
          $query = new WP_Query(['post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1]);
          return ['success' => true, 'data' => ['count' => $query->post_count]];

        case 'wp_get_post_types':
          $post_types = get_post_types(['public' => true], 'objects');
          $result = [];
          foreach ($post_types as $key => $pt) {
              $result[$key] = $pt->label;
          }
          return ['success' => true, 'data' => $result];

        case 'wp_get_posts':
          $query_args = ['post_status' => 'publish'];
          if (isset($args['post_type'])) $query_args['post_type'] = $args['post_type'];
          if (isset($args['post_status'])) $query_args['post_status'] = $args['post_status'];
          if (isset($args['search'])) $query_args['s'] = $args['search'];
          if (isset($args['limit'])) $query_args['posts_per_page'] = $args['limit'];
          if (isset($args['offset'])) $query_args['offset'] = $args['offset'];
          if (isset($args['paged'])) $query_args['paged'] = $args['paged'];
          $posts = get_posts($query_args);
          return ['success' => true, 'data' => $posts];

        case 'wp_get_post':
          $post = get_post($args['ID']);
          return ['success' => true, 'data' => $post];

        case 'wp_get_post_snapshot':
          $post = get_post($args['ID']);
          if (!$post) return ['success' => false, 'error' => 'Post not found.'];
          $snapshot = ['post' => $post];
          $include = isset($args['include']) ? $args['include'] : ['meta', 'terms', 'thumbnail', 'author'];
          if (in_array('meta', $include)) $snapshot['meta'] = get_post_meta($args['ID']);
          if (in_array('terms', $include)) $snapshot['terms'] = wp_get_post_terms($args['ID'], get_object_taxonomies($post));
          if (in_array('thumbnail', $include)) $snapshot['thumbnail'] = get_the_post_thumbnail_url($args['ID']);
          if (in_array('author', $include)) $snapshot['author'] = get_userdata($post->post_author);
          return ['success' => true, 'data' => $snapshot];

        case 'wp_create_post':
          $post_data = ['post_status' => 'draft', 'post_type' => 'post'];
          $post_data = array_merge($post_data, $args);
          $post_id = wp_insert_post($post_data);
          if (is_wp_error($post_id)) return ['success' => false, 'error' => $post_id->get_error_message()];
          return ['success' => true, 'data' => ['ID' => $post_id]];

        case 'wp_update_post':
          $post_data = ['ID' => $args['ID']];
          if (isset($args['fields'])) $post_data = array_merge($post_data, $args['fields']);
          if (isset($args['meta_input'])) $post_data['meta_input'] = $args['meta_input'];
          $post_id = wp_update_post($post_data);
          if (is_wp_error($post_id)) return ['success' => false, 'error' => $post_id->get_error_message()];
          return ['success' => true, 'data' => ['ID' => $post_id]];

        case 'wp_delete_post':
          $force = isset($args['force']) ? $args['force'] : false;
          $result = wp_delete_post($args['ID'], $force);
          return ['success' => (bool)$result, 'data' => ['deleted' => (bool)$result]];

        case 'wp_get_post_meta':
          $key = isset($args['key']) ? $args['key'] : '';
          $meta = get_post_meta($args['ID'], $key, $key === '');
          return ['success' => true, 'data' => $meta];

        case 'wp_update_post_meta':
          if (isset($args['meta'])) {
            foreach ($args['meta'] as $key => $value) {
              update_post_meta($args['ID'], $key, $value);
            }
            return ['success' => true, 'data' => ['updated' => true]];
          } else if (isset($args['key']) && isset($args['value'])) {
            $result = update_post_meta($args['ID'], $args['key'], $args['value']);
            return ['success' => (bool)$result, 'data' => ['updated' => (bool)$result]];
          }
          return ['success' => false, 'error' => 'Either "meta" object or "key" and "value" must be provided.'];

        case 'wp_delete_post_meta':
          $value = isset($args['value']) ? $args['value'] : '';
          $result = delete_post_meta($args['ID'], $args['key'], $value);
          return ['success' => $result, 'data' => ['deleted' => $result]];

        case 'wp_set_featured_image':
          $media_id = isset($args['media_id']) ? $args['media_id'] : -1;
          $result = set_post_thumbnail($args['post_id'], $media_id);
          return ['success' => $result, 'data' => ['updated' => $result]];

        case 'wp_get_taxonomies':
          $post_type = isset($args['post_type']) ? $args['post_type'] : null;
          $taxonomies = get_object_taxonomies($post_type, 'objects');
          $result = [];
          foreach ($taxonomies as $key => $tax) {
              $result[$key] = $tax->label;
          }
          return ['success' => true, 'data' => $result];

        case 'wp_get_terms':
          $query_args = ['taxonomy' => $args['taxonomy'], 'hide_empty' => false];
          if (isset($args['search'])) $query_args['search'] = $args['search'];
          if (isset($args['parent'])) $query_args['parent'] = $args['parent'];
          if (isset($args['limit'])) $query_args['number'] = $args['limit'];
          $terms = get_terms($query_args);
          return ['success' => true, 'data' => $terms];

        case 'wp_create_term':
          $term_args = [];
          if (isset($args['slug'])) $term_args['slug'] = $args['slug'];
          if (isset($args['description'])) $term_args['description'] = $args['description'];
          if (isset($args['parent'])) $term_args['parent'] = $args['parent'];
          $result = wp_insert_term($args['term_name'], $args['taxonomy'], $term_args);
          if (is_wp_error($result)) return ['success' => false, 'error' => $result->get_error_message()];
          return ['success' => true, 'data' => $result];

        case 'wp_update_term':
          $update_args = [];
          if (isset($args['name'])) $update_args['name'] = $args['name'];
          if (isset($args['slug'])) $update_args['slug'] = $args['slug'];
          if (isset($args['description'])) $update_args['description'] = $args['description'];
          if (isset($args['parent'])) $update_args['parent'] = $args['parent'];
          $result = wp_update_term($args['term_id'], $args['taxonomy'], $update_args);
          if (is_wp_error($result)) return ['success' => false, 'error' => $result->get_error_message()];
          return ['success' => true, 'data' => $result];

        case 'wp_delete_term':
          $result = wp_delete_term($args['term_id'], $args['taxonomy']);
          return ['success' => $result, 'data' => ['deleted' => $result]];

        case 'wp_get_post_terms':
          $taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : get_object_taxonomies(get_post_type($args['ID']));
          $terms = wp_get_post_terms($args['ID'], $taxonomy);
          return ['success' => true, 'data' => $terms];

        case 'wp_add_post_terms':
          $append = isset($args['append']) ? $args['append'] : false;
          $result = wp_set_post_terms($args['ID'], $args['terms'], $args['taxonomy'], $append);
          if (is_wp_error($result)) return ['success' => false, 'error' => $result->get_error_message()];
          return ['success' => true, 'data' => $result];

        case 'wp_get_media':
          $query_args = ['post_type' => 'attachment', 'post_status' => 'inherit'];
          if (isset($args['search'])) $query_args['s'] = $args['search'];
          if (isset($args['limit'])) $query_args['posts_per_page'] = $args['limit'];
          // Simplified date query
          if (isset($args['after']) || isset($args['before'])) {
            $query_args['date_query'] = [];
            if (isset($args['after'])) $query_args['date_query']['after'] = $args['after'];
            if (isset($args['before'])) $query_args['date_query']['before'] = $args['before'];
          }
          $media = get_posts($query_args);
          return ['success' => true, 'data' => $media];

        case 'wp_upload_media':
          require_once(ABSPATH . 'wp-admin/includes/image.php');
          require_once(ABSPATH . 'wp-admin/includes/file.php');
          require_once(ABSPATH . 'wp-admin/includes/media.php');
          $media_id = media_sideload_image($args['url'], 0, isset($args['title']) ? $args['title'] : null, 'id');
          if (is_wp_error($media_id)) return ['success' => false, 'error' => $media_id->get_error_message()];
          // Update meta if provided
          $post_data = [];
          if (isset($args['description'])) $post_data['post_content'] = $args['description'];
          if (isset($args['alt'])) update_post_meta($media_id, '_wp_attachment_image_alt', $args['alt']);
          if (!empty($post_data)) {
            $post_data['ID'] = $media_id;
            wp_update_post($post_data);
          }
          return ['success' => true, 'data' => ['ID' => $media_id]];

        case 'wp_update_media':
          $post_data = ['ID' => $args['ID']];
          if (isset($args['title'])) $post_data['post_title'] = $args['title'];
          if (isset($args['caption'])) $post_data['post_excerpt'] = $args['caption'];
          if (isset($args['description'])) $post_data['post_content'] = $args['description'];
          if (isset($args['alt'])) update_post_meta($args['ID'], '_wp_attachment_image_alt', $args['alt']);
          $result = wp_update_post($post_data);
          if (is_wp_error($result)) return ['success' => false, 'error' => $result->get_error_message()];
          return ['success' => true, 'data' => ['updated' => true]];

        case 'wp_delete_media':
          $force = isset($args['force']) ? $args['force'] : false;
          $result = wp_delete_attachment($args['ID'], $force);
          return ['success' => (bool)$result, 'data' => ['deleted' => (bool)$result]];

        case 'mwai_vision':
          global $mwai;
          if (isset($mwai) && method_exists($mwai, 'ai_vision')) {
            $message = $args['message'];
            $url = isset($args['url']) ? $args['url'] : null;
            $path = isset($args['path']) ? $args['path'] : null;
            $vision_result = $mwai->ai_vision($message, $url, $path);
            return ['success' => true, 'data' => $vision_result];
          } else {
            return ['success' => false, 'error' => 'AI Engine Vision function not available.'];
          }

        case 'mwai_image':
          global $mwai;
          if (isset($mwai) && method_exists($mwai, 'ai_image')) {
            $message = $args['message'];
            $post_id = isset($args['postId']) ? $args['postId'] : 0;
            $title = isset($args['title']) ? $args['title'] : '';
            $caption = isset($args['caption']) ? $args['caption'] : '';
            $description = isset($args['description']) ? $args['description'] : '';
            $alt = isset($args['alt']) ? $args['alt'] : '';
            $image_result = $mwai->ai_image($message, $post_id, $title, $caption, $description, $alt);
            return ['success' => true, 'data' => $image_result];
          } else {
            return ['success' => false, 'error' => 'AI Engine Image generation function not available.'];
          }

        default:
          return [ 'success' => false, 'error' => 'Unknown tool' ];
      }
    }
    catch ( Exception $e ) {
      return [ 'success' => false, 'error' => $e->getMessage() ];
    }
    
    return $result;
  }
}
