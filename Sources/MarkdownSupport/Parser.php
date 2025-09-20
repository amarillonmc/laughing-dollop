<?php

namespace MarkdownSupport;

/**
 * Lightweight Markdown to BBCode converter tailored for SMF.
 */
class Parser
{
    /**
     * Convert Markdown syntax to BBCode.
     */
    public function toBBCode(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Extract fenced code blocks to placeholders to avoid accidental conversion.
        $codeBlocks = [];
        $text = preg_replace_callback(
            '/```(\w+)?\n([\s\S]*?)```/m',
            function (array $matches) use (&$codeBlocks) {
                $index = count($codeBlocks);
                $language = !empty($matches[1]) ? '=' . $matches[1] : '';
                $code = rtrim($matches[2], "\n");
                $codeBlocks[$index] = "[code{$language}]{$code}[/code]";
                return "[[MD_CODE_BLOCK_{$index}]]";
            },
            $text
        );

        // Horizontal rules
        $text = preg_replace('/^\s*(\*\s?){3,}$|^\s*(-\s?){3,}$|^\s*(_\s?){3,}$/m', "\n[hr]\n", $text);

        // Headings
        $text = preg_replace_callback(
            '/^(#{1,6})\s+(.+)$/m',
            function (array $matches) {
                $level = strlen($matches[1]);
                $content = trim($matches[2]);
                $sizes = [1 => '24pt', 2 => '18pt', 3 => '16pt', 4 => '14pt', 5 => '12pt', 6 => '11pt'];
                $size = $sizes[$level] ?? '12pt';
                return "[size={$size}][b]{$content}[/b][/size]";
            },
            $text
        );

        // Blockquotes
        $text = preg_replace_callback(
            '/(^>.*(?:\n>.*)*)/m',
            function (array $matches) {
                $content = preg_replace('/^>\s?/m', '', $matches[1]);
                $content = trim($content);
                return "[quote]{$content}[/quote]";
            },
            $text
        );

        // Ordered lists
        $text = preg_replace_callback(
            '/(^\s*\d+\.\s+.*(?:\n\s*\d+\.\s+.*)*)/m',
            function (array $matches) {
                $items = preg_split('/\n/', trim($matches[1]));
                $buffer = "[list type=decimal]\n";
                foreach ($items as $item) {
                    $buffer .= '[*]' . preg_replace('/^\s*\d+\.\s+/', '', $item) . "\n";
                }
                $buffer .= '[/list]';
                return $buffer;
            },
            $text
        );

        // Unordered lists
        $text = preg_replace_callback(
            '/(^\s*[-+*]\s+.*(?:\n\s*[-+*]\s+.*)*)/m',
            function (array $matches) {
                $items = preg_split('/\n/', trim($matches[1]));
                $buffer = "[list]\n";
                foreach ($items as $item) {
                    $buffer .= '[*]' . preg_replace('/^\s*[-+*]\s+/', '', $item) . "\n";
                }
                $buffer .= '[/list]';
                return $buffer;
            },
            $text
        );

        // Images ![alt](src)
        $text = preg_replace_callback(
            '/!\[(.*?)\]\(([^\s\)]+)(?:\s+"(.*?)")?\)/',
            function (array $matches) {
                $alt = trim($matches[1]);
                $url = trim($matches[2]);
                $bbcode = '[img]' . $url . '[/img]';
                if ($alt !== '') {
                    $bbcode = '[img alt=' . $this->escapeAttribute($alt) . ']' . $url . '[/img]';
                }
                return $bbcode;
            },
            $text
        );

        // Links [text](url)
        $text = preg_replace_callback(
            '/\[(.*?)\]\(([^\s\)]+)(?:\s+"(.*?)")?\)/',
            function (array $matches) {
                $label = trim($matches[1]);
                $url = trim($matches[2]);
                return '[url=' . $url . ']' . ($label !== '' ? $label : $url) . '[/url]';
            },
            $text
        );

        // Bold and italic emphasis
        $text = preg_replace('/\*\*(.+?)\*\*/s', '[b]$1[/b]', $text);
        $text = preg_replace('/__(.+?)__/s', '[b]$1[/b]', $text);

        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '[i]$1[/i]', $text);
        $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '[i]$1[/i]', $text);

        // Strikethrough
        $text = preg_replace('/~~(.+?)~~/s', '[s]$1[/s]', $text);

        // Inline code
        $text = preg_replace_callback(
            '/`([^`]+)`/',
            function (array $matches) {
                return '[tt]' . $matches[1] . '[/tt]';
            },
            $text
        );

        // Restore fenced code blocks
        if (!empty($codeBlocks)) {
            foreach ($codeBlocks as $index => $replacement) {
                $text = str_replace("[[MD_CODE_BLOCK_{$index}]]", $replacement, $text);
            }
        }

        // Convert double newlines to paragraphs (handled by SMF automatically) - no change required.
        return $text;
    }

    /**
     * Check if the message likely already contains BBCode markup.
     */
    public function hasBBCode(string $text): bool
    {
        return (bool) preg_match('/\[(?:b|i|u|s|code|quote|list|url|img|table|size|color|font|tt|pre)/i', $text);
    }

    private function escapeAttribute(string $value): string
    {
        return strtr($value, ['[' => '', ']' => '', '=' => '-', '"' => '', "'" => '']);
    }
}
