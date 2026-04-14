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
    $activityId = (int)($payload['activity_id'] ?? 0);

    $service = new ActivityService($pdo, new AuditLogger($pdo));
    $service->register($userId, $activityId);

    JsonResponse::send(['status' => 'ok']);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send(['error' => $exception->getMessage()], 422);
} catch (RuntimeException $exception) {
    JsonResponse::send(['error' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    JsonResponse::send(['error' => 'Unable to join activity'], 500);
}
