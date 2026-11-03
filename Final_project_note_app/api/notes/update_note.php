<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../collab/_common.php';

// 1. Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// 2. Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// 3. Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

$noteId = filter_var($data['note_id'] ?? null, FILTER_VALIDATE_INT);
$title = trim($data['title'] ?? '');
$content = $data['content'] ?? '';
$noteType = $data['note_type'] ?? null;

// Validate note type if provided
if ($noteType !== null && !in_array($noteType, ['text', 'file'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid note type'
    ]);
    exit;
}

if (!$noteId || $title === '' || !is_string($content)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid note ID, title and content are required'
    ]);
    exit;
}

try {
    // 4. Check note ownership
    $stmt = $pdo->prepare("
        SELECT id, is_password_protected, note_password_hash
        FROM notes
        WHERE id = :note_id
          AND owner_id = :owner_id
        LIMIT 1
    ");

    $stmt->execute([
        'note_id' => $noteId,
        'owner_id' => $_SESSION['user_id']
    ]);

    $ownedNote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ownedNote) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Note not found'
        ]);
        exit;
    }

    $ownedNote['id'] = $noteId;
    if (!unlocked($ownedNote)) {
        http_response_code(423);
        echo json_encode(['success'=>false, 'code'=>'NOTE_LOCKED', 'message'=>'Ghi chú cần mở khóa trước khi sửa']);
        exit;
    }

    // 5. Update note
    $stmt = $pdo->prepare("
        UPDATE notes
        SET
            title = :title,
            content = :content,
            note_type = COALESCE(:note_type, note_type),
            updated_at = NOW()
        WHERE id = :note_id
        AND owner_id = :owner_id
    ");

    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'note_id' => $noteId,
        'owner_id' => $_SESSION['user_id'],
        'note_type' => $noteType,
    ]);

    // 6. Get updated note
    $stmt = $pdo->prepare("
        SELECT id, title, content, note_type, is_pinned,
               created_at, updated_at
        FROM notes
        WHERE id = :note_id
          AND owner_id = :owner_id
    ");

    $stmt->execute([
        'note_id' => $noteId,
        'owner_id' => $_SESSION['user_id']
    ]);

    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Note updated successfully',
        'data' => $note
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update note'
    ]);
}