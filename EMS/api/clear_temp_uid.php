<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');
error_reporting(0);

try {
    // 1. Clear any pending registrations from previous attempts
    $conn->query("TRUNCATE TABLE pending_card_registration");
    
    // 2. Insert a marker record with a special UID to mark the start of a new scan session
    $markerUid = 'SCAN_SESSION_' . bin2hex(random_bytes(4));
    $stmt = $conn->prepare("INSERT INTO rfid_logs (uid, status) VALUES (?, 'Scan session started')");
    $stmt->bind_param("s", $markerUid);
    $stmt->execute();
    
    // 3. Store the marker timestamp in pending_card_registration
    $conn->query("INSERT INTO pending_card_registration (uid) VALUES ('$markerUid')");
    
    echo json_encode([
        'success' => true,
        'message' => 'Scanner initialized for new card registration',
        'session_marker' => $markerUid
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error initializing scanner: ' . $e->getMessage()
    ]);
}
?>