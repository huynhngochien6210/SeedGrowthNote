<?php
declare(strict_types=1);

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../register_debug.log');
error_log('REGISTER DEBUG START');

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

// Validate CSRF token
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
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

$name = $data['display_name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

if (
    !is_string($name) ||
    trim($name) === '' ||
    strlen($name) > 255
) {
    respond(422, [
        'success' => false,
        'message' => 'Tên không hợp lệ.'
    ]);
}

$name = trim($name);

if (!is_string($email)) {
    respond(422, [
        'success' => false,
        'message' => 'Email không hợp lệ.'
    ]);
}

$email = strtolower(trim($email));

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 255
) {
    respond(422, [
        'success' => false,
        'message' => 'Email không hợp lệ.'
    ]);
}

if (!is_string($password) || strlen($password) < 8) {
    respond(422, [
        'success' => false,
        'message' => 'Mật khẩu phải có ít nhất 8 ký tự.'
    ]);
}

if (
    !is_string($confirmPassword) ||
    $password !== $confirmPassword
) {
    respond(422, [
        'success' => false,
        'message' => 'Mật khẩu xác nhận không khớp.'
    ]);
}

try {
    require_once __DIR__ . '/../../config/database.php';

    // Check duplicate email
    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE email = ? LIMIT 1'
    );

    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        respond(409, [
            'success' => false,
            'message' => 'Email đã được đăng ký.'
        ]);
    }

    // Hash password
    $passwordHash = password_hash(
        $password,
        PASSWORD_BCRYPT
    );

    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    // Save user and token atomically
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users
            (display_name, email, password_hash,
             created_at, updated_at)
         VALUES (?, ?, ?, NOW(), NOW())'
    );

    $stmt->execute([
        $name,
        $email,
        $passwordHash
    ]);

    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO email_verification_tokens
            (user_id, token_hash, expires_at)
         VALUES
            (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
    );

    $stmt->execute([
        $userId,
        $tokenHash
    ]);

    $pdo->commit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Register DB error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        respond(409, [
            'success' => false,
            'message' => 'Email đã được đăng ký.'
        ]);
    }

    respond(500, [
        'success' => false,
        'message' => 'Không thể lưu tài khoản.'
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Register error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Có lỗi xảy ra trên máy chủ.'
    ]);
}

// Send verification email after database commit
try {
    require_once __DIR__ . '/../../config/mailer.php';

    sendVerificationEmail($email, $name, $token);

    respond(201, [
        'success' => true,
        'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để xác minh tài khoản.',
        'verification_email_sent' => true
    ]);

} catch (Throwable $e) {
    error_log('Verification email error: ' . $e->getMessage());

    respond(201, [
        'success' => true,
        'message' => 'Tài khoản đã được tạo nhưng chưa gửi được email xác minh.',
        'verification_email_sent' => false
    ]);
}
