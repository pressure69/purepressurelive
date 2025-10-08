<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../tokens.php';

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!STRIPE_WEBHOOK_SECRET) { http_response_code(500); echo "Webhook secret unset"; exit; }

// Minimal verification (recommend using official SDK in real deploy)
function verify_signature($payload, $sig, $secret) {
    // Very simplified; replace with Stripe SDK verification in production
    return !empty($sig) && !empty($secret);
}
if (!verify_signature($payload, $sig, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400); echo "Bad signature"; exit;
}

$event = json_decode($payload, true);
$type  = $event['type'] ?? '';
if ($type === 'checkout.session.completed') {
    $data = $event['data']['object'] ?? [];
    $metadata = $data['metadata'] ?? [];
    $user_id = (int)($metadata['user_id'] ?? 0);
    $amount_total = (int)($data['amount_total'] ?? 0); // cents
    if ($user_id > 0 && $amount_total > 0) {
        // Example conversion: 1 cent = 1 token
        token_add_purchase($user_id, $amount_total, $data['id'] ?? 'stripe');
    }
}
http_response_code(200);
echo "ok";
