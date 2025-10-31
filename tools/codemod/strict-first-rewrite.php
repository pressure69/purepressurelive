<?php

declare(strict_types=1);

$targets = ['feed.php','model_dashboard.php','upload_stream.php'];

foreach ($targets as $file) {
    if (!is_file($file)) {
        fwrite(STDOUT, "[skip] $file (not found)\n");
        continue;
    }

    $raw = file_get_contents($file);
    if ($raw === false) {
        fwrite(STDERR, "[skip] $file (unreadable)\n");
        continue;
    }

    // Strip BOM
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
        $raw = substr($raw, 3);
    }

    // Keep ONLY from first PHP open tag onward (drop any HTML/bytes before)
    $phpPos = strpos($raw, '<?php');
    $from = $phpPos !== false ? substr($raw, $phpPos + 5) : $raw; // skip the open tag itself

    // Normalize/cleanup the leading segment
    $code = ltrim($from, "\r\n\t ");

    // Remove any existing declare(strict_types=1);
    $code = preg_replace('/^\s*declare\s*\(\s*strict_types\s*:\s*1\s*\)\s*;\s*/mi', '', $code, 1);

    // Remove previous DB bootstrap lines to avoid duplicates
    $code = preg_replace('/^\s*require(?:_once)?\s+__DIR__\s*\.\s*\'\/bootstrap\/db\.php\'\s*;\s*/mi', '', $code, 1);
    $code = preg_replace('/^\s*\/\*\*\s*@var\s*\\\\PDO\s*\$pdo\s*\*\/\s*/mi', '', $code, 1);
    $code = preg_replace('/^\s*\$pdo\s*=\s*db\s*\(\s*\)\s*;\s*/mi', '', $code, 1);

    // Remove any lingering "global $pdo;"
    $code = preg_replace('/^\s*global\s+\$pdo\s*;\s*$/mi', '', $code);

    // Canonical header
    $header = <<<PHPHDR
<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap/db.php';
/** @var \\PDO \$pdo */
\$pdo = db();

PHPHDR;

    $new = $header . ltrim($code, "\r\n");

    // One final safety: ensure there's no second declare at the top due to fixer
    $new = preg_replace('/\R{3,}/', "\n\n", $new);

    // Write with backup
    $bak = $file . '.' . date('Ymd-His') . '.bak';
    @copy($file, $bak);
    file_put_contents($file, $new);
    fwrite(STDOUT, "[fix]  $file (strict-first header rebuilt, backup: $bak)\n");
}
