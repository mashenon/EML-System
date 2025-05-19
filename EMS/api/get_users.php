<?php
require_once('../config/db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$query = "SELECT * FROM users";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user): ?>
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