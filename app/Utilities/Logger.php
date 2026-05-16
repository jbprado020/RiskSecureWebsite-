<?php

namespace App\Utilities;

declare(strict_types=1);

class Logger
{
    private static string $logDir;

    public static function init(string $dir): void
    {
        self::$logDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }
    }

    public static function info(string $message, array $meta = []): void
    {
        self::write('INFO', $message, $meta);
    }

    public static function warning(string $message, array $meta = []): void
    {
        self::write('WARN', $message, $meta);
    }

    public static function error(string $message, array $meta = []): void
    {
        self::write('ERROR', $message, $meta);
    }

    private static function write(string $level, string $message, array $meta = []): void
    {
        $ts = date('Y-m-d H:i:s');
        $metaStr = $meta ? ' ' . json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $line = "[$ts] [$level] $message$metaStr\n";
        $file = self::$logDir . 'app.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
