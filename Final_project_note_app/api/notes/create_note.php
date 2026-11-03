<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../../config/database.php';

    // 1. Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);

        exit;
    }

    // 2. Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);

        exit;
    }

    $userId = (int) $_SESSION['user_id'];

    // 3. Create default note
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

    $noteId = (int) $pdo->lastInsertId();

    // 4. Retrieve the created note
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
        WHERE id = :id
          AND owner_id = :owner_id
    ");

    $stmt->execute([
        'id' => $noteId,
        'owner_id' => $userId
    ]);

    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Return the created note
    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Note created successfully',
        'data' => $note
    ]);

} catch (Throwable $e) {
    error_log('Create note failed: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to create note'
    ]);
}
