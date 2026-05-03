<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$appConfig = require __DIR__ . '/config/app.php';
$configuredDatabaseName = (string) (($appConfig['database']['name'] ?? 'websms'));

$messages = [];
$stats = [
    'today_count' => 0,
    'processed_count' => 0,
    'failed_count' => 0,
    'unique_senders' => 0,
];
$databaseError = null;

try {
    $pdo = websms_database();

    $stats = $pdo->query(
        'SELECT
            COUNT(CASE WHEN DATE(received_at) = CURDATE() THEN 1 END) AS today_count,
            COUNT(CASE WHEN status = "processed" THEN 1 END) AS processed_count,
            COUNT(CASE WHEN status = "failed" THEN 1 END) AS failed_count,
            COUNT(DISTINCT sender_number) AS unique_senders
         FROM sms_messages'
    )->fetch() ?: $stats;

    $messageStatement = $pdo->query(
        'SELECT id, sender_name, sender_number, receiver_number, message_text, status, received_at
         FROM sms_messages
         ORDER BY received_at DESC, id DESC
         LIMIT 50'
    );
    $messages = $messageStatement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = $exception->getMessage();
}

function websms_badge_class(string $status): string
{
    return match ($status) {
        'processed' => 'text-bg-success',
        'failed' => 'text-bg-danger',
        default => 'badge-soft-success',
    };
}

