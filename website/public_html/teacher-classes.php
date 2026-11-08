<?php
declare(strict_types=1);
require_once __DIR__.'/../private/bootstrap.php';
require_once __DIR__.'/../private/TeacherClasses.php';
require_once __DIR__.'/../private/RateLimiter.php';
$user=require_user(); $cfg=settings();
if (!in_array($user,class_teacher_ids(),true)) reply(['error'=>'Teacher access required'],403);
function esc(string $s): string { return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))) reply(['error'=>'Refresh this page'],403);
    try {
        $action=$_POST['action']??'';
        if ($action==='analyze') {
            $title=trim((string)($_POST['title']??'')); $url=trim((string)($_POST['url']??''));
            if (!isset($_POST['consent']) || !$title || mb_strlen($title)>180 || strlen($url)>2048) throw new DomainException('শিরোনাম, লিংক ও অনুমতি প্রয়োজন।');
            throttle('class-import',(string)$user,8,86400);
            $base=rtrim((string)($cfg['hermes_backup_url']??''),'/');
            $key=$cfg['class_import_key']??'';
            if (parse_url($base,PHP_URL_SCHEME)!=='https' || strlen($key)<32) throw new DomainException('প্রথমে ভিডিও বিশ্লেষণ সেবা কনফিগার করুন।');
            $ch=curl_init($base.'/teacher/class');
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>120,
              CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$key],CURLOPT_POSTFIELDS=>json_encode(['url'=>$url],JSON_THROW_ON_ERROR)]);
            $raw=curl_exec($ch); $status=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
            if ($status!==200 || !is_string($raw)) throw new DomainException('বিশ্লেষণ হয়নি। পাবলিক ক্লাসের লিংক ও ভিডিও সেবার সেটিংস যাচাই করুন।');
            $result=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
            $notes=json_encode($result['notes'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $st=db()->prepare('INSERT INTO hermes_teacher_classes(teacher_id,video_id,title,draft) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE draft=VALUES(draft),revision=revision+1');
            $st->execute([$user,$result['video_id'],$title,$notes]);
        } else {
            $id=(int)($_POST['id']??0); $revision=(int)($_POST['revision']??0);
            if ($action==='approve') {
                $st=db()->prepare('SELECT draft FROM hermes_teacher_classes WHERE id=? AND teacher_id=? AND revision=?');
                $st->execute([$id,$user,$revision]); $draft=$st->fetchColumn();
                if (!$draft) throw new DomainException('খসড়া বদলেছে। আবার দেখুন।');
                $selection=(array)($_POST['keep']??[]);
                $notes=array_values(array_intersect_key(json_decode($draft,true,32,JSON_THROW_ON_ERROR),array_flip($selection)));
                if (!$notes) throw new DomainException('অন্তত একটি নোট বাছাই করুন।');
                $sql='UPDATE hermes_teacher_classes SET approved=?,revision=revision+1 WHERE id=? AND teacher_id=? AND revision=?';
                $params=[json_encode($notes,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$id,$user,$revision];
            } elseif ($action==='remove') {
                $sql='DELETE FROM hermes_teacher_classes WHERE id=? AND teacher_id=? AND revision=?'; $params=[$id,$user,$revision];
            } else throw new DomainException('অজানা অনুরোধ।');
            $st=db()->prepare($sql); $st->execute($params);
            if (!$st->rowCount()) throw new DomainException('ক্লাস বদলেছে। আবার দেখুন।');
        }
        header('Location: teacher-classes.php',true,303); exit;
    } catch (DomainException $e) { $error=$e->getMessage(); }
      catch (Throwable $e) { $error='কাজটি শেষ হয়নি। সার্ভারের সেটিংস ও ডেটাবেস যাচাই করুন।'; }
}
$st=db()->prepare('SELECT * FROM hermes_teacher_classes WHERE teacher_id=? ORDER BY id DESC'); $st->execute([$user]);
?>
<!doctype html><html lang="bn"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>আমার YouTube ক্লাস</title>
<main class="shell"><a href="hermes.php">AI Tutor</a><h1>আমার YouTube ক্লাস</h1>
<?php if ($error): ?><p role="alert"><?=esc($error)?></p><?php endif; ?>
<form method="post" class="card"><input type="hidden" name="csrf" value="<?=esc($_SESSION['csrf'])?>"><input type="hidden" name="action" value="analyze">
<label>ক্লাসের নাম <input name="title" required maxlength="180"></label>
<label>পাবলিক YouTube লিংক <input type="url" name="url" required maxlength="2048"></label>
<label><input type="checkbox" required name="consent"> এই ক্লাসের ভিডিও ও অডিও AI দিয়ে বিশ্লেষণ ও অনুমোদিত নোট শিক্ষার্থীদের শেখাতে ব্যবহারের অনুমতি আমার আছে।</label>
<button>খসড়া তৈরি করুন</button></form>
<?php foreach ($st as $c): ?><form method="post" class="card"><h2><?=esc($c['title'])?></h2>
<p><?= $c['approved']===null?'খসড়া':'অনুমোদিত নোট টিউটরে সক্রিয়' ?></p>
<input type="hidden" name="csrf" value="<?=esc($_SESSION['csrf'])?>"><input type="hidden" name="revision" value="<?=intval($c['revision'])?>"><input type="hidden" name="id" value="<?=intval($c['id'])?>">
<?php foreach(json_decode($c['draft'],true) as $i=>$n): $t=(int)$n['second']; ?>
<p><a href="https://youtu.be/<?=esc($c['video_id'])?>?t=<?=$t?>" target="_blank" rel="noopener"><?=sprintf('%d:%02d',intdiv($t,60),$t%60)?></a>
<label><input type="checkbox" name="keep[]" value="<?=(int)$i?>"> <?=esc($n['lesson'].' '.$n['method'])?></label></p><?php endforeach; ?>
<button name="action" value="approve">বাছাই করা নোট অনুমোদন করুন</button> <button name="action" value="remove">ক্লাস সরান</button></form><?php endforeach; ?></main></html>
