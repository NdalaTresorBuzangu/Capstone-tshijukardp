<?php
/**
 * Seeker–Issuer chat (per document). Accessible by Document Seeker and Document Issuer.
 */

namespace App\Controllers;

use App\Core\BaseController;

class ChatController extends BaseController
{
    /** Chat page for a document (seeker or issuer). */
    public function index(): void
    {
        $this->requireRole('Document Seeker', 'Document Issuer');
        if (!isset($_GET['documentID'])) {
            echo 'Invalid request.';
            exit;
        }
        $path = $this->viewsPath . 'chat.php';
        if (!is_file($path)) {
            throw new \RuntimeException('View not found: chat.php');
        }
        require $path;
    }

    /** Fetch chat messages (JSON) for AJAX. */
    public function fetch(): void
    {
        $this->requireRole('Document Seeker', 'Document Issuer');
        if (!isset($_GET['documentID'])) {
            $this->json(['success' => false, 'message' => 'Invalid request.'], 400);
        }
        $documentID = $_GET['documentID'];
        $userID = (int) $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT * FROM Document WHERE documentID = ? AND (userID = ? OR documentIssuerID = ?)");
        $stmt->bind_param("sii", $documentID, $userID, $userID);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$document) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $stmt = $this->db->prepare("
            SELECT c.chatID, c.message, c.timestamp, c.senderID, u.userName
            FROM Chat c
            JOIN User u ON c.senderID = u.userID
            WHERE c.documentID = ?
            ORDER BY c.timestamp ASC
        ");
        $stmt->bind_param("s", $documentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'chatID'    => (int) $row['chatID'],
                'senderID'  => (int) $row['senderID'],
                'userName'  => $row['userName'],
                'message'   => $row['message'],
                'timestamp' => $row['timestamp'],
                'isMe'      => (int) $row['senderID'] === $userID
            ];
        }
        $stmt->close();

        $this->json(['success' => true, 'messages' => $messages]);
    }
}
