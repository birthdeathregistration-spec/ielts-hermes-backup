<?php
declare(strict_types=1);
function is_platform_admin(int $user): bool {
    return $user > 0 && in_array($user, array_map('intval', settings()['admin_user_ids'] ?? []), true);
}
function class_teacher_directory(): array {
    $config = settings();
    $teachers = [];
    foreach ($config['class_teacher_ids'] ?? [] as $id) {
        if ((int)$id > 0) $teachers[(int)$id] = $config['class_teacher_names'][$id] ?? "শিক্ষক $id";
    }
    try {
        $rows = db()->query('SELECT a.user_id, a.enabled, u.display_name FROM hermes_teacher_access a JOIN users u ON u.id=a.user_id');
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') return $teachers; // Before migration, retain existing config permissions.
        throw $e;
    }
    foreach ($rows as $row) {
        $id = (int)$row['user_id'];
        if ($row['enabled']) $teachers[$id] = $row['display_name'];
        else unset($teachers[$id]);
    }
    return $teachers;
}
function class_teacher_ids(): array {
    return array_keys(class_teacher_directory());
}
function approved_class_context(int $teacher, string $question): array {
    if (!in_array($teacher,class_teacher_ids(),true)) return [];
    $st=db()->prepare('SELECT title,video_id,approved FROM hermes_teacher_classes WHERE teacher_id=? AND approved IS NOT NULL ORDER BY id DESC');
    $st->execute([$teacher]); $rows=[];
    $tokens=preg_split('/[^\p{L}\p{M}\p{N}]+/u',mb_strtolower($question),-1,PREG_SPLIT_NO_EMPTY)?:[];
    $methods=[];
    while ($class=$st->fetch()) {
        foreach (json_decode($class['approved'],true,32,JSON_THROW_ON_ERROR) as $note) {
            $ref=$class['title'].' https://youtu.be/'.$class['video_id'].'?t='.(int)$note['second'];
            if ($note['method']!=='' && count($methods)<6) $methods[]=$note['method'];
            $score=0; $hay=$class['title'].' '.$note['lesson'];
            foreach (array_unique($tokens) as $t) if (mb_strlen($t)>1 && mb_stripos($hay,$t)!==false) $score++;
            if ($score && $note['lesson']!=='') $rows[]=['score'=>$score,'source'=>$ref,'text'=>$note['lesson']];
        }
    }
    usort($rows,fn($a,$b)=>$b['score']<=>$a['score']);
    return ['methods'=>$methods,'lessons'=>array_slice($rows,0,4)];
}
