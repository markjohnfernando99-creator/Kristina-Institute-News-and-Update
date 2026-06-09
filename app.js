const el = (id)=>document.getElementById(id);
const yearEl = el('year');
if(yearEl) yearEl.textContent = new Date().getFullYear();

async function fetchJSON(url, opts){
  const res = await fetch(url, opts);
  if(!res.ok){
    const txt = await res.text().catch(()=>"");
    throw new Error(`Request failed ${res.status}: ${txt}`);
  }
  return res.json();
}

function escapeHtml(s){
  return (s ?? '').toString()
    .replaceAll('&','&amp;')
    .replaceAll('<','<')
    .replaceAll('>','>')
    .replaceAll('"','"')
    .replaceAll("'",'&#039;');
}

function makeCard(item, kind){
const img = item.image_path ? `assets/uploads/${encodeURIComponent(item.image_path)}` : '';
  const video_path = item.video_path ? `assets/uploads/${encodeURIComponent(item.video_path)}` : '';

  // Basic sanity: if details page fails to render video, ensure the file exists.
  // (We still let details.html handle the final rendering.)
  const title = escapeHtml(item.title);
  const date = item.created_at ? new Date(item.created_at).toLocaleDateString() : '';
  const content = escapeHtml(item.content);

  const badgeLabel = kind === 'news' ? 'News' : (kind === 'updates' ? 'Update' : 'Ad');
  const badgeClass = kind === 'news' ? 'badge-primary' : '';

  if(kind === 'advertisement'){
    // Special rendering for the “Strengthened Senior High School Program” ad.
    // Admin can set the title to anything; we detect keywords in title/content.
    const isProgram = (item.title || '').toLowerCase().includes('strengthened senior high') || (item.content || '').toLowerCase().includes('strengthened senior high');

    if(isProgram){
      return `
        <article class="ad">
          ${img ? `<img src="${img}" alt="${title}" loading="lazy" />` : ''}
          <div class="ad-body">
            <h3 class="ad-title">${title}</h3>
            ${date ? `<div class="card-meta">${escapeHtml(date)}</div>` : ''}
            ${content ? `<div class="muted" style="font-size:14px;line-height:1.45; margin-top:10px; white-space:pre-wrap;">${content}</div>` : ''}

            <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
              <a class="small-link" href="details.html?type=${encodeURIComponent(kind)}&id=${encodeURIComponent(item.id)}">View</a>
              ${item.image_path ? `<a class="small-link" href="assets/uploads/${encodeURIComponent(item.image_path)}" target="_blank" rel="noreferrer">Image</a>` : ''}
              ${item.video_path ? `<a class="small-link" href="assets/uploads/${encodeURIComponent(item.video_path)}" target="_blank" rel="noreferrer">Video</a>` : ''}
              ${item.attachment_path ? `<a class="small-link" href="assets/uploads/${encodeURIComponent(item.attachment_path)}" target="_blank" rel="noreferrer">Download</a>` : ''}
            </div>
        </article>
      `;
    }

    return `
      <article class="ad">

        ${img ? `<img src="${img}" alt="${title}" loading="lazy" />` : ''}
        <div class="ad-body">
          <h3 class="ad-title">${title}</h3>
          ${date ? `<div class="card-meta">${escapeHtml(date)}</div>` : ''}
          ${content ? `<div class="muted" style="font-size:14px;line-height:1.45; margin-top:10px;">${content}</div>` : ''}
        </div>
      </article>
    `;
  }


  return `
    <article class="card" tabindex="0" role="article" aria-label="${badgeLabel}: ${title}">
      <div class="card-media">
        ${img ? `<img src="${img}" alt="${title}" loading="lazy" />` : ''}
      </div>
      <div class="card-body">
        <div class="badges">
          <span class="badge ${badgeClass}">${badgeLabel}</span>
          ${item.attachment_path ? `<span class="badge">Has attachment</span>` : ''}
        </div>
        <h3 class="card-title">${title}</h3>
        ${date ? `<div class="card-meta"><span>${escapeHtml(date)}</span></div>` : ''}
        ${content ? `<div class="card-content">${content}</div>` : ''}
        <div class="card-actions">
          <a class="small-link" href="details.html?type=${encodeURIComponent(kind)}&id=${encodeURIComponent(item.id)}">View</a>
          ${item.video_path ? `<a class="small-link" href="assets/uploads/${encodeURIComponent(item.video_path)}" target="_blank" rel="noreferrer">Video</a>` : ''}
          ${item.attachment_path ? `<a class="small-link" href="assets/uploads/${encodeURIComponent(item.attachment_path)}" target="_blank" rel="noreferrer">Download</a>` : `<span></span>`}
        </div>
      </div>
    </article>
  `;
}


async function loadList(kind){
  const targetId = kind === 'news' ? 'newsList' : (kind === 'updates' ? 'updatesList' : 'adsList');
  const listEl = el(targetId);
  if(!listEl) return;

  listEl.innerHTML = `<div class="muted">Loading ${kind}...</div>`;
  const data = await fetchJSON(`api/list.php?type=${encodeURIComponent(kind)}`);

  if(!data.items || data.items.length === 0){
    listEl.innerHTML = `<div class="muted">No items yet.</div>`;
    return;
  }

  listEl.innerHTML = data.items.map(i => makeCard(i, kind === 'advertisement' ? 'advertisement' : kind)).join('');
}

function bindRefresh(buttonId, kind){
  const btn = el(buttonId);
  if(!btn) return;
  btn.addEventListener('click', async (e)=>{
    e.preventDefault();
    await loadList(kind);
  });
}

function renderSkeleton(kind){
  const targetId = kind === 'news' ? 'newsList' : (kind === 'updates' ? 'updatesList' : 'adsList');
  const listEl = el(targetId);
  if(!listEl) return;

  const cards = Array.from({length: 6}).map(()=>`
    <div class="skeleton">
      <div class="skel-thumb" style="background:rgba(13,21,40,.9);"></div>
      <div style="padding:14px;">
        <div class="skel-row" style="width:60%"></div>
        <div class="skel-row" style="width:80%"></div>
        <div class="skel-row" style="width:55%"></div>
        <div class="skel-row" style="width:75%"></div>
      </div>
    </div>
  `).join('');


  listEl.innerHTML = cards;
}

async function safeLoadList(kind){
  const targetId = kind === 'news' ? 'newsList' : (kind === 'updates' ? 'updatesList' : 'adsList');
  const listEl = el(targetId);
  if(!listEl) return;

  renderSkeleton(kind);

  try{
    await loadList(kind);
  }catch(err){
    console.error(err);
    listEl.innerHTML = `
      <div class="alert alert-danger" role="alert" style="margin-top:8px;">
        Failed to load ${kind}. Check server/DB connection.
      </div>
      <div style="margin-top:10px;">
        <button class="btn-secondary" type="button" onclick="location.reload()">Try again</button>
      </div>
    `;
  }
}


bindRefresh('refreshNewsBtn','news');
bindRefresh('refreshUpdatesBtn','updates');
bindRefresh('refreshAdBtn','advertisement');




(async ()=>{
  try{
    await Promise.all([
      safeLoadList('news'),
      safeLoadList('updates'),
      safeLoadList('advertisement'),
    ]);
  }catch(err){
    console.error(err);
    const any = document.querySelector('.grid, .ads');
    if(any) any.innerHTML = `<div class="muted">Failed to load content. Check server configuration.</div>`;
  }
})();


