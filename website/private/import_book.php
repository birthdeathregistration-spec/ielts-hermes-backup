<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__.'/bootstrap.php';
$db = db();
$meta = json_decode((string)fgets(STDIN), true, 16, JSON_THROW_ON_ERROR);
if (!preg_match('/^[a-f0-9]{64}$/D', $meta['sha']) || mb_strlen($meta['title']) > 240) exit(1);
$db->beginTransaction();
try {
    $db->prepare('INSERT INTO hermes_books (sha,title) VALUES (?,?) ON DUPLICATE KEY UPDATE title=?')
       ->execute([$meta['sha'],$meta['title'],$meta['title']]);
    $db->prepare('DELETE FROM hermes_passages WHERE book_sha=?')->execute([$meta['sha']]);
    $insert=$db->prepare('INSERT INTO hermes_passages (book_sha,page_no,passage) VALUES (?,?,?)');
    $count=0; $complete=false;
    while (($line=fgets(STDIN))!==false) {
        $row=json_decode($line,true,16,JSON_THROW_ON_ERROR);
        if (($row['complete']??false)===true) { $complete=true; break; }
        if (!is_int($row['page']) || $row['page']<1 || !is_string($row['text'])) throw new RuntimeException('Invalid passage');
        $insert->execute([$meta['sha'],$row['page'],$row['text']]); $count++;
    }
    if (!$complete || !$count) throw new RuntimeException('Incomplete or unreadable book');
    $db->commit();
    echo "$count passages indexed\n";
} catch (Throwable $error) { $db->rollBack(); throw $error; }
