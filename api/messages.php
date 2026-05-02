<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed. Use GET.',
    ], JSON_PRETTY_PRINT);
    exit;
}

$requestedLimit = (int) ($_GET['limit'] ?? 5);
$allowedLimits = [5, 10];
$limit = in_array($requestedLimit, $allowedLimits, true) ? $requestedLimit : 5;

try {
    $pdo = websms_database();
    $statement = $pdo->prepare(
        'SELECT id, sender_name, sender_number, receiver_number, message_text, status, received_at
         FROM sms_messages
         ORDER BY received_at DESC, id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    $messages = $statement->fetchAll();

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'SMS messages fetched successfully.',
        'meta' => [
            'limit' => $limit,
            'count' => count($messages),
            'allowed_limits' => $allowedLimits,
        ],
        'data' => $messages,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Failed to fetch SMS messages.',
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}