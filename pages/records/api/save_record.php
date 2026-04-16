<?php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

try {
  $record_id = isset($_POST['record_id']) ? trim((string)$_POST['record_id']) : '';
  $athlete_id = trim($_POST['athlete_id'] ?? '');
  $heart_rate = trim($_POST['heart_rate'] ?? '');
  $rate_label = strtoupper(trim($_POST['rate_label'] ?? ''));
  $rhythm_label = strtoupper(trim($_POST['rhythm_label'] ?? ''));
  $status = strtoupper(trim($_POST['status'] ?? ''));

  if ($record_id === '' || $athlete_id === '' || $status === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
  }

  if ($status !== 'NORMAL' && $status !== 'ABNORMAL') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
    exit;
  }

  $allowed_rate_labels = ['', 'NORMAL', 'BRADYCARDIA', 'TACHYCARDIA'];
  if (!in_array($rate_label, $allowed_rate_labels, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid rate label']);
    exit;
  }

  $allowed_rhythm_labels = ['', 'NORMAL', 'AFIB', 'UNKNOWN'];
  if (!in_array($rhythm_label, $allowed_rhythm_labels, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid rhythm label']);
    exit;
  }

  if ($heart_rate !== '') {
    if (!ctype_digit($heart_rate)) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Heart rate must be numeric']);
      exit;
    }

    $heart_rate_int = (int)$heart_rate;
    if ($heart_rate_int < 0 || $heart_rate_int > 999) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Heart rate out of range']);
      exit;
    }

    $heart_rate = (string)$heart_rate_int;
  }

  $numeric_record_id = preg_replace('/\D+/', '', $record_id);
  if ($numeric_record_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Record ID must contain digits']);
    exit;
  }

  $timestamp = date('Y-m-d H:i:s');

  $sql = "INSERT INTO records (
            record_id,
            athlete_id,
            timestamp,
            heart_rate,
            rate_label,
            rhythm_label,
            status
          ) VALUES (
            :record_id,
            :athlete_id,
            :timestamp,
            :heart_rate,
            :rate_label,
            :rhythm_label,
            :status
          )";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':record_id'    => $numeric_record_id,
    ':athlete_id'   => $athlete_id,
    ':timestamp'    => $timestamp,
    ':heart_rate'   => $heart_rate,
    ':rate_label'   => $rate_label,
    ':rhythm_label' => $rhythm_label,
    ':status'       => $status
  ]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Server error'
  ]);
}
