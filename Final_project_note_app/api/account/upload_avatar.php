<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/database.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

if (empty($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Please log in again.'
    ]);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
}

if (!isset($_FILES['avatar'])) {
    respond(400, [
        'success' => false,
        'message' => 'Please select an image.'
    ]);
}

$file = $_FILES['avatar'];

if (!is_array($file) || is_array($file['error'] ?? null)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid upload.'
    ]);
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(400, [
        'success' => false,
        'message' => 'Image upload failed.'
    ]);
}

$maxSize = 2 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    respond(400, [
        'success' => false,
        'message' => 'Image must be smaller than 2 MB.'
    ]);
}

$tmpPath = $file['tmp_name'];

if (!is_uploaded_file($tmpPath)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid uploaded file.'
    ]);
}

$imageInfo = @getimagesize($tmpPath);

if ($imageInfo === false) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid image content.'
    ]);
}

[$width, $height] = $imageInfo;

if (
    $width < 1 || $height < 1 ||
    $width > 4096 || $height > 4096
) {
    respond(400, [
        'success' => false,
        'message' => 'Image dimensions are not supported.'
    ]);
}

$allowedTypes = [
    IMAGETYPE_JPEG => ['extension' => 'jpg', 'mime' => 'image/jpeg'],
    IMAGETYPE_PNG  => ['extension' => 'png', 'mime' => 'image/png'],
    IMAGETYPE_WEBP => ['extension' => 'webp', 'mime' => 'image/webp']
];

$imageType = $imageInfo[2];

if (!isset($allowedTypes[$imageType])) {
    respond(400, [
        'success' => false,
        'message' => 'Only JPG, PNG and WebP are allowed.'
    ]);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$actualMime = $finfo->file($tmpPath);

if ($actualMime !== $allowedTypes[$imageType]['mime']) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid image format.'
    ]);
}

// Giải mã và mã hóa lại ảnh để không lưu nguyên
// dữ liệu upload chưa được xử lý.
if (!extension_loaded('gd')) {
    respond(500, [
        'success' => false,
        'message' => 'Image processing is unavailable.'
    ]);
}

$source = match ($imageType) {
    IMAGETYPE_JPEG => @imagecreatefromjpeg($tmpPath),
    IMAGETYPE_PNG  => @imagecreatefrompng($tmpPath),
    IMAGETYPE_WEBP => @imagecreatefromwebp($tmpPath),
    default => false
};

if ($source === false) {
    respond(400, [
        'success' => false,
        'message' => 'Unable to process image.'
    ]);
}

$root = dirname(__DIR__, 2);
$uploadDir = $root . '/uploads/avatars';

if (!is_dir($uploadDir) &&
    !mkdir($uploadDir, 0755, true) &&
    !is_dir($uploadDir)) {
    imagedestroy($source);

    respond(500, [
        'success' => false,
        'message' => 'Unable to create upload directory.'
    ]);
}

$fileName = bin2hex(random_bytes(16)) . '.png';
$absolutePath = $uploadDir . '/' . $fileName;
$relativePath = 'uploads/avatars/' . $fileName;

$encoded = imagepng($source, $absolutePath);
imagedestroy($source);

if (!$encoded) {
    respond(500, [
        'success' => false,
        'message' => 'Unable to save image.'
    ]);
}

@chmod($absolutePath, 0644);

try {
    $stmt = $pdo->prepare(
        'UPDATE users
         SET avatar_path = :avatar_path,
             updated_at = NOW()
         WHERE id = :user_id'
    );

    $stmt->execute([
        'avatar_path' => $relativePath,
        'user_id' => $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() === 0) {
        @unlink($absolutePath);

        respond(404, [
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    respond(200, [
        'success' => true,
        'message' => 'Avatar updated successfully.',
        'avatar_path' => $relativePath
    ]);

} catch (Throwable $e) {
    @unlink($absolutePath);

    error_log('Upload avatar error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to update avatar.'
    ]);
}
