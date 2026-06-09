<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../config/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Fetch latest sessions with user + first message
$stmt = $pdo->prepare(
  'SELECT cs.id, cs.created_at, u.username,
          (SELECT content FROM chat_messages cm
            WHERE cm.conversation_id = cs.id AND cm.role="user"
            ORDER BY cm.created_at ASC LIMIT 1) AS first_user_message
   FROM chat_sessions cs
   JOIN users u ON u.id = cs.user_id
   ORDER BY cs.created_at DESC
   LIMIT 200'
);
$stmt->execute();
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin — Conversations</title>
  <link rel="stylesheet" href="../assets/style.css" />
  <style>
    .wrap{max-width:1100px; margin:22px auto; padding:0 16px;}
    table{width:100%; border-collapse:collapse;}
    th, td{border-top:1px solid var(--border); padding:10px 8px; vertical-align:top;}
    th{color:var(--muted); font-size:13px; text-align:left}
    .muted{color:var(--muted)}
    .small{font-size:12.5px}
    .danger-link{color:var(--danger); text-decoration:none; font-weight:900}
  </style>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">
          <img class="brand-logo" src="../assets/KIHCA LOGO MALIWANAG.png" alt="" />
        </div>
        <div>
          <div class="brand-title">Kristina Institute</div>
          <div class="brand-subtitle">Admin Panel</div>
        </div>
      </div>
      <nav class="nav">
        <a href="dashboard.php" class="nav-link">Content</a>
        <a href="conversations.php" class="nav-link nav-link-primary">Conversations</a>
        <a href="logout.php" class="nav-link">Logout</a>
      </nav>
  </div>
</header>

<div class="wrap">
  <h1 style="margin:0 0 10px; font-size:22px;">Chat Conversations</h1>
  <div class="muted" style="margin-bottom:14px;">Lists recent user sessions and the first user message.</div>

  <div style="overflow:auto;">
    <table>
      <thead>
        <tr>
          <th style="width:80px;">ID</th>
          <th style="width:220px;">User</th>
          <th>First message</th>
          <th style="width:160px;">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($sessions as $s): ?>
          <tr>
            <td class="small"><?php echo h($s['id']); ?></td>
            <td class="small"><?php echo h($s['username']); ?></td>
            <td>
              <div style="font-weight:900;"><?php echo h($s['first_user_message'] ?: '(no message)'); ?></div>
            </td>
            <td class="small"><?php echo h($s['created_at'] ? (new DateTime($s['created_at']))->format('Y-m-d H:i') : ''); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if(count($sessions)===0): ?>
          <tr><td colspan="4" class="muted small">No conversations yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>

