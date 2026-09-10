<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Fetch reminders due at the current time
    $stmt = $pdo->prepare("SELECT * FROM message_jobs WHERE status = 'pending' AND scheduled_at <= NOW()");
    $stmt->execute();
    $dueJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "due_jobs_count" => count($dueJobs)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}