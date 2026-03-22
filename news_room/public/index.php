<?php

declare(strict_types=1);

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /news.php');
    exit;
}

header('Location: /login.php');
exit;
