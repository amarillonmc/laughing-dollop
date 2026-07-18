<?php

if (!defined('SMF')) {
	die('No direct access...');
}

function markdown_support_preparsecode(&$message, $previewing = false)
{
	if (!is_string($message) || $message === '') {
		return;
	}

	$message = markdown_support_remove_legacy_placeholders($message);
	$message = markdown_support_convert_markdown_tags($message);

	if (markdown_support_looks_like_markdown($message)) {
		$message = markdown_support_to_bbcode($message);
	}
}

function markdown_support_pre_parsebbc(&$message, &$smileys = null, &$cache_id = '', &$parse_tags = array())
{
	global $modSettings;

	if (!is_string($message) || $message === '') {
		return;
	}

	$message = markdown_support_remove_legacy_placeholders($message);
	$message = markdown_support_convert_markdown_tags($message);

	if (!empty($parse_tags)) {
		return;
	}

	if (!empty($modSettings['markdown_support_parse_legacy']) && markdown_support_looks_like_markdown($message)) {
		$message = markdown_support_to_bbcode($message);
	}
}

function markdown_support_bbc_codes(&$codes, &$no_autolink_tags = null)
{
	$codes[] = array(
		'tag' => 'markdown',
		'type' => 'unparsed_content',
		'content' => '$1',
		'disabled_content' => '$1',
		'block_level' => true,
	);

	if (is_array($no_autolink_tags) && !in_array('markdown', $no_autolink_tags, true)) {
		$no_autolink_tags[] = 'markdown';
	}
}

function markdown_support_general_mod_settings(&$config_vars)
{
	global $txt;

	$txt['setting_markdown_support_parse_legacy'] = 'Parse Markdown in existing posts';
	$txt['setting_markdown_support_parse_legacy_desc'] = 'Convert Markdown to BBCode while posts are displayed. New posts and [markdown] blocks are converted regardless of this option.';

	$config_vars[] = array(
		'check',
		'markdown_support_parse_legacy',
		'subtext' => $txt['setting_markdown_support_parse_legacy_desc'],
	);
}

function markdown_support_convert_markdown_tags($text)
{
	return preg_replace_callback(
		'~\[markdown\](.*?)\[/markdown\]~is',
		function ($matches) {
			return markdown_support_to_bbcode($matches[1]);
		},
		$text
	);
}

function markdown_support_to_bbcode($text)
{
	if ($text === '') {
		return $text;
	}

	$text = markdown_support_remove_legacy_placeholders($text);
	$text = str_replace(array("\r\n", "\r"), "\n", $text);

	$placeholders = array();
	$text = markdown_support_extract_existing_bbcode($text, $placeholders);
	$text = markdown_support_extract_fenced_code($text, $placeholders);
	$text = markdown_support_extract_inline_code($text, $placeholders);

	$text = markdown_support_convert_horizontal_rules($text);
	$text = markdown_support_convert_headings($text);
	$text = markdown_support_convert_blockquotes($text);
	$text = markdown_support_convert_lists($text);
	$text = markdown_support_convert_links_and_images($text, $placeholders);
	$text = markdown_support_convert_inline_formatting($text);

	return markdown_support_restore_placeholders($text, $placeholders);
}

function markdown_support_looks_like_markdown($text)
{
	return (bool) preg_match(
		'~(^[ \t]{0,3}#{1,6}[ \t]+|^[ \t]{0,3}>|^[ \t]{0,3}(?:[-+*]|\d+\.)[ \t]+|^[ \t]{0,3}(?:[-*_][ \t]*){3,}$|!\[[^\]]*\]\([^)]+\)|\[[^\]]+\]\([^)]+\)|`{1,3}|(?:\*\*|__|\~\~).+?(?:\*\*|__|\~\~)|(?<![\*\[])\*(?![\*\]\s]).+?(?<![\*\[\s])\*(?![\*\]])|(?<![A-Za-z0-9_])_(?![_\s]).+?(?<![_\s])_(?![A-Za-z0-9_]))~ms',
		$text
	);
}

