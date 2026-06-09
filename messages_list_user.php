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

try{
  $stmt = $pdo->prepare('SELECT id FROM chat_sessions WHERE user_id=:uid ORDER BY created_at DESC LIMIT 20');
  $stmt->execute([':uid'=>$user_id]);
  $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true,'sessions'=>$sessions]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>'Failed','details'=>$e->getMessage()]);
}

