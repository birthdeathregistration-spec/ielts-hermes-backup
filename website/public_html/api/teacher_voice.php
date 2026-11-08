<?php
declare(strict_types=1);
require_once __DIR__.'/../../private/bootstrap.php';
require_once __DIR__.'/../../private/RateLimiter.php';
post_only(); check_csrf(); $user=require_user(); $cfg=settings();
if (empty($cfg['teacher_voice_approved']) || empty($cfg['teacher_voice_id']) || empty($cfg['elevenlabs_api_key'])) reply(['error'=>'Teacher voice unavailable'],404);
$text=mb_substr((string)($_SESSION['hermes_voice_text']??''),0,2800);
if ($text==='') reply(['error'=>'No reply to read'],422);
throttle('tutor-audio',(string)$user,40,86400);
$ch=curl_init('https://api.elevenlabs.io/v1/text-to-speech/'.rawurlencode($cfg['teacher_voice_id']));
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>35,
 CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: audio/mpeg','xi-api-key: '.$cfg['elevenlabs_api_key']],
 CURLOPT_POSTFIELDS=>json_encode(['text'=>$text,'model_id'=>'eleven_v3'],JSON_THROW_ON_ERROR)]);
$audio=curl_exec($ch); $status=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
if ($audio===false || $status!==200) reply(['error'=>'Teacher voice unavailable'],503);
header('Content-Type: audio/mpeg'); header('Cache-Control: private, no-store'); echo $audio;
