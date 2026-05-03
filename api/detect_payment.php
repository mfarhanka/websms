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
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$rawAmount = trim((string) ($_GET['amount'] ?? ''));
if ($rawAmount === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'amount is required. Example: ?amount=30',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$normalizedAmount = preg_replace('/[^0-9.]/', '', $rawAmount) ?? '';
if ($normalizedAmount === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizedAmount)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'amount must be a valid number with up to 2 decimal places.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$targetAmount = round((float) $normalizedAmount, 2);
$timeout = (int) ($_GET['timeout'] ?? 120);
$timeout = max(5, min($timeout, 120));

$sinceSeconds = (int) ($_GET['since_seconds'] ?? 120);
$sinceSeconds = max(5, min($sinceSeconds, 120));

if (function_exists('set_time_limit')) {
    set_time_limit($timeout + 5);
}

$pollIntervalMicroseconds = 500000;
$deadline = microtime(true) + $timeout;

function websms_extract_amounts(string $messageText): array
{
    preg_match_all('/(?:rm\s*)?(\d{1,3}(?:,\d{3})*(?:\.\d{1,2})?|\d+(?:\.\d{1,2})?)/iu', $messageText, $matches);

    $amounts = [];
    foreach ($matches[1] ?? [] as $rawMatch) {
        $normalized = str_replace(',', '', (string) $rawMatch);
        if ($normalized === '' || !is_numeric($normalized)) {
            continue;
        }

        $amounts[] = round((float) $normalized, 2);
    }

    return array_values(array_unique($amounts));
}

function websms_find_matching_payment_message(PDO $pdo, float $targetAmount, int $sinceSeconds): ?array
{
    $statement = $pdo->query(
        'SELECT id, sender_name, sender_number, receiver_number, message_text, status, received_at
         FROM sms_messages
         WHERE status = "received"
           AND received_at >= (NOW() - INTERVAL ' . $sinceSeconds . ' SECOND)
         ORDER BY received_at DESC, id DESC
         LIMIT 100'
    );
    $statement->execute();

    foreach ($statement->fetchAll() as $message) {
        $amounts = websms_extract_amounts((string) ($message['message_text'] ?? ''));
        foreach ($amounts as $amount) {
            if (abs($amount - $targetAmount) < 0.001) {
                return $message;
            }
        }
    }

    return null;
}

try {
    $pdo = websms_database();
    $match = null;

    do {
        $match = websms_find_matching_payment_message($pdo, $targetAmount, $sinceSeconds);
        if ($match !== null) {
            $updateStatement = $pdo->prepare(
                'UPDATE sms_messages
                 SET status = :status
                 WHERE id = :id AND status = "received"'
            );
            $updateStatement->execute([
                'status' => 'processed',
                'id' => (int) $match['id'],
            ]);

            if ($updateStatement->rowCount() === 0) {
                $match = null;
            } else {
                $match['status'] = 'processed';
                http_response_code(200);
                echo json_encode([
                    'ok' => true,
                    'received' => true,
                    'message' => 'Matching incoming payment SMS received.',
                    'meta' => [
                        'amount' => number_format($targetAmount, 2, '.', ''),
                        'timeout' => $timeout,
                        'since_seconds' => $sinceSeconds,
                    ],
                    'data' => $match,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if (microtime(true) >= $deadline) {
            break;
        }

        usleep($pollIntervalMicroseconds);
    } while (true);

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'received' => false,
        'message' => 'No matching payment SMS was received within 2 minutes.',
        'meta' => [
            'amount' => number_format($targetAmount, 2, '.', ''),
            'timeout' => $timeout,
            'since_seconds' => $sinceSeconds,
        ],
        'data' => null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Failed to detect payment SMS.',
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}