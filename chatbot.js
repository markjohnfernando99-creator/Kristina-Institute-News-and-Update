(function(){
  const MODAL_ID = 'chatbot-modal';
  const TOGGLE_ID = 'chatbot-toggle';

  function ensureStyle(){
    if(document.getElementById('chatbot-style')) return;
    const style = document.createElement('style');
    style.id = 'chatbot-style';
    style.textContent = `
      #${MODAL_ID}{position:fixed; right:18px; bottom:86px; width:min(360px, calc(100vw - 36px));
        background:rgb(18,26,45); border:1px solid rgb(255,255,255);

        border-radius:18px; box-shadow:0 12px 35px rgba(0,0,0,.45);

        display:none; overflow:hidden; z-index:9999;}
      #${MODAL_ID}.open{display:block;}
      .cb-head{padding:12px 14px; border-bottom:1px solid rgba(255,255,255,.10); display:flex; align-items:center; justify-content:space-between; gap:12px;}
      .cb-title{font-weight:850; font-size:14px;}
      .cb-close{background:transparent; border:1px solid rgba(255,255,255,.14); color:#e9efff; border-radius:12px; padding:6px 10px; cursor:pointer;}
      .cb-body{height:320px; overflow:auto; padding:14px; display:flex; flex-direction:column; gap:10px;}
      .cb-msg{max-width:90%; padding:10px 12px; border-radius:14px; border:1px solid rgba(255,255,255,.10);}
      .cb-msg.bot{background:rgb(79,140,255); align-self:flex-start;}
      .cb-msg.user{background:rgb(43,212,197); align-self:flex-end;}

      .cb-text{font-size:13.5px; color:#e9efff; white-space:pre-wrap; line-height:1.35;}
      .cb-typing-dots{
        display:inline-block;
        margin-left:2px;
        opacity:.95;
        animation: cbDots 1s infinite;
      }
      @keyframes cbDots{
        0%{opacity:.5}
        50%{opacity:1}
        100%{opacity:.5}
      }


      @media (prefers-reduced-motion: reduce){
        .cb-typing-dots{animation:none}
      }

      .cb-foot{padding:12px; border-top:1px solid rgba(255,255,255,.10); display:flex; gap:10px; align-items:center;}
      .cb-input{flex:1; padding:11px 12px; border-radius:14px; border:1px solid rgba(255,255,255,.10); background:rgb(0,0,0); color:#e9efff; outline:none;}

      .cb-send{padding:11px 14px; border-radius:14px; border:0; cursor:pointer; font-weight:800;
        background:linear-gradient(135deg,var(--primary),var(--primary2)); color:#0b1220;}
      #${TOGGLE_ID}{position:fixed; right:18px; bottom:18px; z-index:9999; width:56px; height:56px; border-radius:18px;
        border:1px solid rgba(255,255,255,.12);
        background:linear-gradient(135deg, rgba(79,140,255,.95), rgba(43,212,197,.95));
        color:#0b1220; font-weight:900; cursor:pointer; box-shadow:0 12px 30px rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center;}
      .cb-hint{position:fixed; right:88px; bottom:34px; z-index:9999; color:rgba(233,239,255,.9); font-size:12px; background:rgba(0,0,0,.25); border:1px solid rgba(255,255,255,.10);
        padding:8px 10px; border-radius:14px; display:none;}
      .cb-hint.show{display:block;}
    `;
    document.head.appendChild(style);
  }

  function classify(textLower){
    const t = textLower;

    const rules = [
      {label:'greeting', keys:['hello','hi','hey','good morning','good afternoon','good evening','good day','kumusta','kamusta','magandang araw']},

      {label:'admissions', keys:[
        'admission','admissions','admit','enroll','enrollment','enrol','apply','application','register','registration',
        'open enrollment','open registration','how to apply','apply now',
        'requirements','requirement','eligibility','intake',
        'tuition','fee','fees','scholar','scholarship',
        'mag-enroll','mag enroll','enrollment requirements','admission requirements','admissions requirements'
      ]},

      {label:'documents', keys:[
        'documents','required documents','requirements list','documentary requirements','what to submit','enrollment checklist',
        'form','forms','submission','submit','2x2','2x2 picture','2x2 photo','pictures','id','valid id',
        'birth certificate','good moral','good moral certificate','gmc','barangay certificate','tor','tor copy','report card',
        'registration form','application form'
      ]},

      {label:'assessment', keys:[
        'assessment','assess','interview','exam','examination','testing','test','screening','schedule of assessment',
        'nc ii assessment','competency assessment','certification','certify','certificate','rating','evaluation','tesda assessment'
      ]},

      {label:'programs', keys:['course','program','programs','degree','diploma','certificate','short course','hospitality','culinary','chef','cooking','art','artsm','hospitality arts','culinary arts']},

      {label:'tesda', keys:['tesda','nc ii','ncii','food processing','food processing nc','cookery','housekeeping','housekeeping nc']},

      {label:'schedule', keys:['schedule','timing','timings','hours','class','classes','open','opening','start','starts','time','day','days','when','what time','morning','afternoon','evening']},

      {label:'contact', keys:['contact','phone','email','facebook','instagram','messenger','address','reach us','contact us','hotline']},

      {label:'location', keys:['where','location','near','vicinity','landmark','address','directions','how to get there','map']},

      {label:'history', keys:['history','kristina institute','krisitina institute','2020','2021','2024','2025','gourmet bangus','foundation','presiados','gerald c','geralc','tarlac','victoria']},
    ];

    for(const r of rules){
      if(r.keys.some(k => t.includes(k))) return r.label;
    }

    return 'general';
  }


  function buildChatGPTStyleAnswer(userText){
    const clean = (userText || '').trim();
    const t = clean.toLowerCase();
    const topic = classify(t);

    // Curated, deterministic knowledge answers (no hallucination)
    function matchAny(substrings){
      return substrings.some(s => t.includes(s));
    }

    const ABOUT_PRESIADOS = `GERALC C. PRESIADOS PROFILE:

• A critic in the different areas of the food service industry.
• A teacher-entrepreneur, researcher and trainer in different skills development.
• A TESDA assessor.
• Owner of TESDA Food Processing NC II in the province of Tarlac (Victoria, Tarlac).
• Owner of Kristina’s Food Products Manufacturing that produces different food products like condiments, meat process and beverages.
• A skills trainer and speaker of the Department of Trade and Industry, Department of Agriculture and other local government units in terms of Food Processing, FDA legal documentations, HACCP, and Sanitation Standard Operating Procedures for food handlers and processors.`;

    const HISTORY_KRISTINA = `KRISTINA INSTITUTE (SHORT HISTORY):

2020 - Foundation Year
Kristina Institute traces its humble beginnings to 2020, launching with Gourmet Bangus and establishing itself as a quality food processor.

2021 - TESDA Assessment Center
Opened as a TESDA-accredited Assessment Center, certifying over 500 candidates in Food Processing from multiple regions.

May 2024 - UTPRAS Registration
Secured UTPRAS registration for Food Processing NC II Training, expanding training capabilities under TESDA.

October 2024 - SHS Registration
Officially registered as a Senior High School with specialized tracks in Hospitality, Culinary Arts, and Food Processing.

May 2025 - National Recognition
Named pilot implementer of the Strengthened Senior High School Curriculum in Tarlac Province.`;

    if(matchAny(['presiados','gerald c','geralc','gerald','presiados profile','geralc c. presiados','tarlac','victoria','tesda food processing','food processing nc ii','haccp','ssop','sanitation standard operating procedures','fda legal documentations','food processing'])){
      return ABOUT_PRESIADOS;
    }

    if(matchAny(['history','krisitina institute','kristina institute','2020','foundation year','gourmet bangus','2021','tesda assessment center','assessment center','over 500','500'])){
      return HISTORY_KRISTINA;
    }


    // Existing deterministic answers by intent.
    if(topic === 'greeting'){
      return `Hi! 👋 I’m Kristina Bot.

Ask me anything about Kristina Institute—admissions/enrollment, programs (Hospitality/Culinary + TESDA NC II), schedules/timings, contact info, and even our people/history. What would you like to know?`;
    }

    if(topic === 'admissions'){
      return `Admissions & enrollment details are usually posted in the latest **News/Updates** on the homepage.

Here’s how we can proceed:
1) Tell me your target: **Hospitality** or **Culinary** (or **TESDA NC II** if that’s what you need).
2) Tell me your intake timeframe (e.g., month) and preferred **morning/afternoon** if applicable.
3) Check the announcement for: **requirements**, **schedule**, and **contact details**.

Once you share your program + intake, I’ll guide you on what to look for in the post.`;
    }

    if(topic === 'documents'){
      return `If you’re looking for requirements/documents, check the announcement that includes keywords like **Requirements**, **Documentary Requirements**, **What to Submit**, or **Enrollment Checklist**.

To be specific:
1) Program: **Hospitality / Culinary / TESDA NC II**?
2) Intake: which month/period?
3) Any specific documents you already know you need (e.g., Good Moral, forms)?`;
    }

    if(topic === 'assessment'){
      return `For **assessment/certification**, check the **News/Updates** posts that mention **Assessment**, **Schedule of Assessment**, **TESDA Assessment**, or **Certification**.

Tell me:
1) Which **TESDA NC II** (Food Processing / Cookery / Housekeeping)?
2) Timeframe (this week / next month)?
3) If the post includes exam/interview details?`;
    }

    if(topic === 'programs'){
      return `We focus on two main areas:

• **Hospitality Arts** — training related to hospitality services and operations.
• **Culinary Arts** — training related to cooking/culinary skills.

To guide you better:
1) Which one do you prefer (**Hospitality** or **Culinary**)?
2) Basics, advanced, or a specific goal?`;
    }

    if(topic === 'tesda'){
      return `TESDA Programs (NC II) available at Kristina Institute:

1) **Food Processing NC II**
   • Learn proper food preparation, processing, safety and hygiene.

2) **Cookery NC II**
   • Develop practical cooking skills and kitchen safety.

3) **Housekeeping NC II**
   • Train for cleaning/sanitation and room preparation tasks.

Tell me which NC II you want and I’ll help you find the latest **intake schedule**, **requirements**, and **next steps**.`;
    }

    if(topic === 'schedule'){
      return `For the latest **schedule/timings**, check the **News** and **Updates** sections on the homepage.

Tip: Tell me the **day/time** you mean (e.g., “morning classes” or “afternoon sessions”) and I’ll point you to the keywords in the posted announcement.`;
    }

    if(topic === 'contact'){
      return `Contact information is typically posted in the latest **News/Updates**.

Tell me what you need (admissions email, phone number, FB page, etc.) and I’ll help you locate it in the latest announcement.`;
    }

    if(topic === 'location'){
      return `For **address/location** announcements, check the **News** section on the homepage.

If you share a landmark/area you’re looking near, I can help you narrow down what to look for in the posted content.`;
    }


    // General fallback that still feels conversational.
    const last = clean.split(/\s+/).slice(-12).join(' ');
    return `I can help with Kristina Institute info shown on this site.

Your question: “${last || clean}”

Supported topics:
• Admissions / enrollment requirements
• Programs (Hospitality vs Culinary)
• TESDA NC II / qualifications
• Schedules / class timings
• Contact / location
• Sir Gerald C. Presiados profile
• Kristina Institute history (2020–2021)

Reply with one supported topic and I’ll answer step-by-step.`;
  }

  function addMessage(container, text, role){
    const msg = document.createElement('div');
    msg.className = `cb-msg ${role === 'user' ? 'user' : 'bot'}`;
    msg.innerHTML = `<div class="cb-text"></div>`;
    msg.querySelector('.cb-text').textContent = text;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;
  }

  function init(){
    ensureStyle();

    if(document.getElementById(TOGGLE_ID)) return;

    const toggle = document.createElement('button');
    toggle.id = TOGGLE_ID;
    toggle.type = 'button';
    toggle.setAttribute('aria-label','Open chat');
      toggle.textContent = '💬';
    document.body.appendChild(toggle);
    toggle.setAttribute('title','Chat with Kristina Bot');


    const hint = document.createElement('div');
    hint.className = 'cb-hint';
    hint.textContent = 'Chat with Kristina Bot';
    document.body.appendChild(hint);

    const modal = document.createElement('div');
    modal.id = MODAL_ID;
    modal.innerHTML = `
      <div class="cb-head">
        <div class="cb-title">Kristina Bot</div>
        <button class="cb-close" type="button" aria-label="Close chat">Close</button>
      </div>
      <div class="cb-body" id="cb-body"></div>
      <form class="cb-foot" id="cb-form">
        <input class="cb-input" id="cb-input" placeholder="Type your question..." autocomplete="off" />
        <button class="cb-send" type="submit">Send</button>
      </form>
    `;
    document.body.appendChild(modal);

    const body = modal.querySelector('#cb-body');
    const form = modal.querySelector('#cb-form');
    const input = modal.querySelector('#cb-input');
    const closeBtn = modal.querySelector('.cb-close');

    addMessage(body, 'Hi! I’m Kristina Bot. Ask me about admissions, courses/programs, schedules, or contact info.', 'bot');

      function open(){
      modal.classList.add('open');
      setTimeout(()=>input.focus(), 50);
      hint.classList.remove('show');
    }
    function close(){
      modal.classList.remove('open');
      toggle.focus();
    }


    toggle.addEventListener('click', ()=>{
      if(modal.classList.contains('open')) close(); else open();
    });
    closeBtn.addEventListener('click', close);

    setTimeout(()=>{ hint.classList.add('show'); setTimeout(()=>hint.classList.remove('show'), 2500); }, 2000);

    form.addEventListener('submit', (e)=>{
      e.preventDefault();
      const v = (input.value || '').trim();
      if(!v) return;

      addMessage(body, v, 'user');
      input.value = '';

      // Typing indicator
      const typingId = `typing-${Date.now()}`;
      const typing = document.createElement('div');
      typing.className = 'cb-msg bot';
      typing.id = typingId;
      typing.innerHTML = `<div class="cb-text">Typing<span class="cb-typing-dots">...</span></div>`;
      body.appendChild(typing);
      body.scrollTop = body.scrollHeight;

      setTimeout(async ()=>{
        const t = document.getElementById(typingId);
        if(t) t.remove();

        const sessionId = (()=>{
          try{
            const k='cb-session-id';
            let sid = localStorage.getItem(k);
            if(!sid){
              sid = 's_' + Math.random().toString(16).slice(2) + '_' + Date.now();
              localStorage.setItem(k, sid);
            }
            return sid;
          }catch(e){
            return 's_' + Date.now();
          }
        })();

        // crude language detection: if it contains common Filipino words/characters, use 'fil'
        const language = (()=>{
          const tl = (v || '').toLowerCase();
          const filHints = ['po','opo','hindi','kung','ano','sino','para','kailan','mag','enroll','enrollment','admission','requirements','tesda'];
          if (/[\u1000-\u109F]/.test(v) || filHints.some(h => tl.includes(h))) return 'fil';
          return 'en';
        })();

        // Try server-side grounded chat first
        let answer = '';
        let shouldFallbackToOpenAI = false;

        try{
          const res = await fetch('api/chat.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
              session_id: sessionId,
              message: v,
              language
            })
          });
          if(res.ok){
            const data = await res.json();
            answer = (data && data.answer) ? data.answer : '';

            const a = (answer || '').toLowerCase();
            // If grounded retrieval couldn't find anything, escalate to OpenAI.
            if(!answer || a.includes('couldn\'t find') || a.includes('hindi ko makita') || a.includes('no matching') || a.includes('wala')){
              shouldFallbackToOpenAI = true;
            }
          }else{
            shouldFallbackToOpenAI = true;
          }
        }catch(e){
          shouldFallbackToOpenAI = true;
        }

        // OpenAI fallback (only when grounded retrieval fails)
        if(shouldFallbackToOpenAI){
          try{
            // conversation_id is what openai_chat.php uses for history.
            const conversationId = (()=>{
              try{
                const k = 'cb-conversation-id';
                let cid = localStorage.getItem(k);
                if(!cid){
                  cid = 'c_' + Math.random().toString(16).slice(2) + '_' + Date.now();
                  localStorage.setItem(k, cid);
                }
                return cid;
              }catch(e){
                return 'c_' + Date.now();
              }
            })();

            const res2 = await fetch('api/openai_chat.php', {
              method:'POST',
              headers:{'Content-Type':'application/json'},
              body: JSON.stringify({
                conversation_id: conversationId,
                message: v,
                language
              })
            });

            if(res2.ok){
              const data2 = await res2.json();
              answer = (data2 && data2.answer) ? data2.answer : '';
            }
          }catch(e){
            // ignore and fallback to deterministic template
          }
        }

        if(!answer){
          answer = buildChatGPTStyleAnswer(v);
        }


        addMessage(body, answer, 'bot');
      }, 450);
    });

  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  }else{
    init();
  }
})();

