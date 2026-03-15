<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/core.php';
include __DIR__ . '/../config/config.php';
isLogin();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admissions Office') {
    echo "Access denied.";
    exit;
}

$officeID = $_SESSION['user_id'];
$documentIssuerID = isset($_GET['documentIssuerID']) ? (int) $_GET['documentIssuerID'] : (isset($_GET['institutionID']) ? (int) $_GET['institutionID'] : (isset($_GET['schoolID']) ? (int) $_GET['schoolID'] : 0));
if ($documentIssuerID <= 0) {
    echo "Invalid institution selected.";
    exit;
}

// Fetch institution info
$stmt = $conn->prepare("SELECT s.documentIssuerName, s.documentIssuerEmail
                        FROM Subscribe s
                        JOIN User u ON s.userID = u.userID
                        WHERE s.userID = ? AND u.userRole = 'Document Issuer'");
$stmt->bind_param("i", $documentIssuerID);
$stmt->execute();
$issuer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$issuer) {
    echo "Institution not found.";
    exit;
}

// Fetch document seekers with documents in this institution
$stmt = $conn->prepare("
    SELECT DISTINCT u.userID, u.userName, u.userEmail, r.documentID
    FROM Document r
    JOIN User u ON r.userID = u.userID
    WHERE r.documentIssuerID = ?
    ORDER BY u.userName ASC
");
$stmt->bind_param("i", $documentIssuerID);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

$selectedDocumentID = $_GET['documentID'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — <?= htmlspecialchars($issuer['documentIssuerName']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/nav.css">
    <style>
        :root {
            --chat-bg: #eef2f5;
            --bubble-me: #0d6efd;
            --bubble-them: #fff;
            --bubble-them-border: #e0e0e0;
        }
        body { background: var(--chat-bg); min-height: 100vh; }
        .chat-page { max-width: 720px; margin: 0 auto; height: calc(100vh - 140px); display: flex; flex-direction: column; }
        .chat-header { background: #fff; border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem; font-weight: 600; border-radius: 12px 12px 0 0; }
        .chat-header .doc-id { font-family: monospace; color: #6c757d; font-size: 0.9rem; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem; background: #fff; }
        .chat-message { display: flex; max-width: 85%; }
        .chat-message.me { align-self: flex-end; }
        .chat-message.them { align-self: flex-start; }
        .chat-bubble { padding: 0.6rem 1rem; border-radius: 18px; word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
        .chat-message.me .chat-bubble { background: var(--bubble-me); color: #fff; border-bottom-right-radius: 4px; }
        .chat-message.them .chat-bubble { background: var(--bubble-them); color: #212529; border: 1px solid var(--bubble-them-border); border-bottom-left-radius: 4px; }
        .chat-message .sender-name { font-size: 0.75rem; font-weight: 600; margin-bottom: 2px; }
        .chat-message .time { font-size: 0.7rem; margin-top: 4px; opacity: .85; }
        .chat-message .msg-attachment { margin-top: 6px; }
        .chat-message .msg-attachment a { font-size: 0.85rem; }
        .chat-input-wrap { background: #fff; padding: 0.75rem 1rem; border-top: 1px solid #dee2e6; border-radius: 0 0 12px 12px; }
        .chat-input-wrap .form-control { border-radius: 24px; resize: none; }
        .chat-input-wrap .btn { border-radius: 24px; min-width: 90px; }
        .chat-empty { text-align: center; color: #6c757d; padding: 2rem; }
        .chat-loading { text-align: center; color: #6c757d; padding: 1rem; }
    </style>
</head>
<body class="container mt-3">
<?php $showLogout = true; include 'nav.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Chat with <?= htmlspecialchars($issuer['documentIssuerName']) ?></h5>
        <a href="admissions_dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
    </div>

    <div class="mb-3">
        <label class="form-label">Select document conversation</label>
        <form method="GET" class="d-flex gap-2" id="docForm">
            <input type="hidden" name="documentIssuerID" value="<?= $documentIssuerID ?>">
            <select name="documentID" class="form-select" id="docSelect">
                <option value="">— Select a document —</option>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($student['documentID']) ?>" <?= $student['documentID'] === $selectedDocumentID ? 'selected' : '' ?>>
                        <?= htmlspecialchars($student['userName']) ?> — <?= htmlspecialchars($student['documentID']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-primary">Open</button>
        </form>
    </div>

<?php if (!empty($selectedDocumentID)): ?>
    <div class="chat-page shadow-sm rounded">
        <div class="chat-header">
            Document <span class="doc-id"><?= htmlspecialchars($selectedDocumentID) ?></span>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div id="chat-loading" class="chat-loading">Loading messages…</div>
            <div id="chat-list"></div>
        </div>
        <div class="chat-input-wrap">
            <form id="chatForm" class="d-flex flex-column gap-2">
                <input type="hidden" name="documentID" value="<?= htmlspecialchars($selectedDocumentID) ?>">
                <input type="hidden" name="documentIssuerID" value="<?= $documentIssuerID ?>">
                <div class="d-flex gap-2 align-items-end">
                    <textarea name="message" id="message" class="form-control" rows="1" placeholder="Type a message…" maxlength="2000"></textarea>
                    <button type="submit" class="btn btn-primary" id="sendBtn">Send</button>
                </div>
                <input type="file" name="attachment" id="attachment" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,image/*,application/pdf" style="max-width: 220px;">
            </form>
        </div>
    </div>
<?php else: ?>
    <p class="text-muted">Select a document above to open the conversation.</p>
<?php endif; ?>

<?php if (!empty($selectedDocumentID)): ?>
<script>
(function() {
    const documentID = "<?= htmlspecialchars($selectedDocumentID, ENT_QUOTES) ?>";
    const documentIssuerID = "<?= (int) $documentIssuerID ?>";
    const listEl = document.getElementById('chat-list');
    const loadingEl = document.getElementById('chat-loading');
    const form = document.getElementById('chatForm');
    const messageInput = document.getElementById('message');
    const sendBtn = document.getElementById('sendBtn');

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    function formatTime(ts) {
        if (!ts) return '';
        var d = new Date(ts);
        var now = new Date();
        return d.toDateString() === now.toDateString() ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : d.toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
    }
    function renderMessages(messages) {
        if (!messages.length) {
            listEl.innerHTML = '<div class="chat-empty">No messages yet. Start the conversation.</div>';
            return;
        }
        listEl.innerHTML = messages.map(function(m) {
            var side = m.isMe ? 'me' : 'them';
            var attach = '';
            if (m.filePath) {
                var path = m.filePath.replace(/^uploads\/images\//, '');
                var viewUrl = 'index.php?controller=Document&action=view_image&path=' + encodeURIComponent(path);
                attach = '<div class="msg-attachment"><a href="' + viewUrl + '" target="_blank" rel="noopener">📎 Attachment</a></div>';
            }
            return '<div class="chat-message ' + side + '"><div class="chat-bubble">' +
                '<div class="sender-name">' + escapeHtml(m.userName) + '</div>' +
                '<div class="msg-text">' + escapeHtml(m.message) + '</div>' + attach +
                '<div class="time">' + formatTime(m.timestamp) + '</div></div></div>';
        }).join('');
        var container = document.getElementById('chat-messages');
        if (container) container.scrollTop = container.scrollHeight;
    }
    function loadMessages() {
        fetch('admissions_chat_fetch.php?documentID=' + encodeURIComponent(documentID) + '&documentIssuerID=' + documentIssuerID, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingEl.style.display = 'none';
                if (data.success && data.messages) renderMessages(data.messages);
                else listEl.innerHTML = '<div class="chat-empty">Could not load messages.</div>';
            })
            .catch(function() {
                loadingEl.style.display = 'none';
                listEl.innerHTML = '<div class="chat-empty">Error loading messages.</div>';
            });
    }
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var text = (messageInput.value || '').trim();
        var file = document.getElementById('attachment');
        if (!text && (!file || !file.files.length)) return;
        sendBtn.disabled = true;
        var formData = new FormData(form);
        fetch('../actions/admissions_chat_send.php', { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                messageInput.value = '';
                if (file) file.value = '';
                sendBtn.disabled = false;
                if (res.success) loadMessages();
                else alert(res.message || 'Failed to send.');
            })
            .catch(function() {
                sendBtn.disabled = false;
                alert('Failed to send. Try again.');
            });
    });
    loadMessages();
    setInterval(loadMessages, 2500);
})();
</script>
<?php endif; ?>
</body>
</html>
