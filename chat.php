<?php
session_start();

require __DIR__ . '/config/db.php';

// If not logged in, allow viewing but require login to save history.
$userId = $_SESSION['user_id'] ?? '';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kristina AI Chat</title>
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    .chat-wrap{max-width:980px; margin:0 auto; padding:18px 16px 30px;}
    .chat-shell{display:grid; grid-template-columns:320px 1fr; gap:16px; align-items:start;}
    @media(max-width:880px){.chat-shell{grid-template-columns:1fr;}}

    .panel{background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow); padding:16px;}
    .side-title{margin:0 0 10px; font-size:18px;}
    .muted{color:var(--muted);}

    .theme-row{display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:10px;}

    .chat-topbar{display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;}

    #messages{height:65vh; min-height:420px; overflow:auto; padding:12px; display:flex; flex-direction:column; gap:10px;}
    @media(max-width:880px){#messages{height:60vh;}}

    .msg{max-width:92%; padding:11px 12px; border-radius:14px; border:1px solid rgba(255,255,255,.08); white-space:pre-wrap; line-height:1.4;}
    .msg.user{align-self:flex-end; background:rgba(43,212,197,.12); border-color:rgba(43,212,197,.30);}
    .msg.ai{align-self:flex-start; background:rgba(79,140,255,.12); border-color:rgba(79,140,255,.30);}

    .typing{display:inline-flex; gap:4px; align-items:center;}
    .dot{width:6px; height:6px; border-radius:50%; background:rgba(233,239,255,.8); animation: tDots 1.2s infinite ease-in-out;}
    .dot:nth-child(2){animation-delay:.15s}
    .dot:nth-child(3){animation-delay:.30s}
    @keyframes tDots{0%,100%{transform:translateY(0); opacity:.35} 50%{transform:translateY(-4px); opacity:1}}

    #composer{display:flex; gap:10px; align-items:flex-end; margin-top:12px;}
    #composer textarea{flex:1; min-height:48px; max-height:140px; resize:none; padding:12px 12px; border-radius:14px; border:1px solid var(--border); background:rgba(0,0,0,.2); color:var(--text); outline:none;}
    .btn{display:inline-flex; align-items:center; justify-content:center; padding:11px 14px; border-radius:12px; color:#0b1220; text-decoration:none; font-weight:750; background:linear-gradient(135deg,var(--primary),var(--primary2)); border:0; cursor:pointer;}

    .btn-ghost{background:transparent; color:var(--text); border:1px solid var(--border);}

    .row{display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;}

    .link{color:var(--primary); text-decoration:none; font-weight:800}

    /* ChatGPT-like scroll anchoring */
    .spacer{height:1px;}
  </style>
</head>
<body class="has-bg-image">

<header class="site-header">
  <div class="container header-inner">
    <div class="brand">
      <div class="brand-mark" aria-hidden="true"><img class="brand-logo" src="assets/KIHCA LOGO MALIWANAG.png" alt="Kristina" /></div>
      <div>
        <div class="brand-title">Kristina Institute</div>
        <div class="brand-subtitle">ChatGPT-style AI</div>
      </div>
    </div>
    <nav class="nav">
      <a href="index.html" class="nav-link">Home</a>
      <?php if(!$userId): ?>
        <a href="login.php" class="nav-link nav-link-primary">Login</a>
      <?php else: ?>
        <a href="history.php" class="nav-link">History</a>
        <a href="logout.php" class="nav-link">Logout</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div class="chat-wrap">
  <div class="chat-shell">

    <aside class="panel">
      <h3 class="side-title">Account</h3>
      <?php if(!$userId): ?>
        <div class="muted" style="font-size:14px; line-height:1.5;">
          Login to save chat history.
        </div>
        <div style="margin-top:12px; display:flex; gap:10px;">
          <a class="btn" href="login.php">Login</a>
          <a class="btn btn-ghost" href="register.php">Register</a>
        </div>
      <?php else: ?>
        <div class="muted" style="font-size:14px; line-height:1.5;">
          Signed in. Your chats will be saved.
        </div>
        <div style="margin-top:12px; display:flex; gap:10px;">
          <a class="btn" href="history.php">Chat History</a>
        </div>
      <?php endif; ?>

      <div class="theme-row">
        <div>
          <div style="font-weight:900; font-size:13px;">Dark mode</div>
          <div class="muted" style="font-size:12.5px; margin-top:2px;">Uses your existing site theme.</div>
        </div>
        <button id="toggleTheme" class="btn btn-ghost" type="button">Toggle</button>
      </div>

      <hr style="border:0; border-top:1px solid var(--border); margin:14px 0;" />
      <div class="muted" style="font-size:13.5px; line-height:1.5;">
        Tip: Ask any topic. This uses OpenAI when configured.
      </div>

      <div style="margin-top:12px;" class="muted" id="connStatus"></div>

    </aside>

    <main class="panel">
      <div class="chat-topbar">
        <div>
          <div style="font-weight:950; font-size:18px;">Chat</div>
          <div class="muted" style="font-size:13.5px; margin-top:2px;">AI typing animation + saved history</div>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button id="newChat" class="btn btn-ghost" type="button">New chat</button>
          <button id="clearChat" class="btn btn-ghost" type="button">Clear</button>
        </div>
      </div>

      <div id="messages"></div>
      <div class="spacer" id="bottom"></div>

      <form id="composer" autocomplete="off">
        <textarea id="prompt" placeholder="Type your message..." maxlength="4000"></textarea>
        <button class="btn" id="sendBtn" type="submit">Send</button>
      </form>

    </main>

  </div>
</div>

<script>
  const USER_ID = <?php echo $userId ? json_encode($userId) : 'null'; ?>;
</script>
<script>
  const messagesEl = document.getElementById('messages');
  const promptEl = document.getElementById('prompt');
  const bottomEl = document.getElementById('bottom');
  const connStatus = document.getElementById('connStatus');

  let conversationId = null;
  let isTyping = false;

  function escapeHtml(s){
    return (s ?? '').toString()
      .replaceAll('&','&amp;')
      .replaceAll('<','<')
      .replaceAll('>','>');
  }

  function addMsg(role, text){
    const div = document.createElement('div');
    div.className = 'msg ' + (role === 'user' ? 'user' : 'ai');
    div.innerHTML = escapeHtml(text);
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function addTyping(){
    const div = document.createElement('div');
    div.className = 'msg ai typing';
    div.id = 'typing-indicator';
    div.innerHTML = '<span class="typing"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="muted" style="margin-left:8px; font-size:13px;">Thinking…</span></span>';
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function removeTyping(){
    const t = document.getElementById('typing-indicator');
    if(t) t.remove();
  }

  async function api(path, body){
    const res = await fetch(path, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
    const data = await res.json().catch(()=> ({}));
    if(!res.ok || !data.ok){
      const msg = data.error || 'Request failed';
      throw new Error(msg);
    }
    return data;
  }

  async function ensureConversation(){
    // Create a new conversation session for the logged user (if possible)
    if(conversationId) return conversationId;
    if(!USER_ID){
      // Anonymous: create a temporary ID for frontend only.
      conversationId = 'anon_' + Math.random().toString(16).slice(2);
      return conversationId;
    }

    const res = await api('api/session_create.php', {user_id: USER_ID});
    conversationId = res.conversation_id;
    return conversationId;
  }

  async function loadHistory(){
    if(!USER_ID) return;
    if(!conversationId) return;
    const res = await api('api/messages_list.php', {conversation_id: conversationId});
    messagesEl.innerHTML = '';
    for(const m of res.messages){
      addMsg(m.role, m.content);
    }
  }

  async function send(text){
    const t = (text || '').trim();
    if(!t) return;

    addMsg('user', t);
    promptEl.value = '';

    isTyping = true;
    addTyping();

    try{
      const cid = await ensureConversation();
      const res = await api('api/messages_send.php', {
        conversation_id: cid,
        message: t,
        language: 'en'
      });
      removeTyping();
      isTyping = false;
      addMsg('assistant', res.answer);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }catch(err){
      removeTyping();
      isTyping = false;
      addMsg('assistant', 'Error: ' + (err.message || err));
    }
  }

  document.getElementById('composer').addEventListener('submit', (e)=>{
    e.preventDefault();
    if(isTyping) return;
    send(promptEl.value);
  });

  document.getElementById('newChat').addEventListener('click', async ()=>{
    conversationId = null;
    messagesEl.innerHTML = '';
    addMsg('assistant', 'New chat started. How can I help you today?');
  });

  document.getElementById('clearChat').addEventListener('click', ()=>{
    messagesEl.innerHTML = '';
  });

  document.getElementById('toggleTheme').addEventListener('click', ()=>{
    document.body.classList.toggle('light-theme');
    // We keep it simple: existing CSS is dark-first.
  });

  addMsg('assistant', 'Hello! I’m Kristina AI. What would you like to ask?');
  
  connStatus.textContent = 'OpenAI chat endpoint ready (configure API key in config/openai.php).';
</script>
</body>
</html>

