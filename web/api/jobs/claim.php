<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized: Missing token"]);
    exit;
}

$rawToken = $matches[1];
$tokenHash = hash('sha256', $rawToken);

$stmt = $pdo->prepare("SELECT id FROM workers WHERE token_hash = ?");
$stmt->execute([$tokenHash]);
$worker = $stmt->fetch();

if (!$worker) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized: Invalid token"]);
    exit;
}

// Find the oldest pending job and mark it as processing
$pdo->beginTransaction();
try {
    $stmt = $pdo->query("SELECT id, recipient_phone, message_body FROM message_jobs WHERE status = 'pending' ORDER BY id ASC LIMIT 1 FOR UPDATE");
    $job = $stmt->fetch();

    if ($job) {
        $updateStmt = $pdo->prepare("UPDATE message_jobs SET status = 'processing', attempts = attempts + 1 WHERE id = ?");
        $updateStmt->execute([$job['id']]);
        
        $pdo->commit();
        echo json_encode(["job" => $job]);
    } else {
        $pdo->commit();
        echo json_encode(["job" => null]);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}