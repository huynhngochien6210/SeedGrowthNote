<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-SeedGrowth-Endpoint: get-attachments-v2');

require_once __DIR__ . '/../../config/database.php';

try {
    // 1. Kiểm tra đăng nhập
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

        exit;
    }

    $userId = (int) $_SESSION['user_id'];

    // 2. Kiểm tra note_id
    $noteId = filter_input(
        INPUT_GET,
        'note_id',
        FILTER_VALIDATE_INT
    );

    if (!$noteId || $noteId <= 0) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Valid note ID is required'
        ]);

        exit;
    }

    // 3. Kiểm tra quyền sở hữu ghi chú
    $stmt = $pdo->prepare("
        SELECT id
        FROM notes
        WHERE id = :note_id
          AND owner_id = :owner_id
        LIMIT 1
    ");

    $stmt->execute([
        ':note_id' => $noteId,
        ':owner_id' => $userId
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Note not found'
        ]);

        exit;
    }

    // 4. Lấy danh sách file đính kèm
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

    // 5. Trả dữ liệu
    http_response_code(200);

    echo json_encode([
        'success' => true,
        'data' => $attachments
    ]);

} catch (Throwable $e) {
    error_log(
        'Get attachments error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load attachments'
    ]);
}
