<?php
// One-time seed helper for TESDA qualification cards.
// Run manually via browser or CLI: http://localhost/kristina-updates/tesda-seed.php
// It will INSERT missing TESDA records into `posts`.

require __DIR__ . '/config/db.php';

function upsertIfMissing(PDO $pdo, string $type, string $title, array $contentObj){
  $check = $pdo->prepare('SELECT id FROM posts WHERE type=:type AND title=:title LIMIT 1');
  $check->execute([':type'=>$type, ':title'=>$title]);
  $row = $check->fetch(PDO::FETCH_ASSOC);
  if($row) return;

  $content = json_encode($contentObj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  $stmt = $pdo->prepare('INSERT INTO posts (type, title, content, author_name, image_path, attachment_path, video_path, created_at) VALUES (:type,:title,:content,NULL,NULL,NULL,NULL,NOW())');
  $stmt->execute([
    ':type'=>$type,
    ':title'=>$title,
    ':content'=>$content,
  ]);
}

$assessmentPrograms = [
  [
    'title' => 'Food Processing NC II',
    'content' => [
      'program_title' => 'Food Processing NC II',
      'description' => 'Training for preparing, processing, and handling food products using proper methods, tools, and safety practices.',
      'training_duration' => '',
      'requirements' => '',
      'learning_outcomes' => '',
      'certificate_information' => 'NC II',
    ]
  ],
  [
    'title' => 'Cookery NC II',
    'content' => [
      'program_title' => 'Cookery NC II',
      'program_description' => 'TESDA assessment and certification programs designed to validate the skills and competencies of learners and workers through national certification.',
      'training_duration' => '',
      'requirements' => '',
      'learning_outcomes' => '',
      'certificate_information' => 'NC II',
    ]
  ],
];

$trainingPrograms = [
  ['Calamansi Juice Making'],
  ['Yogurt Making'],
  ['Tocino and Longganisa Making'],
  ['Ham Making'],
  ['Embutido Making'],
  ['Bread Making'],
  ['Pastry Making'],
  ['Sardines Making'],
  ['Tomato Jam Making'],
  ['Good Manufacturing Practice Training'],
  ['Basic Occupational Health and Food Safety Training'],
  ['Ginger Balm Making'],
  ['Ube Processing'],
  ['Entrepreneurial Management in Food Processing'],
  ['Fundamentals of Asian Cuisine'],
];

foreach($assessmentPrograms as $p){
  upsertIfMissing($pdo, 'tesda_assessment', $p['title'], $p['content']);
}

foreach($trainingPrograms as $p){
  $title = $p[0];
  upsertIfMissing($pdo, 'tesda_training', $title, [
    'program_title' => $title,
    'program_description' => 'Community-based livelihood and skills training programs designed to develop practical competencies, promote entrepreneurship, and support local economic development.',
    'training_duration' => '',
    'requirements' => '',
    'learning_outcomes' => '',
    'certificate_information' => '',
  ]);
}

echo 'TESDA seed complete.';

