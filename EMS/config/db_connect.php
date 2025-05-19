<?php
// File: config/db_connect.php

$servername = "localhost";
$username = "root";
$password = ""; // or your MySQL root password
$database = "ems_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
