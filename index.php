<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow calls from your WP site
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';
    $rules = $input['rules'] ?? '';

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['error' => 'No code provided']);
        exit;
    }

    // Save code to temp file
    $tempCodeFile = sys_get_temp_dir() . '/' . uniqid() . '.java';
    file_put_contents($tempCodeFile, $code);

    // Save rules to temp XML
    $tempRulesFile = sys_get_temp_dir() . '/' . uniqid() . '.xml';
    file_put_contents($tempRulesFile, $rules ?: 'rulesets/java/quickstart.xml'); // Fallback to basic rules if none provided

    // Run PMD via shell_exec
    $pmdOutput = shell_exec('pmd check -d ' . escapeshellarg($tempCodeFile) . ' -R ' . escapeshellarg($tempRulesFile) . ' -f json 2>&1');

    // Cleanup
    unlink($tempCodeFile);
    unlink($tempRulesFile);

    if (empty($pmdOutput)) {
        echo json_encode(['violations' => []]);
    } else {
        $issues = json_decode($pmdOutput, true);
        $violations = $issues['files'][0]['violations'] ?? [];
        echo json_encode(['violations' => $violations]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
