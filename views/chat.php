<?php
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

if (!isset($_GET['documentID'])) {
    echo "Invalid request.";
    exit;
}

$documentID = $_GET['documentID'];
$userID = $_SESSION['user_id'];

// Check if user is part of this document
$stmt = $conn->prepare("SELECT * FROM Document WHERE documentID = ? AND (userID = ? OR documentIssuerID = ?)");
$stmt->bind_param("sii", $documentID, $userID, $userID);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    echo "Access denied.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — Document <?= htmlspecialchars($documentID) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/nav.css">
    <style>
        :root {
            --chat-bg: #eef2f5;
            --bubble-me: #0d6efd;
            --bubble-them: #fff;
            --bubble-them-border: #e0e0e0;
            --text-muted: #6c757d;
        }
        body { background: var(--chat-bg); min-height: 100vh; }
        .chat-page { max-width: 720px; margin: 0 auto; height: calc(100vh - 120px); display: flex; flex-direction: column; }
        .chat-header {
            background: #fff; border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem;
            font-weight: 600; border-radius: 12px 12px 0 0; box-shadow: 0 1px 2px rgba(0,0,0,.05);
        }
        .chat-header .doc-id { font-family: monospace; color: var(--text-muted); font-weight: 500; font-size: 0.9rem; }
        .chat-messages {
            flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;
        }
        .chat-message { display: flex; max-width: 85%; }
        .chat-message.me { align-self: flex-end; }
        .chat-message.them { align-self: flex-start; }
        .chat-bubble {
            padding: 0.6rem 1rem; border-radius: 18px; word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,.06);
        }
        .chat-message.me .chat-bubble {
            background: var(--bubble-me); color: #fff; border-bottom-right-radius: 4px;
        }
        .chat-message.them .chat-bubble {
            background: var(--bubble-them); color: #212529; border: 1px solid var(--bubble-them-border); border-bottom-left-radius: 4px;
        }
        .chat-message .sender-name { font-size: 0.75rem; font-weight: 600; margin-bottom: 2px; opacity: .9; }
        .chat-message.them .sender-name { color: #495057; }
        .chat-message .time { font-size: 0.7rem; margin-top: 4px; opacity: .85; }
        .chat-input-wrap {
            background: #fff; padding: 0.75rem 1rem; border-top: 1px solid #dee2e6;
            border-radius: 0 0 12px 12px; box-shadow: 0 -1px 2px rgba(0,0,0,.05);
        }
        .chat-input-wrap .form-control { border-radius: 24px; resize: none; }
        .chat-input-wrap .btn { border-radius: 24px; min-width: 90px; }
        .chat-empty { text-align: center; color: var(--text-muted); padding: 2rem; }
        .chat-loading { text-align: center; color: var(--text-muted); padding: 1rem; }
    </style>
</head>
<body>
<?php $showLogout = true; include 'nav.php'; ?>

<div class="container mt-3">
    <div class="chat-page shadow-sm rounded">
        <div class="chat-header">
            <span>Document</span> <span class="doc-id"><?= htmlspecialchars($documentID) ?></span>
        </div>

        <div id="chat-messages" class="chat-messages bg-white">
            <div id="chat-loading" class="chat-loading">Loading messages…</div>
            <div id="chat-list"></div>
        </div>

        <div class="chat-input-wrap">
            <form id="chatForm" class="d-flex gap-2 align-items-end">
                <input type="hidden" name="documentID" value="<?= htmlspecialchars($documentID) ?>">
                <textarea name="message" id="message" class="form-control" rows="1" placeholder="Type a message…" required maxlength="2000"></textarea>
                <button type="submit" class="btn btn-primary" id="sendBtn">Send</button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const documentID = "<?= htmlspecialchars($documentID, ENT_QUOTES) ?>";
    const listEl = document.getElementById('chat-list');
    const loadingEl = document.getElementById('chat-loading');
    const form = document.getElementById('chatForm');
    const messageInput = document.getElementById('message');
    const sendBtn = document.getElementById('sendBtn');
    let pollTimer = null;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(ts) {
        if (!ts) return '';
        const d = new Date(ts);
        const now = new Date();
        const sameDay = d.toDateString() === now.toDateString();
        return sameDay ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : d.toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
    }

    function renderMessages(messages) {
        if (!messages.length) {
            listEl.innerHTML = '<div class="chat-empty">No messages yet. Say hello!</div>';
            return;
        }
        listEl.innerHTML = messages.map(function(m) {
            const side = m.isMe ? 'me' : 'them';
            return '<div class="chat-message ' + side + '">' +
                '<div class="chat-bubble">' +
                '<div class="sender-name">' + escapeHtml(m.userName) + '</div>' +
                '<div class="msg-text">' + escapeHtml(m.message) + '</div>' +
                '<div class="time">' + formatTime(m.timestamp) + '</div>' +
                '</div></div>';
        }).join('');
        var container = document.getElementById('chat-messages');
        if (container) container.scrollTop = container.scrollHeight;
    }

    function loadMessages() {
        fetch('index.php?controller=Chat&action=fetch&documentID=' + encodeURIComponent(documentID), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingEl.style.display = 'none';
                if (data.success && data.messages) {
                    renderMessages(data.messages);
                } else {
                    listEl.innerHTML = '<div class="chat-empty">Could not load messages.</div>';
                }
            })
            .catch(function() {
                loadingEl.style.display = 'none';
                listEl.innerHTML = '<div class="chat-empty">Error loading messages.</div>';
            });
    }

    function scrollToBottom() {
        var wrap = document.querySelector('.chat-messages');
        if (wrap) wrap.scrollTop = wrap.scrollHeight;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var text = (messageInput.value || '').trim();
        if (!text) return;

        sendBtn.disabled = true;
        var formData = new FormData(form);

        fetch('../actions/chat_action.php', { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                messageInput.value = '';
                sendBtn.disabled = false;
                if (res.success) {
                    loadMessages();
                } else {
                    alert(res.message || 'Failed to send.');
                }
            })
            .catch(function() {
                sendBtn.disabled = false;
                alert('Failed to send. Try again.');
            });
    });

    loadMessages();
    pollTimer = setInterval(loadMessages, 2500);

    window.addEventListener('beforeunload', function() {
        if (pollTimer) clearInterval(pollTimer);
    });
})();
</script>
</body>
</html>
