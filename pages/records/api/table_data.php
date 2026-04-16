<?php
require __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

$sql = "
  SELECT
    r.id AS ID,
    r.record_id AS RecordID,
    r.athlete_id AS AthleteID,
    COALESCE(a.name, CONCAT('[Unknown] ', r.athlete_id)) AS AthleteName,
    r.timestamp AS Timestamp,
    r.heart_rate AS HeartRate,
    r.rate_label AS RateLabel,
    r.rhythm_label AS RhythmLabel,
    r.status AS Status
  FROM records r
  LEFT JOIN athletes a
    ON a.athlete_id = r.athlete_id
  ORDER BY r.id DESC
";

try {
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as &$row) {
    $row['ID'] = isset($row['ID']) ? (int)$row['ID'] : null;
    $row['RecordID'] = isset($row['RecordID']) ? (string)$row['RecordID'] : '';
    $row['AthleteID'] = isset($row['AthleteID']) ? (string)$row['AthleteID'] : '';
    $row['AthleteName'] = isset($row['AthleteName']) ? (string)$row['AthleteName'] : '';
    $row['Timestamp'] = isset($row['Timestamp']) ? (string)$row['Timestamp'] : '';
    $row['HeartRate'] = isset($row['HeartRate']) ? (string)$row['HeartRate'] : '';
    $row['RateLabel'] = isset($row['RateLabel']) ? (string)$row['RateLabel'] : '';
    $row['RhythmLabel'] = isset($row['RhythmLabel']) ? (string)$row['RhythmLabel'] : '';
    $row['Status'] = strtoupper(trim((string)($row['Status'] ?? '')));
  }
  unset($row);

  echo json_encode([
    'data' => $rows
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'data' => [],
    'error' => 'Server error'
  ], JSON_UNESCAPED_UNICODE);
}
