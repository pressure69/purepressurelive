<?php

/**
 * Extracts top-level classes/interfaces/traits from index.php to src/Front/ with namespace.
 * Usage: php tools/refactor/extract-classes-from-index.php index.php
 */

declare(strict_types=1);

$index = $argv[1] ?? 'index.php';
$root  = realpath(__DIR__ . '/../../') ?: getcwd();
chdir($root);
if (!is_file($index)) {
    fwrite(STDERR, "Not found: $index\n");
    exit(1);
}

$code = file_get_contents($index);
if ($code === false) {
    fwrite(STDERR, "Unreadable: $index\n");
    exit(1);
}

$tokens = token_get_all($code);

/* Build offset map */
$offsets = [];
$buf = '';
foreach ($tokens as $t) {
    $txt = is_array($t) ? $t[1] : $t;
    $offsets[] = strlen($buf);
    $buf .= $txt;
}

$classes = [];
$len = count($tokens);
for ($i = 0; $i < $len; $i++) {
    $t = $tokens[$i];
    if (is_array($t) && ($t[0] === T_CLASS || $t[0] === T_INTERFACE || $t[0] === T_TRAIT)) {
        // skip anonymous class
        $j = $i - 1;
        while ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j--;
        }
        if ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_NEW) {
            continue;
        }

        // name
        $k = $i + 1;
        while ($k < $len && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
            $k++;
        }
        if (!isset($tokens[$k]) || !is_array($tokens[$k]) || $tokens[$k][0] !== T_STRING) {
            continue;
        }
        $name = $tokens[$k][1];

        // opening brace
        $brace = $k;
        while ($brace < $len && (!is_string($tokens[$brace]) || $tokens[$brace] !== '{')) {
            $brace++;
        }
        if ($brace >= $len) {
            continue;
        }

        // optional docblock start
        $docStartIdx = $i;
        $scan = $i - 1;
        while ($scan >= 0 && is_array($tokens[$scan]) && in_array($tokens[$scan][0], [T_WHITESPACE, T_FINAL, T_ABSTRACT, T_DOC_COMMENT], true)) {
            if ($tokens[$scan][0] === T_DOC_COMMENT) {
                $docStartIdx = $scan;
            }
            $scan--;
        }

        // match braces
        $depth = 1;
        $p = $brace + 1;
        while ($p < $len && $depth > 0) {
            $tok = $tokens[$p];
            if (is_string($tok)) {
                if ($tok === '{') {
                    $depth++;
                }
                if ($tok === '}') {
                    $depth--;
                }
            }
            $p++;
        }
        $endIdx = $p - 1;

        $classes[] = [
            'name'      => $name,
            'startOff'  => $offsets[$docStartIdx],
            'endOff'    => $offsets[$endIdx] + 1,
        ];
        $i = $p;
    }
}

if (!$classes) {
    fwrite(STDOUT, "[extract] No classes found in $index\n");
    exit(0);
}

$dstDir = $root . '/src/Front';
if (!is_dir($dstDir) && !mkdir($dstDir, 0775, true) && !is_dir($dstDir)) {
    fwrite(STDERR, "Cannot create $dstDir\n");
    exit(1);
}

$composerFile = $root . '/composer.json';
if (!is_file($composerFile)) {
    fwrite(STDERR, "composer.json not found\n");
    exit(1);
}
$composer = json_decode((string)file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);
$composer['autoload'] ??= [];
$composer['autoload']['psr-4'] ??= [];
if (!array_key_exists('PurePressureLive\\', $composer['autoload']['psr-4'])) {
    $composer['autoload']['psr-4']['PurePressureLive\\'] = 'src/';
    $bak = $composerFile . '.' . date('Ymd-His') . '.bak';
    copy($composerFile, $bak);
    file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    fwrite(STDOUT, "[extract] composer.json updated (backup: $bak)\n");
}

// remove bottom-up
usort($classes, fn ($a, $b) => $b['startOff'] <=> $a['startOff']);
$names = [];

foreach ($classes as $c) {
    $chunk = substr($code, $c['startOff'], $c['endOff'] - $c['startOff']);
    $dst = $dstDir . '/' . $c['name'] . '.php';
    if (!file_exists($dst)) {
        $fileCode = "<?php\ndeclare(strict_types=1);\n\nnamespace PurePressureLive\\Front;\n\n" . trim($chunk) . "\n";
        file_put_contents($dst, $fileCode);
        fwrite(STDOUT, "[extract] Wrote $dst\n");
    } else {
        fwrite(STDERR, "[extract] WARNING: $dst exists, skipped\n");
    }
    $code = substr($code, 0, $c['startOff']) . "\n" . substr($code, $c['endOff']);
    $names[] = $c['name'];
}

// ensure autoload require in index
if (!preg_match('/vendor\/autoload\.php\'\s*;/', $code)) {
    $code = preg_replace(
        '/^<\?php\s*(?:\/\*\*.*?\*\/\s*)?(?:declare\s*\(\s*strict_types\s*:\s*1\s*\)\s*;\s*)?/s',
        "<?php\n/**\n * Front controller\n * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace\n */\n" .
        "declare(strict_types=1);\nrequire __DIR__ . '/vendor/autoload.php';\n",
        $code,
        1
    );
}

// add use statements
$useBlock = '';
foreach ($names as $n) {
    if (!preg_match('/^use\s+PurePressureLive\\\\Front\\\\' . preg_quote($n, '/') . '\s*;/m', $code)) {
        $useBlock .= "use PurePressureLive\\Front\\{$n};\n";
    }
}
if ($useBlock !== '') {
    $code = preg_replace(
        '/(require\s+__DIR__\s*\.\s*\'\/vendor\/autoload\.php\'\s*;\s*)/i',
        "$1\n" . $useBlock,
        $code,
        1,
        $count
    );
    if ($count === 0) {
        $code = preg_replace(
            '/(declare\s*\(\s*strict_types\s*:\s*1\s*\)\s*;\s*)/i',
            "$1\n" . $useBlock,
            $code,
            1
        );
    }
}

$bakIdx = $index . '.' . date('Ymd-His') . '.bak';
copy($index, $bakIdx);
file_put_contents($index, $code);
fwrite(STDOUT, "[extract] Updated $index (backup: $bakIdx)\n");
fwrite(STDOUT, "[extract] Run: composer dump-autoload\n");
