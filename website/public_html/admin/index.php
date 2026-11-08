<?php
declare(strict_types=1);
require_once __DIR__.'/../../private/bootstrap.php';
require_once __DIR__.'/../../private/TeacherClasses.php';
header('Cache-Control: no-store');
if (!is_platform_admin(require_user())) reply(['error'=>'Admin access required'], 403);
function esc(string $text): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) reply(['error'=>'Refresh this page'], 403);
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['grant','revoke'], true)) reply(['error'=>'Invalid action'], 422);
    $id = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) reply(['error'=>'Invalid user'], 422);
    $save = db()->prepare('INSERT INTO hermes_teacher_access (user_id,enabled) SELECT id,? FROM users WHERE id=? ON DUPLICATE KEY UPDATE enabled=?');
    $enabled = (int)($action === 'grant');
    $save->execute([$enabled,$id,$enabled]);
    header('Location: ./?user_id='.$id, true, 303);
    exit;
}
$teachers = class_teacher_directory();
$email = trim((string)($_GET['email'] ?? ''));
$selected = null;
if ($email !== '' || isset($_GET['user_id'])) {
    $byEmail = $email !== '';
    $query = db()->prepare('SELECT id,display_name,email FROM users WHERE '.($byEmail ? 'email=?' : 'id=?'));
    $query->execute([$byEmail ? $email : (int)$_GET['user_id']]);
    $selected = $query->fetch();
}
?>
<!doctype html><html lang="bn"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>অ্যাডমিন সেকশন</title><link rel="stylesheet" href="../assets/style.css">
<main class="shell"><a href="../hermes.php">AI টিউটরে ফিরুন</a><h1>অ্যাডমিন সেকশন</h1>
<section class="card"><h2>শিক্ষকের অনুমতি</h2><form><label>নিবন্ধিত ইমেইল <input type="email" name="email" required></label><button>অ্যাকাউন্ট খুঁজুন</button></form>
<?php if ($selected): $id=(int)$selected['id']; $active=isset($teachers[$id]); ?>
<h3><?=esc($selected['display_name'])?></h3><p><?=esc($selected['email'])?> · <?=$active?'শিক্ষকের অনুমতি আছে':'শিক্ষকের অনুমতি নেই'?></p>
<form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['csrf'])?>"><input type="hidden" name="user_id" value="<?=$id?>">
<button name="action" value="<?=$active?'revoke':'grant'?>"><?=$active?'শিক্ষকের অনুমতি তুলে নিন':'শিক্ষকের অনুমতি দিন'?></button></form>
<?php elseif ($selected === false): ?><p>অ্যাকাউন্ট পাওয়া যায়নি।</p><?php endif; ?></section>
<section class="card"><h2>অনুমোদিত শিক্ষক</h2><?php if (!$teachers): ?><p>এখনও কাউকে অনুমতি দেওয়া হয়নি।</p><?php endif; ?>
<ul><?php foreach ($teachers as $id=>$name): ?><li><a href="?user_id=<?=(int)$id?>"><?=esc((string)$name)?></a></li><?php endforeach; ?></ul></section></main></html>
