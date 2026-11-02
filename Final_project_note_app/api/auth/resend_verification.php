<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    !is_string($csrf) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrf)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = is_array($data) ? ($data['email'] ?? '') : '';

if (!is_string($email) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        'success' => false,
        'message' => 'Email không hợp lệ.'
    ]);
}

$email = strtolower(trim($email));

try {
    require_once __DIR__ . '/../../config/database.php';

    $stmt = $pdo->prepare(
        'SELECT id, display_name, email_verified_at
         FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Avoid revealing whether an account exists
    $genericMessage =
        'Nếu tài khoản tồn tại và chưa xác minh, '
        . 'chúng tôi sẽ gửi liên kết xác minh đến email.';

    if (!$user || $user['email_verified_at'] !== null) {
        respond(200, [
            'success' => true,
            'message' => $genericMessage
        ]);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $pdo->beginTransaction();

    // Invalidate old unused tokens
    $stmt = $pdo->prepare(
        'UPDATE email_verification_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL'
    );
    $stmt->execute([$user['id']]);

    $stmt = $pdo->prepare(
        'INSERT INTO email_verification_tokens
         (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
    );
    $stmt->execute([$user['id'], $tokenHash]);

    $pdo->commit();

    require_once __DIR__ . '/../../config/mailer.php';

    sendVerificationEmail(
        $email,
        $user['display_name'] ?: $email,
        $token
    );

    respond(200, [
        'success' => true,
        'message' => $genericMessage
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Resend verification error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Không thể xử lý yêu cầu lúc này.'
    ]);
}
