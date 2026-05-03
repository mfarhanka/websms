<?php

declare(strict_types=1);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Gateway Watch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f6f0df;
            --ink: #1c1a13;
            --muted: #6f654b;
            --accent: #0f766e;
            --accent-soft: rgba(15, 118, 110, 0.14);
            --warning: #f59e0b;
            --danger: #c2410c;
            --surface: rgba(255, 253, 248, 0.9);
            --border: rgba(28, 26, 19, 0.08);
            --shadow: 0 30px 80px rgba(28, 26, 19, 0.12);
            --progress: 0%;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 20% 20%, rgba(15, 118, 110, 0.16), transparent 28%),
                radial-gradient(circle at 80% 0%, rgba(245, 158, 11, 0.18), transparent 25%),
                linear-gradient(180deg, #f5ead1 0%, var(--paper) 100%);
        }

        a {
            color: inherit;
        }

        .shell {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 56px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-chip {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #115e59, #14b8a6);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 12px 24px rgba(17, 94, 89, 0.24);
        }

        .topbar-link {
            text-decoration: none;
            color: var(--muted);
            font-weight: 500;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 24px;
            align-items: stretch;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .hero-copy {
            padding: 32px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.64);
            color: var(--accent);
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        h1 {
            margin: 20px 0 14px;
            font-family: 'Fraunces', serif;
            font-size: clamp(2.4rem, 5vw, 4.6rem);
            line-height: 0.96;
        }

        .lead {
            max-width: 38rem;
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .facts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 28px;
        }

        .fact {
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(28, 26, 19, 0.06);
        }

        .fact strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1.2rem;
        }

        .watch-panel {
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.84), rgba(248, 243, 231, 0.96));
        }

        .watch-shell {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 22px;
            background: #16130f;
            color: #f8f4e9;
        }

        .watch-shell::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(20, 184, 166, 0.25), transparent 40%, rgba(245, 158, 11, 0.18));
            transform: translateX(-100%);
            animation: slide 3s linear infinite;
            opacity: 0.9;
        }

        @keyframes slide {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }

        .watch-shell > * {
            position: relative;
            z-index: 1;
        }

        .timer-row {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
        }

        .timer-digits {
            font-size: clamp(3rem, 6vw, 4.8rem);
            line-height: 0.9;
            font-weight: 700;
            letter-spacing: -0.04em;
        }

        .timer-label {
            color: rgba(248, 244, 233, 0.72);
            font-size: 0.96rem;
        }

        .progress-rail {
            position: relative;
            height: 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            overflow: hidden;
            margin-top: 18px;
        }

        .progress-fill {
            width: var(--progress);
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2dd4bf, #fbbf24);
            transition: width 0.35s linear;
        }

        .status-card {
            padding: 18px 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(28, 26, 19, 0.06);
        }

        .status-card[data-state='idle'] {
            color: var(--muted);
        }

        .status-card[data-state='watching'] {
            background: rgba(15, 118, 110, 0.08);
            color: #0f5d56;
        }

        .status-card[data-state='success'] {
            background: rgba(34, 197, 94, 0.12);
            color: #166534;
        }

        .status-card[data-state='error'] {
            background: rgba(194, 65, 12, 0.12);
            color: var(--danger);
        }

        .status-title {
            margin: 0 0 6px;
            font-size: 1rem;
            font-weight: 700;
        }

        .status-text {
            margin: 0;
            line-height: 1.6;
        }

        form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        label {
            font-size: 0.94rem;
            color: var(--muted);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 18px 18px;
            border-radius: 18px;
            border: 1px solid rgba(28, 26, 19, 0.12);
            background: rgba(255, 255, 255, 0.92);
            color: var(--ink);
            font: inherit;
            font-size: 1.25rem;
        }

        input:focus {
            outline: 2px solid rgba(15, 118, 110, 0.2);
            border-color: rgba(15, 118, 110, 0.4);
        }

        button {
            align-self: end;
            padding: 18px 22px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #115e59, #0f766e);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            min-width: 180px;
            box-shadow: 0 16px 32px rgba(17, 94, 89, 0.24);
        }

        button[disabled] {
            opacity: 0.72;
            cursor: wait;
        }

        .notes {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .note {
            padding: 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(28, 26, 19, 0.06);
        }

        .note h2 {
            margin: 0 0 10px;
            font-size: 1rem;
        }

        .note p,
        .match-meta,
        .match-message {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .match-box {
            display: none;
            margin-top: 12px;
            padding: 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(28, 26, 19, 0.08);
        }

        .match-box.visible {
            display: block;
        }

        .match-message {
            margin-top: 10px;
            color: var(--ink);
        }

        .pulse-dot {
            display: inline-flex;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            background: currentColor;
            box-shadow: 0 0 0 0 currentColor;
            animation: pulse 1.6s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(45, 212, 191, 0.5); }
            70% { box-shadow: 0 0 0 16px rgba(45, 212, 191, 0); }
            100% { box-shadow: 0 0 0 0 rgba(45, 212, 191, 0); }
        }

        @media (max-width: 920px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .facts,
            .notes {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .shell {
                width: min(100% - 20px, 1120px);
                padding-top: 20px;
            }

            .hero-copy,
            .watch-panel {
                padding: 22px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            form {
                grid-template-columns: 1fr;
            }

            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <a class="brand" href="/websms/">
                <span class="brand-chip">RM</span>
                <span>
                    <strong>Payment Gateway Watch</strong><br>
                    <span class="topbar-link">Watch income SMS and auto-confirm receipt</span>
                </span>
            </a>
            <a class="topbar-link" href="/websms/">Back to inbox</a>
        </div>

        <section class="hero">
            <div class="panel hero-copy">
                <span class="eyebrow">2-minute live confirmation</span>
                <h1>Wait for the exact amount and confirm it the moment the SMS lands.</h1>
                <p class="lead">Enter the expected incoming amount. The page will watch the last 2 minutes of SMS activity, animate the countdown in real time, and mark the matched SMS as processed so it cannot be reused for another payment check.</p>
                <div class="facts">
                    <div class="fact">
                        <strong>RM amount</strong>
                        Match amounts like <span>RM30</span> or <span>RM30.00</span> inside the SMS text.
                    </div>
                    <div class="fact">
                        <strong>120 seconds</strong>
                        Watch runs for 2 minutes and keeps polling the SMS inbox until the timer ends.
                    </div>
                    <div class="fact">
                        <strong>Single use</strong>
                        Once matched, the SMS is marked <span>processed</span> in the inbox.
                    </div>
                </div>
            </div>

            <div class="panel watch-panel">
                <div class="watch-shell">
                    <div class="timer-row">
                        <div>
                            <div class="timer-label">Animated timing</div>
                            <div class="timer-digits" id="timerDigits">02:00</div>
                        </div>
                        <div class="timer-label" id="timerState">Idle</div>
                    </div>
                    <div class="progress-rail">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>

                <form id="paymentForm">
                    <div class="field">
                        <label for="amountInput">Expected incoming amount</label>
                        <input id="amountInput" name="amount" type="number" min="0.01" step="0.01" placeholder="30.00" required>
                    </div>
                    <button id="watchButton" type="submit">Start 2-minute watch</button>
                </form>

                <div class="status-card" id="statusCard" data-state="idle">
                    <p class="status-title">Ready</p>
                    <p class="status-text">Waiting for an amount to monitor.</p>
                </div>

                <div class="match-box" id="matchBox">
                    <p class="status-title">Matched SMS</p>
                    <p class="match-meta" id="matchMeta"></p>
                    <p class="match-message" id="matchMessage"></p>
                </div>
            </div>
        </section>

        <section class="notes">
            <div class="note">
                <h2>How it works</h2>
                <p>Start the watch after your admin confirms the required amount. The page waits up to 2 minutes for a new or recent SMS containing the same amount and updates the result automatically when found.</p>
            </div>
            <div class="note">
                <h2>Matching rule</h2>
                <p>The system scans SMS entries with status <strong>received</strong> inside the latest 120 seconds. On a match, that SMS is updated to <strong>processed</strong> so one transfer cannot confirm multiple pending payments.</p>
            </div>
        </section>
    </div>

    <script>
        const watchSeconds = 120;
        const paymentForm = document.getElementById('paymentForm');
        const amountInput = document.getElementById('amountInput');
        const watchButton = document.getElementById('watchButton');
        const timerDigits = document.getElementById('timerDigits');
        const timerState = document.getElementById('timerState');
        const statusCard = document.getElementById('statusCard');
        const matchBox = document.getElementById('matchBox');
        const matchMeta = document.getElementById('matchMeta');
        const matchMessage = document.getElementById('matchMessage');
        const apiUrl = new URL('api/detect_payment.php', window.location.href);

        let countdownInterval = null;
        let abortController = null;

        function setTimer(secondsRemaining) {
            const safeSeconds = Math.max(0, secondsRemaining);
            const minutes = String(Math.floor(safeSeconds / 60)).padStart(2, '0');
            const seconds = String(safeSeconds % 60).padStart(2, '0');
            timerDigits.textContent = `${minutes}:${seconds}`;
            const progress = ((watchSeconds - safeSeconds) / watchSeconds) * 100;
            document.documentElement.style.setProperty('--progress', `${Math.max(0, Math.min(progress, 100))}%`);
        }

        function setStatus(state, title, text, withPulse = false) {
            statusCard.dataset.state = state;
            statusCard.innerHTML = `
                <p class="status-title">${withPulse ? '<span class="pulse-dot"></span>' : ''}${title}</p>
                <p class="status-text">${text}</p>
            `;
        }

        function clearMatch() {
            matchBox.classList.remove('visible');
            matchMeta.textContent = '';
            matchMessage.textContent = '';
        }

        function showMatch(data) {
            const sender = data.sender_name || data.sender_number || 'Unknown sender';
            matchMeta.textContent = `${sender} • ${data.received_at} • status ${data.status}`;
            matchMessage.textContent = data.message_text || '';
            matchBox.classList.add('visible');
        }

        function stopCountdown(finalState) {
            if (countdownInterval !== null) {
                window.clearInterval(countdownInterval);
                countdownInterval = null;
            }

            if (finalState === 'idle') {
                setTimer(watchSeconds);
                timerState.textContent = 'Idle';
                return;
            }

            timerState.textContent = finalState;
        }

        function startCountdown() {
            const startedAt = Date.now();
            stopCountdown('Watching');
            setTimer(watchSeconds);
            timerState.textContent = 'Watching';

            countdownInterval = window.setInterval(() => {
                const elapsedSeconds = Math.floor((Date.now() - startedAt) / 1000);
                const secondsRemaining = watchSeconds - elapsedSeconds;
                setTimer(secondsRemaining);

                if (secondsRemaining <= 0) {
                    stopCountdown('Expired');
                }
            }, 250);
        }

        async function startWatch(amount) {
            if (abortController !== null) {
                abortController.abort();
            }

            abortController = new AbortController();
            watchButton.disabled = true;
            amountInput.disabled = true;
            clearMatch();
            startCountdown();
            setStatus('watching', 'Watching incoming payment', `Checking SMS messages for RM${amount} over the next 2 minutes.`, true);

            try {
                const params = new URLSearchParams({
                    amount,
                    timeout: String(watchSeconds),
                    since_seconds: String(watchSeconds),
                });
                const response = await fetch(`${apiUrl.toString()}?${params.toString()}`, {
                    signal: abortController.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const responseText = await response.text();
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.toLowerCase().includes('application/json')) {
                    throw new Error('The server returned HTML instead of JSON. Check the API URL or deployment path.');
                }

                let payload;
                try {
                    payload = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('The server returned invalid JSON for the payment watch request.');
                }

                if (!response.ok || payload.ok !== true) {
                    throw new Error(payload.message || 'Payment watch failed.');
                }

                if (payload.received) {
                    stopCountdown('Received');
                    setTimer(0);
                    setStatus('success', 'Payment received', `RM${payload.meta.amount} matched an incoming SMS and has been marked processed.`, false);
                    showMatch(payload.data);
                } else {
                    stopCountdown('Expired');
                    setStatus('error', 'No payment found', payload.message || 'No matching SMS was received within 2 minutes.', false);
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    stopCountdown('Cancelled');
                    setStatus('idle', 'Watch cancelled', 'The previous watch was stopped before it completed.', false);
                } else {
                    stopCountdown('Error');
                    setStatus('error', 'Watch failed', error.message || 'Unable to watch incoming payment SMS.', false);
                }
            } finally {
                watchButton.disabled = false;
                amountInput.disabled = false;
                abortController = null;
            }
        }

        paymentForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const amount = amountInput.value.trim();
            if (amount === '') {
                setStatus('error', 'Amount required', 'Enter the expected amount before starting the watch.', false);
                clearMatch();
                return;
            }

            startWatch(amount);
        });

        setTimer(watchSeconds);
    </script>
</body>
</html>