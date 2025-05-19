<?php
require_once('../config/db_connect.php');

$location = 'Main Door'; // Change to match your ESP32 location string

$query = "SELECT status FROM lock_status WHERE location = '$location' LIMIT 1";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(["status" => $row['status']]);
} else {
    echo json_encode(["status" => "Unknown"]);
}
?>
