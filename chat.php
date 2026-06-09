<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload']);
  exit;
}

$userText = trim((string)($payload['message'] ?? ''));
$language = strtolower(trim((string)($payload['language'] ?? 'en')));
$sessionId = (string)($payload['session_id'] ?? '');

if ($userText === '') {
  http_response_code(200);
  echo json_encode(['ok' => false, 'answer' => 'Please type a message.', 'language' => $language]);
  exit;
}

// Safety: basic filtering (no external AI provider yet)
$blocked = [
  'password', 'credit card', 'ssn', 'social security',
];
$lower = mb_strtolower($userText);
foreach ($blocked as $b) {
  if (mb_strpos($lower, $b) !== false) {
    echo json_encode([
      'ok' => true,
      'language' => $language,
      'answer' => "I can’t help with that request. If you need assistance, tell me what you’re trying to accomplish (e.g., admissions, TESDA programs, schedules, requirements)."
    ]);
    exit;
  }
}

// Simple language handling
$fil = in_array($language, ['fil','filipino','tl','tagalog','tl-ph'], true);

function normalize($s){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/u', ' ', $s);
  return $s;
}

function classify($t){
  $t = mb_strtolower($t);

  $rules = [
    // greetings
    ['greeting', ['hello','hi','hey','good morning','good afternoon','good evening','kumusta','magandang araw','good day','kamusta']],

    // admissions / enrollment / application intent
    ['admissions', [
      'admission','admissions','enroll','enrollment','enrol','apply','application','register','registration',
      'requirements','requirement','eligibility','intake','open enrollment','open registration','how to apply','apply now',
      'mag-enroll','mag enroll','enrollment requirements','admission requirements','admissions requirements',
      'tuition','fee','fees','tuition fee','scholar','scholarship'
    ]],

    // supporting documents / forms
    ['documents', [
      'documents','requirements','required documents','requirements list','form','forms','submission','submit','requirements checklist',
      'birth certificate','bc','good moral','good moral certificate','gmc','barangay certificate','id','valid id',
      '2x2','2x2 picture','2x2 photo','pictures','tor','tor copy','report card','transfer credential','good moral certificate',
      'registration form','application form'
    ]],

    // assessment / certification
    ['assessment', [
      'assessment','assess','interview','exam','examination','testing','test','screening','schedule of assessment',
      'nc ii assessment','competency assessment','certification','certify','certificate','rating','evaluation',
      'tesda assessment'
    ]],

    // program/course
    ['programs', ['course','program','programs','degree','diploma','certificate','short course','hospitality','culinary','chef','cooking','art','artsm','hospitality arts','culinary arts']],

    // TESDA programs
    ['tesda', ['tesda','nc ii','ncii','food processing','food processing nc','cookery','housekeeping']],

    // schedule/timings
    ['schedule', ['schedule','timing','timings','hours','class','classes','open','opening','start','starts','time','day','days','when','what time','morning','afternoon','evening']],

    // contact & location
    ['contact', ['contact','phone','email','facebook','instagram','messenger','address','reach us','contact us','hotline']],
    ['location', ['where','location','near','vicinity','landmark','address','directions','how to get there','map']],

    // history/people
    ['history', ['history','krisitina institute','kristina institute','2020','2021','2024','2025','gourmet bangus','foundation','presiados','gerald c','geralc','gerald c. presiados','tarlac','victoria']],
  ];

  foreach ($rules as $rule) {
    $label = $rule[0];
    $keys = $rule[1];
    foreach ($keys as $k) {
      if ($k !== '' && mb_strpos($t, $k) !== false) return $label;
    }
  }

  return 'general';
}


function answerFixed($topic, $fil){
  if ($topic === 'greeting') {
    return $fil
      ? "Hi! 👋 Ako si Kristina Bot. Maaari mo akong tanungin tungkol sa Kristina Institute—admissions/enrollment, programs (Hospitality/Culinary + TESDA NC II), schedules/timings, contact at maging history. Ano ang gusto mong malaman?"
      : "Hi! 👋 I’m Kristina Bot. Ask me anything about Kristina Institute—admissions/enrollment, programs (Hospitality/Culinary + TESDA NC II), schedules/timings, contact info, and even our history. What would you like to know?";
  }

  if ($topic === 'admissions') {
    return $fil
      ? "Para sa admissions/enrollment, karaniwang naka-post ang detalye sa pinakabagong **News/Updates** sa homepage.

**Gabay (step-by-step):**
1) Sabihin mo ang target mo: **Hospitality** o **Culinary** (o TESDA NC II kung TESDA ang hanap).
2) Sabihin mo ang intake/timeframe (hal. buwan at kung **morning/afternoon**).
3) I-check ang announcement para sa: **requirements**, **schedule**, at **contact details**.

Kapag sinabi mo ang program + target intake, gagabayan kita kung anong keywords ang hahanapin sa post." 
      : "Admissions & enrollment details are usually posted in the latest **News/Updates** on the homepage.

