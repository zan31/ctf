<?php

declare(strict_types=1);

require_once '/var/www/src/db.php';
require_once '/var/www/src/auth.php';
require_once '/var/www/src/uuid.php';

require_login();

$message = '';
$error = '';
$assignedId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $isFinished = isset($_POST['is_finished']) ? 1 : 0;

    if ($title === '' || $content === '') {
        $error = 'Title and content are required.';
    } else {
        $namespace = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
        $nameSeed = implode('|', [
            (string) $_SESSION['user_id'],
            $title,
            (string) microtime(true),
            bin2hex(random_bytes(8)),
        ]);
        $assignedId = uuid5($namespace, $nameSeed);

        $sql = 'INSERT INTO news (id, user_id, title, content, is_finished) VALUES (:id, :user_id, :title, :content, :is_finished)';
        $stmt = get_db()->prepare($sql);
        $stmt->execute([
            ':id' => $assignedId,
            ':user_id' => (string) $_SESSION['user_id'],
            ':title' => $title,
            ':content' => $content,
            ':is_finished' => $isFinished,
        ]);

        $message = 'News uploaded. Your news UUID is: ' . $assignedId;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload News</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="/news.php">News</a>
        <a href="/upload_news.php">Upload News</a>
        <a href="/logout.php">Logout</a>
    </div>

    <h1>Upload News</h1>

    <?php if ($message !== ''): ?>
        <p class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Title</label>
        <input type="text" name="title" required>

        <label>Content</label>
        <textarea name="content" rows="8" required></textarea>

        <label><input type="checkbox" name="is_finished"> Mark as finished</label>

        <button type="submit">Save</button>
    </form>
</div>
</body>
</html>
