<?php
declare(strict_types=1);
require_once __DIR__ . '/../connection.php';

function redirect(string $to): void { header("Location: $to"); exit; }

$token = isset($_COOKIE['auth_token']) && is_string($_COOKIE['auth_token']) ? trim($_COOKIE['auth_token']) : '';
if ($token === '') redirect('../login.php');

$stmt = $conn->prepare("SELECT username FROM users WHERE auth_token = ? LIMIT 1");
if (!$stmt) redirect('../login.php');

$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) redirect('../login.php');
if (strcasecmp((string)$user['username'], 'admin') !== 0) redirect('../index.php');
