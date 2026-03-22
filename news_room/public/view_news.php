<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/auth.php';

require_login();

$newsId = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
$currentUserId = (string) $_SESSION['user_id'];

$item = false;
if ($newsId !== '') {
    $sql = 'SELECT n.id, n.title, n.content, n.is_finished, n.created_at, u.username
        FROM news n
        JOIN users u ON u.id = n.user_id
        WHERE n.id = :id AND (n.is_finished = TRUE OR n.user_id = :current_user OR :is_admin = 1)';
    $stmt = get_db()->prepare($sql);
    $stmt->execute([
        ':id' => $newsId,
        ':current_user' => $currentUserId,
        ':is_admin' => current_is_admin() ? 1 : 0,
    ]);
    $item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View News</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="/news.php">News</a>
        <a href="/upload_news.php">Upload News</a>
        <form method="post" action="/logout.php" class="nav-inline-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="nav-link-button">Logout</button>
        </form>
    </div>

    <?php if (!$item): ?>
        <p class="error">News not found.</p>
    <?php else: ?>
        <h1><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><small>Author: <?= htmlspecialchars((string) $item['username'], ENT_QUOTES, 'UTF-8') ?> | Created: <?= htmlspecialchars((string) $item['created_at'], ENT_QUOTES, 'UTF-8') ?></small></p>
        <p><small>Finished status: <?= $item['is_finished'] ? 'finished' : 'draft' ?></small></p>
        <div class="card">
            <p><?= nl2br(htmlspecialchars((string) $item['content'], ENT_QUOTES, 'UTF-8')) ?></p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