function websms_initials(?string $senderName, string $senderNumber): string
{
    $source = trim((string) $senderName);
    if ($source === '') {
        return strtoupper(substr(preg_replace('/[^0-9]/', '', $senderNumber) ?: 'SMS', 0, 2));
    }

    $parts = preg_split('/\s+/', $source) ?: [];
    $letters = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($letters) === 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'SM';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebSMS Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --sms-green-900: #0f3d2e;
            --sms-green-700: #1d6b4f;
            --sms-green-500: #2f9e68;
            --sms-green-100: #e8f6ee;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(47, 158, 104, 0.18), transparent 30%),
                linear-gradient(180deg, #f9fcfa 0%, #eef7f1 100%);
            color: #183126;
        }

        .hero-card {
            background: linear-gradient(135deg, var(--sms-green-900), var(--sms-green-700));
            border: 0;
            box-shadow: 0 1rem 2rem rgba(15, 61, 46, 0.18);
        }

        .surface-card {
            border: 1px solid rgba(29, 107, 79, 0.12);
            box-shadow: 0 0.75rem 1.5rem rgba(24, 49, 38, 0.06);
        }

        .stats-pill {
            background: var(--sms-green-100);
            color: var(--sms-green-700);
        }

        .message-item {
            border-bottom: 1px solid rgba(29, 107, 79, 0.08);
            padding: 1rem 0;
        }

        .message-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .avatar {
            width: 3rem;
            height: 3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--sms-green-100);
            color: var(--sms-green-700);
            font-weight: 700;
        }

        .badge-soft-success {
            background: rgba(47, 158, 104, 0.14);
            color: var(--sms-green-700);
        }

        code {
            color: var(--sms-green-900);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row g-4 align-items-stretch mb-4">
            <div class="col-lg-8">
                <div class="card hero-card text-white h-100 rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge text-bg-light text-success rounded-pill px-3 py-2 mb-3">Receive SMS API</span>
                                <h1 class="display-6 fw-bold mb-2">WebSMS inbox overview</h1>
                                <p class="mb-0 text-white-50">A minimal PHP and MySQL inbox for receiving SMS webhooks and listing recent messages.</p>
                            </div>
                            <div class="text-start text-lg-end">
                                <div class="small text-white-50 mb-2">Database and APIs</div>
                                <code class="bg-white rounded px-3 py-2 d-inline-block mb-2">DB: <?php echo htmlspecialchars($configuredDatabaseName, ENT_QUOTES, 'UTF-8'); ?></code>
                                <br>
                                <code class="bg-white rounded px-3 py-2 d-inline-block mb-2">POST /api/receive_sms.php</code>
                                <br>
                                <code class="bg-white rounded px-3 py-2 d-inline-block mb-2">GET /api/messages.php?limit=5</code>
                                <br>
                                <code class="bg-white rounded px-3 py-2 d-inline-block mb-2">GET /api/detect_payment.php?amount=30</code>
                                <br>
                                <code class="bg-white rounded px-3 py-2 d-inline-block">GET /api/detect_message.php?keywords=PBB,RM30&amp;timeout=10</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card surface-card h-100 rounded-4 bg-white">
                    <div class="card-body p-4">
                        <p class="text-uppercase text-success fw-semibold small mb-3">Stats</p>
                        <a href="/websms/payment_gateway.php" class="btn btn-success w-100 rounded-pill mb-4">Open Payment Gateway Watch</a>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Received today</span>
                            <span class="stats-pill rounded-pill px-3 py-2 fw-semibold"><?php echo (int) $stats['today_count']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Processed</span>
                            <span class="stats-pill rounded-pill px-3 py-2 fw-semibold"><?php echo (int) $stats['processed_count']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Failed</span>
                            <span class="stats-pill rounded-pill px-3 py-2 fw-semibold"><?php echo (int) $stats['failed_count']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Unique senders</span>
                            <span class="stats-pill rounded-pill px-3 py-2 fw-semibold"><?php echo (int) $stats['unique_senders']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card surface-card rounded-4 bg-white h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Server setup</h2>
                        <p class="text-muted">Change the database name, host, user, and password in <strong>config/app.php</strong>, then create that database on your server.</p>
                        <div class="bg-success-subtle rounded-3 p-3 small mb-4">
                            <div class="fw-semibold text-success mb-2">Editable config</div>
                            <code>config/app.php</code>
                        </div>
                        <div class="bg-success-subtle rounded-3 p-3 small mb-4">
                            <div class="fw-semibold text-success mb-2">SQL files</div>
                            <code>db/create_database.sql</code><br>
                            <code>db/websms.sql</code>
                        </div>

                                                <h3 class="h6 mb-3">Example requests</h3>
                                                <pre class="bg-dark text-light rounded-3 p-3 small mb-0"><code>POST /api/receive_sms.php
Content-Type: application/json

{
  "sender_name": "Demo Sender",
  "from": "+1555000111",
  "to": "+1555099999",
  "message": "Test SMS from gateway"
}

GET /api/messages.php?limit=5
GET /api/messages.php?limit=10
GET /payment_gateway.php
GET /api/detect_payment.php?amount=30
GET /api/detect_message.php?keywords=PBB,RM30&amp;timeout=10</code></pre>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card surface-card rounded-4 bg-white">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                            <div>
                                <h2 class="h4 mb-1">Recent SMS Messages</h2>
                                <p class="text-muted mb-0">Latest inbound messages stored by the receive API.</p>
                            </div>
                        </div>

                        <?php if ($databaseError !== null): ?>
                            <div class="alert alert-warning mb-0">
                                Database connection failed. Create the database from <code>db/create_database.sql</code>, import <code>db/websms.sql</code>, and confirm credentials in <code>config/app.php</code>.
                                <div class="small mt-2"><?php echo htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        <?php elseif ($messages === []): ?>
                            <div class="alert alert-light border mb-0">No SMS messages found yet. Send a POST request to <code>/api/receive_sms.php</code>.</div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message-item">
                                    <div class="d-flex gap-3">
                                        <div class="avatar"><?php echo htmlspecialchars(websms_initials($message['sender_name'], $message['sender_number']), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                                                <h3 class="h6 mb-0"><?php echo htmlspecialchars($message['sender_name'] ?: $message['sender_number'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <small class="text-muted"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime((string) $message['received_at'])), ENT_QUOTES, 'UTF-8'); ?></small>
                                            </div>
                                            <div class="small text-success mb-2">
                                                From <?php echo htmlspecialchars($message['sender_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if (!empty($message['receiver_number'])): ?>
                                                    to <?php echo htmlspecialchars((string) $message['receiver_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-2 text-muted"><?php echo nl2br(htmlspecialchars($message['message_text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                            <span class="badge rounded-pill <?php echo websms_badge_class((string) $message['status']); ?>"><?php echo htmlspecialchars(ucfirst((string) $message['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>