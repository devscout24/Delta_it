<?php

/**
 * Generate a Postman collection from current Laravel API routes.
 *
 * Usage:
 *   php scripts/generate_postman_collection.php [output_file]
 */

function formDataBody(array $fields): array
{
    $formdata = [];
    foreach ($fields as $key => $value) {
        $formdata[] = [
            'key' => $key,
            'value' => (string) $value,
            'type' => 'text',
        ];
    }

    return [
        'mode' => 'formdata',
        'formdata' => $formdata,
    ];
}

function rawJsonBody(array $payload): array
{
    return [
        'mode' => 'raw',
        'raw' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        'options' => [
            'raw' => [
                'language' => 'json',
            ],
        ],
    ];
}

function requestSample(string $uri, string $method): ?array
{
    if ($method === 'GET') {
        return null;
    }

    if ($uri === 'user-login') {
        return ['body' => formDataBody([
            'email' => 'admin@example.com',
            'password' => '12345678',
        ])];
    }

    if ($uri === 'send-otp') {
        return ['body' => formDataBody(['email' => 'user@example.com'])];
    }

    if ($uri === 'verify-otp') {
        return ['body' => formDataBody([
            'email' => 'user@example.com',
            'otp' => '1234',
        ])];
    }

    if ($uri === 'reset-password') {
        return ['body' => formDataBody([
            'email' => 'user@example.com',
            'reset_token' => 'token_here',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])];
    }

    if ($uri === 'meeting/request') {
        return ['body' => rawJsonBody([
            'company_id' => 1,
            'meeting_name' => 'Weekly Meeting',
            'date' => '2026-04-24',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'requested',
        ])];
    }

    if (preg_match('#^meeting/\{[^}]+\}/(accept|reject|cancel|remove-request)$#', $uri)) {
        return ['body' => rawJsonBody([
            'request_type' => 'meeting_model_request',
        ])];
    }

    if ($uri === 'meeting-events/request/create') {
        return ['body' => formDataBody([
            'meeting_event_id' => 1,
            'date' => '2026-04-24',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'invitees' => 2,
        ])];
    }

    if ($uri === 'meeting-bookings/request/create') {
        return ['body' => formDataBody([
            'meeting_booking_id' => 1,
            'date' => '2026-04-24',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'invitees' => 2,
        ])];
    }

    if (preg_match('#^meeting-events/\{[^}]+\}/(accept|reject|cancel|remove-request)$#', $uri)) {
        return ['body' => rawJsonBody([
            'request_type' => 'event_config',
        ])];
    }

    if (preg_match('#^meeting-bookings/\{[^}]+\}/(accept|reject|cancel|remove-request)$#', $uri)) {
        return ['body' => rawJsonBody([
            'request_type' => 'booking_config',
        ])];
    }

    if (preg_match('#^meeting/\{[^}]+\}/remove-request$#', $uri)) {
        return ['body' => rawJsonBody([
            'note' => 'No body required, this API moves request to removed status',
        ])];
    }

    return ['body' => rawJsonBody([
        'note' => 'Sample body not defined yet. Check controller validation rules for exact required fields.',
    ])];
}

function getSampleQuery(string $uri, string $method): ?array
{
    if ($method !== 'GET') {
        return null;
    }

    if ($uri === 'calendar/overview') {
        return [
            ['key' => 'view', 'value' => 'month', 'description' => 'day,week,month'],
            ['key' => 'date', 'value' => '2026-04-24', 'description' => 'base date'],
            ['key' => 'start_date', 'value' => '2026-04-01', 'disabled' => true],
            ['key' => 'end_date', 'value' => '2026-04-30', 'disabled' => true],
        ];
    }

    if ($uri === 'get-meeting') {
        return [
            ['key' => 'date', 'value' => '24-04-2026', 'description' => 'required d-m-Y'],
            ['key' => 'mode', 'value' => 'week', 'description' => 'day,week,month'],
            ['key' => 'month', 'value' => '2026-04', 'disabled' => true],
        ];
    }

    return null;
}

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

chdir($root);

$outputFile = $argv[1] ?? 'postman.json';

