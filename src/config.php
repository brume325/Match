<?php
// config.php

function load_env_file($path) {
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        $val = trim($val, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
}

load_env_file(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/database/connection.php';

try {
    $pdo = db_connect_from_env();
} catch (PDOException $e) {
    die('Erreur de connexion a la base : ' . $e->getMessage());
}

// ── Rate limiting (anti-spam / anti-DDoS) ────────────────────
function check_rate_limit($action = 'global', $max = 60, $window = 60) {
    $key     = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $tmpFile = sys_get_temp_dir() . '/mm_rl_' . md5($key) . '.txt';
    $now     = time();
    
    $data = ['count' => 0, 'start' => $now];
    if (file_exists($tmpFile)) {
        $raw = @json_decode(file_get_contents($tmpFile), true);
        if ($raw && ($now - $raw['start']) < $window) {
            $data = $raw;
        }
    }
    
    $data['count']++;
    file_put_contents($tmpFile, json_encode($data), LOCK_EX);
    
    if ($data['count'] > $max) {
        http_response_code(429);
        die(json_encode(['error' => 'Trop de requêtes. Veuillez patienter.']));
    }
}

// Appliquer sur les pages sensibles (login, register)
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
if (in_array($current_page, ['login.php', 'register.php', 'mot_de_passe_oublie.php'])) {
    check_rate_limit('auth', 10, 60); // max 10 tentatives par minute
}
