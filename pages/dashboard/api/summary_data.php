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
  // TODAY in YYYY-MM-DD (matches your VARCHAR timestamp format)
  $today = date('Y-m-d');

  // Total scans today
  $stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM records WHERE `timestamp` LIKE ?"
  );
  $stmt->execute([$today . '%']);
  $total = (int)$stmt->fetchColumn();

  // Normal scans today
  $stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM records 
     WHERE status = ? AND `timestamp` LIKE ?"
  );
  $stmt->execute(['Normal', $today . '%']);
  $normal = (int)$stmt->fetchColumn();

  // Abnormal / Needs Review scans today
  $stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM records 
     WHERE status = ? AND `timestamp` LIKE ?"
  );
  $stmt->execute(['Abnormal', $today . '%']);
  $review = (int)$stmt->fetchColumn();

  // Percentages (safe)
  $normalPct = $total > 0 ? round(($normal / $total) * 100) : 0;
  $reviewPct = $total > 0 ? round(($review / $total) * 100) : 0;

  echo json_encode([
    'ok' => true,
    'data' => [
      'date'        => $today,
      'total'       => $total,
      'normal'      => $normal,
      'review'      => $review,
      'normal_pct'  => $normalPct,
      'review_pct'  => $reviewPct
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
