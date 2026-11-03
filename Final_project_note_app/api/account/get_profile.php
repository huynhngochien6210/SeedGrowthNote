<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please log in again.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, email, display_name,
                date_of_birth, gender, avatar_path
         FROM users
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $_SESSION['user_id']
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (Throwable $e) {
    error_log('Get profile error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load profile.'
    ]);
}
