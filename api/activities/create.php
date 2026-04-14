<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Security\SessionManager;
use App\Services\ActivityService;
use App\Support\AuditLogger;

$pdo = require dirname(__DIR__, 2) . '/src/bootstrap.php';

$userId = SessionManager::requireUser();

try {
    $payload = json_decode((string)file_get_contents('php://input'), true) ?? [];
    $service = new ActivityService($pdo, new AuditLogger($pdo));
    $activityId = $service->create($userId, $payload);

    JsonResponse::send(['status' => 'ok', 'activity_id' => $activityId], 201);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    JsonResponse::send(['error' => 'Unable to create activity'], 500);
}
