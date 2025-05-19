<?php
header('Content-Type: application/json');
require_once('../config/db_connect.php');
date_default_timezone_set('Asia/Manila'); // Change as appropriate for your location


file_put_contents("debug_log.txt", date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

try {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $contentType === 'application/json') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['uid'])) {
            throw new Exception('UID parameter missing');
        }

        $uid = htmlspecialchars(strip_tags(trim($data['uid'])));
        $status = "";
        $access = "denied";

        // Check if UID exists
        $checkStmt = $conn->prepare("SELECT name, schedule_start, schedule_end FROM users WHERE uid = ?");
        $checkStmt->bind_param("s", $uid);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $user = $checkResult->fetch_assoc();
            $name = $user['name'];
            $start = $user['schedule_start'];
            $end = $user['schedule_end'];

            $now = date("H:i:s");

            if ($now >= $start && $now <= $end) {
                $status = "Access granted to " . $name;
                $access = "granted";
            } else {
                $status = "Access denied to " . $name . " (outside schedule)";
                $access = "denied";
            }
        } else {
            $status = "Unknown card scanned, Denied";
            $access = "unknown";
        }

        $checkStmt->close();

        // Log the attempt
        $insertStmt = $conn->prepare("INSERT INTO rfid_logs (uid, status) VALUES (?, ?)");
        $insertStmt->bind_param("ss", $uid, $status);
        $insertStmt->execute();
        $insertStmt->close();

        echo json_encode(['success' => true, 'message' => $status, 'access' => $access]);
    } else {
        throw new Exception('Invalid request method or content type');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
