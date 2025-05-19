<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');

// Read the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if the required fields are present
if (!isset($data['status'], $data['location'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'status' or 'location' field"]);
    exit;
}

$status = strtolower(trim($data['status']));
$location = trim($data['location']);

// Validate the lock status
$valid_statuses = ['locked', 'unlocked'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid lock status value"]);
    exit;
}

// Prepare and execute the query using prepared statements for safety
$stmt = $conn->prepare("
    INSERT INTO lock_status (location, status, updated_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()
");

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Database prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("ss", $location, $status);

if ($stmt->execute()) {
    echo json_encode(["message" => "Lock status updated successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update lock status: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
