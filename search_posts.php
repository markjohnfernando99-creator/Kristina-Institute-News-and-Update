<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$limit = intval($_GET['limit'] ?? 8);
if ($limit <= 0 || $limit > 25) $limit = 8;

if ($q === '') {
  echo json_encode(['items' => [], 'query' => '']);
  exit;
}

$allowedTypes = ['all','news','updates','advertisement','tesda_assessment','tesda_training'];
if (!in_array($type, $allowedTypes, true)) $type = 'all';

$like = '%'.$q.'%';

if ($type === 'all') {
  $stmt = $pdo->prepare(
    'SELECT id, type, title, content, author_name, image_path, attachment_path, video_path, created_at
     FROM posts
     WHERE (title LIKE :like OR content LIKE :like)
     ORDER BY created_at DESC
     LIMIT :lim'
  );
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':like', $like, PDO::PARAM_STR);
} else {
  $stmt = $pdo->prepare(
    'SELECT id, type, title, content, author_name, image_path, attachment_path, video_path, created_at
     FROM posts
     WHERE type = :type AND (title LIKE :like OR content LIKE :like)
     ORDER BY created_at DESC
     LIMIT :lim'
  );
  $stmt->bindValue(':type', $type, PDO::PARAM_STR);
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':like', $like, PDO::PARAM_STR);
}

$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['items' => $items, 'query' => $q]);

