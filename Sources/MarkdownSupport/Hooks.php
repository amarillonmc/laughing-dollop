<?php

namespace MarkdownSupport;

if (!defined('SMF')) {
    die('No direct access...');
}

class Hooks
{
    /** @var Parser|null */
    protected static $parser = null;

    protected static function getParser(): Parser
    {
        if (self::$parser === null) {
            self::$parser = new Parser();
        }

        return self::$parser;
    }

    /**
     * Hook for integrate_preparsecode: converts Markdown to BBCode before saving posts.
     *
     * @param string $message
     */
    public static function preparseCode(&$message)
    {
        if (empty($message) || !is_string($message)) {
            return;
        }

        $message = self::getParser()->toBBCode($message);
    }

    /**
     * Hook for integrate_preparsebbc: allows rendering Markdown in existing posts.
     *
     * @param string $message
     */
    public static function preparseBBC(&$message)
    {
        if (empty($message) || !is_string($message)) {
            return;
        }

        $parser = self::getParser();
        if ($parser->hasBBCode($message)) {
            return;
        }

        $message = $parser->toBBCode($message);
    }
}
