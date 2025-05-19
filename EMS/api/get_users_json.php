<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');

$query = "SELECT uid, name, schedule_start, schedule_end FROM users";
$result = $conn->query($query);

$users = [];
while($row = $result->fetch_assoc()) {
    $users[] = [
        'uid' => $row['uid'],
        'name' => $row['name'],
        'start' => $row['schedule_start'],
        'end' => $row['schedule_end']
    ];
}

echo json_encode($users);
?>