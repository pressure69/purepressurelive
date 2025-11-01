<?php

/**
 * Tools codemod; excluded from PHPStan via excludePaths.
 */

/* ignored by PHPStan via excludePaths */

declare(strict_types=1);

/**
 * Hard reset header so declare(strict_types=1) is truly the first statement.
 * Strategy: write canonical header, CLOSE PHP, then append original file.
 */

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

    // Split around the FIRST open tag
    $pos = strpos($raw, '<?php');
    $preHTML = $pos !== false ? substr($raw, 0, $pos) : '';
    $post = $pos !== false ? substr($raw, $pos + 5) : $raw; // after "<?php"
    $post = ltrim($post, "\r\n\t ");

    // Remove ALL strict_types declarations anywhere in the remainder
    $post = preg_replace('/\bdeclare\s*\(\s*strict_types\s*:\s*1\s*\)\s*;\s*/mi', '', $post);

    // Drop duplicate bootstrap/header noise
    $post = preg_replace('/^\s*require(?:_once)?\s+__DIR__\s*\.\s*\'\/bootstrap\/db\.php\'\s*;\s*/mi', '', $post);
    $post = preg_replace('/^\s*\/\*\*\s*@var\s*\\\\PDO\s*\$pdo\s*\*\/\s*/mi', '', $post);
    $post = preg_replace('/^\s*\$pdo\s*=\s*db\s*\(\s*\)\s*;\s*/mi', '', $post);
    $post = preg_replace('/^\s*global\s+\$pdo\s*;\s*$/mi', '', $post);

    // If remainder immediately starts with a closing tag, drop it (we will close ourselves)
    if (strncmp($post, '?>', 2) === 0) {
        $post = substr($post, 2);
    }

    // Canonical header (declare FIRST), then CLOSE PHP to avoid nested tags issues
    $header = <<<HDR
<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap/db.php';
/** @var \\PDO \$pdo */
\$pdo = db();
?>

HDR;

    // Assemble: header + original pre-HTML + cleaned rest
    $new = $header . $preHTML . $post;

    // Compact excessive blank lines
    $new = preg_replace("/(\r?\n){3,}/", "\n\n", $new);

    // Write with backup
    $bak = $file . '.' . date('Ymd-His') . '.bak';
    @copy($file, $bak);
    file_put_contents($file, $new);
    fwrite(STDOUT, "[fix]  $file (strict-first hard reset, backup: $bak)\n");
}
