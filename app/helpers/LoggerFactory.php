<?php

declare(strict_types=1);

namespace App\Helpers;

final class LoggerFactory
{
    public static function create(): FileLogger
    {
        $configFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $config = is_file($configFile) ? require $configFile : [];
        $logDirectory = $config['logging']['path'] ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';

        return new FileLogger((string) $logDirectory);
    }
}
