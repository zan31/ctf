<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/auth.php';

require_login();

$userId = isset($_GET['id']) ? trim((string) $_GET['id']) : (string) $_SESSION['user_id'];

$sql = 'SELECT id, username, full_name, email, personal_notes, is_admin FROM users WHERE id = :id';
$stmt = get_db()->prepare($sql);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="/news.php">News</a>
        <a href="/upload_news.php">Upload News</a>
        <a href="/logout.php">Logout</a>
    </div>

    <h1>User Profile</h1>

    <?php if (!$user): ?>
        <p class="error">User not found.</p>
    <?php else: ?>
        <div class="card">
            <p><strong>UUID:</strong> <?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Username:</strong> <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Personal Notes:</strong> <?= htmlspecialchars($user['personal_notes'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Admin:</strong> <?= $user['is_admin'] ? 'yes' : 'no' ?></p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
