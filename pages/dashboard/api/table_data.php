<?php
// admin/dashboard/records_data.php (example)
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

$sql = "
  SELECT
    r.id AS ID,
    r.record_id AS RecordID,
    r.athlete_id AS AthleteID,
    r.timestamp AS Timestamp,
    r.status AS Status
  FROM records r
  ORDER BY r.id DESC
";

try {
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['data' => [], 'error' => 'Server error']);
}
