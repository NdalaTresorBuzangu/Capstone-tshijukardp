<?php
/**
 * Home / landing page controller (MVC).
 */

namespace App\Controllers;

use App\Core\BaseController;

class HomeController extends BaseController
{
    /**
     * Display home page (index).
     */
    public function index(): void
    {
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        $baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/';
        $loggedIn = !empty($_SESSION['user_id']);
        $this->render('home.php', [
            'L' => $L,
            'lang' => $lang,
            'baseUrl' => $baseUrl,
            'loggedIn' => $loggedIn
        ]);
    }
}
