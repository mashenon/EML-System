<?php
// File: api/register_uid.php
header("Content-Type: application/json");
require_once("../config/db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['uid'] ?? null;

    if (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'UID not provided']);
        exit;
    }

    // Check if UID is already pending
    $check = $conn->prepare("SELECT * FROM pending_card_registration WHERE uid = ?");
    $check->bind_param("s", $uid);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'duplicate', 'message' => 'UID already pending']);
    } else {
        // Insert new UID into pending table
        $insert = $conn->prepare("INSERT INTO pending_card_registration (uid) VALUES (?)");
        $insert->bind_param("s", $uid);
        if ($insert->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'UID registered for pending']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to register UID']);
        }
    }

    $check->close();
    $insert->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>
