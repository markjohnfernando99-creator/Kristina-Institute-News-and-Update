<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid JSON payload']);
  exit;
}

$user_id = (int)($payload['user_id'] ?? 0);
if($user_id <= 0){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'user_id required']);
  exit;
}

try {
  $stmt = $pdo->prepare(
    'INSERT INTO chat_sessions (user_id, created_at) VALUES (:uid, NOW())'
  );
  $stmt->execute([':uid'=>$user_id]);
  $cid = (int)$pdo->lastInsertId();

  echo json_encode(['ok'=>true, 'conversation_id'=> (string)$cid]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Failed to create session','details'=>$e->getMessage()]);
}

