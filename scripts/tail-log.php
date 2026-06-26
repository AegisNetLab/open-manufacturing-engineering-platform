<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['lines::', 'date::', 'level::']);
$lines = max(1, (int) ($options['lines'] ?? 50));
$date = (string) ($options['date'] ?? gmdate('Y-m-d'));
$level = isset($options['level']) ? strtoupper((string) $options['level']) : null;

$logFile = dirname(__DIR__) . '/storage/logs/app-' . $date . '.log';

if (!is_file($logFile)) {
    fwrite(STDERR, "No log file found for {$date}.\n");
    exit(1);
}

$entries = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
if ($level !== null) {
    $entries = array_values(array_filter($entries, static function (string $line) use ($level): bool {
        $decoded = json_decode($line, true);
        return is_array($decoded) && strtoupper((string) ($decoded['level'] ?? '')) === $level;
    }));
}

foreach (array_slice($entries, -$lines) as $entry) {
    $decoded = json_decode($entry, true);
    if (!is_array($decoded)) {
        echo $entry . PHP_EOL;
        continue;
    }

    printf(
        "[%s] %-7s %s %s\n",
        $decoded['timestamp'] ?? '',
        $decoded['level'] ?? '',
        $decoded['request_id'] ?? '',
        $decoded['message'] ?? ''
    );
}
