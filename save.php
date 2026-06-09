<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../config/db.php';



function h($s){return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');}

$type = $_POST['type'] ?? 'news';
if(!in_array($type, ['news','updates','advertisement','tesda_assessment','tesda_training'], true)) $type = 'news';


$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? ''); 

$author_name = trim($_POST['author_name'] ?? '');

// Helpful error reporting for debugging uploads.
$uploadVideoErr = isset($_FILES['video']['error']) ? intval($_FILES['video']['error']) : null;

if($title === '' || $content === ''){
  header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode('Title and content are required.'));
  exit;
}

if($uploadVideoErr !== null && $uploadVideoErr !== UPLOAD_ERR_NO_FILE && $uploadVideoErr !== UPLOAD_ERR_OK){
  $errMap = [
    UPLOAD_ERR_INI_SIZE => 'Uploaded file exceeds server max size (php.ini).',
    UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds form max size.',
    UPLOAD_ERR_PARTIAL => 'Video upload was only partially completed.',
    UPLOAD_ERR_NO_FILE => 'No video file sent.',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
    UPLOAD_ERR_CANT_WRITE => 'Server failed writing video to disk.',
    UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension.',
  ];
  $msg = $errMap[$uploadVideoErr] ?? ('Video upload failed with error code: ' . $uploadVideoErr);
  header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode($msg));
  exit;
}


$uploadsDir = __DIR__ . '/../assets/uploads';
if(!is_dir($uploadsDir)) mkdir($uploadsDir, 0775, true);

$image_path = null;
$attachment_path = null;

function randomName($ext){
  return bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
}

function safeExt($filename){
  $parts = explode('.', $filename);
  $ext = strtolower(end($parts));
  return preg_replace('/[^a-z0-9]/','',$ext);
}

// Image
if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0){
  $tmp = $_FILES['image']['tmp_name'];
  $orig = $_FILES['image']['name'];
  $ext = safeExt($orig);
  if(!in_array($ext, ['jpg','jpeg','png','webp'], true)){
    header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode('Invalid image type. Use JPG/PNG/WEBP.'));
    exit;
  }
  $name = randomName($ext);
  $dest = $uploadsDir . '/' . $name;
  if(!move_uploaded_file($tmp, $dest)){
    header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode('Failed to save image.'));
    exit;
  }
  $image_path = $name;
}

// Attachment
if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK && $_FILES['attachment']['size'] > 0){
  $tmp = $_FILES['attachment']['tmp_name'];
  $orig = $_FILES['attachment']['name'];
  $ext = safeExt($orig);
  $name = randomName($ext);
  $dest = $uploadsDir . '/' . $name;
  if(!move_uploaded_file($tmp, $dest)){
    header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode('Failed to save attachment.'));
    exit;
  }
  $attachment_path = $name;
}

// Video
$video_path = null;
if(isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK && $_FILES['video']['size'] > 0){
  $tmp = $_FILES['video']['tmp_name'];
  $orig = $_FILES['video']['name'];
  $ext = safeExt($orig);

  // Some browsers send empty/odd extensions; rely on extension whitelist.
  if(!in_array($ext, ['mp4','webm'], true)){
    header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode('Invalid video type. Use MP4 or WEBM.'));
    exit;
  }

  $name = randomName($ext);
  $dest = $uploadsDir . '/' . $name;

  if(!move_uploaded_file($tmp, $dest)){
    header('Location: dashboard.php?type=' . urlencode($type) . '&err=' . urlencode('Failed to save video.'));
    exit;
  }

  $video_path = $name;
}


$stmt = $pdo->prepare('INSERT INTO posts (type, title, content, author_name, image_path, attachment_path, video_path, created_at) VALUES (:type,:title,:content,:author,:img,:att,:vid,NOW())');
$stmt->execute([
  ':type'=>$type,
  ':title'=>$title,
  ':content'=>$content,
  ':author'=>$author_name !== '' ? $author_name : null,
  ':img'=>$image_path,
  ':att'=>$attachment_path,
  ':vid'=>$video_path
]);

header('Location: dashboard.php?type=' . urlencode($type) . '&ok=1');
exit;





