<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/auth.php';

require_login();

$currentUserId = (string) $_SESSION['user_id'];

$sql = 'SELECT n.id, n.title, n.created_at, n.is_finished, n.user_id, u.id AS author_id, u.username
    FROM news n
    JOIN users u ON u.id = n.user_id
    WHERE n.is_finished = TRUE OR n.user_id = :current_user
    ORDER BY n.created_at DESC';
$stmt = get_db()->prepare($sql);
$stmt->execute([':current_user' => $currentUserId]);
$news = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Feed</title>
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

    <h1>News Feed</h1>
    <p><small>Finished news is public. Your own drafts are also visible to you here.</small></p>

    <?php foreach ($news as $item): ?>
        <?php
        $authorId = (string) $item['author_id'];
        $canViewAuthorProfile = current_is_admin() || $authorId === $currentUserId;
        ?>
        <div class="card">
            <h2><a href="/view_news.php?id=<?= urlencode((string) $item['id']) ?>"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?></a></h2>
            <p>
                Author:
                <?php if ($canViewAuthorProfile): ?>
                    <a href="/user.php?id=<?= urlencode($authorId) ?>"><?= htmlspecialchars((string) $item['username'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <?= htmlspecialchars((string) $item['username'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </p>
            <small>Status: <?= $item['is_finished'] ? 'finished' : 'draft' ?></small><br>
            <small>Created: <?= htmlspecialchars((string) $item['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
