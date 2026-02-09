<?php
// admin/dashboard/records_data.php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

$sql = "
  SELECT
    r.id AS ID,
    r.record_id AS RecordID,
    r.athlete_id AS AthleteID,
    COALESCE(a.name, CONCAT('[Unknown] ', r.athlete_id)) AS AthleteName,
    r.timestamp AS Timestamp,
    r.status AS Status
  FROM records r
  LEFT JOIN athletes a
    ON a.athlete_id = r.athlete_id
  ORDER BY r.id DESC
";

try {
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // If you want the DataTable column "AthleteID" to literally contain the name,
  // uncomment the loop below and change your JS column to data: 'AthleteID'
  /*
  foreach ($rows as &$row) {
    $row['AthleteID'] = $row['AthleteName'];
  }
  unset($row);
  */

  echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['data' => [], 'error' => 'Server error']);
}
