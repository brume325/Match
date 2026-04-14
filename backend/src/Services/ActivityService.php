<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AuditLogger;
use PDO;

/**
 * Applies activity domain constraints so API handlers remain stateless and deterministic.
 */
final class ActivityService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function create(int $organizerId, array $input): int
    {
        $title = trim((string)($input['title'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $categoryName = trim((string)($input['category_name'] ?? ''));
        $locationName = trim((string)($input['location_name'] ?? ''));
        $activityDate = trim((string)($input['activity_date'] ?? ''));
        $activityTime = trim((string)($input['activity_time'] ?? ''));
        $durationMinutes = (int)($input['duration_minutes'] ?? 0);
        $maxParticipants = isset($input['max_participants']) ? (int)$input['max_participants'] : null;
        $imageUrl = trim((string)($input['image_url'] ?? ''));
        $isPaid = (bool)($input['is_paid'] ?? false);

        if ($title === '' || $categoryName === '' || $locationName === '' || $activityDate === '' || $activityTime === '' || $durationMinutes <= 0) {
            throw new \InvalidArgumentException('Invalid activity payload');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO activities (organizer_id, title, description, category_name, location_name, activity_date, activity_time, duration_minutes, max_participants, image_url, is_paid) VALUES (:organizer_id, :title, :description, :category_name, :location_name, :activity_date, :activity_time, :duration_minutes, :max_participants, :image_url, :is_paid)'
        );

        $statement->execute([
            ':organizer_id' => $organizerId,
            ':title' => $title,
            ':description' => $description !== '' ? $description : null,
            ':category_name' => $categoryName,
            ':location_name' => $locationName,
            ':activity_date' => $activityDate,
            ':activity_time' => $activityTime,
            ':duration_minutes' => $durationMinutes,
            ':max_participants' => $maxParticipants,
            ':image_url' => $imageUrl !== '' ? $imageUrl : null,
            ':is_paid' => $isPaid ? 1 : 0,
        ]);

        $activityId = (int)$this->pdo->lastInsertId();
        $this->auditLogger->log($organizerId, 'activity_created', ['activity_id' => $activityId]);

        return $activityId;
    }

    public function register(int $userId, int $activityId): void
    {
        if ($activityId <= 0) {
            throw new \InvalidArgumentException('Invalid activity id');
        }

        $statement = $this->pdo->prepare('SELECT max_participants FROM activities WHERE activity_id = :activity_id LIMIT 1');
        $statement->execute([':activity_id' => $activityId]);
        $activity = $statement->fetch();

        if (!$activity) {
            throw new \RuntimeException('Activity not found');
        }

        if ($activity['max_participants'] !== null) {
            $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM registrations WHERE activity_id = :activity_id');
            $countStatement->execute([':activity_id' => $activityId]);
            $registeredCount = (int)$countStatement->fetchColumn();
            if ($registeredCount >= (int)$activity['max_participants']) {
                throw new \RuntimeException('Activity is full');
            }
        }

        $insertStatement = $this->pdo->prepare('INSERT INTO registrations (user_id, activity_id) VALUES (:user_id, :activity_id)');
        $insertStatement->execute([':user_id' => $userId, ':activity_id' => $activityId]);

        $this->auditLogger->log($userId, 'activity_joined', ['activity_id' => $activityId]);
    }

    public function list(array $filters = []): array
    {
        $query = 'SELECT activity_id, organizer_id, title, category_name, location_name, activity_date, activity_time, duration_minutes, max_participants, is_paid FROM activities WHERE 1 = 1';
        $params = [];

        if (!empty($filters['category_name'])) {
            $query .= ' AND category_name = :category_name';
            $params[':category_name'] = (string)$filters['category_name'];
        }

        if (!empty($filters['activity_date'])) {
            $query .= ' AND activity_date = :activity_date';
            $params[':activity_date'] = (string)$filters['activity_date'];
        }

        $query .= ' ORDER BY activity_date ASC, activity_time ASC LIMIT 100';

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        return $statement->fetchAll();
    }
}
