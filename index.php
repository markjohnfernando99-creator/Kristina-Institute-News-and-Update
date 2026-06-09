<?php
session_start();
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true){
  header('Location: dashboard.php');
  exit;
}

$err = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  // Default credentials (override in config/admin.php)
  require __DIR__ . '/../config/admin.php';

  if(hash_equals($admin_username, $username) && hash_equals($admin_password, $password)){
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    header('Location: dashboard.php');
    exit;
  }else{
    $err = 'Invalid username or password.';
  }
}
?>
<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin — Kristina Institute</title>
  <link rel="stylesheet" href="../assets/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet" />
  <style>

    .admin-wrap{max-width:720px; margin:30px auto; padding:0 16px;}
    .admin-card{
      background:rgba(18,26,45,.62);
      border:1px solid rgba(255,255,255,.14);
      border-radius:18px;
      box-shadow:var(--shadow);
      padding:18px;
      backdrop-filter: blur(10px) saturate(140%);
    }
    label{display:block; font-size:13px; color:var(--muted); margin:12px 0 6px;}

    input, select, textarea{
      width:100%;
      padding:12px 12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.14);
      background:rgba(0,0,0,.28);
      color:var(--text);
      outline:none;
      transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    input::placeholder{color:rgba(154,167,199,.75)}

    input:focus, select:focus, textarea:focus{
      border-color: rgba(79,140,255,.55);
      box-shadow: 0 0 0 4px rgba(79,140,255,.16);
    }

    textarea{min-height:120px; resize:vertical;}
    .row{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
    @media(max-width:680px){.row{grid-template-columns:1fr;}}
    .actions{margin-top:14px; display:flex; gap:12px; flex-wrap:wrap;}
    .error{color:var(--danger); margin-top:10px;}

    .btn-primary{display:inline-flex;align-items:center;justify-content:center;padding:11px 14px;border-radius:12px;color:#0b1220;text-decoration:none;font-weight:750;background:linear-gradient(135deg,var(--primary),var(--primary2));border:0;cursor:pointer;}
    .btn-secondary{display:inline-flex;align-items:center;justify-content:center;padding:11px 14px;border-radius:12px;color:var(--muted);text-decoration:none;font-weight:700;background:transparent;border:1px solid var(--border);cursor:pointer;}

    /* Make text + card blend better over background image */
    .admin-card .muted{color:rgba(154,167,199,.92)}
  </style>

</head>
<body class="has-bg-image">

  <header class="site-header">
    <div class="container header-inner">
        <div class="brand">
        <div class="brand-mark" aria-hidden="true">
<img class="brand-logo" src="../assets/KIHCA LOGO MALIWANAG.png" alt="Kristina Institute logo" />
        </div>
        <div>
          <div class="brand-title">Kristina Institute</div>
          <div class="brand-subtitle">Admin Panel</div>
        </div>
      </div>
      <nav class="nav">
        <a href="../index.html" class="nav-link">Home</a>
      </nav>
    </div>
  </header>

  <div class="admin-wrap">
    <div class="admin-card">
      <h1 style="margin:0 0 6px; font-size:22px;">Login</h1>
      <div class="muted">Upload News, Updates, and Advertisements.</div>

      <?php if($err): ?>
        <div class="error"><?php echo htmlspecialchars($err); ?></div>
      <?php endif; ?>

      <form method="post" style="margin-top:14px;">
        <label>Username</label>
        <input name="username" autocomplete="username" required />

        <label>Password</label>
        <input name="password" type="password" autocomplete="current-password" required />

        <div class="actions">
          <button class="btn-primary" type="submit">Login</button>
          <button class="btn-secondary" type="button" onclick="window.location='../index.html'">Back</button>
        </div>
        <div class="muted" style="margin-top:12px; font-size:12.5px;">
          Default credentials are set in <code>config/admin.php</code>.
        </div>
      </form>
    </div>
  </div>
</body>
  <script>
document.documentElement.style.setProperty('--bg-image-url', 'url("https://images.unsplash.com/photo-1556911220-bff31c812dba?q=80&w=1600&auto=format&fit=crop")');
  </script>
</html>



