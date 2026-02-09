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
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) jsonFail("Invalid athlete id.");

  $stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) jsonFail("Athlete not found.", 404);

  echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
