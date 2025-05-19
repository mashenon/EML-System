<?php
require_once('../config/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';


    if ($action === 'add') {
        // Check if UID has been scanned and stored
        $uid = trim($_POST['uid'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = password_hash(trim($_POST['password'] ?? ''), PASSWORD_BCRYPT);
        $schedule_start = $_POST['schedule_start'] ?? '';
        $schedule_end = $_POST['schedule_end'] ?? '';

        if ($uid === '') {
            echo "No RFID UID found. Please scan a card.";
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users (uid, name, username, password, schedule_start, schedule_end) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $uid, $name, $username, $password, $schedule_start, $schedule_end);

        if ($stmt->execute()) {
            header("Location: ../user_dashboard.php?added=1");
        } else {
            echo "Error adding user: " . $stmt->error;
        }
// Check if UID or username already exists
$checkStmt = $conn->prepare("SELECT id FROM users WHERE uid = ? OR username = ?");
$checkStmt->bind_param("ss", $uid, $username);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    echo "Error: UID or Username already exists.";
    exit();
}
$checkStmt->close();
    }

    if ($action === 'update') {
        // Sanitize inputs
        $id = intval($_POST['id']);
        $uid = $_POST['uid'];
        $name = $_POST['name'];
        $username = $_POST['username'];
        $schedule_start = $_POST['schedule_start'];
        $schedule_end = $_POST['schedule_end'];

        // Update user
        $stmt = $conn->prepare("UPDATE users SET uid = ?, name = ?, username = ?, schedule_start = ?, schedule_end = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $uid, $name, $username, $schedule_start, $schedule_end, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: ../user_dashboard.php");
        exit();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: ../user_dashboard.php");
        exit();
    } else {
        echo "Invalid action.";
    }
} else {
    echo "Invalid request method.";
}
