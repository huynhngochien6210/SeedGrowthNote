<?php
require __DIR__ . '/_common.php';
require_once dirname(__DIR__, 2) . '/config/mailer.php';

try {
    $user = me();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $d = $_GET;
    } else {
        writeOnly();
        $d = payload();
    }

    $id = filter_var($d['note_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        out(['success' => false, 'message' => 'note_id không hợp lệ'], 400);
    }

    $n = note($pdo, $id, $user);
    if ((int) $n['owner_id'] !== $user) {
        out(['success' => false, 'message' => 'Chỉ chủ sở hữu được chia sẻ'], 403);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT u.email, ns.permission
             FROM note_shares ns
             JOIN users u ON u.id = ns.recipient_id
             WHERE ns.note_id = ? AND ns.revoked_at IS NULL
             ORDER BY u.email'
        );
        $stmt->execute([$id]);
        out(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    $shares = $d['shares'] ?? null;
    if (!is_array($shares) || count($shares) > 50) {
        out(['success' => false, 'message' => 'Danh sách chia sẻ không hợp lệ (tối đa 50)'], 422);
    }

    $senderStmt = $pdo->prepare('SELECT display_name FROM users WHERE id = ? LIMIT 1');
    $senderStmt->execute([$user]);
    $senderName = (string) ($senderStmt->fetchColumn() ?: 'A SeedGrowthNote user');

    $emailsToSend = [];
    $pdo->beginTransaction();

    $find = $pdo->prepare(
        'SELECT id, email, display_name FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1'
    );
    $existing = $pdo->prepare(
        'SELECT id, permission, revoked_at FROM note_shares
         WHERE note_id = ? AND recipient_id = ?
         ORDER BY id DESC LIMIT 1'
    );
    $update = $pdo->prepare(
        'UPDATE note_shares
         SET permission = ?, revoked_at = NULL, updated_at = NOW()
         WHERE id = ?'
    );
    $insert = $pdo->prepare(
        'INSERT INTO note_shares (note_id, recipient_id, permission)
         VALUES (?, ?, ?)'
    );
    $revoke = $pdo->prepare(
        'UPDATE note_shares SET revoked_at = NOW()
         WHERE note_id = ? AND recipient_id = ? AND revoked_at IS NULL'
    );

    $seen = [];
    foreach ($shares as $entry) {
        if (!is_array($entry)) {
            $pdo->rollBack();
            out(['success' => false, 'message' => 'Dữ liệu người nhận không hợp lệ'], 422);
        }
        $email = trim((string) ($entry['email'] ?? ''));
        $permission = (string) ($entry['permission'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($permission, ['read', 'edit'], true)) {
            $pdo->rollBack();
            out(['success' => false, 'message' => 'Email hoặc quyền không hợp lệ'], 422);
        }

        $find->execute([$email]);
        $recipient = $find->fetch(PDO::FETCH_ASSOC);
        if (!$recipient || (int) $recipient['id'] === $user) {
            $pdo->rollBack();
            out([
                'success' => false,
                'message' => 'Người nhận chưa đăng ký hoặc là chính bạn: ' . $email
            ], 422);
        }
        $recipientId = (int) $recipient['id'];
        if (isset($seen[$recipientId])) {
            continue;
        }
        $seen[$recipientId] = true;

        $existing->execute([$id, $recipientId]);
        $previous = $existing->fetch(PDO::FETCH_ASSOC);
        $wasActive = $previous && $previous['revoked_at'] === null;
        $permissionChanged = $wasActive && $previous['permission'] !== $permission;
        $shouldEmail = !$wasActive || $permissionChanged;

        if ($previous) {
            if (!$wasActive || $permissionChanged) {
                $update->execute([$permission, (int) $previous['id']]);
            }
        } else {
            $insert->execute([$id, $recipientId, $permission]);
        }

        if ($shouldEmail) {
            $emailsToSend[] = [
                'email' => $recipient['email'],
                'name' => $recipient['display_name'],
                'permission' => $permission,
                'changed' => $permissionChanged
            ];
        }
    }

    // Recipients omitted from the submitted list lose access.
    $stmt = $pdo->prepare(
        'SELECT recipient_id FROM note_shares
         WHERE note_id = ? AND revoked_at IS NULL'
    );
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $recipientId) {
        if (!isset($seen[(int) $recipientId])) {
            $revoke->execute([$id, $recipientId]);
        }
    }

    // Commit before SMTP: an email failure must not undo valid sharing.
    $pdo->commit();

    $sent = 0;
    $failed = 0;
    foreach ($emailsToSend as $recipient) {
        try {
            sendNoteShareEmail(
                $recipient['email'],
                $recipient['name'],
                $senderName,
                (string) $n['title'],
                $recipient['permission'],
                $recipient['changed']
            );
            $sent++;
        } catch (Throwable $mailError) {
            $failed++;
            error_log('SeedGrowthNote share email failed for note ' . $id
                . ': ' . $mailError->getMessage());
        }
    }

    $message = 'Đã cập nhật danh sách chia sẻ.';
    if ($sent > 0) {
        $message .= " Đã gửi {$sent} email thông báo.";
    }
    if ($failed > 0) {
        $message .= " Có {$failed} email gửi thất bại; quyền chia sẻ vẫn được lưu.";
    }

    out([
        'success' => true,
        'message' => $message,
        'emails_sent' => $sent,
        'emails_failed' => $failed
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    internalError($e);
}
