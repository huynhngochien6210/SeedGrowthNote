<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
function aiOut(array $data, int $status = 200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function aiUser(): int { $id=(int)($_SESSION['user_id']??0); if($id<1) aiOut(['success'=>false,'message'=>'Vui lòng đăng nhập'],401); return $id; }
function aiBody(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') aiOut(['success'=>false,'message'=>'Method not allowed'],405);
    $csrf=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');
    if (!$csrf || !hash_equals((string)($_SESSION['sgn_csrf']??''),$csrf)) aiOut(['success'=>false,'message'=>'CSRF không hợp lệ; hãy tải lại trang'],403);
    $body=json_decode(file_get_contents('php://input'),true);
    if(!is_array($body)) aiOut(['success'=>false,'message'=>'JSON không hợp lệ'],400);
    return $body;
}
function aiNote(PDO $pdo,int $noteId,int $userId): array {
    $stmt=$pdo->prepare('SELECT n.id,n.owner_id,n.title,n.content,n.is_password_protected,n.note_password_hash FROM notes n WHERE n.id = ? AND (n.owner_id = ? OR EXISTS (SELECT 1 FROM note_shares s WHERE s.note_id = n.id AND s.recipient_id = ? AND s.revoked_at IS NULL AND s.permission IN (\'read\',\'edit\'))) LIMIT 1');
    $stmt->execute([$noteId,$userId,$userId]); $note=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$note) aiOut(['success'=>false,'message'=>'Không tìm thấy ghi chú hoặc không có quyền'],404);
    if (!empty($note['is_password_protected'])) {
        $key='sgn_note_unlock_'.$noteId;
        $expected=hash('sha256',(string)$note['note_password_hash']);
        if(!hash_equals($expected,(string)($_SESSION[$key]??''))) aiOut(['success'=>false,'code'=>'NOTE_LOCKED','message'=>'Hãy mở khóa ghi chú trước khi dùng AI'],423);
    }
    return $note;
}
function aiText(string $html): string {
    $html=preg_replace('/<\s*(script|style)\b[^>]*>.*?<\/\s*\1\s*>/is',' ',$html)??$html;
    $html=preg_replace('/<\s*br\s*\/?\s*>/i',"\n",$html)??$html;
    return trim(html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8'));
}
function aiFail(Throwable $e): never {
    if ($e instanceof GeminiApiException) {
        aiOut([
            'success' => false,
            'code' => $e->errorCode,
            'message' => $e->getMessage(),
        ], $e->httpStatus);
    }
    error_log('AI endpoint: ' . $e->getMessage());
    aiOut([
        'success' => false,
        'code' => 'AI_INTERNAL_ERROR',
        'message' => 'Không thể xử lý yêu cầu AI. Kiểm tra nhật ký PHP.',
    ], 502);
}
