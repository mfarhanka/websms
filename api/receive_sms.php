<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed. Use POST.',
    ], JSON_PRETTY_PRINT);
    exit;
}

$rawBody = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$payload = [];

if (stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode($rawBody ?: '[]', true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'message' => 'Invalid JSON payload.',
        ], JSON_PRETTY_PRINT);
        exit;
    }
    $payload = is_array($decoded) ? $decoded : [];
} else {
    $payload = $_POST;
}

$senderName = trim((string) ($payload['sender_name'] ?? $payload['name'] ?? ''));
$senderNumber = trim((string) ($payload['sender_number'] ?? $payload['from'] ?? $payload['sender'] ?? ''));
$receiverNumber = trim((string) ($payload['receiver_number'] ?? $payload['to'] ?? ''));
$messageText = trim((string) ($payload['message'] ?? $payload['text'] ?? $payload['body'] ?? ''));
$status = trim((string) ($payload['status'] ?? 'received'));

if ($senderNumber === '' || $messageText === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'sender_number and message are required.',
    ], JSON_PRETTY_PRINT);
    exit;
}

$allowedStatuses = ['received', 'processed', 'failed'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'received';
}

try {
    $pdo = websms_database();
    $statement = $pdo->prepare(
        'INSERT INTO sms_messages (sender_name, sender_number, receiver_number, message_text, status, gateway_payload)
         VALUES (:sender_name, :sender_number, :receiver_number, :message_text, :status, :gateway_payload)'
    );
    $statement->execute([
        'sender_name' => $senderName !== '' ? $senderName : null,
        'sender_number' => $senderNumber,
        'receiver_number' => $receiverNumber !== '' ? $receiverNumber : null,
        'message_text' => $messageText,
        'status' => $status,
        'gateway_payload' => $rawBody !== false && $rawBody !== '' ? $rawBody : json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'message' => 'SMS received successfully.',
        'data' => [
            'id' => (int) $pdo->lastInsertId(),
            'sender_number' => $senderNumber,
            'receiver_number' => $receiverNumber,
            'status' => $status,
        ],
    ], JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Failed to store SMS.',
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT);
}