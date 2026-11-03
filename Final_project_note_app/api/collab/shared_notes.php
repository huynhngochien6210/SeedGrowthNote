<?php
require __DIR__.'/_common.php';
try { $user=me(); if ($_SERVER['REQUEST_METHOD']!=='GET') out(['success'=>false,'message'=>'GET only'],405);
 $s=$pdo->prepare('SELECT n.id, n.title, n.updated_at, n.is_password_protected, u.email AS sharer_email, ns.permission, ns.created_at AS received_at FROM note_shares ns JOIN notes n ON n.id=ns.note_id JOIN users u ON u.id=n.owner_id WHERE ns.recipient_id=? AND ns.revoked_at IS NULL AND ns.id=(SELECT MAX(ns2.id) FROM note_shares ns2 WHERE ns2.note_id=n.id AND ns2.recipient_id=?) ORDER BY ns.updated_at DESC');
 $s->execute([$user,$user]); out(['success'=>true,'data'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
} catch(Throwable $e){internalError($e);}
