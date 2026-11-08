<?php
declare(strict_types=1);

function backup_eligible(int $status, int $curlError): bool {
    return in_array($curlError, [6, 7, 28, 52, 55, 56], true)
        || in_array($status, [408, 429, 500, 502, 503, 504], true);
}

function hermes_backup(string $system, string $message, int $limit, bool $research=false, string $language="bn"): string {
    $base = rtrim((string)(settings()['hermes_backup_url'] ?? ''), '/');
    $key = (string)(settings()['hermes_backup_key'] ?? '');
    if (parse_url($base, PHP_URL_SCHEME) !== 'https' || $key === '') {
        throw new RuntimeException('AI temporarily unavailable.');
    }
    $ch = curl_init($base . '/reply');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_POSTFIELDS => json_encode(compact('system', 'message', 'limit', 'research', 'language'), JSON_THROW_ON_ERROR),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $research ? 135 : 60,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $answer = is_string($raw) ? (json_decode($raw, true)['answer'] ?? null) : null;
    if ($status !== 200 || !is_string($answer) || trim($answer) === '') {
        throw new RuntimeException('Backup tutor temporarily unavailable.');
    }
    return $answer;
}
