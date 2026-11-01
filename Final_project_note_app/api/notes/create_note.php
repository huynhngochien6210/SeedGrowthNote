<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Check login session
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Create a new note
    $sql = "
        INSERT INTO notes (
            owner_id,
            title,
            content,
            is_pinned,
            created_at,
            updated_at
        )
        VALUES (
            :owner_id,
            :title,
            :content,
            0,
            NOW(),
            NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'owner_id' => $userId,
        'title' => 'Untitled',
        'content' => ''
    ]);

    $noteId = $pdo->lastInsertId();

    // Return newly created note
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            content,
            is_pinned,
            pinned_at,
            created_at,
            updated_at
        FROM notes
        WHERE id = :id AND owner_id = :owner_id
    ");

    $stmt->execute([
        'id' => $noteId,
        'owner_id' => $userId
    ]);

    $note = $stmt->fetch();

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Note created successfully',
        'data' => $note
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to create note'
    ]);
}