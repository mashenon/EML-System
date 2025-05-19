<?php
require_once("../config/db_connect.php");

// Get latest EM lock status
$lock_result = $conn->query("SELECT status, updated_at FROM lock_status ORDER BY updated_at DESC LIMIT 1");
if ($lock_result && $lock_result->num_rows > 0) {
    $lock_row = $lock_result->fetch_assoc();
    $lock_status = $lock_row['status'];
    $lock_updated_at = $lock_row['updated_at'];
} else {
    $lock_status = 'Unknown';
    $lock_updated_at = '-';
}


// Get latest RFID log with user info
$log_query = "
    SELECT r.id, r.uid, r.status, r.timestamp, u.name 
    FROM rfid_logs r
    LEFT JOIN users u ON r.uid = u.uid
    ORDER BY r.id DESC
    LIMIT 1
";
$log_result = $conn->query($log_query);

$last_log = ($log_result && $log_result->num_rows > 0) 
    ? $log_result->fetch_assoc() 
    : ['id' => '-', 'uid' => '-', 'status' => 'No recent scans', 'timestamp' => '-', 'name' => 'Unknown'];

// Send response as JSON
header('Content-Type: application/json');
echo json_encode([
    'lock_status' => $lock_status,
    'updated_at' => $lock_updated_at,
    'last_log' => $last_log
]);

?>
