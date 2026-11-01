<?php

session_start();

// Only allow local development
$allowedHosts = ['127.0.0.1', '::1', 'localhost'];

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedHosts, true)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/config/database.php';

$email = 'test@seedgrowthnote.local';

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE email = :email
    LIMIT 1
");

$stmt->execute(['email' => $email]);

$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('Test user not found');
}

// Temporary session for development
$_SESSION['user_id'] = $user['id'];

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'message' => 'Development session created',
    'user_id' => $_SESSION['user_id']
]);