**How we’ll do it (step-by-step):**
1) Tell me your target: **Hospitality** or **Culinary** (or **TESDA NC II** if that’s what you need).
2) Tell me your intake timeframe (e.g., month) and preferred **morning/afternoon** if applicable.
3) Check the announcement for: **requirements**, **schedule**, and **contact details**.

Once you share your program + intake, I’ll guide you on what to look for in the post.";
  }

  if ($topic === 'documents') {
    return $fil
      ? "Kapag requirements/documents ang hanap mo, i-check ang announcement na may pamagat na may keywords tulad ng **Requirements**, **Documentary Requirements**, **What to Submit**, o **Enrollment Checklist**.

Para masakto: sagutin mo ito:
1) Program: **Hospitality / Culinary / TESDA NC II**?
2) Intake: anong month/period?
3) May specifics ka ba (e.g., **Good Moral**, **Form**, **2x2 pictures**)?" 
      : "If you’re looking for requirements/documents, check the announcement that includes keywords like **Requirements**, **Documentary Requirements**, **What to Submit**, or **Enrollment Checklist**.

To be specific:
1) Program: **Hospitality / Culinary / TESDA NC II**?
2) Intake: which month/period?
3) Any specific documents you already know you need (e.g., Good Moral, forms)?";
  }

  if ($topic === 'assessment') {
    return $fil
      ? "Para sa **assessment/certification**, hanapin sa **News/Updates** ang mga post na may salitang tulad ng **Assessment**, **Schedule of Assessment**, **TESDA Assessment**, o **Certification**.

Sabihin mo:
1) Anong **TESDA NC II** (Food Processing / Cookery / Housekeeping)?
2) Timeframe (hal. this week / next month)?
3) Kung may exam/interview details ba sa post?" 
      : "For **assessment/certification**, check the **News/Updates** posts that mention **Assessment**, **Schedule of Assessment**, **TESDA Assessment**, or **Certification**.

Tell me:
1) Which **TESDA NC II** (Food Processing / Cookery / Housekeeping)?
2) Timeframe (this week / next month)?
3) If the post includes exam/interview details?";
  }

  if ($topic === 'programs') {
    return $fil
      ? "Dalawang pangunahing focus natin:

• **Hospitality Arts** — training para sa hospitality services at operations.
• **Culinary Arts** — training para sa pagluluto at culinary skills.

Para maitama ang next steps:
1) Alin ang gusto mo (**Hospitality** o **Culinary**)?
2) Basics ba, advanced, o may specific na goal?"
      : "We focus on two main areas:

• **Hospitality Arts** — training related to hospitality services and operations.
• **Culinary Arts** — training related to cooking/culinary skills.

To guide you better:
1) Which one do you prefer (**Hospitality** or **Culinary**)?
2) Basics, advanced, or a specific goal?";
  }

  if ($topic === 'tesda') {
    return $fil
      ? "Mga TESDA Programs (NC II) sa Kristina Institute:

1) **Food Processing NC II** — tamang paghahanda, processing, safety at hygiene.
2) **Cookery NC II** — practical cooking skills at kitchen safety.
3) **Housekeeping NC II** — sanitation, housekeeping tasks, at room preparation.

Sabihin mo kung alin ang gusto mo (Food Processing / Cookery / Housekeeping) at i-match ko sa latest **intake schedule**, **requirements**, at **next steps**." 
      : "TESDA Programs (NC II) available at Kristina Institute:

1) **Food Processing NC II**
   • Learn proper food preparation, processing, safety and hygiene.

2) **Cookery NC II**
   • Develop practical cooking skills and kitchen safety.

3) **Housekeeping NC II**
   • Train for cleaning/sanitation and room preparation tasks.

