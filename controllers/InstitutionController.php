<?php
/**
 * Document Issuer (institution) – subscribe and panel (MVC).
 */

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Subscribe;

class InstitutionController extends BaseController
{
    /** Show subscribe form or redirect to panel if already subscribed */
    public function subscribe(): void
    {
        $user = $this->requireRole('Document Issuer');
        $subscribeModel = new Subscribe($this->db);
        $existing = $subscribeModel->findByUserId((int) $user['user_id']);
        if ($existing) {
            $this->redirect('index.php?controller=Institution&action=panel');
        }
        if (!isset($GLOBALS['L'])) {
            require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        }
        $this->render('subscribe_institution.php', [
            'L' => $GLOBALS['L'] ?? [],
            'lang' => $GLOBALS['lang'] ?? 'en'
        ]);
    }

    /** Handle subscribe form POST */
    public function subscribeSubmit(): void
    {
        $this->requireRole('Document Issuer');
        $subscribeModel = new Subscribe($this->db);
        $userId = (int) $_SESSION['user_id'];
        $existing = $subscribeModel->findByUserId($userId);
        if ($existing) {
            $this->redirect('index.php?controller=Institution&action=panel');
        }
        if (!isset($_POST['accept_terms_subscribe']) || $_POST['accept_terms_subscribe'] !== '1') {
            $_SESSION['message'] = 'You must accept the Terms of Service and Privacy Policy to subscribe.';
            $_SESSION['message_type'] = 'error';
            $this->redirect('index.php?controller=Institution&action=subscribe');
        }
        $name = trim($_POST['documentIssuerName'] ?? '');
        $contact = trim($_POST['documentIssuerContact'] ?? '');
        $email = trim($_POST['documentIssuerEmail'] ?? '');
        if (!$name || !$email) {
            $_SESSION['message'] = 'Name and email are required.';
            $_SESSION['message_type'] = 'error';
            $this->redirect('index.php?controller=Institution&action=subscribe');
        }
        $ok = $subscribeModel->create([
            'userID' => $userId,
            'documentIssuerName' => $name,
            'documentIssuerContact' => $contact,
            'documentIssuerEmail' => $email
        ]);
        if ($ok) {
            $_SESSION['message'] = 'Institution subscribed successfully.';
            $_SESSION['message_type'] = 'success';
            $this->redirect('index.php?controller=Institution&action=panel');
        }
        $_SESSION['message'] = 'Subscription failed.';
        $_SESSION['message_type'] = 'error';
        $this->redirect('index.php?controller=Institution&action=subscribe');
    }

    /** Institution panel (dashboard) – delegate to view which still contains logic for now */
    public function panel(): void
    {
        $this->requireRole('Document Issuer');
        if (!isset($GLOBALS['L'])) {
            require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        }
        $this->render('institutionpanel.php', [
            'L' => $GLOBALS['L'] ?? [],
            'lang' => $GLOBALS['lang'] ?? 'en'
        ]);
    }

    /** Issuer upload documents (stored docs) page */
    public function uploadDocuments(): void
    {
        $this->requireRole('Document Issuer');
        if (!isset($GLOBALS['L'])) {
            require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        }
        $this->render('issuer_upload_documents.php', [
            'L' => $GLOBALS['L'] ?? [],
            'lang' => $GLOBALS['lang'] ?? 'en'
        ]);
    }
}
