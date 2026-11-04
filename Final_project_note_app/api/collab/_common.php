<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config/database.php';

// Trả về JSON
function out(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Kiểm tra đăng nhập
function me(): int
{
    $id = (int) ($_SESSION['user_id'] ?? 0);

    if (!$id) {
        out([
            'success' => false,
            'message' => 'Vui lòng đăng nhập'
        ], 401);
    }

    return $id;
}

// Đọc dữ liệu JSON từ request
function payload(): array
{
    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        out([
            'success' => false,
            'message' => 'JSON không hợp lệ'
        ], 400);
    }

    return $data;
}

// Kiểm tra POST và CSRF token
function writeOnly(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out([
            'success' => false,
            'message' => 'Method not allowed'
        ], 405);
    }

    $token = (string) (
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''
    );

    $sessionToken = (string) (
        $_SESSION['sgn_csrf'] ?? ''
    );

    if (
        !$token ||
        !hash_equals($sessionToken, $token)
    ) {
        out([
            'success' => false,
            'message' => 'CSRF token không hợp lệ; hãy tải lại trang'
        ], 403);
    }
}

// Lấy ghi chú và kiểm tra quyền truy cập
function note(PDO $pdo, int $id, int $user): array
{
    $sql = '
        SELECT
            n.*,
            CASE
                WHEN n.owner_id = ? THEN ?
                ELSE (
                    SELECT ns.permission
                    FROM note_shares ns
                    WHERE ns.note_id = n.id
                      AND ns.recipient_id = ?
                      AND ns.revoked_at IS NULL
                    ORDER BY ns.id DESC
                    LIMIT 1
                )
            END AS access_level
        FROM notes n
        WHERE n.id = ?
        LIMIT 1
    ';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $user,
        'owner',
        $user,
        $id
    ]);

    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$note || !$note['access_level']) {
        out([
            'success' => false,
            'message' => 'Không tìm thấy ghi chú hoặc không có quyền'
        ], 404);
    }

    return $note;
}

// Tạo khóa session cho từng ghi chú
function unlockKey(int $id): string
{
    return 'sgn_note_unlock_' . $id;
}

// Kiểm tra ghi chú đã được mở khóa hay chưa
function unlocked(array $note): bool
{
    if (empty($note['is_password_protected'])) {
        return true;
    }

    $key = unlockKey((int) $note['id']);

    $sessionHash = $_SESSION[$key] ?? '';

    $passwordHash = hash(
        'sha256',
        (string) $note['note_password_hash']
    );

    return $sessionHash === $passwordHash;
}

// Yêu cầu mở khóa nếu ghi chú có mật khẩu
function needsUnlock(array $note): void
{
    if (!unlocked($note)) {
        out([
            'success' => false,
            'code' => 'NOTE_LOCKED',
            'message' => 'Ghi chú yêu cầu mật khẩu'
        ], 423);
    }
}

// Kiểm tra quyền đọc hoặc chỉnh sửa ghi chú
function access(
    PDO $pdo,
    int $id,
    int $user,
    string $needed = 'read'
): array {
    $note = note($pdo, $id, $user);

    if (
        $needed === 'edit' &&
        !in_array(
            $note['access_level'],
            ['owner', 'edit'],
            true
        )
    ) {
        out([
            'success' => false,
            'message' => 'Bạn chỉ có quyền xem'
        ], 403);
    }

    needsUnlock($note);

    return $note;
}

// Ghi log lỗi và trả về thông báo chung
function internalError(Throwable $e): never
{
    error_log(
        'Collab API: ' . $e->getMessage()
    );

    out([
        'success' => false,
        'message' => 'Có lỗi máy chủ'
    ], 500);
}
