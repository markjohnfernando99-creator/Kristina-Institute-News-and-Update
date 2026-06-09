<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kristina Institute — Updates</title>
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    .container{max-width:980px; margin:0 auto; padding:0 16px;}
    .page{padding:26px 0 40px;}
    .section-head{display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:14px;}
    .tabs{display:flex; gap:10px; flex-wrap:wrap;}
    .tab{padding:9px 12px; border-radius:12px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-weight:800; background:rgba(255,255,255,.02)}
    .tab.active{background:linear-gradient(135deg,var(--primary),var(--primary2)); color:#0b1220; border-color:transparent}
    .grid{display:grid; grid-template-columns:repeat(3, 1fr); gap:14px;}
    @media(max-width:980px){.grid{grid-template-columns:repeat(2, 1fr);}}
    @media(max-width:680px){.grid{grid-template-columns:1fr;}}

    .card{background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:18px; overflow:hidden; box-shadow:var(--shadow)}
    .card-body{padding:14px 14px 16px;}
    .card-title{margin:0; font-size:16px; font-weight:900;}
    .card-meta{margin-top:6px; color:var(--muted); font-size:12.5px; display:flex; gap:10px; flex-wrap:wrap;}
    .card-content{margin-top:10px; color:var(--text); font-size:14px; line-height:1.6; display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden;}
    .thumb{height:140px; background:#0d1528; display:flex; align-items:center; justify-content:center; overflow:hidden;}
    .thumb img{width:100%; height:100%; object-fit:cover; display:block;}
    .card-actions{margin-top:12px; display:flex; gap:12px; flex-wrap:wrap;}
    .btn-link{display:inline-flex; align-items:center; justify-content:center; padding:10px 12px; border-radius:12px; color:#0b1220; text-decoration:none; font-weight:850; background:linear-gradient(135deg,var(--primary),var(--primary2));}
    .small{font-size:12.5px; color:var(--muted)}
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">

        </div>
        <div>
          <div class="brand-title">Kristina Institute</div>
          <div class="brand-subtitle">Hospitality &amp; Culinary Arts</div>
        </div>
      </div>
      <nav class="nav">
        <a href="index.html" class="nav-link">Home</a>
        <a href="admin" class="nav-link nav-link-primary">Admin</a>
      </nav>
    </div>
  </header>

  <main class="page">
    <div class="container">
      <div class="section-head">
        <div>
          <h1 style="margin:0; font-size:22px;">All Postings</h1>
          <div class="muted" style="margin-top:6px;">View everything posted by admin (read-only).</div>
        </div>
        <div class="tabs" aria-label="Post type">
          <a class="tab" id="tab-news" href="updates.php?type=news">News</a>
          <a class="tab" id="tab-updates" href="updates.php?type=updates">Updates</a>
          <a class="tab" id="tab-ads" href="updates.php?type=advertisement">Advertisement</a>
        </div>
      </div>

      <div id="items" class="grid"></div>
      <div id="loading" class="small" style="margin-top:14px;">Loading...</div>
      <div id="empty" class="small" style="margin-top:14px; display:none;">No posts found.</div>
    </div>
  </main>

  <script>
    const qs = new URLSearchParams(location.search);
    const type = qs.get('type') || 'news';

    const tabs = {
      news: document.getElementById('tab-news'),
      updates: document.getElementById('tab-updates'),
      advertisement: document.getElementById('tab-ads')
    };
    if(tabs[type]) tabs[type].classList.add('active');

    function escapeHtml(s){
      return (s ?? '').toString()
        .replaceAll('&','&amp;')
        .replaceAll('<','<')
        .replaceAll('>','>')
        .replaceAll('"','"')
        .replaceAll("'",'&#039;');
    }

    async function fetchJSON(url){
      const res = await fetch(url);
      if(!res.ok) throw new Error(await res.text());
      return res.json();
    }

    (async()=>{
      try{
        document.getElementById('loading').style.display = 'block';
        document.getElementById('empty').style.display = 'none';

        const data = await fetchJSON(`api/list.php?type=${encodeURIComponent(type)}&limit=30`);
        const items = data.items || [];

        const wrap = document.getElementById('items');
        wrap.innerHTML = '';

        for(const it of items){
          const img = it.image_path ? `assets/uploads/${encodeURIComponent(it.image_path)}` : '';
          const contentPreview = escapeHtml(it.content).slice(0, 140);
          const date = it.created_at ? new Date(it.created_at).toLocaleDateString() : '';

          const el = document.createElement('div');
          el.className = 'card';
          el.innerHTML = `
            ${img ? `<div class="thumb"><img src="${img}" alt="${escapeHtml(it.title)}" /></div>` : `<div class="thumb"><div class="small">No Image</div></div>`}
            <div class="card-body">
              <h3 class="card-title">${escapeHtml(it.title)}</h3>
              <div class="card-meta">
                <span>${escapeHtml(type === 'advertisement' ? 'Advertisement' : (type === 'updates' ? 'Update' : 'News'))}</span>
                ${date ? `<span>${escapeHtml(date)}</span>` : ''}
              </div>
              <div class="card-content">${escapeHtml(contentPreview)}${escapeHtml(it.content).length > 140 ? '…' : ''}</div>
              <div class="card-actions">
                <a class="btn-link" href="details.html?type=${encodeURIComponent(type)}&id=${encodeURIComponent(it.id)}">View full post</a>
              </div>
            </div>
          `;
          wrap.appendChild(el);
        }

        document.getElementById('loading').style.display = 'none';
        if(items.length === 0) document.getElementById('empty').style.display = 'block';
      }catch(e){
        console.error(e);
        document.getElementById('loading').textContent = 'Failed to load posts.';
      }
    })();
  </script>
</body>
</html>

