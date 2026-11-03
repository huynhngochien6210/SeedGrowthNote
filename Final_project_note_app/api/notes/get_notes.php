
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

    // Get notes and their attachments
    $sql = "
        SELECT
            n.id,
            n.title,
            n.content,
            n.is_password_protected,
            n.is_pinned,
            n.pinned_at,
            n.created_at,
            n.updated_at,
            n.note_type,
            n.seed_color,

            a.id AS attachment_id,
            a.file_name,
            a.file_path,
            a.mime_type,
            a.file_size

        FROM notes n

        LEFT JOIN attachments a
            ON n.id = a.note_id

        WHERE n.owner_id = :owner_id

        ORDER BY
            n.is_pinned DESC,
            n.pinned_at DESC,
            n.created_at DESC,
            n.id DESC,
            a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['owner_id' => $userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group attachments under their corresponding notes
    $notes = [];

    foreach ($rows as $row) {
        $noteId = $row['id'];

        if (!isset($notes[$noteId])) {
            $notes[$noteId] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'content' => (int)$row['is_password_protected'] === 1 ? '' : $row['content'],
                'is_password_protected' => (bool)$row['is_password_protected'],
                'note_type' => $row['note_type'],
                'is_pinned' => $row['is_pinned'],
                'pinned_at' => $row['pinned_at'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'seed_color' => $row['seed_color'],
                'attachments' => []
            ];
        }

        // Add attachment if it exists
        if ($row['attachment_id'] !== null) {
            $notes[$noteId]['attachments'][] = [
                'id' => $row['attachment_id'],
                'file_name' => $row['file_name'],
                'file_path' => $row['file_path'],
                'mime_type' => $row['mime_type'],
                'file_size' => $row['file_size']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => array_values($notes)
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load notes'
    ]);
}
