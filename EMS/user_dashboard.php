<?php
require_once('config/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM users";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        /* Your red and white theme styling here */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }

        header {
            background-color: #c82333;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        nav {
            background-color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        nav a {
            text-decoration: none;
            color: #c82333;
            font-weight: bold;
        }

        nav a:hover {
            color: #a71d2a;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto 50px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        form input, form button {
            padding: 10px;
            border-radius: 5px;
        }

        form input {
            border: 1px solid #ccc;
        }

        form button {
            grid-column: span 2;
            background-color: #c82333;
            color: white;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background-color: #a71d2a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        th {
            background-color: #c82333;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .status-box, .log-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3f3;
            border: 1px solid #c82333;
            border-radius: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 150px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            text-align: center;
        }

        .modal-content {
            background-color: #c82333;
            margin: auto;
            padding: 20px;
            border: 1px solid #fff;
            width: 40%;
            border-radius: 10px;
            color: white;
            font-size: 24px;
        }
    </style>
</head>
<body>

<header>
    <h1>User Management Dashboard</h1>
</header>

<nav>
    <a href="logs.php">View Access Logs</a>
    <a href="dashboard.php">Back to Dashboard</a>
    <a href="index.php">Home</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <div class="status-box">
        Electromagnetic Lock is: <span id="lockStatus" style="color: red;">Loading...</span>
    </div>

    <div class="log-box">
        <p><strong>Last Scan:</strong> <span id="scanStatus">Loading...</span></p>
        <p><em id="scanTimestamp"></em></p>
    </div>

    <h2>Add New User</h2>
    <form id="addUserForm">
        <input type="text" name="name" id="name" placeholder="Name" required>
        <input type="text" name="username" id="username" placeholder="Username" required>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <input type="time" name="schedule_start" id="schedule_start" required>
        <input type="time" name="schedule_end" id="schedule_end" required>
        <button type="submit" id="addUserBtn">Add User</button>

    </form>

    <div class="modal" id="waitingModal">
        <div class="modal-content">
            Waiting for card scan...
        </div>
    </div>

    <h2>Current Users</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>UID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Schedule Start</th>
                <th>Schedule End</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                
                    <form method="POST" action="api/users.php">
                        <td><?php echo $user['id']; ?></td>
                        <td><input type="text" name="uid" value="<?php echo $user['uid']; ?>" required></td>
                        <td><input type="text" name="name" value="<?php echo $user['name']; ?>" required></td>
                        <td><input type="text" name="username" value="<?php echo $user['username']; ?>" required></td>
                        <td><input type="time" name="schedule_start" value="<?php echo $user['schedule_start']; ?>" required></td>
                        <td><input type="time" name="schedule_end" value="<?php echo $user['schedule_end']; ?>" required></td>
                        <td>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            <button type="submit">Update</button>
                    </form>
                    <form method="POST" action="api/users.php" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <button type="submit">Delete</button>
                    </form>
                        </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function updateStatus() {
    fetch("api/fetch_updates.php")
        .then(response => response.json())
        .then(data => {
            const lockStatusEl = document.getElementById("lockStatus");
            const scanStatusEl = document.getElementById("scanStatus");
            const scanTimestampEl = document.getElementById("scanTimestamp");

            lockStatusEl.textContent = data.lock_status || "Unavailable";
            lockStatusEl.style.color = (data.lock_status === "Unlocked") ? "green" : "red";

            if (data.last_log) {
                scanStatusEl.textContent = data.last_log.status || "No recent scans";
                scanTimestampEl.textContent = data.last_log.timestamp || "";
            } else {
                scanStatusEl.textContent = "No recent scans";
                scanTimestampEl.textContent = "";
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            document.getElementById("lockStatus").textContent = "Error";
            document.getElementById("scanStatus").textContent = "Error";
            document.getElementById("scanTimestamp").textContent = "";
        });
}

window.onload = () => {
    updateStatus();
    setInterval(updateStatus, 1000);
};

document.getElementById("addUserForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const modal = document.getElementById("waitingModal");
    modal.style.display = "block";

    const startTime = new Date().toISOString();
    let attempts = 0;
    const maxAttempts = 20;

    const interval = setInterval(() => {
        fetch("api/get_latest_uid.php?after=" + encodeURIComponent(startTime))
            .then(res => res.json())
            .then(data => {
                if (data.uid && data.uid !== "null") {
                    clearInterval(interval);
                    modal.style.display = "none";

                    const formData = new FormData();
                    formData.append("action", "add");
                    formData.append("uid", data.uid);
                    formData.append("name", document.getElementById("name").value);
                    formData.append("username", document.getElementById("username").value);
                    formData.append("password", document.getElementById("password").value);
                    formData.append("schedule_start", document.getElementById("schedule_start").value);
                    formData.append("schedule_end", document.getElementById("schedule_end").value);

                    fetch("api/users.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        alert(res.message);
                        if (res.success) location.reload();
                    })
                    .catch(() => alert("Failed to add user."));
                }
            });

            document.getElementById('addUserBtn').addEventListener('click', () => {
  fetch('api/users.php')
    .then(res => res.text())
    .then(msg => alert(msg));
});

        attempts++;
        if (attempts >= maxAttempts) {
            clearInterval(interval);
            modal.style.display = "none";
            alert("No card scanned. Try again.");
        }
    }, 3000);
});

function loadUsers() {
  fetch('api/get_users.php')
    .then(res => res.text())
    .then(data => {
      document.getElementById('usersTable').innerHTML = data;
    });
}

// Load initially and refresh every 5 seconds
loadUsers();
setInterval(loadUsers, 1000);
</script>

</body>
</html>
