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

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON'
    ]);
}

$token = $data['token'] ?? '';
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

if (!is_string($token)
    || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid reset token'
    ]);
}

if (!is_string($password)
    || strlen($password) < 8
    || strlen($password) > 72) {
    respond(400, [
        'success' => false,
        'message' => 'Password must be 8-72 bytes'
    ]);
}

if (!is_string($confirmPassword)
    || $password !== $confirmPassword) {
    respond(400, [
        'success' => false,
        'message' => 'Passwords do not match'
    ]);
}

try {
    $tokenHash = hash('sha256', $token);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, user_id
         FROM password_resets
         WHERE token_hash = :token_hash
           AND used_at IS NULL
           AND expires_at > NOW()
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute(['token_hash' => $tokenHash]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        $pdo->rollBack();

        respond(400, [
            'success' => false,
            'message' => 'Reset link is invalid or expired'
        ]);
    }

    $newHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'UPDATE users
         SET password_hash = :password_hash,
             updated_at = NOW()
         WHERE id = :user_id'
    );

    $stmt->execute([
        'password_hash' => $newHash,
        'user_id' => $reset['user_id']
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('User not found');
    }

    // Vô hiệu hóa toàn bộ token khôi phục còn lại.
    $stmt = $pdo->prepare(
        'UPDATE password_resets
         SET used_at = NOW()
         WHERE user_id = :user_id
           AND used_at IS NULL'
    );

    $stmt->execute(['user_id' => $reset['user_id']]);

    $pdo->commit();

    // Đăng xuất phiên hiện tại sau khi reset thành công
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    session_destroy();

    respond(200, [
        'success' => true,
        'message' => 'Password reset successfully. Please log in again.'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Reset password error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to reset password'
    ]);
}