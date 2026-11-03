<?php
require __DIR__.'/_common.php';
try {
 $user=me(); if ($_SERVER['REQUEST_METHOD']==='GET') { $d=$_GET; } else { writeOnly(); $d=payload(); }
 $id=filter_var($d['note_id']??null,FILTER_VALIDATE_INT); if (!$id) out(['success'=>false,'message'=>'note_id không hợp lệ'],400);
 $n=note($pdo,$id,$user); if ((int)$n['owner_id']!==$user) out(['success'=>false,'message'=>'Chỉ chủ sở hữu được chia sẻ'],403);
 if ($_SERVER['REQUEST_METHOD']==='GET') {
  $s=$pdo->prepare('SELECT u.email, ns.permission FROM note_shares ns JOIN users u ON ns.recipient_id=u.id WHERE ns.note_id=? AND ns.revoked_at IS NULL ORDER BY u.email');
  $s->execute([$id]); out(['success'=>true,'data'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
 }
 $shares=$d['shares']??null; if (!is_array($shares) || count($shares)>50) out(['success'=>false,'message'=>'Danh sách chia sẻ không hợp lệ (tối đa 50)'],422);
 $pdo->beginTransaction();
 $find=$pdo->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1');
 $existing=$pdo->prepare('SELECT id FROM note_shares WHERE note_id=? AND recipient_id=? ORDER BY id DESC LIMIT 1');
 $update=$pdo->prepare('UPDATE note_shares SET permission=?, revoked_at=NULL, updated_at=NOW() WHERE id=?');
 $insert=$pdo->prepare('INSERT INTO note_shares (note_id,recipient_id,permission) VALUES (?,?,?)');
 $revoke=$pdo->prepare('UPDATE note_shares SET revoked_at=NOW() WHERE note_id=? AND recipient_id=? AND revoked_at IS NULL');
 $seen=[];
 foreach($shares as $entry){
  $email=trim((string)($entry['email']??'')); $permission=(string)($entry['permission']??'');
  if (!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($permission,['read','edit'],true)) { $pdo->rollBack(); out(['success'=>false,'message'=>'Email hoặc quyền không hợp lệ'],422); }
  $find->execute([$email]); $recipient=$find->fetchColumn();
  if (!$recipient || (int)$recipient===$user) { $pdo->rollBack(); out(['success'=>false,'message'=>'Người nhận chưa đăng ký hoặc là chính bạn: '.$email],422); }
  if (isset($seen[$recipient])) continue; $seen[$recipient]=true;
  $existing->execute([$id,$recipient]); $shareId=$existing->fetchColumn();
  if ($shareId) $update->execute([$permission,$shareId]); else $insert->execute([$id,$recipient,$permission]);
 }
 // Synchronize access list: omitted entries are revoked
 $s=$pdo->prepare('SELECT recipient_id FROM note_shares WHERE note_id=? AND revoked_at IS NULL'); $s->execute([$id]);
 foreach($s->fetchAll(PDO::FETCH_COLUMN) as $recipient) if (!isset($seen[$recipient])) $revoke->execute([$id,$recipient]);
 $pdo->commit(); out(['success'=>true,'message'=>'Đã cập nhật danh sách chia sẻ']);
} catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();internalError($e);}
