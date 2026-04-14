<?php

declare(strict_types=1);

namespace App\Security;

use App\Support\AuditLogger;
use PDO;

/**
 * Encapsulates account lifecycle rules to keep endpoints thin and auditable.
 */
final class AuthService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function register(array $input): int
    {
        $firstName = trim((string)($input['first_name'] ?? ''));
        $lastName = trim((string)($input['last_name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $organization = trim((string)($input['organization'] ?? ''));
        $levelName = trim((string)($input['level_name'] ?? ''));
        $rgpdConsent = (bool)($input['rgpd_consent'] ?? false);

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            throw new \InvalidArgumentException('Required fields are missing');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email format is invalid');
        }

        if ($rgpdConsent !== true) {
            throw new \InvalidArgumentException('Explicit RGPD consent is required');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $statement = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash, organization, level_name, rgpd_consent) VALUES (:first_name, :last_name, :email, :password_hash, :organization, :level_name, :rgpd_consent)'
        );

        $statement->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':organization' => $organization !== '' ? $organization : null,
            ':level_name' => $levelName !== '' ? $levelName : null,
            ':rgpd_consent' => 1,
        ]);

        $userId = (int)$this->pdo->lastInsertId();
        $this->auditLogger->log($userId, 'user_registered', ['email' => $email]);

        return $userId;
    }

    public function login(string $email, string $password): int
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, password_hash, is_active FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute([':email' => trim($email)]);
        $user = $statement->fetch();

        if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
            throw new \RuntimeException('Invalid credentials');
        }

        $userId = (int)$user['user_id'];
        $this->auditLogger->log($userId, 'user_logged_in');

        return $userId;
    }
}
