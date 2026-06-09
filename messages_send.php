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
$conversation_id = (string)($payload['conversation_id'] ?? '');
$message = trim((string)($payload['message'] ?? ''));
$language = strtolower(trim((string)($payload['language'] ?? 'en')));

if ($message === '' || $conversation_id === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'conversation_id and message are required']);
  exit;
}

// If logged in, validate ownership of conversation
if ($user_id > 0) {
  try {
    $stmt = $pdo->prepare('SELECT id FROM chat_sessions WHERE id=:cid AND user_id=:uid LIMIT 1');
    $stmt->execute([':cid'=>(int)$conversation_id, ':uid'=>$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row){
      http_response_code(403);
      echo json_encode(['ok'=>false,'error'=>'Forbidden']);
      exit;
    }
  } catch (Throwable $e) {
    // ignore ownership checks if tables not ready
  }
}

// Call OpenAI endpoint
$req = [
  'conversation_id' => $conversation_id,
  'message' => $message,
  'language' => $language
];

$ch = curl_init(__DIR__ . '/openai_chat.php');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($req),
  CURLOPT_TIMEOUT => 60
]);

$res = curl_exec($ch);
$err = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'OpenAI call failed: '.$err]);
  exit;
}

$data = json_decode($res, true);
if ($code < 200 || $code >= 300 || !is_array($data)) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Bad OpenAI response','details'=>$data]);
  exit;
}

echo json_encode($data);

