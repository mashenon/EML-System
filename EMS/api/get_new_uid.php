<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');
error_reporting(0);

try {
    // Get the latest scan session marker
    $markerResult = $conn->query("SELECT uid FROM pending_card_registration ORDER BY id DESC LIMIT 1");
    $marker = $markerResult->fetch_assoc();
    
    if (!$marker) {
        throw new Exception("No active scan session");
    }
    
    // Find scans after this marker (excluding the marker itself)
    $query = "SELECT uid FROM rfid_logs 
              WHERE timestamp > (SELECT timestamp FROM rfid_logs WHERE uid = ? LIMIT 1)
              AND uid != ?
              ORDER BY timestamp DESC LIMIT 1";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $marker['uid'], $marker['uid']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Don't return system markers
        if (strpos($row['uid'], 'SCAN_SESSION_') === 0) {
            echo json_encode(['uid' => null]);
        } else {
            echo json_encode(['uid' => $row['uid']]);
        }
    } else {
        echo json_encode(['uid' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>