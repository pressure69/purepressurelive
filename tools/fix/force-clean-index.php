<?php

/**
 * Force-clean index.php header and close PHP before first HTML tag.
 * Usage: php tools/fix/force-clean-index.php index.php
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

$header = <<<HDR
<?php
/**
 * Front controller (sanitized)
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

HDR;

$pattern = '/<(?:!DOCTYPE|html|head|body|main|header|footer|section|article|nav|div|span|p|a|ul|ol|li|table|thead|tbody|tr|td|th|form|input|button|select|option|textarea|img|script|link|meta|style|h[1-6])\b/i';

if (preg_match($pattern, $raw, $m, PREG_OFFSET_CAPTURE)) {
    $offset = $m[0][1];
    $new = $header . "?>\n" . substr($raw, $offset);
} else {
    $tail = preg_replace('/^<\?php.*?\?>/s', '', $raw, 1);
    $new = $header . ltrim((string)$tail, "\r\n");
}

$bak = $path . '.' . date('Ymd-His') . '.bak';
@copy($path, $bak);
file_put_contents($path, $new);
fwrite(STDOUT, "[force-clean] wrote $path (backup: $bak)\n");
