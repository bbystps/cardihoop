<?php
// admin/dashboard/save_record.php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

try {
  $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
  $athlete_id = trim($_POST['athlete_id'] ?? '');
  $status = trim($_POST['status'] ?? '');

  if ($record_id <= 0 || $athlete_id === '' || $status === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
  }


  // Optional: enforce allowed values
  if ($status !== 'Normal' && $status !== 'Abnormal') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
    exit;
  }

  // timestamp from server
  $timestamp = date('Y-m-d H:i:s');

  // IMPORTANT:
  // Your table says record_id is int(16), but you're now saving "1771383266911.json" (string).
  // You MUST change record_id to VARCHAR if you truly want to store filename.
  // If you keep it INT, store only the numeric part (e.g. 1771383266911).
  //
  // We'll store numeric part only to match your current schema:
  $numeric_record_id = preg_replace('/\D+/', '', $record_id); // keep digits only
  if ($numeric_record_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Record ID must contain digits']);
    exit;
  }

  $sql = "INSERT INTO records (record_id, athlete_id, timestamp, status)
          VALUES (:record_id, :athlete_id, :timestamp, :status)";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':record_id'  => $numeric_record_id,
    ':athlete_id' => $athlete_id,
    ':timestamp'  => $timestamp,
    ':status'     => $status
  ]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error']);
}
