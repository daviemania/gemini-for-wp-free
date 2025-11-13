# AI Editor

This project focuses on using AI to improve the content of the user's multisite WordPress network.

## Goals

- Improve SEO of articles.
- Correct grammar and spelling.
- Go deeper into topics while maintaining the original style.

## Guiding Principles

- How can we improve it and add to it meaningfully, without diminishing the intent, substance, literal and implied message of the post?
- Any post suggestions should always include comparison with the current or older versions, which can be simply marked with highlights showing where changes have been suggested to be made.

### Image Guidelines

- Always use images from Pexels or Unsplash.
- Images should always be placed in between paragraphs for better readability and visual flow.
- Always add both an appropriate caption and alt text.
- The caption should credit the relevant source (e.g., "Source: Unsplash").

## Post Update Workflow

1. Propose changes to the user with a highlighted comparison between the original and the new version.
2. Once the user approves the changes, write the new content to a temporary file inside the `gemini-project/tmp` directory.
3. Use the `propose_post_update.php` script to update the post. The script takes the post ID and the path to the temporary file as arguments. It will update the post content and set the status to "pending review".
4. After the script has been executed successfully, delete the temporary file.

## Post Creation Workflow

1. Parse the source file (e.g., `.docx`, `.txt`) to extract the content.
2. Structure the content into a title and body.
3. Present the structured content and suggested enhancements to the user.
4. Upon approval, use the `create_new_post.php` script to create the new post. The script takes the site slug, post title, and the path to the content file as arguments. It will create the post and set the status to "pending review".
5. After the script has been executed successfully, delete any temporary files.

## External Tools

### Security Analysis

- **Usefulness:** The security analysis tool can be used to scan any HTML or script-based content that might be added to posts. This ensures that any embedded elements, like iframes or custom JavaScript, are free from vulnerabilities such as Cross-Site Scripting (XSS), protecting the site and its visitors.

### Code Review

- **Usefulness:** For posts that are code-heavy (e.g., tutorials), the code-review tool can be used to ensure any code snippets added or modified are high-quality, adhere to best practices, and are free of errors.

### Critical Think

- **Usefulness:**
  - **Deepen Content:** Analyze the core topic of an article to explore related concepts, uncover different perspectives, and generate ideas for adding more substance and value.
  - **Improve SEO:** Strategize on keyword usage, content structure, and user intent to better optimize posts for search engines.
  - **Analyze Intent:** Better understand the "intent, substance, literal and implied message" of a post before suggesting edits, ensuring changes align with the original vision.
