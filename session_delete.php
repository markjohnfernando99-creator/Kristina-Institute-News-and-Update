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

$conversation_id = (int)($payload['conversation_id'] ?? 0);
$user_id = (int)($payload['user_id'] ?? 0);

if($conversation_id <= 0 || $user_id <= 0){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'conversation_id and user_id required']);
  exit;
}

$stmt = $pdo->prepare('DELETE FROM chat_sessions WHERE id=:cid AND user_id=:uid');
$stmt->execute([':cid'=>$conversation_id, ':uid'=>$user_id]);

echo json_encode(['ok'=>true]);

