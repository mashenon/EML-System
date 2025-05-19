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

        .action-btn {
            padding: 8px 12px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .update-btn {
            background-color: #28a745;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .action-btn:hover {
            opacity: 0.8;
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
    <!-- Your existing status boxes and add user form -->
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
        <tbody id="usersTableBody">
            <?php foreach ($users as $user): ?>
                <tr data-user-id="<?php echo $user['id']; ?>">
                    <td><?php echo $user['id']; ?></td>
                    <td><input type="text" class="uid-input" value="<?php echo htmlspecialchars($user['uid']); ?>" required></td>
                    <td><input type="text" class="name-input" value="<?php echo htmlspecialchars($user['name']); ?>" required></td>
                    <td><input type="text" class="username-input" value="<?php echo htmlspecialchars($user['username']); ?>" required></td>
                    <td><input type="time" class="schedule-start-input" value="<?php echo htmlspecialchars($user['schedule_start']); ?>" required></td>
                    <td><input type="time" class="schedule-end-input" value="<?php echo htmlspecialchars($user['schedule_end']); ?>" required></td>
                    <td>
                        <button class="action-btn update-btn" data-user-id="<?php echo $user['id']; ?>">Update</button>
                        <button class="action-btn delete-btn" data-user-id="<?php echo $user['id']; ?>">Delete</button>
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
            document.getElementById("lockStatus").textContent = data.lock_status || "Unavailable";
            document.getElementById("lockStatus").style.color = (data.lock_status === "Unlocked") ? "green" : "red";
            
            if (data.last_log) {
                document.getElementById("scanStatus").textContent = data.last_log.status || "No recent scans";
                document.getElementById("scanTimestamp").textContent = data.last_log.timestamp || "";
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
        });
}

// Function to load users
function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById("usersTableBody").innerHTML = html;
            attachButtonListeners(); // Reattach event listeners after table update
        })
        .catch(error => console.error('Error loading users:', error));
}

// Function to handle update
function handleUpdate(userId) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    const formData = new FormData();
    
    formData.append('action', 'update');
    formData.append('id', userId);
    formData.append('uid', row.querySelector('.uid-input').value);
    formData.append('name', row.querySelector('.name-input').value);
    formData.append('username', row.querySelector('.username-input').value);
    formData.append('schedule_start', row.querySelector('.schedule-start-input').value);
    formData.append('schedule_end', row.querySelector('.schedule-end-input').value);

    fetch('api/users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User updated successfully');
            loadUsers(); // Refresh the table
        } else {
            alert(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Update error');
    });
}

// Function to handle delete
function handleDelete(userId) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', userId);

    fetch('api/users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User deleted successfully');
            loadUsers(); // Refresh the table
        } else {
            alert(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Delete error');
    });
}

// Function to attach button listeners
function attachButtonListeners() {
    // Update buttons
    document.querySelectorAll('.update-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            handleUpdate(btn.getAttribute('data-user-id'));
        });
    });

    // Delete buttons
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            handleDelete(btn.getAttribute('data-user-id'));
        });
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    updateStatus();
    loadUsers();
    attachButtonListeners();

    // Set intervals
    setInterval(updateStatus, 1000);
    setInterval(loadUsers, 5000);

    // Add user form
    document.getElementById("addUserForm").addEventListener("submit", async function(e) {
        e.preventDefault();
        const modal = document.getElementById("waitingModal");
        modal.style.display = "block";
        
        try {
            // Clear any previous UID
            await fetch("api/clear_temp_uid.php");
            
            // Wait for scan
            const startTime = new Date().toISOString();
            let uid = null;
            
            for (let attempts = 0; attempts < 20 && !uid; attempts++) {
                const response = await fetch(`api/get_new_uid.php?after=${encodeURIComponent(startTime)}`);
                const data = await response.json();
                if (data.uid && data.uid !== "null") {
                    uid = data.uid;
                    break;
                }
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            if (!uid) throw new Error("No card scanned within 10 seconds");
            
            // Submit user data
            const formData = new FormData();
            formData.append("action", "add");
            formData.append("uid", uid);
            formData.append("name", document.getElementById("name").value);
            formData.append("username", document.getElementById("username").value);
            formData.append("password", document.getElementById("password").value);
            formData.append("schedule_start", document.getElementById("schedule_start").value);
            formData.append("schedule_end", document.getElementById("schedule_end").value);
            
            const response = await fetch("api/users.php", {
                method: "POST",
                body: formData
            });
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            
            alert("User added successfully!");
            document.getElementById("addUserForm").reset();
            loadUsers(); // Refresh the table
        } catch (error) {
            alert(error.message);
            console.error("Error:", error);
        } finally {
            modal.style.display = "none";
        }
    });
});
</script>

</body>
</html>