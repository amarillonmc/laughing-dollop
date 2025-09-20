# Markdown Support for Simple Machines Forum 2.1

This modification enables Markdown formatting in Simple Machines Forum (SMF) 2.1.4 and higher. It automatically converts Markdown syntax into BBCode when users create new posts and seamlessly parses legacy messages that contain Markdown when they are displayed.

## Features

- Automatic Markdown → BBCode conversion on post submission
- Runtime conversion for existing Markdown posts to maintain formatting
- Support for headings, emphasis, lists, blockquotes, code blocks, inline code, links, images, horizontal rules, and strikethrough

## Installation

1. Create a release archive (e.g. `MarkdownSupport-1.0.0.zip`) containing the contents of this repository.
2. Upload the archive via the SMF Package Manager (`Admin » Package Manager » Download Packages`).
3. Follow the installation prompts.

## Uninstallation

Uninstall through the Package Manager. All integration hooks and added files are removed automatically.

## Customisation

The Markdown to BBCode conversion is implemented in `Sources/MarkdownSupport/Parser.php`. You can expand or adjust the supported syntax there if needed.
