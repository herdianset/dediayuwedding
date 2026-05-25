<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Payload tidak valid.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$name = trim((string)($payload['name'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));
$attendance = trim((string)($payload['attendance'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));

if ($name === '' || $attendance === '' || $message === '') {
  http_response_code(422);
  echo json_encode(['status' => 'error', 'message' => 'Nama, kehadiran, dan ucapan wajib diisi.'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (mb_strlen($name) > 120 || mb_strlen($phone) > 40 || mb_strlen($attendance) > 40 || mb_strlen($message) > 1200) {
  http_response_code(422);
  echo json_encode(['status' => 'error', 'message' => 'Panjang data melebihi batas.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir) && !mkdir($dataDir, 0777, true) && !is_dir($dataDir)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Gagal membuat folder data.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$filePath = $dataDir . DIRECTORY_SEPARATOR . 'rsvp.json';
$fp = fopen($filePath, 'c+');
if ($fp === false) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file data.'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!flock($fp, LOCK_EX)) {
  fclose($fp);
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Gagal mengunci file data.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = stream_get_contents($fp);
$list = json_decode($raw ?: '[]', true);
if (!is_array($list)) {
  $list = [];
}

$list[] = [
  'id' => uniqid('rsvp_', true),
  'name' => $name,
  'phone' => $phone,
  'attendance' => $attendance,
  'message' => $message,
  'created_at' => date('c')
];

if (count($list) > 300) {
  $list = array_slice($list, -300);
}

rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
