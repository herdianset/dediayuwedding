<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rsvp.json';
if (!file_exists($filePath)) {
  echo json_encode(['status' => 'ok', 'data' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = file_get_contents($filePath);
$items = json_decode($raw ?: '[]', true);
if (!is_array($items)) {
  $items = [];
}

$publicData = [];
foreach ($items as $item) {
  $name = trim((string)($item['name'] ?? ''));
  $message = trim((string)($item['message'] ?? ''));
  if ($name === '' || $message === '') {
    continue;
  }

  $publicData[] = [
    'name' => $name,
    'message' => $message
  ];
}

$publicData = array_reverse($publicData);

echo json_encode(['status' => 'ok', 'data' => $publicData], JSON_UNESCAPED_UNICODE);
