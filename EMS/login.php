<?php
require_once('config/db_connect.php');
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $password === $user['password']) { // plaintext comparison
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        header('Location: logs.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - EMS</title>
    <link rel="stylesheet" href="/EMS/assets/style.css">
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #fff;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-container {
    text-align: center;
}

.login-title {
    color: #b22222; /* Firebrick red */
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 30px;
}

label {
    display: block;
    margin-bottom: 5px;
    color: #b22222;
    font-size: 18px;
    font-weight: bold;
    text-align: left;
    max-width: 300px;
    margin: 10px auto 5px;
}

input[type="text"],
input[type="password"] {
    width: 300px;
    padding: 10px;
    border: 2px solid #999;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 16px;
}

form {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.login-btn {
    padding: 10px 20px;
    background-color: #b22222;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    width: 320px;
    margin-top: 5px; /* Small gap under password field */
}

.login-btn:hover {
    background-color: #8b1a1a;
}


.back-link {
    margin-top: 20px;
}

.back-link a {
    color: purple;
    text-decoration: none;
    font-size: 16px;
}

.back-link a:hover {
    text-decoration: underline;
}

.error {
    color: red;
    font-weight: bold;
    margin-bottom: 15px;
}
input[type="text"],
input[type="password"] {
    width: 300px;
    padding: 10px;
    border: 2px solid #999;
    border-radius: 8px;
    margin-bottom: 8px; /* Tighter spacing */
    font-size: 16px;
}

    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Login to EMLS</h1>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="login-btn">Login</button>
        </form>

        <p class="back-link"><a href="index.php">← Back to Home</a></p>
    </div>
</body>
</html>

