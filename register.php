<?php
session_start();

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if(isset($_SESSION['user_id'])){
  header('Location: chat.php');
  exit;
}

$err = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  require __DIR__ . '/config/db.php';

  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $confirm = (string)($_POST['confirm_password'] ?? '');

  if($username === '' || strlen($username) < 3){
    $err = 'Username must be at least 3 characters.';
  } elseif($password === '' || strlen($password) < 6){
    $err = 'Password must be at least 6 characters.';
  } elseif($password !== $confirm){
    $err = 'Passwords do not match.';
  } else {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    if($stmt->fetch()){
      $err = 'Username already exists.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt2 = $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:u, :h, NOW())');
      $stmt2->execute([':u' => $username, ':h' => $hash]);

      $_SESSION['user_id'] = (int)$pdo->lastInsertId();
      $_SESSION['username'] = $username;
      header('Location: chat.php');
      exit;
    }
  }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register — Kristina AI</title>
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body class="has-bg-image">
<header class="site-header">
  <div class="container header-inner">
    <div class="brand">
      <div class="brand-mark" aria-hidden="true"><img class="brand-logo" src="assets/KIHCA LOGO MALIWANAG.png" alt="Kristina" /></div>
      <div>
        <div class="brand-title">Kristina Institute</div>
        <div class="brand-subtitle">AI Chat</div>
      </div>
    </div>
    <nav class="nav">
      <a class="nav-link" href="index.html">Home</a>
    </nav>
  </div>
</header>

<div class="chat-wrap">
  <div class="panel" style="max-width:520px; margin:0 auto;">
    <h2 style="margin:0 0 10px;">Register</h2>
    <div class="muted" style="margin-bottom:12px;">Create an account to save chat history.</div>
    <?php if($err): ?><div class="alert alert-danger" style="margin-bottom:12px;"> <?php echo h($err); ?> </div><?php endif; ?>

    <form method="post" style="display:flex; flex-direction:column; gap:12px;">
      <label>
        <div class="muted" style="font-size:13px; margin-bottom:6px;">Username</div>
        <input name="username" required style="width:100%; padding:12px; border-radius:12px; border:1px solid var(--border); background:rgba(0,0,0,.2); color:var(--text);" />
      </label>
      <label>
        <div class="muted" style="font-size:13px; margin-bottom:6px;">Password</div>
        <input name="password" type="password" required style="width:100%; padding:12px; border-radius:12px; border:1px solid var(--border); background:rgba(0,0,0,.2); color:var(--text);" />
      </label>
      <label>
        <div class="muted" style="font-size:13px; margin-bottom:6px;">Confirm password</div>
        <input name="confirm_password" type="password" required style="width:100%; padding:12px; border-radius:12px; border:1px solid var(--border); background:rgba(0,0,0,.2); color:var(--text);" />
      </label>
      <button class="btn" type="submit">Create account</button>
      <div class="muted" style="font-size:13px;">Already have an account? <a class="link" href="login.php">Login</a></div>
    </form>
  </div>
</div>
</body>
</html>

