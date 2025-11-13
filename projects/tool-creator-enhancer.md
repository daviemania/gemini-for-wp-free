# Tool Creator/Enhancer

This project focuses on using AI to create and enhance custom tools for the user's multisite WordPress network.

## Goals

- Create new tools to enhance the user experience.
- Enhance existing tools like the Geekline feed.
- Create new tools to improve our workflow, such as the `propose_post_update.php` and `create_new_post.php` scripts.

## External Tools

### Jules

- **Usefulness:**
  - **Build Robust Tools:** Ensure any new tools created are well-structured, readable, and have proper test coverage from the start.
  - **Refactor Existing Tools:** Improve the architecture and maintainability of existing scripts, like `propose_post_update.php`.

### Security Analysis

- **Usefulness:**
  - **"Security by Design":** Analyze every new tool created for potential vulnerabilities. For example, when building a script that handles file uploads or user input, use the security tool to ensure it's protected against injection attacks and path traversal.
  - **Harden Existing Tools:** Before enhancing an existing tool, first run a security analysis to identify and fix any underlying vulnerabilities.

### Code Review

- **Usefulness:** Acts as an automated peer reviewer to ensure that:
  - Any new tools built are high-quality, maintainable, and well-documented from the start.
  - Enhancements to existing tools are implemented cleanly and don't introduce new issues.

### Critical Think

- **Usefulness:**
  - **Design Better Tools:** Brainstorm new tool ideas, define their features, and map out the implementation strategy before writing code.
  - **Assess Risks:** Identify potential security flaws, performance issues, or usability problems in a new tool's design.
  - **Plan Enhancements:** Analyze requests to improve existing tools, considering the full impact of the changes and designing the most effective solution.