function markdown_support_extract_existing_bbcode($text, &$placeholders)
{
	$tags = array(
		'b', 'i', 'u', 's', 'sub', 'sup',
		'url', 'iurl', 'ftp', 'email', 'img',
		'quote', 'code', 'php', 'nobbc', 'html', 'tt', 'pre',
		'list', 'table', 'tr', 'td',
		'size', 'font', 'color', 'left', 'center', 'right',
		'move', 'glow', 'shadow', 'member',
	);

	foreach ($tags as $tag) {
		$text = preg_replace_callback(
			'~\[' . $tag . '(?:[=\s][^\]]*)?\].*?\[/' . $tag . '\]~is',
			function ($matches) use (&$placeholders) {
				return markdown_support_store_placeholder($placeholders, $matches[0]);
			},
			$text
		);
	}

	return $text;
}

function markdown_support_extract_fenced_code($text, &$placeholders)
{
	return preg_replace_callback(
		'~(^|\n)(```|\~\~\~)[ \t]*([A-Za-z0-9_-]+)?[^\n]*\n(.*?)\n\2[ \t]*(?=\n|$)~s',
		function ($matches) use (&$placeholders) {
			$language = !empty($matches[3]) ? '=' . preg_replace('~[^A-Za-z0-9_-]~', '', $matches[3]) : '';
			$code = rtrim($matches[4], "\n");

			return $matches[1] . markdown_support_store_placeholder($placeholders, '[code' . $language . ']' . $code . '[/code]');
		},
		$text
	);
}

function markdown_support_extract_inline_code($text, &$placeholders)
{
	return preg_replace_callback(
		'~`([^`\n]+)`~',
		function ($matches) use (&$placeholders) {
			$code = str_ireplace('[/nobbc]', '&#91;/nobbc&#93;', $matches[1]);

			return markdown_support_store_placeholder($placeholders, '[tt][nobbc]' . $code . '[/nobbc][/tt]');
		},
		$text
	);
}

function markdown_support_convert_horizontal_rules($text)
{
	return preg_replace('~^[ \t]{0,3}(?:[-*_][ \t]*){3,}$~m', '[hr]', $text);
}

function markdown_support_convert_headings($text)
{
	return preg_replace_callback(
		'~^[ \t]{0,3}(#{1,6})[ \t]+(.+?)[ \t]*#*[ \t]*$~m',
		function ($matches) {
			$sizes = array(
				1 => '24pt',
				2 => '18pt',
				3 => '16pt',
				4 => '14pt',
				5 => '12pt',
				6 => '11pt',
			);
			$level = strlen($matches[1]);
			$content = trim($matches[2]);

			return '[size=' . $sizes[$level] . '][b]' . $content . '[/b][/size]';
		},
		$text
	);
}

function markdown_support_convert_blockquotes($text)
{
	return preg_replace_callback(
		'~(^[ \t]{0,3}>[^\n]*(?:\n[ \t]{0,3}>[^\n]*)*)~m',
		function ($matches) {
			$content = preg_replace('~^[ \t]{0,3}>[ \t]?~m', '', $matches[1]);

			return '[quote]' . trim($content) . '[/quote]';
		},
		$text
	);
}

function markdown_support_convert_lists($text)
{
	$text = preg_replace_callback(
		'~(^[ \t]{0,3}\d+\.[ \t]+[^\n]+(?:\n[ \t]{0,3}\d+\.[ \t]+[^\n]+)*)~m',
		function ($matches) {
			$items = preg_split('~\n~', trim($matches[1]));
			$buffer = "[list type=decimal]\n";

			foreach ($items as $item) {
				$buffer .= '[*]' . preg_replace('~^[ \t]{0,3}\d+\.[ \t]+~', '', $item) . "\n";
			}

			return $buffer . '[/list]';
		},
		$text
	);

	return preg_replace_callback(
		'~(^[ \t]{0,3}[-+*][ \t]+[^\n]+(?:\n[ \t]{0,3}[-+*][ \t]+[^\n]+)*)~m',
		function ($matches) {
			$items = preg_split('~\n~', trim($matches[1]));
			$buffer = "[list]\n";

			foreach ($items as $item) {
				$buffer .= '[*]' . preg_replace('~^[ \t]{0,3}[-+*][ \t]+~', '', $item) . "\n";
			}

			return $buffer . '[/list]';
		},
		$text
	);
}

