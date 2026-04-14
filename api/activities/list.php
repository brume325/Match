<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Services\ActivityService;
use App\Support\AuditLogger;

$pdo = require dirname(__DIR__, 2) . '/src/bootstrap.php';

try {
    $filters = [
        'category_name' => $_GET['category_name'] ?? null,
        'activity_date' => $_GET['activity_date'] ?? null,
    ];

    $service = new ActivityService($pdo, new AuditLogger($pdo));
    $activities = $service->list($filters);

    JsonResponse::send(['status' => 'ok', 'data' => $activities]);
} catch (Throwable $exception) {
    JsonResponse::send(['error' => 'Unable to fetch activities'], 500);
}
