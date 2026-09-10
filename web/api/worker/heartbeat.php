<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized token format"]);
    exit;
}

$token = $matches[1];
$stmt = $pdo->prepare("SELECT id FROM workers WHERE token_hash = ?");
$stmt->execute([hash('sha256', $token)]);
$worker = $stmt->fetch();

if (!$worker) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid worker token"]);
    exit;
}

$workerId = $worker['id'];

try {
    $stmt = $pdo->prepare("UPDATE workers SET status = 'online', last_heartbeat = NOW() WHERE id = ?");
    $stmt->execute([$workerId]);
    
    echo json_encode(["success" => true, "message" => "Heartbeat recorded"]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database heartbeat error"]);
}