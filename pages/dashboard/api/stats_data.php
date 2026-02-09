<?php
// admin/dashboard/stats_data.php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

function jsonFail($msg, $code = 400)
{
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // NOTE: your timestamps are VARCHAR, but stored like "YYYY-MM-DD HH:MM:SS"
  // We can still filter using LIKE 'YYYY-MM-DD%'.
  $today = date('Y-m-d');

  // 1) Registered Athletes
  $athletes_total = (int)$pdo->query("SELECT COUNT(*) FROM athletes")->fetchColumn();

  // 2) ECG Scans Today
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM records WHERE `timestamp` LIKE ?");
  $stmt->execute([$today . '%']);
  $scans_today = (int)$stmt->fetchColumn();

  // 3) Abnormal Readings (all-time)
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM records WHERE status = ?");
  $stmt->execute(['Abnormal']);
  $abnormal_total = (int)$stmt->fetchColumn();

  echo json_encode([
    'ok' => true,
    'data' => [
      'athletes_total' => $athletes_total,
      'scans_today' => $scans_today,
      'abnormal_total' => $abnormal_total,
      'today' => $today
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
