<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/database.php';
function out(array $data, int $status = 200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function me(): int { $id = (int)($_SESSION['user_id'] ?? 0); if (!$id) out(['success'=>false,'message'=>'Vui lòng đăng nhập'],401); return $id; }
function payload(): array { $v = json_decode(file_get_contents('php://input'), true); if (!is_array($v)) out(['success'=>false,'message'=>'JSON không hợp lệ'],400); return $v; }
function writeOnly(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['success'=>false,'message'=>'Method not allowed'],405); $t=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??''); if (!$t || !hash_equals((string)($_SESSION['sgn_csrf']??''),$t)) out(['success'=>false,'message'=>'CSRF token không hợp lệ; hãy tải lại trang'],403); }
function note(PDO $pdo,int $id,int $user): array {
  $s=$pdo->prepare('SELECT n.*, CASE WHEN n.owner_id=? THEN "owner" ELSE (SELECT ns.permission FROM note_shares ns WHERE ns.note_id=n.id AND ns.recipient_id=? AND ns.revoked_at IS NULL ORDER BY ns.id DESC LIMIT 1) END AS access_level FROM notes n WHERE n.id=? LIMIT 1');
  $s->execute([$user,$user,$id]); $n=$s->fetch(PDO::FETCH_ASSOC);
  if (!$n || !$n['access_level']) out(['success'=>false,'message'=>'Không tìm thấy ghi chú hoặc không có quyền'],404);
  return $n;
}
function unlockKey(int $id): string {return 'sgn_note_unlock_'.$id;}
function unlocked(array $n): bool {return empty($n['is_password_protected']) || (($_SESSION[unlockKey((int)$n['id'])]??'') === hash('sha256', (string)$n['note_password_hash']));}
function needsUnlock(array $n): void { if (!unlocked($n)) out(['success'=>false,'code'=>'NOTE_LOCKED','message'=>'Ghi chú yêu cầu mật khẩu'],423); }
function access(PDO $pdo,int $id,int $user,string $needed='read'): array { $n=note($pdo,$id,$user); if ($needed==='edit' && !in_array($n['access_level'],['owner','edit'],true)) out(['success'=>false,'message'=>'Bạn chỉ có quyền xem'],403); needsUnlock($n); return $n; }
function internalError(Throwable $e): never { error_log('Collab API: '.$e->getMessage()); out(['success'=>false,'message'=>'Có lỗi máy chủ'],500); }
