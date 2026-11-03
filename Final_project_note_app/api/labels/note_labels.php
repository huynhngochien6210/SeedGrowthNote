<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Only owner's notes and owner's labels are exposed.
        $sql = 'SELECT nl.note_id, nl.label_id, l.name
            FROM note_labels nl
            JOIN notes n ON n.id = nl.note_id AND n.owner_id = ?
            JOIN labels l ON l.id = nl.label_id AND l.user_id = ?
            ORDER BY nl.note_id, l.name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        reply(200, true, 'OK', $stmt->fetchAll());
    }

    $body = requestJson();
    $noteId = positiveId($body['note_id'] ?? null, 'note_id');
    $labelIds = $body['label_ids'] ?? null;
    if (!is_array($labelIds) || count($labelIds) > 200) {
        reply(422, false, 'label_ids phải là một mảng tối đa 200 phần tử.');
    }
    $ids = [];
    foreach ($labelIds as $value) {
        $id = positiveId($value, 'label_id');
        $ids[$id] = $id;
    }
    $ids = array_values($ids);

    $pdo->beginTransaction();
    $note = $pdo->prepare('SELECT id FROM notes WHERE id = ? AND owner_id = ? FOR UPDATE');
    $note->execute([$noteId, $userId]);
    if (!$note->fetch()) {
        $pdo->rollBack();
        reply(404, false, 'Ghi chú không tồn tại hoặc không thuộc về bạn.');
    }

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM labels WHERE user_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$userId], $ids));
        if (count($stmt->fetchAll()) !== count($ids)) {
            $pdo->rollBack();
            reply(422, false, 'Có label không thuộc về tài khoản hiện tại.');
        }
    }

    // Replace all label relationships for one note atomically.
    $pdo->prepare('DELETE FROM note_labels WHERE note_id = ?')->execute([$noteId]);
    if ($ids) {
        $insert = $pdo->prepare('INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)');
        foreach ($ids as $id) $insert->execute([$noteId, $id]);
    }
    $pdo->commit();
    reply(200, true, 'Đã cập nhật labels cho ghi chú.', [
        'note_id' => $noteId, 'label_ids' => $ids
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[note_labels] ' . $e->getMessage());
    reply(500, false, 'Không thể cập nhật labels của ghi chú.');
}
