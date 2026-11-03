
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

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

$noteId = filter_var(
    $data['note_id'] ?? null,
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

try {
    // 4. Check note ownership
    $stmt = $pdo->prepare("
        SELECT id
        FROM notes
        WHERE id = :note_id
          AND owner_id = :owner_id
        LIMIT 1
    ");

    $stmt->execute([
        'note_id' => $noteId,
        'owner_id' => $_SESSION['user_id']
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Note not found'
        ]);
        exit;
    }

    // 5. Get all attachment paths belonging to this note
    $stmt = $pdo->prepare("
        SELECT id, file_path
        FROM attachments
        WHERE note_id = :note_id
    ");

    $stmt->execute([
        'note_id' => $noteId
    ]);

    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Delete database records in a transaction
    $pdo->beginTransaction();

    // Remove references before deleting a note (no ON DELETE CASCADE in schema).
    $cleanup = [
        'DELETE FROM notifications WHERE note_share_id IN (SELECT id FROM note_shares WHERE note_id = ?)',
        'DELETE FROM note_shares WHERE note_id = ?',
        'DELETE FROM note_labels WHERE note_id = ?',
        'DELETE FROM note_summaries WHERE note_id = ?',
        'DELETE FROM ai_query_notes WHERE note_id = ?',
    ];
    foreach ($cleanup as $query) {
        $cleanupStmt = $pdo->prepare($query);
        $cleanupStmt->execute([$noteId]);
    }

    $stmt = $pdo->prepare("
        DELETE FROM attachments
        WHERE note_id = :note_id
    ");

    $stmt->execute([
        'note_id' => $noteId
    ]);

    $stmt = $pdo->prepare("
        DELETE FROM notes
        WHERE id = :note_id
          AND owner_id = :owner_id
    ");

    $stmt->execute([
        'note_id' => $noteId,
        'owner_id' => $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Could not delete note');
    }

    $pdo->commit();

    // 7. Delete physical files safely
    $projectRoot = dirname(__DIR__, 2);
    $uploadDirectory = realpath(
        $projectRoot . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'attachments'
    );

    $deletedFiles = 0;
    $failedFiles = 0;

    foreach ($attachments as $attachment) {
        $relativePath = $attachment['file_path'];

        // Only allow files inside uploads/attachments
        if (!str_starts_with(
            $relativePath,
            'uploads/attachments/'
        )) {
            $failedFiles++;
            error_log('Invalid attachment path: ' . $relativePath);
            continue;
        }

        if ($uploadDirectory === false) {
            $failedFiles++;
            continue;
        }

        $absolutePath = $projectRoot . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        $realFilePath = realpath($absolutePath);

        // Ensure the resolved file is inside the upload directory
        if (
            $realFilePath !== false &&
            str_starts_with(
                $realFilePath,
                $uploadDirectory . DIRECTORY_SEPARATOR
            ) &&
            is_file($realFilePath)
        ) {
            if (unlink($realFilePath)) {
                $deletedFiles++;
            } else {
                $failedFiles++;
                error_log('Could not delete file: ' . $realFilePath);
            }
        }
    }

    // 8. Return result
    echo json_encode([
        'success' => true,
        'message' => 'Note deleted successfully',
        'data' => [
            'note_id' => $noteId,
            'deleted_attachments' => count($attachments),
            'deleted_files' => $deletedFiles,
            'failed_files' => $failedFiles
        ]
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete note'
    ]);
}
