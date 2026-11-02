<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Get current user ID
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);

        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get notes belonging to current user
    $sql = "
        SELECT
            id,
            title,
            content,
            is_pinned,
            pinned_at,
            created_at,
            updated_at
        FROM notes
        WHERE owner_id = :owner_id
        ORDER BY
        created_at DESC, id DESC,
            is_pinned DESC,
            pinned_at DESC,
            updated_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['owner_id' => $userId]);

    $notes = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $notes
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load notes'
    ]);
}