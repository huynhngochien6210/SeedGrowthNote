<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Check login
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);

        exit;
    }

    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    $noteId = $data['note_id'] ?? null;
    $seedColor = $data['seed_color'] ?? null;
    $userId = $_SESSION['user_id'];

    // Validate input
    if (!$noteId || !is_numeric($noteId)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid note ID'
        ]);

        exit;
    }

    if (
        !is_string($seedColor) ||
        !preg_match('/^#[0-9A-Fa-f]{6}$/', $seedColor)
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid color'
        ]);

        exit;
    }

    // Update note color
    $sql = "
        UPDATE notes
        SET seed_color = :seed_color
        WHERE id = :note_id
        AND owner_id = :owner_id
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'seed_color' => $seedColor,
        'note_id' => $noteId,
        'owner_id' => $userId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Note color updated successfully'
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to update note color'
    ]);
}