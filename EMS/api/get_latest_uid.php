<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');

if (!isset($_GET['after'])) {
    echo json_encode(['uid' => null]);
    exit;
}

$after = $_GET['after'];

// Prepare the query
$sql = "SELECT uid FROM rfid_logs 
        WHERE timestamp > ? AND status = 'Unknown card scanned, Denied'
        ORDER BY timestamp ASC 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $after);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['uid' => $row['uid']]);
} else {
    echo json_encode(['uid' => null]);
}
?>
