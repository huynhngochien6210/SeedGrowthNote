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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

// Validate CSRF token
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

// Read JSON request
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid JSON data.'
    ]);
}

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validate credentials
if (
    !is_string($email) ||
    !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
    !is_string($password) ||
    $password === ''
) {
    respond(422, [
        'success' => false,
        'message' => 'Email hoặc mật khẩu không hợp lệ.'
    ]);
}

$email = strtolower(trim($email));

try {
    require_once __DIR__ . '/../../config/database.php';

    // Find user by email
    $stmt = $pdo->prepare(
        'SELECT id, display_name, email,
                password_hash, email_verified_at
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password
    if (
        !$user ||
        !password_verify($password, $user['password_hash'])
    ) {
        respond(401, [
            'success' => false,
            'message' => 'Email hoặc mật khẩu không đúng.'
        ]);
    }

    // Check email verification
    if ($user['email_verified_at'] === null) {
        respond(403, [
            'success' => false,
            'verification_required' => true,
            'message' => 'Vui lòng xác minh email trước khi đăng nhập.'
        ]);
    }

    // Regenerate session ID after authentication
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['display_name'] = $user['display_name'];

    respond(200, [
        'success' => true,
        'message' => 'Đăng nhập thành công.'
    ]);

} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Có lỗi xảy ra trên máy chủ.'
    ]);
}
