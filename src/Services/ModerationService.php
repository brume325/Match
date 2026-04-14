<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AuditLogger;
use PDO;

/**
 * Captures reports in a consistent format to enable moderation triage and traceability.
 */
final class ModerationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function report(int $reporterId, array $input): int
    {
        $activityId = isset($input['activity_id']) ? (int)$input['activity_id'] : null;
        $reportedUserId = isset($input['reported_user_id']) ? (int)$input['reported_user_id'] : null;
        $reportReason = trim((string)($input['report_reason'] ?? ''));

        if ($reportReason === '') {
            throw new \InvalidArgumentException('Report reason is required');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO reports (reporter_id, activity_id, reported_user_id, report_reason) VALUES (:reporter_id, :activity_id, :reported_user_id, :report_reason)'
        );

        $statement->execute([
            ':reporter_id' => $reporterId,
            ':activity_id' => $activityId,
            ':reported_user_id' => $reportedUserId,
            ':report_reason' => $reportReason,
        ]);

        $reportId = (int)$this->pdo->lastInsertId();
        $this->auditLogger->log($reporterId, 'content_reported', ['report_id' => $reportId]);

        return $reportId;
    }
}
