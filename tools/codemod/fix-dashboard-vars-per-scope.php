<?php

/**
 * Tools codemod; excluded from PHPStan via excludePaths.
 */

/* ignored by PHPStan via excludePaths */

declare(strict_types=1);

/**
 * Insert guards for $display/$progress/$remaining at the start of every PHP segment
 * AFTER the first (header). Idempotent via a marker block.
 */

$F = __DIR__ . '/../../model_dashboard.php';
if (!is_file($F)) {
    fwrite(STDERR, "[skip] not found: $F\n");
    exit(1);
}

$s = file_get_contents($F);
if ($s === false) {
    fwrite(STDERR, "read fail: $F\n");
    exit(1);
}
$orig = $s;

$GUARD = <<<GUARD
/* dash-var-guards */
if (!isset(\$display) || !is_string(\$display)) { \$display = ''; }
if (!isset(\$progress) || !is_int(\$progress)) { \$progress = 0; }
if (!isset(\$remaining) || !is_int(\$remaining)) { \$remaining = 0; }
/* /dash-var-guards */

GUARD;

$pos = 0;
$first = true;
while (true) {
    $start = strpos($s, '<?php', $pos);
    if ($start === false) {
        break;
    }
    $openEnd = $start + 5; // after '<?php'
    $end = strpos($s, '?>', $openEnd);
    if ($end === false) {
        $end = strlen($s);
    }

    if ($first) {
        $first = false;
        $pos = $end; // skip header
        continue;
    }

    $segment = substr($s, $openEnd, $end - $openEnd);
    if (strpos($segment, '/* dash-var-guards */') === false) {
        $s = substr($s, 0, $openEnd) . "\n" . $GUARD . substr($s, $openEnd);
        $pos = $end + strlen($GUARD); // account for insertion
    } else {
        $pos = $end;
    }
}

if ($s !== $orig) {
    $bak = $F . '.' . date('Ymd-His') . '.bak';
    if (!@copy($F, $bak)) {
        fwrite(STDERR, "backup failed: $bak\n");
        exit(1);
    }
    if (file_put_contents($F, $s) === false) {
        fwrite(STDERR, "write failed: $F\n");
        exit(1);
    }
    echo "[patched] model_dashboard.php (backup: $bak)\n";
} else {
    echo "[noop] No PHP segments needed guards\n";
}
