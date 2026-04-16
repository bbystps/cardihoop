<?php
header('Content-Type: application/json; charset=UTF-8');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
  }

  // You can post either:
  // - saved_path: "ecg_out/1771383266911.json"
  // - OR just the filename: "1771383266911.json"
  $saved_path = trim($_POST['saved_path'] ?? '');
  if ($saved_path === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing saved_path']);
    exit;
  }

  // Normalize to forward slashes
  $saved_path = str_replace('\\', '/', $saved_path);

  // Extract filename only (prevents path traversal)
  $filename = basename($saved_path);

  // Only allow safe JSON filename
  if (!preg_match('/^[A-Za-z0-9._-]+\.json$/', $filename)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid filename']);
    exit;
  }

  // ✅ Your real folder (relative to this PHP file)
  // admin/dashboard/ -> ../../mltest/pythoncodes/ecg_out
  $base_dir = realpath(__DIR__ . '/../../../mltest/pythoncodes/ecg_out');

  if ($base_dir === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'ecg_out folder not found']);
    exit;
  }

  // Build absolute file path
  $full_path = $base_dir . DIRECTORY_SEPARATOR . $filename;

  // If it doesn't exist, treat as OK (idempotent)
  if (!file_exists($full_path)) {
    echo json_encode(['ok' => true, 'deleted' => false, 'message' => 'File not found']);
    exit;
  }

  // Safety: ensure it's really a file
  if (!is_file($full_path)) {
    echo json_encode(['ok' => true, 'deleted' => false, 'message' => 'Not a file']);
    exit;
  }

  // Delete
  if (!@unlink($full_path)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to delete file']);
    exit;
  }

  echo json_encode(['ok' => true, 'deleted' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error']);
}
