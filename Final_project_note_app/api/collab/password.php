<?php
require __DIR__.'/_common.php';
try {
 $user=me(); if ($_SERVER['REQUEST_METHOD']==='GET') {
  $id=filter_input(INPUT_GET,'note_id',FILTER_VALIDATE_INT); if (!$id) out(['success'=>false,'message'=>'Thiếu note_id'],400);
  $n=note($pdo,$id,$user);
  out(['success'=>true,'protected'=>(bool)$n['is_password_protected'],'unlocked'=>unlocked($n),'owner'=>$n['access_level']==='owner']);
 }
 writeOnly(); $d=payload(); $id=filter_var($d['note_id']??null,FILTER_VALIDATE_INT); if (!$id) out(['success'=>false,'message'=>'note_id không hợp lệ'],400);
 $n=note($pdo,$id,$user); $action=(string)($d['action']??'');
 if ($action==='unlock') {
   $password=(string)($d['password']??'');
   if (empty($n['is_password_protected'])) out(['success'=>true,'message'=>'Ghi chú không khóa']);
   // Note: for public-facing production rate-limit attempts at reverse proxy / backend
   if (!password_verify($password,(string)$n['note_password_hash'])) out(['success'=>false,'message'=>'Mật khẩu ghi chú không đúng'],403);
   $_SESSION[unlockKey($id)]=hash('sha256',(string)$n['note_password_hash']);
   out(['success'=>true,'message'=>'Đã mở khóa']);
 }
 if ($n['access_level']!=='owner') out(['success'=>false,'message'=>'Chỉ chủ sở hữu đổi được mật khẩu'],403);
 if (!in_array($action,['enable','disable','change'],true)) out(['success'=>false,'message'=>'Action không hợp lệ'],400);
 $new=(string)($d['new_password']??''); $old=(string)($d['current_password']??'');
 if ($action==='enable' && !empty($n['is_password_protected'])) out(['success'=>false,'message'=>'Ghi chú đã khóa'],409);
 if ($action!=='enable' && empty($n['is_password_protected'])) out(['success'=>false,'message'=>'Ghi chú chưa khóa'],409);
 if ($action!=='enable' && !password_verify($old,(string)$n['note_password_hash'])) out(['success'=>false,'message'=>'Mật khẩu hiện tại không đúng'],403);
 if ($action!=='disable' && (strlen($new)<8 || strlen($new)>200)) out(['success'=>false,'message'=>'Mật khẩu mới phải có 8–200 ký tự'],422);
 $hash=$action==='disable'?null:password_hash($new,PASSWORD_DEFAULT);
 $s=$pdo->prepare('UPDATE notes SET is_password_protected=?, note_password_hash=?, updated_at=NOW() WHERE id=? AND owner_id=?');
 $s->execute([$action==='disable'?0:1,$hash,$id,$user]);
 if ($hash) $_SESSION[unlockKey($id)]=hash('sha256',$hash); else unset($_SESSION[unlockKey($id)]);
 out(['success'=>true,'protected'=>$action!=='disable','message'=>'Đã cập nhật bảo vệ mật khẩu']);
} catch(Throwable $e){internalError($e);}
