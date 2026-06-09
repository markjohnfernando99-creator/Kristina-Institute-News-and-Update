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

$stmt = $pdo->prepare(
  'SELECT cs.id, cs.created_at,
          (SELECT content FROM chat_messages cm
            WHERE cm.conversation_id = cs.id AND cm.role="user"
            ORDER BY cm.created_at ASC LIMIT 1) AS first_user_message
   FROM chat_sessions cs
   WHERE cs.user_id = :uid
   ORDER BY cs.created_at DESC
   LIMIT 40'
);
$stmt->execute([':uid'=>$user_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok'=>true,'sessions'=>$sessions]);

