<?php

declare(strict_types=1);

namespace App;

final class RuntimeGuard
{
    public static function assertSupported(): void
    {
        if (!self::isAffectedVersion(PHP_VERSION_ID)) {
            return;
        }

        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "This application requires a patched PHP runtime. "
            . "Upgrade PHP before serving requests (CVE-2026-109618).";
        exit(1);
    }

    private static function isAffectedVersion(int $versionId): bool
    {
        return ($versionId >= 80200 && $versionId < 80235)
            || ($versionId >= 80300 && $versionId < 80336)
            || ($versionId >= 80400 && $versionId < 80427)
            || ($versionId >= 80500 && $versionId < 80512);
    }
}
