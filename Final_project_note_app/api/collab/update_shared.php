<?php
require __DIR__.'/_common.php';
try { $user=me(); writeOnly(); $d=payload(); $id=filter_var($d['note_id']??null,FILTER_VALIDATE_INT); if (!$id) out(['success'=>false,'message'=>'Thiếu note_id'],400); $n=access($pdo,$id,$user,'edit');
 if (!array_key_exists('title',$d)||!is_string($d['title'])||trim($d['title'])===''||(function_exists('mb_strlen') ? mb_strlen($d['title']) : strlen($d['title']))>255||!isset($d['content'])||!is_string($d['content'])) out(['success'=>false,'message'=>'Tiêu đề hoặc nội dung không hợp lệ'],422);
 $s=$pdo->prepare('UPDATE notes SET title=?,content=?,updated_at=NOW() WHERE id=?'); $s->execute([trim($d['title']),$d['content'],$id]); out(['success'=>true,'message'=>'Đã lưu ghi chú']);
} catch(Throwable $e){internalError($e);}
