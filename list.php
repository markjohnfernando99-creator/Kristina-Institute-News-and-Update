<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';

$type = $_GET['type'] ?? 'news';
if(!in_array($type, ['news','updates','advertisement','tesda_assessment','tesda_training'], true)) $type = 'news';


$limit = intval($_GET['limit'] ?? 9);
if($limit <= 0 || $limit > 30) $limit = 9;

$stmt = $pdo->prepare('SELECT id, type, title, content, author_name, image_path, attachment_path, video_path, created_at FROM posts WHERE type=:type ORDER BY created_at DESC LIMIT :lim');



$stmt->bindValue(':type', $type, PDO::PARAM_STR);

$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['items'=>$items]);

