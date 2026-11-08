<?php
declare(strict_types=1);
require_once __DIR__.'/../../private/bootstrap.php';
require_once __DIR__.'/../../private/DocumentIngest.php';
require_once __DIR__.'/../../private/RateLimiter.php';
$userId=require_user();

if (($_SERVER['REQUEST_METHOD']??'')==='GET') {
    try {
        $q=db()->prepare('SELECT id,title,original_filename,file_kind,indexing_status,indexed_chars,was_truncated,external_ai_consent,created_at FROM student_documents WHERE owner_user_id=? ORDER BY id DESC LIMIT 30');
        $q->execute([$userId]); reply(['documents'=>$q->fetchAll(),'max_upload_mb'=>8,'private_by_default'=>true]);
    } catch (Throwable $e) { fail_server($e); }
}
post_only(); check_csrf();
try {
    throttle('doc-action',(string)$userId,25,86400);
    throttle_ip('doc-action-ip',90,86400);
    if (isset($_FILES['file'])) {
        $input=$_FILES['file'];
        if (!is_array($input) || $input['error']!==UPLOAD_ERR_OK || !is_uploaded_file((string)$input['tmp_name'])) {
            reply(['error'=>'Upload failed. Check cPanel PHP upload_max_filesize and post_max_size.'],422);
        }
        $bytes=(int)$input['size'];
        if ($bytes<20 || $bytes>8*1024*1024) reply(['error'=>'Allowed file size: 20 bytes to 8 MB. Split large books into parts.'],413);
        if (empty($_POST['rights_confirmed']) || $_POST['rights_confirmed']!=='yes') reply(['error'=>'Confirm you have the right to upload and process this material.'],422);
        $count=db()->prepare('SELECT COUNT(*) FROM student_documents WHERE owner_user_id=?'); $count->execute([$userId]);
        if ((int)$count->fetchColumn()>=20) reply(['error'=>'Up to 20 private documents per account in this MVP. Delete old items first.'],429);
        $tmp=(string)$input['tmp_name'];
        $kind=document_type($tmp);
        if ($kind===null) reply(['error'=>'Only genuine PDF, JPG, PNG and UTF-8 TXT are accepted.'],415);
        $rawName=(string)$input['name'];
        $filename=mb_substr(preg_replace('/[\x00-\x1F\\\/]+/u','_',basename($rawName))??'upload',0,240);
        $title=trim((string)($_POST['title']??pathinfo($filename,PATHINFO_FILENAME)));
        if ($title===''||mb_strlen($title)>200) reply(['error'=>'Title must be 1–200 characters.'],422);
        $aiConsent=($_POST['external_ai_consent']??'')==='yes';
        $result=extract_document_text($tmp,$kind,settings());
        $sha=hash_file('sha256',$tmp);
        $pdo=db(); $pdo->beginTransaction();
        $sql='INSERT INTO student_documents (owner_user_id,title,original_filename,file_kind,sha256,bytes_size,rights_confirmed,external_ai_consent,indexing_status,indexed_chars,was_truncated) VALUES (?,?,?,?,?,?,1,?,?,?,?)';
        $st=$pdo->prepare($sql);
        $st->execute([$userId,$title,$filename,$kind,$sha,$bytes,(int)$aiConsent,$result['status'],$result['chars'],(int)$result['truncated']]);
        $docId=(int)$pdo->lastInsertId();
        $chunkStmt=$pdo->prepare('INSERT INTO student_document_chunks (document_id,chunk_no,body_text) VALUES (?,?,?)');
        foreach ($result['chunks'] as $i=>$text) $chunkStmt->execute([$docId,$i+1,$text]);
        $pdo->commit();
        // Uploaded temporary file is automatically removed by PHP; nothing saved in public_html.
        reply(['id'=>$docId,'status'=>$result['status'],'chars'=>$result['chars'],'truncated'=>$result['truncated'],'note'=>$result['note']]);
    }
    $raw=file_get_contents('php://input',false,null,0,240_001);
    if ($raw===false || strlen($raw)>240_000) reply(['error'=>'Text submission too large.'],413);
    $data=json_decode($raw,true);
    if (!is_array($data)) reply(['error'=>'Invalid JSON.'],400);
    $action=(string)($data['action']??'');
    $docId=filter_var($data['document_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if (!$docId) reply(['error'=>'Valid document_id required.'],422);
    $stmt=db()->prepare('SELECT id,indexing_status FROM student_documents WHERE id=? AND owner_user_id=? LIMIT 1');
    $stmt->execute([$docId,$userId]); $doc=$stmt->fetch();
    if (!$doc) reply(['error'=>'Document not found.'],404);
    if ($action==='delete') {
        $st=db()->prepare('DELETE FROM student_documents WHERE id=? AND owner_user_id=?');
        $st->execute([$docId,$userId]); reply(['deleted'=>true]);
    }
    if ($action==='paste_text') {
        if ($doc['indexing_status']!=='needs_text') reply(['error'=>'This document is already indexed. Delete/reupload to replace.'],409);
        $text=(string)($data['text']??'');
        if (mb_strlen($text)<60 || mb_strlen($text)>90_000) reply(['error'=>'Paste 60–90,000 characters.'],422);
        $result=split_document_text($text);
        if (!$result['chunks']) reply(['error'=>'No readable text found.'],422);
        $pdo=db(); $pdo->beginTransaction();
        $st=$pdo->prepare('INSERT INTO student_document_chunks (document_id,chunk_no,body_text) VALUES (?,?,?)');
        foreach ($result['chunks'] as $i=>$chunk) $st->execute([$docId,$i+1,$chunk]);
        $st=$pdo->prepare('UPDATE student_documents SET indexing_status=\'indexed\',indexed_chars=?,was_truncated=? WHERE id=? AND owner_user_id=?');
        $st->execute([$result['chars'],(int)$result['truncated'],$docId,$userId]);
        $pdo->commit(); reply(['id'=>$docId,'status'=>'indexed','chars'=>$result['chars']]);
    }
    reply(['error'=>'Unknown document action.'],422);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fail_server($e);
}
