<?php

/**
 * Fix raw HTML inside PHP blocks by closing PHP before HTML tags.
 * Usage: php tools/fix/repair-index.php index.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}
if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/fix/repair-index.php index.php\n");
    exit(1);
}
$file = $argv[1];
if (!is_file($file)) {
    fwrite(STDERR, "Not found: $file\n");
    exit(1);
}

$code = file_get_contents($file);
$orig = $code;

if (str_starts_with($code, "\xEF\xBB\xBF")) {
    $code = substr($code, 3);
}
$code = ltrim($code, "\r\n\t ");
if (!str_starts_with($code, '<?php')) {
    $code = "<?php\n" . $code;
}

$header = "<?php\n/**\n * Front controller (HTML/PHP boundaries repaired)\n * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace\n */\n";
$code = preg_replace('/^\s*<\?php/s', $header, $code, 1);

if (!preg_match('/declare\s*\(\s*strict_types\s*:\s*1\s*\)\s*;/i', $code)) {
    $code = preg_replace('/^<\?php\s*\/\*\*.*?\*\/\s*/s', "$0declare(strict_types=1);\n", $code, 1);
}

$inPhp = true;
$out = '';
$len = strlen($code);
$inS = $inD = $inLC = $inBC = false;

for ($i = 0; $i < $len; $i++) {
    if (!$inPhp) {
        if (substr($code, $i, 5) === '<?php') {
            $inPhp = true;
            $out .= '<?php';
            $i += 4;
            continue;
        }
        if (substr($code, $i, 3) === '<?=') {
            $inPhp = true;
            $out .= '<?=';
            $i += 2;
            continue;
        }
        $out .= $code[$i];
        continue;
    }
    if (!$inS && !$inD) {
        if (!$inBC && substr($code, $i, 2) === '//') {
            $inLC = true;
        }
        if (!$inBC && !$inLC && $code[$i] === '#') {
            $inLC = true;
        }
        if (!$inBC && substr($code, $i, 2) === '/*') {
            $inBC = true;
        }
        if ($inBC && substr($code, $i, 2) === '*/') {
            $inBC = false;
            $out .= '*/';
            $i++;
            continue;
        }
        if ($inLC && ($code[$i] === "\n" || $code[$i] === "\r")) {
            $inLC = false;
        }
    }
    if (!$inLC && !$inBC) {
        if (!$inD && $code[$i] === '\'' && ($i === 0 || $code[$i - 1] !== '\\')) {
            $inS = !$inS;
        } elseif (!$inS && $code[$i] === '"' && ($i === 0 || $code[$i - 1] !== '\\')) {
            $inD = !$inD;
        }
    }
    if (!$inS && !$inD && !$inLC && !$inBC && substr($code, $i, 2) === '?>') {
        $inPhp = false;
        $out .= '?>';
        $i++;
        continue;
    }

    if (
        !$inS && !$inD && !$inLC && !$inBC &&
        $code[$i] === '<' &&
        !(substr($code, $i, 5) === '<?php' || substr($code, $i, 3) === '<?=') &&
        preg_match('/\G<(?:!DOCTYPE|html|head|body|main|header|footer|section|article|nav|div|span|p|a|ul|ol|li|table|thead|tbody|tr|td|th|form|input|button|select|option|textarea|img|script|link|meta|style|h[1-6])\b/i', $code, $m, 0, $i)
    ) {
        $out .= '?>';
        $inPhp = false;
        $i--;
        continue;
    }
    $out .= $code[$i];
}

if ($out !== $orig) {
    $bak = $file . '.' . date('Ymd-His') . '.bak';
    copy($file, $bak);
    file_put_contents($file, $out);
    fwrite(STDOUT, "[repair] wrote $file (backup: $bak)\n");
} else {
    fwrite(STDOUT, "[repair] no changes for $file\n");
}