Tell me which NC II you want and I’ll help you find the latest **intake schedule**, **requirements**, and **next steps**.";
  }


  if ($topic === 'schedule') {
    return $fil
      ? "Para sa **latest schedule/timings**, i-check ang **News** at **Updates** sa homepage.

Tip: Kapag sinabi mo kung anong **day/time** (hal. “morning classes” o “afternoon sessions”), tutulungan kitang hanapin kung ano ang keywords na dapat i-check sa announcement." 
      : "For the latest **schedule/timings**, check the **News** and **Updates** sections on the homepage.

Tip: Tell me the **day/time** you mean (e.g., “morning classes” or “afternoon sessions”) and I’ll point you to the keywords in the posted announcement.";
  }


  if ($topic === 'contact') {
    return $fil
      ? "Karaniwang naka-post ang contact information sa pinakabagong **News/Updates**.

Sabihin mo kung anong kailangan mo (hal. **email for admissions**, **phone number**, o **FB page**) para mas madali nating mahanap sa post." 
      : "Contact information is typically posted in the latest **News/Updates**.

Tell me what you need (admissions email, phone number, FB page, etc.) and I’ll help you locate it in the latest announcement.";
  }

  if ($topic === 'location') {
    return $fil
      ? "Para sa **address/location** announcements, i-check ang **News** section sa homepage.

Kung sasabihin mo ang landmark/area (hal. near anong lugar), tutulungan kitang i-narrow down kung ano ang dapat hanapin sa post." 
      : "For **address/location** announcements, check the **News** section on the homepage.

If you share a landmark/area you’re looking near, I can help you narrow down what to look for in the posted content.";
  }

  if ($topic === 'history') {
    return $fil
      ? "**KRISTINA INSTITUTE (SHORT HISTORY)**

2020 — Foundation Year
Sinimulan ang Kristina Institute noong 2020, inilunsad ang Gourmet Bangus at naging kilala bilang quality food processor.

2021 — TESDA Assessment Center
Na-open bilang TESDA-accredited assessment center at nakapagtala ng mahigit 500 kandidato sa Food Processing.

May 2024 — UTPRAS Registration
Na-secure ang UTPRAS registration para sa Food Processing NC II Training.

October 2024 — SHS Registration
Opisyal na nakapag-register bilang Senior High School (Hospitality, Culinary Arts, at Food Processing tracks).

May 2025 — National Recognition
Pinangalanang pilot implementer ng Strengthened Senior High School Curriculum sa Tarlac Province."
      : "**KRISTINA INSTITUTE (SHORT HISTORY)**

2020 - Foundation Year
Kristina Institute traces its humble beginnings to 2020, launching with Gourmet Bangus and establishing itself as a quality food processor.

2021 - TESDA Assessment Center
Opened as a TESDA-accredited Assessment Center, certifying over 500 candidates in Food Processing from multiple regions.

May 2024 - UTPRAS Registration
Secured UTPRAS registration for Food Processing NC II Training.

October 2024 - SHS Registration
Officially registered as a Senior High School with specialized tracks in Hospitality, Culinary Arts, and Food Processing.

May 2025 - National Recognition
Named pilot implementer of the Strengthened Senior High School Curriculum in Tarlac Province.";
  }

  return null;
}


$topic = classify($userText);
$fixed = answerFixed($topic, $fil);
if ($fixed !== null) {
  echo json_encode(['ok' => true, 'language' => $language, 'answer' => $fixed]);
  exit;
}

// Retrieval (grounded) using posts table
$q = normalize($userText);

// Keep the query bounded to avoid extremely broad LIKEs
if (mb_strlen($q) > 80) $q = mb_substr($q, 0, 80);

// Try multiple matching signals (still grounded: from your posts only)
$stmt = $pdo->prepare(
  'SELECT id, type, title, content, author_name, created_at
   FROM posts
   WHERE (
     title LIKE :like
     OR content LIKE :like
     OR author_name LIKE :like
     OR type LIKE :like
   )
   ORDER BY created_at DESC
   LIMIT 10'
);

$like = '%'.$q.'%';
$stmt->execute([':like' => $like]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


function briefContent($content){
  $content = trim((string)$content);
  // remove excessive whitespace
  $content = preg_replace('/\s+/u', ' ', $content);
  if (mb_strlen($content) > 420) {
    $content = mb_substr($content, 0, 420).'...';
  }
  return $content;
}

if (!$items) {
  $unc = $fil
    ? "Hindi ko makita sa mga naka-post na announcements ang eksaktong sagot sa ngayon. 

Subukan mo:
• i-type kung anong **program** (Hospitality/Culinary o TESDA NC II) 
• anong **timeframe/intake** (hal. Sept/Oct, morning classes)

Pwede rin mong i-check ang **News/Updates** para sa latest requirements."
    : "I couldn’t find a matching answer in the currently posted announcements.

Try:
• specify the **program** (Hospitality/Culinary or TESDA NC II)
• include **timeframe/intake** (e.g., Sept/Oct, morning classes)

You can also check **News/Updates** for the latest requirements.";

  echo json_encode(['ok' => true, 'language' => $language, 'answer' => $unc]);
  exit;
}

// Build grounded response from top items
$lines = [];
if ($fil) {
  $lines[] = "Batay sa mga naka-post na detalye, ito ang pinaka-malapit na impormasyon:";
} else {
  $lines[] = "Based on the latest posted information, here are the closest matches:";
}

foreach ($items as $it) {
  $type = $it['type'] ?? '';
  $kind = $type === 'updates' ? 'Update' : ($type === 'advertisement' ? 'Advertisement' : ($type === 'news' ? 'News' : strtoupper($type)));
  $title = (string)($it['title'] ?? '');
  $content = (string)($it['content'] ?? '');
  $snippet = briefContent($content);

  $lines[] = "• **{$kind}:** {$title}\n  — {$snippet}";
}

$follow = $fil
  ? "

Gusto mo ba na i-summarize ko ito para sa iyong situation (hal. **admissions requirements** o **TESDA NC II** na kailangan)? Sabihin mo lang ang program at target mo." 
  : "

Do you want me to summarize this for your specific situation (e.g., admissions requirements or the TESDA NC II you need)? Tell me your program and goal.";

$answer = implode("\n", $lines).$follow;

echo json_encode(['ok' => true, 'language' => $language, 'answer' => $answer]);

