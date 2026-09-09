<?php

declare(strict_types=1);

use App\Support\ReleaseFreezePolicy;

require dirname(__DIR__) . '/vendor/autoload.php';

$baseSha = trim((string) getenv('RC_BASE_SHA'));
$headSha = trim((string) getenv('RC_HEAD_SHA'));
$labelsJson = (string) getenv('RC_LABELS_JSON');
$prTitle = (string) getenv('RC_PR_TITLE');
$configPath = (string) getenv('RC_FREEZE_CONFIG_PATH');

if ($baseSha === '' || $headSha === '') {
    fwrite(STDERR, "RC_BASE_SHA and RC_HEAD_SHA are required.\n");
    exit(2);
}

$config = [
    'active' => false,
];

if ($configPath !== '' && is_file($configPath)) {
    $decoded = json_decode(
        file_get_contents($configPath),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (is_array($decoded)) {
        $config = $decoded;
    }
}

$labels = [];

if ($labelsJson !== '') {
    $decodedLabels = json_decode(
        $labelsJson,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (is_array($decodedLabels)) {
        $labels = array_map(
            static fn ($label): string => is_array($label)
                ? (string) ($label['name'] ?? '')
                : (string) $label,
            $decodedLabels
        );
    }
}

$command = sprintf(
    'git diff --name-status %s...%s',
    escapeshellarg($baseSha),
    escapeshellarg($headSha)
);

exec($command, $lines, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Unable to inspect changed files for RC freeze guard.\n");
    exit(2);
}

$changes = [];

foreach ($lines as $line) {
    $parts = preg_split('/\t+/', trim($line));

    if (! $parts || count($parts) < 2) {
        continue;
    }

    $status = (string) $parts[0];
    $normalizedStatus = substr($status, 0, 1);
    $path = (string) end($parts);
    $oldPath = in_array($normalizedStatus, ['R', 'C'], true)
        && count($parts) >= 3
            ? (string) $parts[1]
            : null;

    $changes[] = [
        'status' => $normalizedStatus,
        'path' => $path,
        'old_path' => $oldPath,
    ];
}

$result = (new ReleaseFreezePolicy())->evaluate(
    $config,
    $changes,
    $labels,
    $prTitle
);

echo json_encode(
    array_merge($result, [
        'base_sha' => $baseSha,
        'head_sha' => $headSha,
        'change_count' => count($changes),
        'labels' => $labels,
        'pr_title' => $prTitle,
        'base_freeze_active' => (bool) ($config['active'] ?? false),
    ]),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
) . PHP_EOL;

exit($result['allowed'] ? 0 : 1);
