<?php
require_once('../config/db_connect.php');

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 10;

$stmt = $conn->prepare("
    SELECT r.id, r.uid, r.status, r.timestamp, u.name 
    FROM rfid_logs r 
    LEFT JOIN users u ON r.uid = u.uid 
    ORDER BY r.timestamp DESC 
    LIMIT ?, ?
");

$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

header('Content-Type: application/json');
echo json_encode($logs);
