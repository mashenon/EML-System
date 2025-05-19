<?php
require_once("../config/db_connect.php");

// Check if all required fields are present
if (
    isset($_POST['id'], $_POST['name'], $_POST['username'], $_POST['password'], 
          $_POST['schedule_start'], $_POST['schedule_end'])
) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password']; // You can hash this later if needed
    $schedule_start = $_POST['schedule_start'];
    $schedule_end = $_POST['schedule_end'];

    // Prepare SQL update statement
    $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, password = ?, schedule_start = ?, schedule_end = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $username, $password, $schedule_start, $schedule_end, $id);

    if ($stmt->execute()) {
        // Redirect back to the dashboard or user list
        header("Location: ../user_dashboard.php");
        exit();
    } else {
        echo "Error updating user: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "Missing required fields.";
}

$conn->close();
?>