function markdown_support_convert_links_and_images($text, &$placeholders)
{
	$text = preg_replace_callback(
		'~!\[([^\]]*)\]\(([^\s\)]+)(?:\s+"[^"]*")?\)~',
		function ($matches) use (&$placeholders) {
			$alt = trim($matches[1]);
			$url = markdown_support_clean_url($matches[2], true);

			if ($url === '') {
				return $alt !== '' ? $alt : $matches[0];
			}

			$replacement = $alt !== ''
				? '[img alt=' . markdown_support_clean_attribute($alt) . ']' . $url . '[/img]'
				: '[img]' . $url . '[/img]';

			return markdown_support_store_placeholder($placeholders, $replacement);
		},
		$text
	);

	return preg_replace_callback(
		'~(?<!!)\[([^\]]+)\]\(([^\s\)]+)(?:\s+"[^"]*")?\)~',
		function ($matches) use (&$placeholders) {
			$label = trim($matches[1]);
			$url = markdown_support_clean_url($matches[2], false);

			if ($url === '') {
				return $label;
			}

			return markdown_support_store_placeholder($placeholders, '[url=' . $url . ']' . ($label !== '' ? $label : $url) . '[/url]');
		},
		$text
	);
}

function markdown_support_convert_inline_formatting($text)
{
	$text = preg_replace('~\*\*(.+?)\*\*~s', '[b]$1[/b]', $text);
	$text = preg_replace('~(?<![A-Za-z0-9_])__(?![_\s])(.+?)(?<![_\s])__(?![A-Za-z0-9_])~s', '[b]$1[/b]', $text);
	$text = preg_replace('#~~(.+?)~~#s', '[s]$1[/s]', $text);
	$text = preg_replace('~(?<![\*\[])\*(?![\*\]\s])(.+?)(?<![\*\[\s])\*(?![\*\]])~s', '[i]$1[/i]', $text);

	return preg_replace('~(?<![A-Za-z0-9_])_(?![_\s])(.+?)(?<![_\s])_(?![A-Za-z0-9_])~s', '[i]$1[/i]', $text);
}

function markdown_support_clean_url($url, $image)
{
	$url = trim(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
	$url = preg_replace('~[\x00-\x20\x7F]+~', '', $url);
	$url = str_replace(array('[', ']'), array('%5B', '%5D'), $url);

	if ($url === '') {
		return '';
	}

	$scheme = parse_url($url, PHP_URL_SCHEME);
	if ($scheme !== null) {
		$scheme = strtolower($scheme);
		$allowed = $image ? array('http', 'https') : array('http', 'https', 'ftp', 'ftps', 'mailto');

		return in_array($scheme, $allowed, true) ? $url : '';
	}

	if (preg_match('~^(//|/|\?|#)~', $url)) {
		return $image && !preg_match('~^//~', $url) ? '' : $url;
	}

	return $url;
}

function markdown_support_clean_attribute($value)
{
	return strtr($value, array(
		'[' => '',
		']' => '',
		'=' => '-',
		'"' => '',
		"'" => '',
		"\n" => ' ',
		"\r" => ' ',
	));
}

function markdown_support_store_placeholder(&$placeholders, $replacement)
{
	do {
		$nonce = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 16));
		$key = '{SMFMD' . $nonce . 'P' . count($placeholders) . '}';
	} while (isset($placeholders[$key]) || strpos($replacement, $key) !== false);

	$placeholders[$key] = $replacement;

	return $key;
}

function markdown_support_restore_placeholders($text, $placeholders)
{
	if (empty($placeholders)) {
		return $text;
	}

	foreach (array_reverse($placeholders, true) as $key => $replacement) {
		$text = str_replace($key, $replacement, $text);
	}

	return $text;
}

function markdown_support_remove_legacy_placeholders($text)
{
	$entity = '(?:&#(?:x(?:1a|241a)|(?:26|9242));)';
	$cleaned = preg_replace(
		array(
			'~\x1AMSMD\d+\x1A~',
			'~\x{241A}MSMD\d+\x{241A}~u',
			'~' . $entity . 'MSMD\d+' . $entity . '~i',
			'~\{SMFMD[A-F0-9]{16}P\d+\}~',
		),
		'',
		$text
	);

	return $cleaned === null ? $text : $cleaned;
}
