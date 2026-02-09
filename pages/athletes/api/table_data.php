<?php
// admin/dashboard/recent_readings_data.php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

$sql = "
  SELECT
    a.id         AS ID,
    a.athlete_id AS AthleteID,
    a.name       AS Name,
    a.sex        AS Gender,

    CASE
      WHEN r.id IS NULL THEN 'N/A'
      ELSE r.timestamp
    END AS ScannedDate,

    CASE
      WHEN r.id IS NULL THEN 'N/A'
      ELSE r.status
    END AS Status

  FROM athletes a
  LEFT JOIN records r
    ON r.id = (
      SELECT MAX(r2.id)
      FROM records r2
      WHERE r2.athlete_id = a.athlete_id
    )

  ORDER BY a.name ASC
";

try {
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'data' => $rows
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'data' => [],
    'error' => 'Server error'
  ]);
}
