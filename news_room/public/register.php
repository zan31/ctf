<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/uuid.php';

session_start();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $personalNotes = trim($_POST['personal_notes'] ?? '');

    if ($username === '' || $password === '' || $fullName === '' || $email === '' || $personalNotes === '') {
        $error = 'All fields are required.';
    } else {
        $namespace = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
        $userIdSeed = implode('|', [$username, $email, (string) microtime(true), bin2hex(random_bytes(8))]);
        $userId = uuid5($namespace, $userIdSeed);

        $sql = 'INSERT INTO users (id, username, password, full_name, email, personal_notes) VALUES (:id, :username, :password, :full_name, :email, :personal_notes)';
        $stmt = get_db()->prepare($sql);

        try {
            $stmt->execute([
                ':id' => $userId,
                ':username' => $username,
                ':password' => $password,
                ':full_name' => $fullName,
                ':email' => $email,
                ':personal_notes' => $personalNotes,
            ]);
            $message = 'Registration successful. You can log in now.';
        } catch (Throwable $e) {
            $error = 'Username already exists or invalid input.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - News Room</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <h1>Register</h1>

    <?php if ($message !== ''): ?>
        <p class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Personal Notes</label>
        <textarea name="personal_notes" rows="4" required></textarea>

        <button type="submit">Create Account</button>
    </form>

    <p><a href="/login.php">Back to login</a></p>
</div>
</body>
</html>
