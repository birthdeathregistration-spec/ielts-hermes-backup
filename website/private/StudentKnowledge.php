<?php
declare(strict_types=1);
require_once __DIR__.'/DocumentIngest.php';

/** Only retrieve chunks belonging to this authenticated user. This query is an ACL boundary. */
function retrieve_my_document_chunks(int $userId, string $query, ?int $specificId=null, int $limit=5, bool $forExternalAi=false): array {
    $sql='SELECT d.id AS document_id,d.title,c.chunk_no,c.body_text
          FROM student_document_chunks c JOIN student_documents d ON d.id=c.document_id
          WHERE d.owner_user_id=? AND d.indexing_status=\'indexed\'';
    $params=[$userId];
    if ($forExternalAi) $sql.=' AND d.external_ai_consent=1';
    if ($specificId!==null) { $sql.=' AND d.id=?'; $params[]=$specificId; }
    // Limit the candidate pool on shared hosting. Advanced vector search comes later.
    $sql.=' ORDER BY d.id DESC,c.chunk_no ASC LIMIT 900';
    $st=db()->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
    $tokens=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower($query),-1,PREG_SPLIT_NO_EMPTY)?:[];
    $tokens=array_slice(array_values(array_unique(array_filter($tokens,fn($v)=>mb_strlen($v)>=3))),0,20);
    $matched=[];
    foreach ($rows as $row) {
        $hay=mb_strtolower($row['title'].' '.$row['body_text']); $score=0;
        foreach ($tokens as $term) {
            if (mb_stripos($hay,$term)!==false) $score++;
            if (mb_stripos(mb_strtolower($row['title']),$term)!==false) $score+=3;
        }
        if ($score>0 || !$tokens) {
            $matched[]=['document_id'=>(int)$row['document_id'],'title'=>$row['title'],
                'chunk_no'=>(int)$row['chunk_no'],'text'=>mb_substr($row['body_text'],0,1500),
                'score'=>$score];
        }
    }
    usort($matched,fn($a,$b)=>$b['score']<=>$a['score'] ?: $a['document_id']<=>$b['document_id']);
    return array_slice($matched,0,$limit);
}

/** Restrict assistant to IELTS/English, school study, academics, admissions, study-abroad planning. */
function education_scope(string $question): bool {
    $q=mb_strtolower(trim($question));
    // A lightweight prefilter; AI system prompt also enforces this. Broad to avoid rejecting legitimate study distress.
    $patterns=['/ielts|speaking|writing|reading|listening|grammar|vocab|english|essay|exam|test|band|score|study|learn|lesson|school|college|university|student|scholarship|admission|career|course|subject|research|cv|interview|application|sat|duolingo/i',
      '/পড়|পড়|শিক্ষ|ইংরেজি|আইইএলটিএস|পরীক্ষা|ব্যাকরণ|ভোকাব|রাইটিং|স্পিকিং|লিসেনিং|রিডিং|ব্যান্ড|স্কোর|বিশ্ববিদ্যাল|ভর্তি|স্কলারশিপ|ক্যারিয়ার|ক্যারিয়ার|স্টাডি|ভিসা|রুটিন|মনোযোগ|ক্লাস|সময়|সময়|প্রস্তুতি|দুশ্চিন্তা|চাপ|নোট|বই/u'];
    foreach ($patterns as $pattern) if (preg_match($pattern,$q)) return true;
    return false;
}
