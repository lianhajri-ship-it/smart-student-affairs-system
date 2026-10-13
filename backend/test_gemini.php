<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireManager();

$apiKey        = getSetting('gemini_api_key', '');
$chatModel     = getSetting('gemini_model', 'gemini-2.5-flash');
$embedModel    = getSetting('gemini_embedding_model', 'gemini-embedding-001');

function callUrl(string $url, array $data): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    $error  = curl_error($ch);
    $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($error) return ['curl_error' => $error, 'http_code' => $http];
    $decoded = json_decode($result, true);
    return ['http_code' => $http, 'response' => is_array($decoded) ? $decoded : $result];
}

function callGet(string $url): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    $error  = curl_error($ch);
    $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($error) return ['curl_error' => $error, 'http_code' => $http];
    $decoded = json_decode($result, true);
    return ['http_code' => $http, 'response' => is_array($decoded) ? $decoded : $result];
}

// 1. List models
$listResult = callGet("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}&pageSize=50");

// 2. Test embedding v1
$embedV1 = callUrl(
    "https://generativelanguage.googleapis.com/v1/models/{$embedModel}:embedContent?key={$apiKey}",
    ['model' => "models/{$embedModel}", 'content' => ['parts' => [['text' => 'test']]]]
);

// 3. Test embedding v1beta
$embedV1beta = callUrl(
    "https://generativelanguage.googleapis.com/v1beta/models/{$embedModel}:embedContent?key={$apiKey}",
    ['model' => "models/{$embedModel}", 'content' => ['parts' => [['text' => 'test']]]]
);

// 4. Test chat model
$chatResult = callUrl(
    "https://generativelanguage.googleapis.com/v1beta/models/{$chatModel}:generateContent?key={$apiKey}",
    ['contents' => [['parts' => [['text' => 'say hello in one word']]]]]
);

// 5. Test embedding-001 as fallback
$embedOld = callUrl(
    "https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent?key={$apiKey}",
    ['model' => 'models/embedding-001', 'content' => ['parts' => [['text' => 'test']]]]
);

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>اختبار Gemini API</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width:900px">
    <h3 class="mb-4">🔬 تشخيص Gemini API</h3>

    <div class="card mb-3">
        <div class="card-header fw-bold">الإعدادات الحالية</div>
        <div class="card-body">
            <p><b>API Key:</b> <?= empty($apiKey) ? '<span class="text-danger">غير مضبوط</span>' : substr($apiKey,0,8).'...' ?></p>
            <p><b>Chat Model:</b> <?= htmlspecialchars($chatModel) ?></p>
            <p><b>Embedding Model:</b> <?= htmlspecialchars($embedModel) ?></p>
        </div>
    </div>

    <?php
    $tests = [
        ['label' => 'Chat ('.$chatModel.' — v1beta)', 'result' => $chatResult,  'check' => isset($chatResult['response']['candidates'])],
        ['label' => 'Embedding v1 ('.$embedModel.')',  'result' => $embedV1,     'check' => isset($embedV1['response']['embedding'])],
        ['label' => 'Embedding v1beta ('.$embedModel.')','result'=>$embedV1beta, 'check' => isset($embedV1beta['response']['embedding'])],
        ['label' => 'Embedding v1beta (embedding-001 — fallback)', 'result' => $embedOld, 'check' => isset($embedOld['response']['embedding'])],
    ];
    foreach ($tests as $t):
        $ok = $t['check'];
    ?>
    <div class="card mb-3 border-<?= $ok ? 'success' : 'danger' ?>">
        <div class="card-header bg-<?= $ok ? 'success' : 'danger' ?> text-white fw-bold">
            <?= $ok ? '✅' : '❌' ?> <?= htmlspecialchars($t['label']) ?>
            <span class="float-end small">HTTP <?= $t['result']['http_code'] ?? '?' ?></span>
        </div>
        <div class="card-body">
            <pre style="font-size:.8rem;max-height:200px;overflow:auto"><?= htmlspecialchars(json_encode($t['result']['response'] ?? $t['result'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></pre>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="card mb-3">
        <div class="card-header fw-bold">النماذج المتاحة لمفتاحك</div>
        <div class="card-body">
            <?php if (isset($listResult['response']['models'])): ?>
            <ul class="mb-0 small">
                <?php foreach ($listResult['response']['models'] as $m): ?>
                    <li><b><?= htmlspecialchars($m['name'] ?? '') ?></b>
                        — <?= htmlspecialchars(implode(', ', $m['supportedGenerationMethods'] ?? [])) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <pre style="font-size:.8rem"><?= htmlspecialchars(json_encode($listResult, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></pre>
            <?php endif; ?>
        </div>
    </div>

    <a href="/STD/manager/settings.php" class="btn btn-secondary">← الإعدادات</a>
</div>
</body>
</html>
