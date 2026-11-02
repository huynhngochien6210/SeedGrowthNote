
<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    // Lấy note_id từ URL
    $noteId = filter_input(INPUT_GET, 'note_id', FILTER_VALIDATE_INT);

    if (!$noteId || $noteId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid note ID is required'
        ]);
        exit;
    }

    // Kiểm tra Note có thuộc người dùng hiện tại không
    $stmt = $pdo->prepare("
        SELECT id
        FROM notes
        WHERE id = :note_id
          AND owner_id = :owner_id
    ");

    $stmt->execute([
        ':note_id' => $noteId,
        ':owner_id' => $_SESSION['user_id']
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Note not found'
        ]);
        exit;
    }

    // Lấy danh sách file đính kèm
    $stmt = $pdo->prepare("
        SELECT
            id,
            note_id,
            file_name,
            file_path,
            mime_type,
            file_size,
            created_at
        FROM attachments
        WHERE note_id = :note_id
        ORDER BY created_at DESC, id DESC
    ");

    $stmt->execute([
        ':note_id' => $noteId
    ]);

    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $attachments
    ]);

} catch (PDOException $e) {
    error_log('Get attachments error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
