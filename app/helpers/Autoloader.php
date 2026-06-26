<?php

declare(strict_types=1);

namespace App\Helpers;

final class Autoloader
{
    /**
     * @var array<string, string>
     */
    private const DIRECTORY_MAP = [
        'Controllers' => 'controllers',
        'Helpers' => 'helpers',
        'Migrations' => 'migrations',
        'Models' => 'models',
        'Repositories' => 'repositories',
        'Simulation' => 'simulation',
        'Services' => 'services',
        'Validators' => 'validators',
    ];

    public static function register(): void
    {
        spl_autoload_register(static function (string $className): void {
            $prefix = 'App\\';
            $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

            if (strncmp($prefix, $className, strlen($prefix)) !== 0) {
                return;
            }

            $relativeClass = substr($className, strlen($prefix));
            $parts = explode('\\', $relativeClass);
            if ($parts === []) {
                return;
            }

            if (isset(self::DIRECTORY_MAP[$parts[0]])) {
                $parts[0] = self::DIRECTORY_MAP[$parts[0]];
            }

            $file = $baseDir . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

            if (is_file($file)) {
                require $file;
            }
        });
    }
}
