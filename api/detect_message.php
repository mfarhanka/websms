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

$rawKeywords = trim((string) ($_GET['keywords'] ?? $_GET['contains'] ?? ''));
if ($rawKeywords === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'keywords is required. Example: ?keywords=PBB,RM30',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$keywords = array_values(array_filter(array_map(
    static fn (string $keyword): string => trim($keyword),
    preg_split('/[,|]/', $rawKeywords) ?: []
), static fn (string $keyword): bool => $keyword !== ''));

if ($keywords === []) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Provide at least one non-empty keyword.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$timeout = (int) ($_GET['timeout'] ?? 10);
$timeout = max(1, min($timeout, 30));

$sinceSeconds = (int) ($_GET['since_seconds'] ?? $timeout);
$sinceSeconds = max($timeout, min($sinceSeconds, 3600));

$pollIntervalMicroseconds = 500000;
$deadline = microtime(true) + $timeout;

function websms_detect_matching_message(PDO $pdo, array $keywords, int $sinceSeconds): ?array
{
    $statement = $pdo->query(
        'SELECT id, sender_name, sender_number, receiver_number, message_text, status, received_at
         FROM sms_messages
         WHERE received_at >= (NOW() - INTERVAL ' . $sinceSeconds . ' SECOND)
         ORDER BY received_at DESC, id DESC
         LIMIT 100'
    );
    $statement->execute();

    foreach ($statement->fetchAll() as $message) {
        $messageText = mb_strtolower((string) ($message['message_text'] ?? ''), 'UTF-8');
        $matchedAllKeywords = true;

        foreach ($keywords as $keyword) {
            if (!str_contains($messageText, mb_strtolower($keyword, 'UTF-8'))) {
                $matchedAllKeywords = false;
                break;
            }
        }

        if ($matchedAllKeywords) {
            return $message;
        }
    }

    return null;
}

try {
    $pdo = websms_database();
    $match = null;

    do {
        $match = websms_detect_matching_message($pdo, $keywords, $sinceSeconds);
        if ($match !== null) {
            http_response_code(200);
            echo json_encode([
                'ok' => true,
                'detected' => true,
                'message' => 'Matching SMS detected.',
                'meta' => [
                    'keywords' => $keywords,
                    'timeout' => $timeout,
                    'since_seconds' => $sinceSeconds,
                ],
                'data' => $match,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (microtime(true) >= $deadline) {
            break;
        }

        usleep($pollIntervalMicroseconds);
    } while (true);

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'detected' => false,
        'message' => 'No matching SMS detected within the timeout window.',
        'meta' => [
            'keywords' => $keywords,
            'timeout' => $timeout,
            'since_seconds' => $sinceSeconds,
        ],
        'data' => null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Failed to detect SMS message.',
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
