<?php
/**
 * Base Controller for MVC.
 * All web controllers should extend this for render/redirect and DB access.
 */

namespace App\Core;

abstract class BaseController
{
    protected \mysqli $db;
    protected string $viewsPath;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->viewsPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    }

    /**
     * Require login; redirect to login if not authenticated.
     */
    protected function requireLogin(): array
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            $this->redirect('index.php?controller=Auth&action=login_form&redirect=' . $redirect);
        }
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'user_role' => $_SESSION['user_role'] ?? ''
        ];
    }

    /**
     * Require specific role; redirect or 403.
     */
    protected function requireRole(string ...$roles): array
    {
        $user = $this->requireLogin();
        if (!in_array($user['user_role'], $roles)) {
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied.']);
                exit;
            }
            echo 'Access denied.';
            exit;
        }
        return $user;
    }

    protected function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || strpos($accept, 'application/json') !== false;
    }

    /**
     * Render a view with data. View file receives $data array as variables.
     */
    protected function render(string $viewFile, array $data = []): void
    {
        $path = $this->viewsPath . $viewFile;
        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $viewFile);
        }
        extract($data, EXTR_SKIP);
        require $path;
    }

    /**
     * Redirect and exit.
     */
    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    /**
     * Output JSON and exit (for AJAX actions).
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
