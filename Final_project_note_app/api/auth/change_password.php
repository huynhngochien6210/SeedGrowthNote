<?php
declare(strict_types=1);

session_start();

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
        'message' => 'Method not allowed'
    ]);
}

if (empty($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'User not logged in'
    ]);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON'
    ]);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid CSRF token'
    ]);
}

$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

if (!is_string($currentPassword)
    || !is_string($newPassword)
    || !is_string($confirmPassword)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid password data'
    ]);
}

if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
    respond(400, [
        'success' => false,
        'message' => 'New password must be 8-72 bytes'
    ]);
}

if ($newPassword !== $confirmPassword) {
    respond(400, [
        'success' => false,
        'message' => 'New passwords do not match'
    ]);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT password_hash
         FROM users
         WHERE id = :user_id
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user
        || !password_verify($currentPassword, $user['password_hash'])) {
        $pdo->rollBack();

        respond(400, [
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
    }

    if (password_verify($newPassword, $user['password_hash'])) {
        $pdo->rollBack();

        respond(400, [
            'success' => false,
            'message' => 'New password must differ from current password'
        ]);
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'UPDATE users
         SET password_hash = :password_hash,
             updated_at = NOW()
         WHERE id = :user_id'
    );

    $stmt->execute([
        'password_hash' => $newHash,
        'user_id' => $_SESSION['user_id']
    ]);

    // Hủy các liên kết khôi phục mật khẩu còn hiệu lực.
    $stmt = $pdo->prepare(
        'UPDATE password_resets
         SET used_at = NOW()
         WHERE user_id = :user_id
           AND used_at IS NULL'
    );

    $stmt->execute(['user_id' => $_SESSION['user_id']]);

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Password changed successfully'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Change password error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to change password'
    ]);
}