<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code  = $input['code'] ?? '';
$rules = $input['rules'] ?? '';

if ($code === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No code provided']);
    exit;
}

/* ---------- 1. Write source file ---------- */
$srcFile = sys_get_temp_dir() . '/' . uniqid('src_', true) . '.java';
file_put_contents($srcFile, $code);

/* ---------- 2. Write rules file ---------- */
if ($rules !== '' && stripos($rules, '<?xml') === 0) {
    $rulesFile = sys_get_temp_dir() . '/' . uniqid('rules_', true) . '.xml';
    file_put_contents($rulesFile, $rules);
} else {
    // fallback to the tiny rule set shipped with the image
    $rulesFile = '/var/www/html/fallback-rules.xml';
}

/* ---------- 3. Verify PMD is reachable ---------- */
$ver = shell_exec('pmd --version 2>&1');
if (trim($ver) === '') {
    @unlink($srcFile);
    @unlink($rulesFile ?? '');
    http_response_code(500);
    echo json_encode(['error' => 'PMD binary not found']);
    exit;
}

/* ---------- 4. Build the exact command (full path) ---------- */
$cmd = sprintf(
    '/usr/local/bin/pmd check -d %s -R %s -f json 2>&1',
    escapeshellarg($srcFile),
    escapeshellarg($rulesFile)
);

/* ---------- 5. Log the command (visible in Render logs) ---------- */
error_log("PMD COMMAND: $cmd");

/* ---------- 6. Execute PMD ---------- */
$pmdRaw = shell_exec($cmd);
error_log("PMD RAW OUTPUT: " . ($pmdRaw ?: '(empty)'));

/* ---------- 7. Clean up temporary files ---------- */
@unlink($srcFile);
if (isset($rulesFile) && strpos($rulesFile, sys_get_temp_dir()) === 0) {
    @unlink($rulesFile);
}

/* ---------- 8. Extract JSON from raw output (strip warnings) ---------- */
$jsonStart = strpos($pmdRaw, '{');
if ($jsonStart === false) {
    http_response_code(500);
    echo json_encode(['error' => 'No JSON found in PMD output', 'raw' => $pmdRaw]);
    exit;
}

$cleanJson = substr($pmdRaw, $jsonStart);
$pmdJson = json_decode($cleanJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'JSON decode failed: ' . json_last_error_msg(),
        'raw' => $pmdRaw,
        'clean' => $cleanJson
    ]);
    exit;
}

/* ---------- 9. Return violations ---------- */
$violations = $pmdJson['files'][0]['violations'] ?? [];
echo json_encode([
    'violations' => $violations,
    'debug' => 'PMD executed and parsed successfully'
]);
