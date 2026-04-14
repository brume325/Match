<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Security\SessionManager;
use App\Services\ModerationService;
use App\Support\AuditLogger;

$pdo = require dirname(__DIR__, 2) . '/src/bootstrap.php';

$userId = SessionManager::requireUser();

try {
    $payload = json_decode((string)file_get_contents('php://input'), true) ?? [];
    $service = new ModerationService($pdo, new AuditLogger($pdo));
    $reportId = $service->report($userId, $payload);

    JsonResponse::send(['status' => 'ok', 'report_id' => $reportId], 201);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    JsonResponse::send(['error' => 'Unable to submit report'], 500);
}
