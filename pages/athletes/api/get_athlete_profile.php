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
  if (!isset($_POST['athlete_id']) || trim($_POST['athlete_id']) === '') {
    jsonFail('Missing field: athlete_id');
  }

  $athlete_id = trim((string)$_POST['athlete_id']);
  // $athlete_id = "12345"; // Temporary hardcoded value for testing

  // 1) Athlete details
  $stmt = $pdo->prepare("
    SELECT
      athlete_id, name, age, sex, address, birthdate, birthplace,
      civil_status, citizenship, religion, contact_number, email,
      height, weight, emergency_contact, em_contact_address, em_contact_number,
      timestamp
    FROM athletes
    WHERE athlete_id = ?
    LIMIT 1
  ");
  $stmt->execute([$athlete_id]);
  $athlete = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$athlete) {
    jsonFail('Athlete not found.', 404);
  }

  // 2) Latest ECG record (more robust ordering)
  $stmt2 = $pdo->prepare("
  SELECT `timestamp`, `status`
  FROM records
  WHERE athlete_id = ?
  ORDER BY STR_TO_DATE(`timestamp`, '%Y-%m-%d %H:%i:%s') DESC
  LIMIT 1
");
  $stmt2->execute([$athlete_id]);
  $latest = $stmt2->fetch(PDO::FETCH_ASSOC);

  $athlete['last_scan']   = $latest ? $latest['timestamp'] : '';
  $athlete['last_status'] = $latest ? $latest['status'] : '';


  echo json_encode([
    'ok' => true,
    'data' => $athlete,
    'debug' => [
      'athlete_id_in' => $athlete_id,
      'latest_row' => $latest,
      'records_found' => $latest ? 1 : 0,
      'server_time' => date('Y-m-d H:i:s')
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
