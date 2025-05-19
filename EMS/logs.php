<?php
require_once('config/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch initial logs (limit 10 for performance)
$stmt = $conn->query("
    SELECT r.id, r.uid, r.status, r.timestamp, u.name 
    FROM rfid_logs r 
    LEFT JOIN users u ON r.uid = u.uid 
    ORDER BY r.id DESC
    LIMIT 10
");

$query = "SELECT status, updated_at FROM lock_status WHERE location = 'classroom-01'";
$result = $conn->query($query);
$lockStatus = "unknown";
$updatedAt = "";

if ($result && $row = $result->fetch_assoc()) {
    $lockStatus = $row['status'];
    $updatedAt = $row['updated_at'];
}

$logs = [];
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $logs[] = $row;
    }
} else {
    echo "Error fetching logs: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Logs</title>
    <link rel="stylesheet" href="/EMS/assets/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 40px;
            background-color: #fff;
            color: #333;
        }

        h1 {
            color: #c82333;
        }

        a {
            color: #c82333;
            text-decoration: none;
            margin-right: 10px;
        }

        .status-box, .log-box {
            background-color: #fefefe;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            border-left: 5px solid #c82333;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .status-box span {
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 30px;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #c82333;
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        #loadMore {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #c82333;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
        }

        #loadMore:hover {
            background-color: #a81c29;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
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

        .logout {
            background-color: #555;
        }

        .logout:hover {
            background-color: #333;
        }

        #logContainer {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <h1>Access Log Dashboard</h1>

    <div class="nav-buttons">
        <a class="btn" href="dashboard.php">← Back to Dashboard</a>
        <a class="btn" href="user_dashboard.php">Manage Users</a>
        <a class="btn logout" href="logout.php">Logout</a>
    </div>

    <div class="status-box">
    <h3>Electromagnetic Lock is: <span id="lockStatus" style="color: <?= $lockStatus == 'unlocked' ? 'green' : 'red' ?>"><?= strtoupper($lockStatus) ?></span></h3>

    <p>Last Updated: <span id="lastUpdated"><?= $updatedAt ?></span></p>

    </div>

    <div class="log-box">
        <p><strong>Last Scan:</strong> <span id="scanStatus">Loading...</span></p>
        <p><em id="scanTimestamp"></em></p>
    </div>

    <div id="logContainer" style="max-height: 400px; overflow-y: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>UID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody id="logTableBody">
                <?php foreach ($logs as $log): ?>
                    <tr data-id="<?= $log['id'] ?>">
                        <td><?= htmlspecialchars($log['id']) ?></td>
                        <td><?= htmlspecialchars($log['uid']) ?></td>
                        <td><?= htmlspecialchars($log['name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($log['status']) ?></td>
                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button id="loadMore">Load More</button>

    <script>
        let offset = 10;
        let latestLogId = <?= !empty($logs) ? (int)$logs[0]['id'] : 0 ?>;

        function fetchUpdates() {
    fetch("api/fetch_updates.php")
        .then(response => response.json())
        .then(data => {
            const lockStatusEl = document.getElementById("lockStatus");
            const lastUpdatedEl = document.getElementById("lastUpdated"); // 👈 FIXED
            const scanStatusEl = document.getElementById("scanStatus");
            const scanTimestampEl = document.getElementById("scanTimestamp");

            // Update lock status and timestamp
            lockStatusEl.textContent = data.lock_status.toUpperCase();
            lockStatusEl.style.color = (data.lock_status === "unlocked") ? "green" : "red";
            lastUpdatedEl.textContent = data.updated_at;

            // Update scan status
            scanStatusEl.textContent = data.last_log.status;
            scanTimestampEl.textContent = data.last_log.timestamp;

            // Append new log row if new log ID
            if (data.last_log.id > latestLogId) {
                latestLogId = data.last_log.id;

                const tbody = document.getElementById("logTableBody");
                if (!tbody.querySelector(`tr[data-id="${latestLogId}"]`)) {
                    const newRow = document.createElement("tr");
                    newRow.setAttribute("data-id", latestLogId);
                    newRow.innerHTML = `
                        <td>${data.last_log.id}</td>
                        <td>${data.last_log.uid}</td>
                        <td>${data.last_log.name || 'Unknown'}</td>
                        <td>${data.last_log.status}</td>
                        <td>${data.last_log.timestamp}</td>
                    `;
                    tbody.insertBefore(newRow, tbody.firstChild);
                    document.getElementById("logContainer").scrollTop = 0;
                    offset++;
                }
            }
        })
        .catch(err => console.error("Failed to fetch updates:", err));
}


fetchUpdates(); // Run it once immediately
setInterval(fetchUpdates, 500); // Then every half a second



        function loadMoreLogs() {
            fetch("api/load_more_logs.php?offset=" + offset)
                .then(res => res.json())
                .then(logs => {
                    const tbody = document.getElementById("logTableBody");
                    logs.forEach(log => {
                        const row = document.createElement("tr");
                        row.setAttribute("data-id", log.id);
                        row.innerHTML = `
                            <td>${log.id}</td>
                            <td>${log.uid}</td>
                            <td>${log.name || 'Unknown'}</td>
                            <td>${log.status}</td>
                            <td>${log.timestamp}</td>
                        `;
                        tbody.appendChild(row);
                    });

                    if (logs.length > 0) {
                        offset += logs.length;
                    } else {
                        document.getElementById("loadMore").style.display = "none";
                    }
                });
        }

        setInterval(fetchUpdates, 1000);
        document.getElementById("loadMore").addEventListener("click", loadMoreLogs);
    </script>
</body>
</html>
