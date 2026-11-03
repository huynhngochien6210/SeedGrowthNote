<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
requireMethod('GET');

try {
    $labelId = positiveId($_GET['label_id'] ?? null, 'label_id');
    $label = $pdo->prepare('SELECT id FROM labels WHERE id = ? AND user_id = ?');
    $label->execute([$labelId, $userId]);
    if (!$label->fetch()) reply(404, false, 'Label không tồn tại.');

    $sql = 'SELECT n.id FROM notes n
            INNER JOIN note_labels nl ON nl.note_id = n.id
            INNER JOIN labels l ON l.id = nl.label_id
            WHERE n.owner_id = ? AND nl.label_id = ? AND l.user_id = ?
            ORDER BY n.is_pinned DESC, n.pinned_at DESC, n.created_at DESC, n.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $labelId, $userId]);
    reply(200, true, 'OK', array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
} catch (PDOException $e) {
    error_log('[filter_notes] ' . $e->getMessage());
    reply(500, false, 'Không thể lọc ghi chú theo label.');
}
