<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/auth.php';

require_login();

$currentUserId = (string) $_SESSION['user_id'];
$requestedUserId = isset($_GET['id']) ? trim((string) $_GET['id']) : $currentUserId;
$forbidden = false;
$user = false;

if ($requestedUserId !== $currentUserId && !current_is_admin()) {
    http_response_code(403);
    $forbidden = true;
} else {
    $sql = 'SELECT id, username, full_name, email, personal_notes, is_admin FROM users WHERE id = :id';
    $stmt = get_db()->prepare($sql);
    $stmt->execute([':id' => $requestedUserId]);
    $user = $stmt->fetch();
}
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
        <a href="/user.php?id=<?= urlencode($currentUserId) ?>">My Profile</a>
        <form method="post" action="/logout.php" class="nav-inline-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="nav-link-button">Logout</button>
        </form>
    </div>

    <h1>User Profile</h1>

    <?php if ($forbidden): ?>
        <p class="error">Forbidden.</p>
    <?php elseif (!$user): ?>
        <p class="error">User not found.</p>
    <?php else: ?>
        <div class="card">
            <p><strong>UUID:</strong> <?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Username:</strong> <?= htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Full Name:</strong> <?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Personal Notes:</strong> <?= htmlspecialchars((string) $user['personal_notes'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Admin:</strong> <?= !empty($user['is_admin']) ? 'yes' : 'no' ?></p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
