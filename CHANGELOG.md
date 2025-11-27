# CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-11-27
### Fixed
- Fatal error on activation (include SDK in test ZIP for standalone test; prod excludes for Freemius inject)

## [Unreleased]

### Added
- `acknowledgements.md` crediting AI Engine WP plugin (MCP foundation), Ollama, Gemini CLI, Claude Code, Exa AI Search, OpenRouter
- Gitleaks secret scanning CI (`.github/workflows/secret-scan.yml`) on PRs to main/workspacedev
- `.env.example` template with WP_MCP_TOKEN, GitHub token placeholders + README.md "Environment Setup" section
- `.well-known/security.txt` for vulnerability reporting (mailto:mail@davidmania.com with auto-subject)
- AI Engine WordPress Plugin noted as MCP dependency in WIKI.md

### Changed
- All hardcoded WP MCP Bearer tokens removed (12+ instances across JS files/docs → `process.env.WP_MCP_TOKEN` / `${WP_MCP_TOKEN}`)
- `security.txt`: Email updated to mail@davidmania.com + PGP encryption key link + vuln report auto-subject
- Git history cleaned: `git filter-repo --strip-blobs-bigger-than 100M` (removed 1.7GB core dump)
- WIKI.md: Added acknowledgements.md link + AI Engine MCP credit
- Recent merges from localdev, main, workspacedev branches ([50e63cd1](https://gitlab.com/daviemania/gemini-project/-/commit/50e63cd1), [#3](https://gitlab.com/daviemania/gemini-project/-/merge_requests/3), [#2](https://gitlab.com/daviemania/gemini-project/-/merge_requests/2), [#1](https://gitlab.com/daviemania/gemini-project/-/merge_requests/1))

### Other
- Random cleanup ([8a5caf32](https://gitlab.com/daviemania/gemini-project/-/commit/8a5caf32))

## Previous Versions
See git history for changes prior to changelog adoption.
