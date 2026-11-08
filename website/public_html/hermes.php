<?php
require_once __DIR__.'/../private/bootstrap.php';
require_once __DIR__.'/../private/TeacherClasses.php';
$user=require_user(); $teachers=class_teacher_directory(); $cfg=settings();
?>
<!doctype html><html lang="bn"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?=htmlspecialchars($_SESSION['csrf'],ENT_QUOTES)?>">
<title>IELTS AI Tutor</title><link rel="stylesheet" href="assets/style.css">
<main class="shell"><section class="card">
<a href="index.php">IELTS Practice Lab</a><h1>IELTS AI Tutor</h1>
<?php if (!empty($cfg['teacher_profile_approved']) && is_file(__DIR__.'/teacher-avatar.jpg')): ?>
<img src="teacher-avatar.jpg" width="96" alt="অনুমোদিত শিক্ষকের ছবি"><?php endif; ?>
<p>এটি AI শিক্ষক। ভয়েস চালু করলে ব্রাউজারের speech service তোমার কথা প্রক্রিয়া করতে পারে।</p>
<select id="language" aria-label="Language"><option value="bn-BD">বাংলা</option><option value="en-GB">English</option></select>
<?php if (is_platform_admin($user)): ?><p><a href="admin/">অ্যাডমিন সেকশন</a></p><?php endif; ?>
<?php if (isset($teachers[$user])): ?><p><a href="teacher-classes.php">আমার YouTube ক্লাস যোগ করুন</a></p><?php endif; ?>
<label>ক্লাস থেকে শিখুন <select id="classTeacher"><option value="0">সাধারণ টিউটর</option>
<?php foreach ($teachers as $id => $name): ?><option value="<?=$id?>"><?=htmlspecialchars((string)$name,ENT_QUOTES)?></option><?php endforeach; ?></select></label>
<label><input type="checkbox" id="research"> ওয়েব গবেষণা (শুধু প্রশ্নটি সার্চে যাবে)</label>
<label><input type="checkbox" id="teacher" <?=empty($cfg['teacher_voice_approved'])?'disabled':''?>> অনুমোদিত শিক্ষক-কণ্ঠ (উত্তরটি ভয়েস সেবায় যাবে)</label>
<div id="chat" role="log"></div><p id="status" role="status"></p>
<form id="form"><label>প্রশ্ন <input id="question" maxlength="1000" required></label><button id="send">পাঠাও</button></form>
<button id="start">ভয়েস চ্যাট শুরু</button> <button id="stop">থামাও</button>
</section></main><script src="assets/hermes-voice.js"></script></html>
