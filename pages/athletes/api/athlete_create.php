<?php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

function jsonFail($msg, $code = 400)
{
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $required = [
    'athlete_id',
    'name',
    'age',
    'sex',
    'address',
    'birthdate',
    'birthplace',
    'civil_status',
    'citizenship',
    'religion',
    'contact_number',
    'email',
    'height',
    'weight',
    'emergency_contact',
    'em_contact_address',
    'em_contact_number'
  ];

  foreach ($required as $k) {
    if (!isset($_POST[$k]) || trim($_POST[$k]) === '') {
      jsonFail("Missing field: $k");
    }
  }

  // Basic sanitize
  $data = [];
  foreach ($required as $k) {
    $data[$k] = trim((string)$_POST[$k]);
  }

  // timestamp (your table uses varchar(32))
  $timestamp = date('Y-m-d H:i:s');

  // Optional: enforce athlete_id uniqueness
  $chk = $pdo->prepare("SELECT 1 FROM athletes WHERE athlete_id = ? LIMIT 1");
  $chk->execute([$data['athlete_id']]);
  if ($chk->fetchColumn()) {
    jsonFail("Athlete ID already exists.");
  }

  $sql = "
    INSERT INTO athletes (
      athlete_id, name, age, sex, address, birthdate, birthplace,
      civil_status, citizenship, religion, contact_number, email,
      height, weight, emergency_contact, em_contact_address, em_contact_number, timestamp
    ) VALUES (
      :athlete_id, :name, :age, :sex, :address, :birthdate, :birthplace,
      :civil_status, :citizenship, :religion, :contact_number, :email,
      :height, :weight, :emergency_contact, :em_contact_address, :em_contact_number, :timestamp
    )
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':athlete_id' => $data['athlete_id'],
    ':name' => $data['name'],
    ':age' => $data['age'],
    ':sex' => $data['sex'],
    ':address' => $data['address'],
    ':birthdate' => $data['birthdate'],
    ':birthplace' => $data['birthplace'],
    ':civil_status' => $data['civil_status'],
    ':citizenship' => $data['citizenship'],
    ':religion' => $data['religion'],
    ':contact_number' => $data['contact_number'],
    ':email' => $data['email'],
    ':height' => $data['height'],
    ':weight' => $data['weight'],
    ':emergency_contact' => $data['emergency_contact'],
    ':em_contact_address' => $data['em_contact_address'],
    ':em_contact_number' => $data['em_contact_number'],
    ':timestamp' => $timestamp
  ]);

  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