$routeJson = shell_exec('php artisan route:list --path=api --json');
if ($routeJson === null) {
    fwrite(STDERR, "Failed to execute: php artisan route:list --path=api --json\n");
    exit(1);
}

$routes = json_decode($routeJson, true);
if (!is_array($routes)) {
    fwrite(STDERR, "Invalid JSON output from route:list.\n");
    exit(1);
}

$noAuthUris = [
    'user-login',
    'send-otp',
    'verify-otp',
    'reset-password',
    'create-account',
];

$groups = [];

foreach ($routes as $route) {
    $uri = $route['uri'] ?? '';
    $action = $route['action'] ?? '';
    $methodText = $route['method'] ?? 'GET';

    if ($uri === '' || str_contains($action, 'Closure')) {
        continue;
    }

    if (str_starts_with($uri, 'api/')) {
        $uri = substr($uri, 4);
    }

    if ($uri === '' || $uri === '/') {
        continue;
    }

    $methods = array_values(array_filter(
        explode('|', $methodText),
        fn($m) => !in_array($m, ['HEAD', 'OPTIONS'], true)
    ));

    if (empty($methods)) {
        continue;
    }

    $controllerGroup = 'Misc';
    if (preg_match('/Controllers\\\\([^@]+)@/', $action, $matches)) {
        $controller = $matches[1];
        $controller = str_replace('\\\\', '/', $controller);
        $controller = basename($controller);
        $controllerGroup = preg_replace('/Controller$/', '', $controller) ?: 'Misc';
    }

    foreach ($methods as $method) {
        $segments = array_map(function ($segment) {
            if (preg_match('/^\{(.+)\}$/', $segment, $m)) {
                return '{{' . $m[1] . '}}';
            }
            return $segment;
        }, explode('/', $uri));

        $rawUrl = '{{base_url}}/' . implode('/', $segments);

        $item = [
            'name' => sprintf('%s %s', strtoupper($method), $uri),
            'request' => [
                'auth' => in_array($uri, $noAuthUris, true)
                    ? ['type' => 'noauth']
                    : [
                        'type' => 'bearer',
                        'bearer' => [[
                            'key' => 'token',
                            'value' => '{{token}}',
                            'type' => 'string',
                        ]],
                    ],
                'method' => strtoupper($method),
                'header' => [],
                'url' => [
                    'raw' => $rawUrl,
                    'host' => ['{{base_url}}'],
                    'path' => $segments,
                ],
            ],
            'response' => [],
        ];

        $query = getSampleQuery($uri, strtoupper($method));
        if ($query !== null) {
            $item['request']['url']['query'] = $query;
        }

        $sample = requestSample($uri, strtoupper($method));
        if ($sample !== null && isset($sample['body'])) {
            $item['request']['body'] = $sample['body'];
        }

        $groups[$controllerGroup][] = $item;
    }
}

ksort($groups);
foreach ($groups as &$items) {
    usort($items, fn($a, $b) => strcmp($a['name'], $b['name']));
}
unset($items);

$collectionItems = [];
foreach ($groups as $groupName => $items) {
    $collectionItems[] = [
        'name' => $groupName,
        'item' => $items,
    ];
}

$collection = [
    'info' => [
        '_postman_id' => '8f790540-7ec0-4039-8671-6ec4aa5c351d',
        'name' => 'Delta_IT API Updated',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'item' => $collectionItems,
    'event' => [
        [
            'listen' => 'prerequest',
            'script' => [
                'type' => 'text/javascript',
                'exec' => [''],
            ],
        ],
        [
            'listen' => 'test',
            'script' => [
                'type' => 'text/javascript',
                'exec' => [''],
            ],
        ],
    ],
    'variable' => [
        ['key' => 'base_url', 'value' => ''],
        ['key' => 'live_url', 'value' => ''],
        ['key' => 'token', 'value' => ''],
    ],
];

$json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Failed to encode Postman collection JSON.\n");
    exit(1);
}

if (file_put_contents($outputFile, $json) === false) {
    fwrite(STDERR, "Failed to write output file: {$outputFile}\n");
    exit(1);
}

fwrite(STDOUT, "Generated Postman collection: {$outputFile}\n");
fwrite(STDOUT, "Groups: " . count($collectionItems) . "\n");
