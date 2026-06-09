<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'news';
if(!in_array($type, ['news','updates','advertisement','tesda_assessment','tesda_training'], true)) $type = 'news';


if($id <= 0){
  header('Location: dashboard.php?type=' . urlencode($type));
  exit;
}

$stmt = $pdo->prepare('SELECT image_path, attachment_path, video_path FROM posts WHERE id=:id AND type=:type LIMIT 1');
$stmt->execute([':id'=>$id, ':type'=>$type]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$uploadsDir = __DIR__ . '/../assets/uploads';

if($row){
  if(!empty($row['image_path'])){
    $f = $uploadsDir . '/' . $row['image_path'];
    if(is_file($f)) @unlink($f);
  }
  if(!empty($row['attachment_path'])){
    $f = $uploadsDir . '/' . $row['attachment_path'];
    if(is_file($f)) @unlink($f);
  }
  if(!empty($row['video_path'])){
    $f = $uploadsDir . '/' . $row['video_path'];
    if(is_file($f)) @unlink($f);
  }
}


$del = $pdo->prepare('DELETE FROM posts WHERE id=:id AND type=:type');
$del->execute([':id'=>$id, ':type'=>$type]);

header('Location: dashboard.php?type=' . urlencode($type));
exit;

