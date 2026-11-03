<?php
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $noteId = filter_var($data['note_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$noteId || $noteId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid note ID'
        ]);
        exit;
    }

    $ownerId = $_SESSION['user_id'];

    // Get current pin status
    $stmt = $pdo->prepare("
        SELECT is_pinned
        FROM notes
        WHERE id = :note_id AND owner_id = :owner_id
    ");

    $stmt->execute([
        ':note_id' => $noteId,
        ':owner_id' => $ownerId
    ]);

    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$note) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Note not found'
        ]);
        exit;
    }

    // Toggle pin status
    $newStatus = ((int)$note['is_pinned'] === 1) ? 0 : 1;

    $update = $pdo->prepare("
        UPDATE notes
        SET
            is_pinned = :is_pinned,
            pinned_at = CASE
                WHEN :pin_time = 1 THEN NOW()
                ELSE NULL
            END
        WHERE id = :note_id AND owner_id = :owner_id
    ");

    $update->execute([
        ':is_pinned' => $newStatus,
        ':pin_time' => $newStatus,
        ':note_id' => $noteId,
        ':owner_id' => $ownerId
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'note_id' => $noteId,
            'is_pinned' => $newStatus
        ]
    ]);

} catch (Throwable $e) {
    error_log("Toggle pin error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}