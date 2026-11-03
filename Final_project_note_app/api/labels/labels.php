<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare('SELECT l.id, l.name, l.created_at, l.updated_at,
            (SELECT COUNT(*) FROM note_labels nl WHERE nl.label_id = l.id) AS note_count
            FROM labels l WHERE l.user_id = ? ORDER BY LOWER(l.name), l.id');
        $stmt->execute([$userId]);
        reply(200, true, 'OK', $stmt->fetchAll());
    }

    $body = requestJson();
    $action = $body['action'] ?? '';

    if ($action === 'create' || $action === 'rename') {
        if (!is_string($body['name'] ?? null)) {
            reply(422, false, 'Tên label không hợp lệ.');
        }
        $name = trim(preg_replace('/\s+/u', ' ', $body['name']));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 40) {
            reply(422, false, 'Tên label phải có từ 1 đến 40 ký tự.');
        }
        $id = $action === 'rename' ? positiveId($body['label_id'] ?? null, 'label_id') : null;

        $check = $pdo->prepare('SELECT id FROM labels WHERE user_id = ? AND LOWER(name) = LOWER(?)' .
            ($id !== null ? ' AND id <> ?' : '') . ' LIMIT 1');
        $params = [$userId, $name];
        if ($id !== null) $params[] = $id;
        $check->execute($params);
        if ($check->fetch()) reply(409, false, 'Label này đã tồn tại.');

        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO labels (user_id, name) VALUES (?, ?)');
            $stmt->execute([$userId, $name]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare('UPDATE labels SET name = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$name, $id, $userId]);
            $checkOwner = $pdo->prepare('SELECT id FROM labels WHERE id = ? AND user_id = ?');
            $checkOwner->execute([$id, $userId]);
            if (!$checkOwner->fetch()) reply(404, false, 'Không tìm thấy label.');
        }
        reply($action === 'create' ? 201 : 200, true, 'Đã lưu label.', [
            'id' => $id, 'name' => $name
        ]);
    }

    if ($action === 'delete') {
        $id = positiveId($body['label_id'] ?? null, 'label_id');
        $pdo->beginTransaction();
        $owner = $pdo->prepare('SELECT id FROM labels WHERE id = ? AND user_id = ? FOR UPDATE');
        $owner->execute([$id, $userId]);
        if (!$owner->fetch()) {
            $pdo->rollBack();
            reply(404, false, 'Không tìm thấy label.');
        }
        // Schema has no ON DELETE CASCADE: remove relationships first.
        $pdo->prepare('DELETE FROM note_labels WHERE label_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM labels WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        $pdo->commit();
        reply(200, true, 'Đã xóa label; ghi chú vẫn được giữ.');
    }

    reply(400, false, 'Action không hợp lệ.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[labels] ' . $e->getMessage());
    reply(500, false, 'Không thể xử lý labels.');
}
