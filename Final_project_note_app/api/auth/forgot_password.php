<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mailer.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// 1. Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

// 2. Đọc dữ liệu JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
}

// 3. Kiểm tra email
$email = $data['email'] ?? null;

if (!is_string($email)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid email address'
    ]);
}

$email = strtolower(trim($email));

if (
    strlen($email) > 255 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid email address'
    ]);
}

// Không tiết lộ email có tồn tại hay không
$genericResponse = [
    'success' => true,
    'message' =>
        'If the email is registered, a password reset link will be sent.'
];

try {
    // 4. Tìm tài khoản
    $stmt = $pdo->prepare("
        SELECT id, email, display_name
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->execute([
        'email' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        respond(200, $genericResponse);
    }

    // 5. Giới hạn yêu cầu: 60 giây / tài khoản
    $stmt = $pdo->prepare("
        SELECT id
        FROM password_resets
        WHERE user_id = :user_id
          AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
        LIMIT 1
    ");

    $stmt->execute([
        'user_id' => $user['id']
    ]);

    if ($stmt->fetch()) {
        respond(200, $genericResponse);
    }

    // 6. Tạo token ngẫu nhiên
    $token = bin2hex(random_bytes(32));

    // Chỉ lưu hash, không lưu token gốc
    $tokenHash = hash('sha256', $token);

    // 7. Lưu token vào Aiven
    $pdo->beginTransaction();

    // Vô hiệu hóa token cũ
    $stmt = $pdo->prepare("
        UPDATE password_resets
        SET used_at = NOW()
        WHERE user_id = :user_id
          AND used_at IS NULL
    ");

    $stmt->execute([
        'user_id' => $user['id']
    ]);

    // Token mới hết hạn sau 30 phút
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (
            user_id,
            token_hash,
            expires_at,
            created_at
        )
        VALUES (
            :user_id,
            :token_hash,
            DATE_ADD(NOW(), INTERVAL 30 MINUTE),
            NOW()
        )
    ");

    $stmt->execute([
        'user_id' => $user['id'],
        'token_hash' => $tokenHash
    ]);

    $pdo->commit();

    // 8. Gửi email khôi phục mật khẩu
    try {
        sendPasswordResetEmail(
            $user['email'],
            $user['display_name'] ?? 'User',
            $token
        );
    } catch (Throwable $mailError) {
        error_log(
            'Password reset email error: '
            . $mailError->getMessage()
        );
    }

    // 9. Trả kết quả chung
    respond(200, $genericResponse);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Forgot password error: ' . $e->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to process request'
    ]);
}
