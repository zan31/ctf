<?php

declare(strict_types=1);

function require_env(string $name): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException("Missing required environment variable: {$name}");
    }

    return $value;
}

function get_db(): PDO
{
    static $pdo = null;
    static $schemaChecked = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = require_env('DB_HOST');
    $port = require_env('DB_PORT');
    $name = require_env('DB_NAME');
    $user = require_env('DB_USER');
    $password = require_env('DB_PASSWORD');

    $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if (!$schemaChecked) {
        ensure_news_uuid_schema($pdo);
        $schemaChecked = true;
    }

    return $pdo;
}

function ensure_news_uuid_schema(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "SELECT data_type, udt_name
         FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = 'news' AND column_name = 'id'"
    );
    $stmt->execute();
    $column = $stmt->fetch();

    if (!$column) {
        return;
    }

    $isIntegerId = ($column['data_type'] ?? '') === 'integer' || ($column['udt_name'] ?? '') === 'int4';
    if (!$isIntegerId) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('ALTER TABLE news ADD COLUMN id_uuid UUID');
        $pdo->exec(
            "UPDATE news
             SET id_uuid = (
                 substr(md5('news-room-v5:' || id::text), 1, 8) || '-' ||
                 substr(md5('news-room-v5:' || id::text), 9, 4) || '-' ||
                 '5' || substr(md5('news-room-v5:' || id::text), 14, 3) || '-' ||
                 'a' || substr(md5('news-room-v5:' || id::text), 18, 3) || '-' ||
                 substr(md5('news-room-v5:' || id::text), 21, 12)
             )::uuid"
        );
        $pdo->exec('ALTER TABLE news ALTER COLUMN id_uuid SET NOT NULL');
        $pdo->exec('ALTER TABLE news DROP CONSTRAINT news_pkey');
        $pdo->exec('ALTER TABLE news DROP COLUMN id');
        $pdo->exec('ALTER TABLE news RENAME COLUMN id_uuid TO id');
        $pdo->exec('ALTER TABLE news ADD PRIMARY KEY (id)');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
