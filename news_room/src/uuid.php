<?php

declare(strict_types=1);

function uuid5(string $namespace, string $name): string
{
    $nhex = str_replace(['-', '{', '}'], '', $namespace);

    if (strlen($nhex) !== 32) {
        throw new InvalidArgumentException('Invalid namespace UUID.');
    }

    $nstr = '';
    for ($i = 0; $i < 32; $i += 2) {
        $nstr .= chr((int) hexdec(substr($nhex, $i, 2)));
    }

    $hash = sha1($nstr . $name);

    return sprintf(
        '%08s-%04s-%04x-%04x-%12s',
        substr($hash, 0, 8),
        substr($hash, 8, 4),
        (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
        (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
        substr($hash, 20, 12)
    );
}
