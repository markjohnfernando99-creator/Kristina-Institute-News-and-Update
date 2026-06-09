<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../config/db.php';

function h($s){return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');}

$type = $_GET['type'] ?? 'news';
if(!in_array($type, ['news','updates','advertisement','tesda_assessment','tesda_training'], true)) $type = 'news';


$filter_title = trim($_GET['title'] ?? '');

$items = [];
$stmt = $pdo->prepare("SELECT id, type, title, content, created_at FROM posts WHERE type = :type AND (:t = '' OR title LIKE CONCAT('%', :t, '%')) ORDER BY created_at DESC LIMIT 30");

$stmt->execute([':type'=>$type, ':t'=>$filter_title]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mode = $_GET['mode'] ?? 'list';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard — Kristina Institute</title>
  <link rel="stylesheet" href="../assets/style.css" />
  <style>
    .admin-wrap{max-width:1200px; margin:24px auto; padding:0 16px;}
    .topbar{display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px;}
    .tabs{display:flex; gap:10px; flex-wrap:wrap;}
    .tab{padding:9px 12px; border-radius:12px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-weight:700; background:rgba(255,255,255,.02)}
    .tab.active{background:linear-gradient(135deg,var(--primary),var(--primary2)); color:#0b1220; border-color:transparent}
    .grid2{display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:start}
    @media(max-width:980px){.grid2{grid-template-columns:1fr}}
    label{display:block; font-size:13px; color:var(--muted); margin:12px 0 6px;}
    input, textarea, select{width:100%; padding:11px 12px; border-radius:12px; border:1px solid var(--border); background:rgba(0,0,0,.15); color:var(--text); outline:none;}
    textarea{min-height:120px; resize:vertical;}
    .panel{background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow); padding:16px;}
    .actions{margin-top:14px; display:flex; gap:12px; flex-wrap:wrap;}
    .btn-primary{display:inline-flex;align-items:center;justify-content:center;padding:11px 14px;border-radius:12px;color:#0b1220;text-decoration:none;font-weight:750;background:linear-gradient(135deg,var(--primary),var(--primary2));border:0;cursor:pointer;}
    .btn-secondary{display:inline-flex;align-items:center;justify-content:center;padding:11px 14px;border-radius:12px;color:var(--muted);text-decoration:none;font-weight:700;background:transparent;border:1px solid var(--border);cursor:pointer;}
    table{width:100%; border-collapse:collapse;}
    th, td{border-top:1px solid var(--border); padding:10px 8px; vertical-align:top;}
    th{color:var(--muted); font-size:13px; text-align:left}
    td{font-size:14px}
    .small{font-size:12.5px; color:var(--muted)}
    .danger-link{color:var(--danger); text-decoration:none; font-weight:800}
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
        <a href="../index.html" class="nav-link">Home</a>
        <a href="logout.php" class="nav-link">Logout</a>
      </nav>
    </div>
  </header>

  <div class="admin-wrap">
    <div class="topbar">
      <div>
        <h1 style="margin:0; font-size:22px;">Manage Content</h1>
        <div class="muted">Posting for <strong><?php echo h($type); ?></strong></div>
      </div>
    <div class="tabs">

          <a class="tab <?php echo $type==='news'?'active':''; ?>" href="dashboard.php?type=news">News</a>
          <a class="tab <?php echo $type==='updates'?'active':''; ?>" href="dashboard.php?type=updates">Updates</a>
          <a class="tab <?php echo $type==='advertisement'?'active':''; ?>" href="dashboard.php?type=advertisement">Advertisement</a>
          <a class="tab <?php echo $type==='tesda_assessment'?'active':''; ?>" href="dashboard.php?type=tesda_assessment">TESDA Assessment</a>
          <a class="tab <?php echo $type==='tesda_training'?'active':''; ?>" href="dashboard.php?type=tesda_training">TESDA Training</a>
          <a class="tab" href="conversations.php">Conversations</a>
        </div>


    </div>

    <div class="grid2">
      <div class="panel">
        <h2 style="margin:0 0 8px; font-size:18px;">Create New</h2>
        <div class="muted">Upload an image (optional), attachment (optional), and video (optional).</div>





        <form method="post" action="save.php" enctype="multipart/form-data">


          <input type="hidden" name="type" value="<?php echo h($type); ?>" />

          <?php if($type==='tesda_assessment' || $type==='tesda_training'): ?>
            <div class="small" style="margin:10px 0 12px; color:var(--muted);">
              TESDA content must be valid <strong>JSON</strong>.
            </div>
          <?php endif; ?>

          <label>Title</label>

          <input name="title" required maxlength="140" />

          <label>Author name (optional)</label>
          <input name="author_name" maxlength="80" />

          <label>Content / Description</label>
          <textarea name="content" required></textarea>

          <label>Image (JPG/PNG) optional</label>
          <input name="image" type="file" accept="image/*" />

          <label>Attachment (PDF/DOC/PPT/ZIP etc.) optional</label>
          <input name="attachment" type="file" />

          <label>Video (MP4/WebM) optional</label>
          <input name="video" type="file" accept="video/mp4,video/webm" />
          <div class="small" style="margin:-6px 0 8px;">Video will show on the details page.</div>


          <div class="actions">
            <button class="btn-primary" type="submit">Publish</button>

            <button class="btn-secondary" type="button" onclick="window.location='dashboard.php?type=<?php echo h($type); ?>'">Reset</button>
          </div>
        </form>

        <?php if(isset($_GET['ok']) && $_GET['ok']==='1'): ?>
          <div class="small" style="margin-top:12px;">Saved successfully.</div>
        <?php endif; ?>
        <?php if(isset($_GET['err'])): ?>
          <div class="small" style="margin-top:12px; color:var(--danger);"><?php echo h($_GET['err']); ?></div>
        <?php endif; ?>
      </div>

      <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
          <h2 style="margin:0; font-size:18px;">Recent (last 30)</h2>
          <form method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="type" value="<?php echo h($type); ?>" />
            <input name="title" placeholder="Filter title" value="<?php echo h($filter_title); ?>" style="width:220px" />
            <button class="btn-secondary" type="submit">Filter</button>
          </form>
        </div>
        <div style="overflow:auto; margin-top:10px;">
          <table>
            <thead>
              <tr>
                <th style="width:60px;">ID</th>
                <th>Title</th>
                <th style="width:180px;">Date</th>
                <th style="width:140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($items as $it): ?>
                <tr>
                  <td class="small"><?php echo h($it['id']); ?></td>
                  <td>
                    <div style="font-weight:800;"><?php echo h($it['title']); ?></div>
                    <div class="small" style="margin-top:6px; max-width:520px;"><?php echo h(mb_strimwidth($it['content'],0,120,'...')); ?></div>
                  </td>
                  <td class="small"><?php echo h((new DateTime($it['created_at']))->format('Y-m-d')); ?></td>
                  <td>
                    <a class="small-link" href="<?php echo ($type==='tesda_assessment' || $type==='tesda_training') ? '../tesda-details.html?type=' . urlencode($type) . '&id=' . $it['id'] : '../details.html?type=' . urlencode($type) . '&id=' . $it['id']; ?>" target="_blank" rel="noreferrer">View</a>

                    <span style="display:block; height:8px;"></span>
                    <a class="danger-link" href="delete.php?id=<?php echo h($it['id']); ?>&type=<?php echo h($type); ?>" onclick="return confirm('Delete this item?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if(count($items)===0): ?>
                <tr><td colspan="4" class="small">No items found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

