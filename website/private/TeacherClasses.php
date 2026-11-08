<?php
declare(strict_types=1);
function class_teacher_ids(): array {
    return array_values(array_filter(array_map('intval', (array)(settings()['class_teacher_ids'] ?? [])), fn($id)=>$id>0));
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
