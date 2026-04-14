<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Persists critical actions to support moderation and incident investigation.
 */
final class AuditLogger
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function log(?int $actorUserId, string $eventName, array $eventPayload = []): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_logs (actor_user_id, event_name, event_payload) VALUES (:actor_user_id, :event_name, :event_payload)'
        );

        $statement->execute([
            ':actor_user_id' => $actorUserId,
            ':event_name' => $eventName,
            ':event_payload' => json_encode($eventPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
