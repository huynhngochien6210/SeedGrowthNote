<?php
declare(strict_types=1);

$token = $_GET['token'] ?? '';

function verificationError(string $message): void
{
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit($message);
}

// Validate verification token
if (
    !is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/', $token)
) {
    verificationError('Liên kết xác minh không hợp lệ.');
}

try {
    require_once __DIR__ . '/../../config/database.php';

    $tokenHash = hash('sha256', $token);

    $pdo->beginTransaction();

    // Find a valid, unused verification token
    $stmt = $pdo->prepare(
        'SELECT id, user_id
         FROM email_verification_tokens
         WHERE token_hash = ?
           AND used_at IS NULL
           AND expires_at > NOW()
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute([$tokenHash]);
    $verification = $stmt->fetch();

    if (!$verification) {
        $pdo->rollBack();
        verificationError(
            'Liên kết xác minh đã hết hạn hoặc không hợp lệ.'
        );
    }

    // Mark the account as verified
    $stmt = $pdo->prepare(
        'UPDATE users
         SET email_verified_at = COALESCE(
             email_verified_at, NOW()
         ),
         updated_at = NOW()
         WHERE id = ?'
    );

    $stmt->execute([$verification['user_id']]);

    // Mark token as used
    $stmt = $pdo->prepare(
        'UPDATE email_verification_tokens
         SET used_at = NOW()
         WHERE id = ?'
    );

    $stmt->execute([$verification['id']]);

    $pdo->commit();

    // Verification successful: redirect to Login
    header('Location: /?page=login&verified=1');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Email verification error: ' . $e->getMessage()
    );

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Không thể xác minh email lúc này.');
}
