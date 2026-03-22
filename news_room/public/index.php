<?php

declare(strict_types=1);

require_once '/var/www/src/auth.php';

if (current_user_id() !== null) {
    header('Location: /news.php');
    exit;
}

header('Location: /login.php');
exit;
