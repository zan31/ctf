<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token. Please refresh and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = get_db()->prepare('SELECT id, username, password, is_admin FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            $storedPassword = (string) $user['password'];
            $hashInfo = password_get_info($storedPassword);
            $usesPasswordHash = ($hashInfo['algo'] ?? null) !== 0;

            $isAuthenticated = $usesPasswordHash
                ? password_verify($password, $storedPassword)
                : hash_equals($storedPassword, $password);

            if ($isAuthenticated) {
                $newPasswordHash = null;
                if (!$usesPasswordHash || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                }

                if (is_string($newPasswordHash) && $newPasswordHash !== '') {
                    $updateStmt = get_db()->prepare('UPDATE users SET password = :password WHERE id = :id');
                    $updateStmt->execute([
                        ':password' => $newPasswordHash,
                        ':id' => (string) $user['id'],
                    ]);
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = (string) $user['id'];
                $_SESSION['username'] = (string) $user['username'];
                $_SESSION['is_admin'] = (bool) $user['is_admin'];

                header('Location: /news.php');
                exit;
            }
        }

        $error = 'Invalid credentials.';
    }
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

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

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
