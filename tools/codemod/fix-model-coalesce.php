<?php

/**
 * Tools codemod; excluded from PHPStan via excludePaths.
 */

/* ignored by PHPStan via excludePaths */

declare(strict_types=1);

$F = 'model_dashboard.php';
$s = file_get_contents($F);
if ($s === false) {
    fwrite(STDERR, "read fail: $F\n");
    exit(1);
}
$orig = $s;

/* 1) Rewrite `$model ??= EXPR;` → `if (!isset($model)) { $model = EXPR; }` */
$s = preg_replace(
    '/\$model\s*\?\?=\s*([^;]+);/i',
    'if (!isset($model)) { $model = $1; }',
    $s
);

/* 2) Rewrite occurrences of `$model ?? EXPR` inside expressions
      (before ; , ) ] :) → `isset($model) ? $model : EXPR` */
$s = preg_replace(
    '/\$model\s*\?\?\s*([^;,\)\]\:]+)(?=\s*[;,\)\]\:])/i',
    'isset($model) ? $model : $1',
    $s
);

if ($s === null) {
    fwrite(STDERR, "regex fail\n");
    exit(1);
}

if ($s !== $orig) {
    $bak = $F . '.' . date('Ymd-His') . '.bak';
    copy($F, $bak);
    file_put_contents($F, $s);
    echo "[patched] $F (backup: $bak)\n";
} else {
    echo "[noop] nothing to change in $F\n";
}
