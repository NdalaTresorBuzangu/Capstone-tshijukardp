<?php
/**
 * Static pages (about, etc.) – MVC.
 */

namespace App\Controllers;

use App\Core\BaseController;

class PageController extends BaseController
{
    public function about(): void
    {
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        $this->render('about.php', ['L' => $L, 'lang' => $lang]);
    }

    /** Terms of Service (GDPR / Ghana / Africa compliant) */
    public function terms(): void
    {
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        $this->render('terms.php', ['L' => $L, 'lang' => $lang]);
    }

    /** Privacy Policy (GDPR / Ghana / Africa compliant) */
    public function privacy(): void
    {
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang.php';
        $this->render('privacy.php', ['L' => $L, 'lang' => $lang]);
    }
}
