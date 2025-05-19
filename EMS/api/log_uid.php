<?php
include_once("../config/db_connect.php");

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data["uid"])) {
    $uid = $data["uid"];
    $location = $data["location"] ?? 'unknown';  // Optional field
    $timestamp = date("Y-m-d H:i:s");
    $message = "RFID card scanned";

    // Store into logs
    $stmt = $conn->prepare("INSERT INTO rfid_logs (uid, timestamp, message, location) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $uid, $timestamp, $message, $location);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "UID logged"]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB error"]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "No UID received"]);
}
$conn->close();
?>
