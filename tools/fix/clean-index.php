<?php

/**
 * Force-clean index.php header and close PHP before first HTML.
 * Usage: php tools/fix/clean-index.php index.php
 */

declare(strict_types=1);

$path = $argv[1] ?? 'index.php';
if (!is_file($path)) {
    fwrite(STDERR, "Not found: $path\n");
    exit(1);
}

$raw = file_get_contents($path);
if ($raw === false) {
    fwrite(STDERR, "Read fail: $path\n");
    exit(1);
}

if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
    $raw = substr($raw, 3);
}
$raw = ltrim($raw, "\r\n\t ");

$header = <<<'HDR'
<?php
/**
 * Front controller (sanitized)
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

HDR;

$re = '/<(?:!DOCTYPE|html|head|body|main|header|footer|section|article|nav|div|span|p|a|ul|ol|li|table|thead|tbody|tr|td|th|form|input|button|select|option|textarea|img|script|link|meta|style|h[1-6])\b/i';
if (preg_match($re, $raw, $m, PREG_OFFSET_CAPTURE)) {
    $htmlPos = $m[0][1];
    $out = $header . "?>\n" . substr($raw, $htmlPos);
} else {
    $out = $header;
}

$bak = $path . '.' . date('Ymd-His') . '.bak';
@copy($path, $bak);
file_put_contents($path, $out);
fwrite(STDOUT, "[clean-index] wrote $path (backup: $bak)\n");
