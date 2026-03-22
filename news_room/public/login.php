<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT id, username FROM users WHERE username = '$username' AND password = '$password'";
    $stmt = get_db()->query($sql);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = (string) $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: /news.php');
        exit;
    }

    $error = 'Invalid credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - News Room</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <h1>News Room Login</h1>
    <p><small>CTF challenge app. Login is intentionally weak.</small></p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p>No account? <a href="/register.php">Register here</a></p>
</div>
</body>
</html>
