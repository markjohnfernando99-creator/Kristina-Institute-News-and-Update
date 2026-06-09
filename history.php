<?php
session_start();
require __DIR__ . '/config/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if($userId <= 0){
  header('Location: login.php');
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
$stmt->execute([':uid'=>$userId]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chat History — Kristina AI</title>
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    .wrap{max-width:980px; margin:0 auto; padding:18px 16px 40px;}
    .grid{display:grid; grid-template-columns:1fr; gap:12px;}
    .item{background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:18px; padding:14px; box-shadow:var(--shadow);}
    .meta{color:var(--muted); font-size:13px;}
    a.btn{display:inline-flex; align-items:center; justify-content:center; padding:11px 14px; border-radius:12px; color:#0b1220; text-decoration:none; font-weight:750; background:linear-gradient(135deg,var(--primary),var(--primary2));}
  </style>
</head>
<body class="has-bg-image">
<header class="site-header">
  <div class="container header-inner">
    <div class="brand">
      <div class="brand-mark" aria-hidden="true"><img class="brand-logo" src="assets/KIHCA LOGO MALIWANAG.png" alt="Kristina" /></div>
      <div>
        <div class="brand-title">Kristina Institute</div>
        <div class="brand-subtitle">Chat History</div>
      </div>
    </div>
    <nav class="nav">
      <a href="chat.php" class="nav-link">Back to Chat</a>
      <a href="logout.php" class="nav-link">Logout</a>
    </nav>
  </div>
</header>

<div class="wrap">
  <h2 style="margin:0 0 10px; font-size:22px;">Your Chats</h2>
  <div class="meta" style="margin-bottom:14px;">Click to open a chat.</div>

  <div class="grid">
    <?php if(!$sessions): ?>
      <div class="item"><div class="muted">No chats yet.</div></div>
    <?php endif; ?>

    <?php foreach($sessions as $s): ?>
      <div class="item">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
          <div>
            <div style="font-weight:950;">Session #<?php echo (int)$s['id']; ?></div>
            <div class="meta"><?php echo $s['created_at'] ? h((new DateTime($s['created_at']))->format('Y-m-d H:i')) : ''; ?></div>
            <div class="meta" style="margin-top:6px;"><?php echo h($s['first_user_message'] ?? ''); ?></div>
          </div>
          <a class="btn" href="chat.php?session_id=<?php echo (int)$s['id']; ?>">Open</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>

