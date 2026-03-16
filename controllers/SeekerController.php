<?php
/**
 * Document Seeker dashboard and pages (MVC).
 */

namespace App\Controllers;

use App\Core\BaseController;

class SeekerController extends BaseController
{
    public function dashboard(): void
    {
        $this->requireRole('Document Seeker');
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('student_dashboard.php', ['L' => $L, 'lang' => $lang]);
    }

    /** Tshijuka Pack (seeker documents) */
    public function pack(): void
    {
        $this->requireRole('Document Seeker');
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('tshijuka_pack.php', ['L' => $L, 'lang' => $lang]);
    }

    /** Submit document request form */
    public function submitForm(): void
    {
        $this->requireRole('Document Seeker');
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('submit_document.php', ['L' => $L ?? [], 'lang' => $lang ?? 'en']);
    }

    /** Track progress (check document status) */
    public function progress(): void
    {
        $this->requireRole('Document Seeker');
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('progress.php', ['L' => $L ?? [], 'lang' => $lang ?? 'en']);
    }

    /** Preloss – upload & protect documents */
    public function preloss(): void
    {
        $this->requireRole('Document Seeker');
        $langPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        require_once $langPath;
        $this->render('preloss.php', ['L' => $L ?? [], 'lang' => $lang ?? 'en']);
    }
}
