<?php

define('SMF', 1);
require dirname(__DIR__) . '/Sources/MarkdownSupport.php';

$failures = array();

function markdown_support_test_same($expected, $actual, $label)
{
	global $failures;

	if ($expected !== $actual) {
		$failures[] = $label . "\nExpected: " . $expected . "\nActual:   " . $actual;
	}
}

$message = '[center][b]Alpha[/b][/center] and **Beta**';
markdown_support_preparsecode($message);
markdown_support_test_same(
	'[center][b]Alpha[/b][/center] and [b]Beta[/b]',
	$message,
	'Nested BBCode is restored after Markdown conversion'
);

$message = '[center][quote][b]Alpha[/b][/quote][/center] and [label](https://example.com/a_b) and `code`';
markdown_support_preparsecode($message);
markdown_support_test_same(
	'[center][quote][b]Alpha[/b][/quote][/center] and [url=https://example.com/a_b]label[/url] and [tt][nobbc]code[/nobbc][/tt]',
	$message,
	'Multiple placeholder levels are restored in dependency order'
);

$modSettings = array('markdown_support_parse_legacy' => 0);
$substitute = chr(26);
$visible_substitute = pack('H*', 'e2909a');
$entity = chr(38) . '#x241a;';
$message = 'Before '
	. $substitute . 'MSMD12' . $substitute
	. ' middle ' . $visible_substitute . 'MSMD34' . $visible_substitute
	. ' after ' . $entity . 'MSMD56' . $entity
	. ' current {SMFMD0123456789ABCDEFP78}';
$smileys = true;
$cache_id = '';
$parse_tags = array();
markdown_support_pre_parsebbc($message, $smileys, $cache_id, $parse_tags);
markdown_support_test_same(
	'Before  middle  after  current ',
	$message,
	'Legacy placeholders are hidden when legacy Markdown parsing is disabled'
);

$message = '[markdown]_forced_[/markdown] and **unchanged**';
$smileys = true;
$cache_id = '';
$parse_tags = array();
markdown_support_pre_parsebbc($message, $smileys, $cache_id, $parse_tags);
markdown_support_test_same(
	'[i]forced[/i] and **unchanged**',
	$message,
	'Markdown BBCode remains independent from legacy post parsing'
);

if (!empty($failures)) {
	fwrite(STDERR, implode("\n\n", $failures) . "\n");
	exit(1);
}

echo "All MarkdownSupport tests passed.\n";
