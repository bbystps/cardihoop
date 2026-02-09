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
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id <= 0) jsonFail("Missing/invalid athlete id.");

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

  $data = [];
  foreach ($required as $k) {
    $data[$k] = trim((string)$_POST[$k]);
  }

  // ensure record exists
  $exists = $pdo->prepare("SELECT 1 FROM athletes WHERE id = ? LIMIT 1");
  $exists->execute([$id]);
  if (!$exists->fetchColumn()) {
    jsonFail("Athlete not found.", 404);
  }

  // athlete_id uniqueness (excluding this row)
  $chk = $pdo->prepare("SELECT 1 FROM athletes WHERE athlete_id = ? AND id <> ? LIMIT 1");
  $chk->execute([$data['athlete_id'], $id]);
  if ($chk->fetchColumn()) {
    jsonFail("Athlete ID already exists.");
  }

  $sql = "
    UPDATE athletes SET
      athlete_id = :athlete_id,
      name = :name,
      age = :age,
      sex = :sex,
      address = :address,
      birthdate = :birthdate,
      birthplace = :birthplace,
      civil_status = :civil_status,
      citizenship = :citizenship,
      religion = :religion,
      contact_number = :contact_number,
      email = :email,
      height = :height,
      weight = :weight,
      emergency_contact = :emergency_contact,
      em_contact_address = :em_contact_address,
      em_contact_number = :em_contact_number
    WHERE id = :id
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':id' => $id,
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
  ]);

  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
