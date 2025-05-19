<?php
require_once('config/db_connect.php');

// Fetch the most recent UID from pending_card_registration table
$stmt = $pdo->query("SELECT * FROM pending_card_registration ORDER BY id DESC LIMIT 1");
$pendingCard = $stmt->fetch();

if ($pendingCard) {
    echo json_encode([
        'status' => 'pending',
        'message' => 'Waiting for card scan...',
        'uid' => $pendingCard['uid']
    ]);
} else {
    echo json_encode([
        'status' => 'no_pending',
        'message' => 'No UID scanned yet.'
    ]);
}
