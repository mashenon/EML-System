<?php
session_start();
require_once '../config/db_connect.php';

$key = 'your256bitsecretkey'; // 32 chars

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = json_decode(file_get_contents('php://input'), true);
    if (!$json_data || !isset($json_data['uid']) || !isset($json_data['location'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    $uid = $json_data['uid'];
    $location = $json_data['location'];

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE AES_DECRYPT(uid, UNHEX(SHA2(:key,512))) = :uid");
    $stmt->bind_param('ss', $key, $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (isset($_SESSION['add_mode']) && $_SESSION['add_mode'] === true) {
        if ($user) {
            echo json_encode(['status' => 'error', 'message' => 'UID already exists.']);
        } else {
            $defaultName = "User_" . substr($uid, -4);
            $insert = $conn->prepare("INSERT INTO users (uid, name) VALUES (AES_ENCRYPT(:uid, UNHEX(SHA2(:key,512))), :name)");
            $insert->bind_param('s', $uid);
            $insert->bind_param('sss', $uid, $key, $defaultName);
            if ($insert->execute()) {
                unset($_SESSION['add_mode']);
                echo json_encode(['status' => 'success', 'message' => 'User registered successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to register user']);
            }
        }
    } else {
        // Normal logging mode
        $name = $user ? $user['name'] : null;
        $status = '';
        $message = '';

        if ($user) {
            $stmt = $conn->prepare("SELECT * FROM schedules WHERE user_id = :user_id AND CURTIME() BETWEEN start_time AND end_time AND DAYNAME(CURDATE()) = day_of_week");
            if ($user && isset($user['id'])) {
                $stmt->bind_param('i', $user['id']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $schedule = $result->fetch_assoc();
            $stmt->bind_param('i', $user['id']);
            if ($schedule) {
                $status = 'Access granted';
            } else {
                $status = 'Access denied due to schedule';
            }
        } else {
            $status = 'Unknown card scanned, Denied';
        }

        $log = $conn->prepare("INSERT INTO rfid_logs (uid, name, location, access_status) VALUES (:uid, :name, :location, :access_status)");
        $log->bind_param(':uid', $uid);
        $log->bind_param(':name', $name);
        $log->bind_param(':location', $location);
        $log->bind_param('ssss', $uid, $name, $location, $status);
    }
}
?>
