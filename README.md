# Markdown Support for SMF 2.1

This package adds lightweight Markdown support to Simple Machines Forum 2.1.x, including SMF 2.1.7.

## Features

- Converts Markdown to BBCode when new posts are submitted.
- Adds a `Parse Markdown in existing posts` option under the SMF modification settings page.
- Parses Markdown inside `[markdown]...[/markdown]` blocks even when the existing-post option is disabled.
- Supports headings, bold, italic, strikethrough, links, images, blockquotes, ordered and unordered lists, horizontal rules, fenced code blocks, and inline code.

## Installation

1. Create a zip archive containing `package-info.xml`, `README.md`, `LICENSE`, and the `Sources` directory.
2. Upload the archive in the SMF Package Manager.
3. Install the package.
4. To render Markdown in old posts, enable `Parse Markdown in existing posts` in the modification settings page.

New posts and `[markdown]...[/markdown]` blocks do not require the old-post option.

## Notes

The package uses SMF integration hooks only. It does not edit core SMF files.

The Markdown parser is implemented in `Sources/MarkdownSupport.php`. It intentionally converts Markdown to BBCode rather than storing rendered HTML.
