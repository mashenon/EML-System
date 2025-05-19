<?php
require_once("config/db_connect.php");

$status_result = $conn->query("SELECT status FROM em_lock_status ORDER BY updated_at DESC LIMIT 1");
$lock_status = ($status_result && $status_result->num_rows > 0) ? $status_result->fetch_assoc()['status'] : 'Unknown';

$log_result = $conn->query("SELECT status, timestamp FROM rfid_logs ORDER BY id DESC LIMIT 1");
$last_log = ($log_result && $log_result->num_rows > 0) ? $log_result->fetch_assoc() : ['status' => 'No recent scans', 'timestamp' => ''];
?>
<!DOCTYPE html>
<html>
<head>
    <title>EMLS Home</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background-color: white;
            color: #b30000;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }

        .container {
            border: 3px solid #b30000;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .status-box {
            font-size: 24px;
            font-weight: bold;
            margin-top: 20px;
        }

        .log-box {
            font-size: 18px;
            margin-top: 30px;
            color: #333;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            background-color: #c82333;
            color: #fff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn:hover {
            background-color: #a81c29;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to the EML System</h1>
        <div class="status-box">
            Electromagnetic Lock is:
            <span id="lock-status" style="color: <?= ($lock_status === 'Unlocked') ? 'green' : 'red'; ?>">
                <?= htmlspecialchars($lock_status) ?>
            </span>
        </div>
        <div class="log-box">
            <p><strong>Last Scan:</strong> <span id="last-message"><?= htmlspecialchars($last_log['status']) ?></span></p>
            <p><em id="last-timestamp"><?= htmlspecialchars($last_log['timestamp']) ?></em></p>
        </div>

        <div class="nav-buttons">
            <a href="dashboard.php" class="btn">Dashboard</a>
            <a href="logs.php" class="btn">Access Logs</a>
            <a href="user_dashboard.php" class="btn">Manage Users</a>
        </div>
    </div>

    <script>
    function fetchUpdates() {
        fetch('api/fetch_updates.php')
            .then(response => response.json())
            .then(data => {
                const lockStatusElement = document.getElementById('lock-status');
                lockStatusElement.textContent = data.lock_status;
                lockStatusElement.style.color = (data.lock_status === 'Unlocked') ? 'green' : 'red';

                document.getElementById('last-message').textContent = data.last_log.status;
                document.getElementById('last-timestamp').textContent = data.last_log.timestamp;
            })
            .catch(error => console.error('Error fetching updates:', error));
    }

    setInterval(fetchUpdates, 3000);
    </script>
</body>
</html>
