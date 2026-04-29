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
            'request_type' => 'event_request',
        ])];
    }

    if (preg_match('#^meeting-bookings/\{[^}]+\}/(accept|reject|cancel|remove-request)$#', $uri)) {
        return ['body' => rawJsonBody([
            'request_type' => 'booking_request',
        ])];
    }

    if (preg_match('#^meeting/\{[^}]+\}/remove-request$#', $uri)) {
        return ['body' => rawJsonBody([
            'note' => 'No body required, this API moves request to removed status',
        ])];
    }

    // Web API - Companies
    if ($uri === 'web/companies') {
        return ['body' => rawJsonBody([
            'name' => 'Tech Startup Inc',
            'email' => 'contact@techstartup.com',
            'phone' => '+1-555-0123',
            'nif' => '123456789',
            'incubation_type' => 'Pre-Seed',
            'business_area' => 'Software Development',
            'manager_name' => 'John Doe',
            'description' => 'Innovative tech company',
        ])];
    }

    if (preg_match('#^web/companies/\{[^}]+\}$#', $uri)) {
        return ['body' => rawJsonBody([
            'name' => 'Tech Startup Inc',
            'email' => 'contact@techstartup.com',
            'phone' => '+1-555-0123',
            'nif' => '123456789',
            'incubation_type' => 'Seed',
            'business_area' => 'Software Development',
            'manager_name' => 'Jane Smith',
            'description' => 'Updated company description',
        ])];
    }

    // Web API - Collaborators
    if ($uri === 'web/collaborators') {
        return ['body' => rawJsonBody([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'phone' => '+1-555-0456',
            'expertise' => 'Business Development',
        ])];
    }

    if (preg_match('#^web/collaborators/\{[^}]+\}$#', $uri)) {
        return ['body' => rawJsonBody([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'phone' => '+1-555-0789',
            'expertise' => 'Strategic Advisory',
        ])];
    }

    // Web API - Contracts
    if ($uri === 'web/contracts/{company_id}') {
        return ['body' => rawJsonBody([
            'name' => 'Annual Service Agreement',
            'type' => 'Service',
            'start_date' => '2026-04-30',
            'end_date' => '2027-04-30',
            'renewal_date' => '2027-03-30',
            'status' => 'active',
        ])];
    }

    // Web API - Company Users
    if ($uri === 'web/company-users') {
        return ['body' => rawJsonBody([
            'name' => 'Bob Wilson',
            'email' => 'bob@company.com',
            'password' => 'SecurePass123!',
            'role' => 'company_user',
            'company_id' => 1,
            'phone' => '+1-555-0321',
            'job_title' => 'Project Manager',
        ])];
    }

    if (preg_match('#^web/company-users/\{[^}]+\}$#', $uri)) {
        return ['body' => rawJsonBody([
            'name' => 'Bob Wilson',
            'email' => 'bob@company.com',
            'phone' => '+1-555-0654',
            'job_title' => 'Senior Project Manager',
        ])];
    }

    // Web API - Company Notes
    if ($uri === 'web/company-notes') {
        return ['body' => rawJsonBody([
            'company_id' => 1,
            'note' => 'Follow up on quarterly review meeting scheduled for next month',
        ])];
    }

    // Web API - Rooms
    if ($uri === 'web/map/rooms/floors') {
        return ['body' => rawJsonBody([
            'note' => 'No body required for this endpoint',
        ])];
    }

    if ($uri === 'web/map/rooms') {
        return ['body' => rawJsonBody([
            'floor_id' => 2,
            'name' => 'Meeting Room A',
            'area' => 45.5,
            'polygon_points' => [0, 0, 10, 0, 10, 5, 0, 5],
        ])];
    }

    if (preg_match('#^web/map/rooms/assign-company$#', $uri)) {
        return ['body' => rawJsonBody([
            'room_id' => 1,
            'company_id' => 5,
        ])];
    }

    if (preg_match('#^web/map/rooms/remove-company$#', $uri)) {
        return ['body' => rawJsonBody([
            'room_id' => 1,
        ])];
    }

    if (preg_match('#^web/map/rooms/\{[^}]+\}/status$#', $uri)) {
        return ['body' => rawJsonBody([
            'status' => 'maintenance',
        ])];
    }

    // Web API - Meeting Events
    if ($uri === 'web/meeting-events') {
        return ['body' => rawJsonBody([
            'name' => 'Monthly Networking Event',
            'description' => 'Connect with other startups and mentors',
            'capacity' => 50,
        ])];
    }

    if (preg_match('#^web/meeting-events/\{[^}]+\}$#', $uri)) {
        return ['body' => rawJsonBody([
            'name' => 'Quarterly Investor Pitch',
            'description' => 'Present your startup to investors',
            'capacity' => 100,
        ])];
    }

    if (preg_match('#^web/meeting-events/\{[^}]+\}/schedule$#', $uri)) {
        return ['body' => rawJsonBody([
            'days' => [
                [
                    'day' => 'Monday',
                    'time_ranges' => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                ],
                [
                    'day' => 'Friday',
                    'time_ranges' => [
                        ['start_time' => '10:00', 'end_time' => '16:00'],
                    ],
                ],
            ],
        ])];
    }

    if (preg_match('#^web/meeting-events/requests/\{[^}]+\}/(approve|reject)$#', $uri)) {
        return ['body' => rawJsonBody([
            'status' => preg_match('#/approve$#', $uri) ? 'approved' : 'rejected',
        ])];
    }

    // Web API - User Management
    if ($uri === 'web/users') {
        return ['body' => rawJsonBody([
            'name' => 'Admin User',
            'email' => 'newadmin@example.com',
            'password' => 'SecurePass123!',
            'role' => 'admin',
            'phone' => '+1-555-0999',
            'job_title' => 'Administrator',
        ])];
    }

    if (preg_match('#^web/users/\{[^}]+\}$#', $uri)) {
        return ['body' => rawJsonBody([
            'name' => 'Admin User Updated',
            'email' => 'newadmin@example.com',
            'phone' => '+1-555-0888',
            'job_title' => 'Senior Administrator',
        ])];
    }

    // Mobile API - Company
    if ($uri === 'mobile/company/update') {
        return ['body' => rawJsonBody([
            'name' => 'Updated Company Name',
            'email' => 'newemail@company.com',
            'phone' => '+1-555-9999',
        ])];
    }

    // Mobile API - Collaborators
    if ($uri === 'mobile/collaborators/store') {
        return ['body' => rawJsonBody([
            'name' => 'New Collaborator',
            'email' => 'collab@example.com',
            'phone' => '+1-555-1234',
            'expertise' => 'Marketing',
        ])];
    }

    if (preg_match('#^mobile/collaborators/update/\{[^}]+\}$#', $uri)) {
        return ['body' => rawJsonBody([
            'name' => 'Updated Collaborator',
            'email' => 'collab@example.com',
            'phone' => '+1-555-5678',
            'expertise' => 'Digital Marketing',
        ])];
    }

    // Mobile API - Tickets
    if ($uri === 'mobile/tickets') {
        return ['body' => rawJsonBody([
            'title' => 'Need help with onboarding',
            'description' => 'Can someone assist with setting up my workspace?',
            'priority' => 'medium',
        ])];
    }

    if (preg_match('#^mobile/tickets/\{[^}]+\}/messages$#', $uri)) {
        return ['body' => rawJsonBody([
            'message' => 'Thank you for your response. Can you provide more details?',
        ])];
    }

    // Mobile API - Notifications
    if ($uri === 'mobile/notifications/mark-all-read') {
        return ['body' => rawJsonBody([])];
    }

    if ($uri === 'mobile/notifications/delete') {
        return ['body' => rawJsonBody([
            'notification_id' => 1,
        ])];
    }

    // Mobile API - Meetings
    if ($uri === 'mobile/meetings/bookings') {
        return ['body' => rawJsonBody([
            'meeting_event_id' => 1,
            'date' => '2026-05-15',
            'start_time' => '14:00',
            'end_time' => '15:00',
        ])];
    }

    // Mobile API - Spaces
    if ($uri === 'mobile/spaces/book') {
        return ['body' => rawJsonBody([
            'space_id' => 1,
            'date' => '2026-05-20',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ])];
    }

    // Profile endpoints
    if ($uri === 'profile/update') {
        return ['body' => rawJsonBody([
            'name' => 'John Updated',
            'email' => 'john.updated@example.com',
            'phone' => '+1-555-4321',
            'job_title' => 'Senior Manager',
        ])];
    }

    if ($uri === 'profile/change-password') {
        return ['body' => rawJsonBody([
            'current_password' => 'currentPass123!',
            'password' => 'newSecurePass123!',
            'password_confirmation' => 'newSecurePass123!',
        ])];
    }

    // Default fallback
    return null;
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

function collectionTypeFromOutput(string $outputFile): string
{
    $filename = strtolower(basename($outputFile));

    if (str_contains($filename, 'mobile')) {
        return 'mobile';
    }

    if (str_contains($filename, 'web')) {
        return 'web';
    }

    return 'global';
}

function routeMatchesCollection(string $uri, string $collectionType): bool
{
    $isWeb = str_starts_with($uri, 'web/');
    $isMobile = str_starts_with($uri, 'mobile/');

    return match ($collectionType) {
        'web' => $isWeb,
        'mobile' => $isMobile,
        default => !$isWeb && !$isMobile,
    };
}

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

chdir($root);

$outputFile = $argv[1] ?? 'postman.json';
$collectionType = collectionTypeFromOutput($outputFile);

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

    if (!routeMatchesCollection($uri, $collectionType)) {
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
        'name' => match ($collectionType) {
            'web' => 'Delta IT Web API',
            'mobile' => 'Delta IT Mobile API',
            default => 'Delta IT Global API',
        },
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
