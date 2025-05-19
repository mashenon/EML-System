<?php
// File: dashboard.php
session_start();
require_once('config/db_connect.php');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch the user's name from session
//$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EML Dashboard</title>
    <link rel="stylesheet" href="/EMS/assets/style.css">
    <style>
        .dashboard-box {
            background-color: #f0f0f0;
            padding: 20px;
            margin-top: 30px;
            border-radius: 10px;
            border-left: 6px solid #dc3545;
        }
        a.button-link {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a.button-link:hover {
            background-color: #a71d2a;
        }
    </style>
</head>
<body>
    <h1>Welcome to the EML Dashboard</h1>
    <div class="dashboard-box">
        <p>Hello, <strong><?= htmlspecialchars($username) ?></strong> 👋</p>
        <p>Select an option below to get started:</p>
        <a class="button-link" href="logs.php">📜 View Access Logs</a><br>
        <a class="button-link" href="user_dashboard.php">👥 Manage Users</a><br>
        <a class="button-link" href="logout.php">🔓 Logout</a>
    </div>
</body>
</html>
