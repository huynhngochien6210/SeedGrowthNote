<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

try {

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

    // 3. Get note ID
    $noteId = filter_var(
        $_POST['note_id'] ?? null,
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

    if (!$stmt->fetch()) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Note not found'
        ]);
        exit;
    }

    // 5. Check uploaded file
    if (!isset($_FILES['attachment'])) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded'
        ]);
        exit;
    }

    $file = $_FILES['attachment'];

    if ($file['error'] !== UPLOAD_ERR_OK) {

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporary folder is missing',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by server extension'
        ];

        throw new RuntimeException(
            $uploadErrors[$file['error']] ?? 'Unknown upload error'
        );
    }

    // 6. Validate file size
    if ($file['size'] <= 0 || $file['size'] > MAX_FILE_SIZE) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'File size must be greater than 0 and not exceed 10 MB'
        ]);
        exit;
    }

    // 7. Validate file extension
    $originalName = basename($file['name']);
    $extension = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );

    $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'csv',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'mp3', 'wav', 'ogg', 'mp4', 'webm',
        'zip'
    ];

    if (!in_array($extension, $allowedExtensions, true)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'File type is not supported'
        ]);
        exit;
    }

    // 8. Detect MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!$mimeType) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Could not determine file type'
        ]);
        exit;
    }

    // 9. Prepare upload directory
    $uploadDirectory = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'attachments';

    if (!is_dir($uploadDirectory)) {
        if (!mkdir($uploadDirectory, 0755, true)) {
            throw new RuntimeException(
                'Could not create upload directory'
            );
        }
    }

    // 10. Generate a unique server filename
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

    $absolutePath = $uploadDirectory
        . DIRECTORY_SEPARATOR . $storedName;

    $relativePath = 'uploads/attachments/' . $storedName;

    // 11. Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Failed to save uploaded file');
    }

    // 12. Save file information in database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO attachments (
                note_id,
                file_name,
                file_path,
                mime_type,
                file_size,
                created_at
            )
            VALUES (
                :note_id,
                :file_name,
                :file_path,
                :mime_type,
                :file_size,
                NOW()
            )
        ");

        $stmt->execute([
            'note_id' => $noteId,
            'file_name' => $originalName,
            'file_path' => $relativePath,
            'mime_type' => $mimeType,
            'file_size' => $file['size']
        ]);

        $attachmentId = $pdo->lastInsertId();

    } catch (PDOException $e) {

        // Remove physical file if database insert fails
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        throw $e;
    }

    // 13. Return uploaded attachment
    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'data' => [
            'id' => $attachmentId,
            'note_id' => $noteId,
            'file_name' => $originalName,
            'file_path' => $relativePath,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size']
        ]
    ]);

} catch (RuntimeException $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to save attachment information'
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ]);
}