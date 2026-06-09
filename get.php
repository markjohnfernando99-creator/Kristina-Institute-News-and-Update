<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';

$type = $_GET['type'] ?? 'news';

if(!in_array($type, ['news','updates','advertisement','tesda_assessment','tesda_training'], true)) $type = 'news';





$id = intval($_GET['id'] ?? 0);

if($id <= 0){
  echo json_encode(['item'=>null]);
  exit;
}

$stmt = $pdo->prepare('SELECT id, type, title, content, author_name, image_path, attachment_path, video_path, created_at FROM posts WHERE id=:id AND type=:type LIMIT 1');

$stmt->execute([':id'=>$id, ':type'=>$type]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['item'=>$item]);

