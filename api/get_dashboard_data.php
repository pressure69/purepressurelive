<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (empty($_SESSION['model_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
    ]);
    exit;
}

$modelId = (int) $_SESSION['model_id'];

try {
    $tipsStmt = $pdo->prepare(
        'SELECT COALESCE(f.display_name, f.username) AS username, t.amount, t.message, t.created_at
         FROM tips t
         LEFT JOIN fans f ON f.id = t.fan_id
         WHERE t.model_id = :model_id
         ORDER BY t.created_at DESC
         LIMIT 20'
    );
    $tipsStmt->execute(['model_id' => $modelId]);
    $tipsRaw = $tipsStmt->fetchAll();

    $fansStmt = $pdo->prepare(
        'SELECT COALESCE(f.display_name, f.username) AS username, SUM(t.amount) AS total
         FROM tips t
         INNER JOIN fans f ON f.id = t.fan_id
         WHERE t.model_id = :model_id
         GROUP BY t.fan_id, f.display_name, f.username
         ORDER BY total DESC
         LIMIT 10'
    );
    $fansStmt->execute(['model_id' => $modelId]);
    $fansRaw = $fansStmt->fetchAll();

    $totalsStmt = $pdo->prepare(
        'SELECT 
            COALESCE(SUM(t.amount), 0) AS total_tokens,
            m.token_goal AS token_goal
         FROM models m
         LEFT JOIN tips t ON t.model_id = m.id
         WHERE m.id = :model_id
         GROUP BY m.token_goal'
    );
    $totalsStmt->execute(['model_id' => $modelId]);
    $totals = $totalsStmt->fetch() ?: ['total_tokens' => 0, 'token_goal' => 0];
} catch (Throwable $e) {
    error_log('Dashboard data load failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load dashboard data right now.',
    ]);
    exit;
}

$tips = array_map(static function (array $row): array {
    return [
        'username'   => $row['username'] ?? 'Anonymous',
        'amount'     => isset($row['amount']) ? (int) $row['amount'] : 0,
        'message'    => $row['message'] ?? null,
        'created_at' => $row['created_at'] ?? null,
    ];
}, $tipsRaw ?: []);

$fans = array_map(static function (array $row): array {
    return [
        'username' => $row['username'] ?? 'Anonymous',
        'total'    => isset($row['total']) ? (int) $row['total'] : 0,
    ];
}, $fansRaw ?: []);

$totalTokens = isset($totals['total_tokens']) ? (int) $totals['total_tokens'] : 0;
$tokenGoal = isset($totals['token_goal']) ? (int) $totals['token_goal'] : 0;

if ($tokenGoal < 0) {
    $tokenGoal = 0;
}

echo json_encode([
    'success' => true,
    'tips'    => $tips,
    'fans'    => $fans,
    'total'   => $totalTokens,
    'goal'    => $tokenGoal,
]);
