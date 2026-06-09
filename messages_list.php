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

if($conversation_id <= 0){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'conversation_id required']);
  exit;
}

try{
  $stmt = $pdo->prepare(
    'SELECT role, content
     FROM chat_messages
     WHERE conversation_id=:cid
     ORDER BY created_at ASC'
  );
  $stmt->execute([':cid'=>$conversation_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $messages = [];
  foreach($rows as $r){
    $messages[] = [
      'role' => $r['role'] ?? 'user',
      'content' => (string)($r['content'] ?? '')
    ];
  }

  echo json_encode(['ok'=>true,'messages'=>$messages]);
} catch(Throwable $e){
  echo json_encode(['ok'=>true,'messages'=>[]]);
}

