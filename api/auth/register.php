<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Security\AuthService;
use App\Security\SessionManager;
use App\Support\AuditLogger;

$pdo = require dirname(__DIR__, 2) . '/src/bootstrap.php';

SessionManager::start();

try {
    $payload = json_decode((string)file_get_contents('php://input'), true) ?? [];
    $authService = new AuthService($pdo, new AuditLogger($pdo));
    $userId = $authService->register($payload);

    SessionManager::regenerate();
    $_SESSION['user_id'] = $userId;

    JsonResponse::send(['status' => 'ok', 'user_id' => $userId], 201);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    JsonResponse::send(['error' => 'Registration failed'], 500);
}
