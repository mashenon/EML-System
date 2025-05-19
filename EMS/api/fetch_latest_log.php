<?php
require_once("../config/db_connect.php");

$lastId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "
    SELECT r.id, r.uid, r.status, r.timestamp, u.name 
    FROM rfid_logs r
    LEFT JOIN users u ON r.uid = u.uid
    WHERE r.id > ?
    ORDER BY r.id ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lastId);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

header('Content-Type: application/json');
echo json_encode($logs);
