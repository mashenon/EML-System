<?php
require_once '../config/db_connect.php';

$stmt = $conn->query("SELECT id, name, AES_DECRYPT(uid, UNHEX(SHA2('your256bitsecretkey',512))) AS decrypted_uid FROM users ORDER BY id DESC");
echo "<table><tr><th>ID</th><th>Name</th><th>UID</th></tr>";
while ($row = $stmt->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['decrypted_uid']}</td></tr>";
}
echo "</table>";
?>
