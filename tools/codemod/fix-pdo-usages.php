<?php

/**
 * Ensures DB bootstrap and local $pdo = db(); in selected files.
 * Idempotent: removes "global $pdo;", inserts header pieces once.
 * Usage: php tools/codemod/fix-pdo-usages.php
 */

declare(strict_types=1);

$targets = [
    'feed.php',
    'model_dashboard.php',
    'model_stream.php',
    'upload_stream.php',
];

foreach ($targets as $file) {
    if (!is_file($file)) {
        fwrite(STDOUT, "[skip] $file (not found)\n");
        continue;
    }

    $code = file_get_contents($file);
    if ($code === false) {
        fwrite(STDERR, "[skip] $file (unreadable)\n");
        continue;
    }

    // Normalize BOM and leading whitespace
    if (strncmp($code, "\xEF\xBB\xBF", 3) === 0) {
        $code = substr($code, 3);
    }
    $code = ltrim($code, "\r\n\t ");

    $orig = $code;

    // Ensure PHP open tag exists
    if (!str_starts_with($code, '<?php')) {
        $code = "<?php\n" . $code;
    }

    // Strip any existing declare(strict_types=1);
    $code = preg_replace('/^\s*<\?php\s*/', "<?php\n", $code, 1);
    $code = preg_replace('/^\s*declare\s*\(\s*strict_types\s*:\s*1\s*\)\s*;\s*/mi', '', $code, 1);

    // Remove previous bootstrap lines (avoid dupes)
    $code = preg_replace('/^\s*require_once\s+__DIR__\s*\.\s*\'\/bootstrap\/db\.php\'\s*;\s*/mi', '', $code, 1);
    $code = preg_replace('/^\s*\/\*\*\s*@var\s*\\\\PDO\s*\$pdo\s*\*\/\s*$/mi', '', $code, 1);
    $code = preg_replace('/^\s*\$pdo\s*=\s*db\s*\(\s*\)\s*;\s*$/mi', '', $code, 1);

    // Remove "global $pdo;"
    $code = preg_replace('/^\s*global\s+\$pdo\s*;\s*$/mi', '', $code);

    // Build canonical header: declare → bootstrap → $pdo
    $header  = "declare(strict_types=1);\n";
    $header .= "require_once __DIR__ . '/bootstrap/db.php';\n";
    $header .= "/** @var \\PDO \$pdo */\n";
    $header .= "\$pdo = db();\n\n";

    // Reassemble: keep the rest of the file after opening tag
    $code = preg_replace('/^<\?php\s*/', "<?php\n" . $header, $code, 1);

    if ($code !== $orig) {
        copy($file, $file . '.bak');
        file_put_contents($file, $code);
        fwrite(STDOUT, "[upd]  $file (strict-first + db bootstrap)\n");
    } else {
        fwrite(STDOUT, "[ok ] $file\n");
    }
}
