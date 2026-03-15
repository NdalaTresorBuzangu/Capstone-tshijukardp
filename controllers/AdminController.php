<?php
/**
 * Admin dashboard and actions (MVC).
 */

namespace App\Controllers;

use App\Core\BaseController;

class AdminController extends BaseController
{
    public function dashboard(): void
    {
        $this->requireRole('Admin');
        if (!isset($GLOBALS['L'])) {
            require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        }
        $this->render('admin_landing.php', [
            'L' => $GLOBALS['L'] ?? [],
            'lang' => $GLOBALS['lang'] ?? 'en'
        ]);
    }
}
