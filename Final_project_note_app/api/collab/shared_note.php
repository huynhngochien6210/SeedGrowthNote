<?php
require __DIR__.'/_common.php';
try { $user=me(); $id=filter_input(INPUT_GET,'note_id',FILTER_VALIDATE_INT); if (!$id) out(['success'=>false,'message'=>'Thiếu note_id'],400); $n=access($pdo,$id,$user);
 out(['success'=>true,'data'=>['id'=>$n['id'],'title'=>$n['title'],'content'=>$n['content'],'permission'=>$n['access_level'],'updated_at'=>$n['updated_at']]]);
} catch(Throwable $e){internalError($e);}
