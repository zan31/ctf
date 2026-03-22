<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user_id(): ?string
{
    return isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null;
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}
