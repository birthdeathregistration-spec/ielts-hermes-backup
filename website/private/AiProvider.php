<?php
declare(strict_types=1);
require_once __DIR__.'/GeminiClient.php';

function ai_provider_enabled(): bool {
    return (string)(settings()['ai_provider'] ?? 'none') === 'gemini';
}

function gemini_chat(string $system,string $message,int $maxOutput=1200): string {
    if (!ai_provider_enabled()) throw new RuntimeException('AI provider disabled.');
    $key=trim((string)(settings()['gemini_api_key'] ?? ''));
    $model=(string)(settings()['gemini_model'] ?? 'gemini-3.5-flash-lite');
    if ($key==='' || !preg_match('/^[A-Za-z0-9._-]+$/',$model)) throw new RuntimeException('Configure model and key.');
    $body=[
      'systemInstruction'=>['parts'=>[['text'=>$system]]],
      'contents'=>[['role'=>'user','parts'=>[['text'=>$message]]]],
      'generationConfig'=>['maxOutputTokens'=>$maxOutput],
    ];
    $ch=curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent');
    curl_setopt_array($ch,[
      CURLOPT_POST=>true,
      CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-goog-api-key: '.$key],
      CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
      CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>70,
    ]);
    $raw=curl_exec($ch);
    $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlError=curl_errno($ch);
    $error=curl_error($ch);
    curl_close($ch);
    if (backup_eligible($status, $curlError) && !empty(settings()['hermes_backup_url'])) {
        return mb_substr(hermes_backup($system, $message, $maxOutput), 0, 6500);
    }
    if ($raw===false || $status<200 || $status>=300) throw new RuntimeException('AI request failed HTTP '.$status.' '.$error);
    $json=json_decode($raw,true);
    $out='';
    foreach (($json['candidates'][0]['content']['parts'] ?? []) as $part) $out.=$part['text'] ?? '';
    if (trim($out)==='') throw new RuntimeException('Empty AI response.');
    return mb_substr($out,0,6500);
}
