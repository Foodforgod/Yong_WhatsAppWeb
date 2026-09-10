<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['job_id'], $data['status'])) {
    $stmt = $pdo->prepare("UPDATE message_jobs SET status = ?, completed_at = NOW() WHERE id = ?");
    $stmt->execute([$data['status'], $data['job_id']]);
    echo json_encode(["success" => true]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload"]);
}