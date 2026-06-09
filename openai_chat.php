<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/openai.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload']);
  exit;
}

$userMessage = trim((string)($payload['message'] ?? ''));
$conversation_id = (string)($payload['conversation_id'] ?? '');
$language = strtolower(trim((string)($payload['language'] ?? 'en')));

if ($userMessage === '') {
  http_response_code(200);
  echo json_encode(['ok' => false, 'answer' => 'Please type a message.', 'language' => $language]);
  exit;
}

// Basic safety: refuse sensitive info requests
$blocked = [
  'password',
  'credit card',
  'ssn',
  'social security'
];
$lower = mb_strtolower($userMessage);
foreach ($blocked as $b) {
  if (mb_strpos($lower, $b) !== false) {
    echo json_encode([
      'ok' => true,
      'language' => $language,
      'answer' => "I can’t help with that request."
    ]);
    exit;
  }
}

// Conversation retrieval (chat history) to keep context
$history = [];
if ($conversation_id !== '') {
  try {
    $stmt = $pdo->prepare(
      'SELECT role, content
       FROM chat_messages
       WHERE conversation_id = :cid
       ORDER BY created_at ASC
       LIMIT 30'
    );
    $stmt->execute([':cid' => $conversation_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    // If tables don’t exist yet, ignore history.
    $history = [];
  }
}

$system = "You are OpenChatBot, a powerful AI assistant designed to provide accurate, helpful, and intelligent responses across a wide range of topics.

CORE MISSION:
Help users learn, solve problems, create content, write code, brainstorm ideas, and make informed decisions.

PERSONALITY:
- Friendly and professional
- Intelligent but easy to understand
- Patient and respectful
- Conversational and engaging
- Adaptable to different user needs

BEHAVIOR RULES:
1. Always provide clear, accurate, and useful answers.
2. Explain complicated topics in simple terms when needed.
3. Ask clarifying questions if the user's request is unclear.
4. Provide step-by-step instructions for technical tasks.
5. Use examples to improve understanding.
6. Format responses using headings, bullet points, and code blocks when appropriate.
7. Maintain a helpful and positive tone.
8. Never invent facts. If information is uncertain, state the uncertainty.
9. Prioritize user understanding over technical jargon.
10. Adjust response length based on the complexity of the question.

KNOWLEDGE AREAS:
General Knowledge; Education and Learning; Science and Technology; Mathematics; Programming and Software Development; Business and Marketing; Writing and Communication; Research Assistance; Productivity and Planning; Creative Content Creation.

CODING ASSISTANT MODE:
When users request code: provide complete and working code examples, explain how it works, fix errors, follow best practices.

WRITING ASSISTANT MODE:
When users request content: write clearly and professionally, match requested tone, improve grammar/readability.

PROBLEM-SOLVING MODE:
Break problems into smaller parts, analyze logically, present multiple solutions when appropriate, explain pros/cons.

RESPONSE FORMAT:
- Use Markdown.
- Use headings for longer answers.
- Use numbered steps for instructions.
- Use bullet points for lists.
- Use code blocks for code.";

$system .= ($language === 'fil')
  ? "\nRespond in Filipino when possible."
  : "\nRespond in English.";







$messages = [];
$messages[] = ['role' => 'system', 'content' => $system];

foreach ($history as $m) {
  $role = $m['role'] ?? 'user';
  $content = (string)($m['content'] ?? '');
  if ($content === '') continue;
  if (!in_array($role, ['user', 'assistant'], true)) $role = 'user';
  $messages[] = ['role' => $role, 'content' => $content];
}

$messages[] = ['role' => 'user', 'content' => $userMessage];

// OpenAI call
$endpoint = 'https://api.openai.com/v1/chat/completions';

$reqBody = [
  'model' => $openai_model,
  'messages' => $messages,
  'temperature' => 0.7,
  'max_tokens' => 700
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openai_api_key
  ],
  CURLOPT_POSTFIELDS => json_encode($reqBody),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 45
]);

$res = curl_exec($ch);
$err = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'OpenAI request failed: ' . $err]);
  exit;
}

$data = json_decode($res, true);

if ($httpCode < 200 || $httpCode >= 300 || !is_array($data)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'OpenAI error', 'details' => $data]);
  exit;
}

$answer = $data['choices'][0]['message']['content'] ?? '';
$answer = trim((string)$answer);

// Persist conversation + message
// We store only if chat_messages table exists.
try {
  if ($conversation_id !== '') {
    $stmt = $pdo->prepare(
      'INSERT INTO chat_messages (conversation_id, role, content, created_at)
       VALUES (:cid, :role, :content, NOW())'
    );
    $stmt->execute([':cid' => $conversation_id, ':role' => 'user', ':content' => $userMessage]);
    $stmt->execute([':cid' => $conversation_id, ':role' => 'assistant', ':content' => $answer]);
  }
} catch (Throwable $e) {
  // ignore persistence errors
}

echo json_encode(['ok' => true, 'language' => $language, 'answer' => $answer]);

