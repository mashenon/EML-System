<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');
error_reporting(0);

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $uid = trim($_POST['uid'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $schedule_start = $_POST['schedule_start'] ?? '';
        $schedule_end = $_POST['schedule_end'] ?? '';

        if (empty($uid)) {
            throw new Exception("No RFID UID found. Please scan a card.");
        }

        // Check if UID or username exists FIRST
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE uid = ? OR username = ?");
        $checkStmt->bind_param("ss", $uid, $username);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            throw new Exception("Error: UID or Username already exists.");
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (uid, name, username, password, schedule_start, schedule_end) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $uid, $name, $username, $hashedPassword, $schedule_start, $schedule_end);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            throw new Exception("Error adding user: " . $stmt->error);
        }
    }
    elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $uid = $_POST['uid'];
        $name = $_POST['name'];
        $username = $_POST['username'];
        $schedule_start = $_POST['schedule_start'];
        $schedule_end = $_POST['schedule_end'];

        $stmt = $conn->prepare("UPDATE users SET uid = ?, name = ?, username = ?, schedule_start = ?, schedule_end = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $uid, $name, $username, $schedule_start, $schedule_end, $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    }
    else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Add this after successful add/update/delete operations
function notifyESP32s() {
    $context = stream_context_create([
        'http' => ['ignore_errors' => true]
    ]);
    
    // List of known ESP32 IPs (you might want to store this in DB)
    $espIPs = ["192.168.1.100", "192.168.1.101"];
    
    foreach($espIPs as $ip) {
        @file_get_contents("http://{$ip}/update", false, $context);
    }
}
?>