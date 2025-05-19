<?php
// File: api/verify_uid.php
header("Content-Type: application/json");
require_once("../config/db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['uid'] ?? null;

    if (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'UID not provided']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, full_name, schedule_start, schedule_end FROM users WHERE rfid_uid = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $status = "";
    $log_message = "";

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fullName = $user['full_name'];
        $startTime = $user['schedule_start'];
        $endTime = $user['schedule_end'];
        $currentTime = date("H:i:s");

        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            $status = "granted";
            $log_message = "$fullName, Access granted";
        } else {
            $status = "denied_schedule";
            $log_message = "$fullName, Access denied due to schedule";
        }

        // Log the access
        $log = $conn->prepare("INSERT INTO rfid_logs (uid, message, status) VALUES (?, ?, ?)");
        $log->bind_param("sss", $uid, $log_message, $status);
        $log->execute();
        $log->close();

        echo json_encode([
            "status" => $status,
            "message" => $log_message
        ]);
    } else {
        // Unknown card
        $status = "denied_unknown";
        $log_message = "Unknown card scanned, Denied";

        $log = $conn->prepare("INSERT INTO rfid_logs (uid, message, status) VALUES (?, ?, ?)");
        $log->bind_param("sss", $uid, $log_message, $status);
        $log->execute();
        $log->close();

        echo json_encode([
            "status" => $status,
            "message" => $log_message
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
