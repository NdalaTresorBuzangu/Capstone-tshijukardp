<?php
/**
 * MVC Router – single entry point dispatcher.
 * Routes: index.php?controller=Auth&action=login → App\Controllers\AuthController::login()
 */

namespace App\Core;

class Router
{
    private \mysqli $db;
    private string $controllersNamespace = 'App\Controllers';
    private string $controllersPath;
    private string $viewsPath;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->controllersPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR;
        $this->viewsPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    }

    /**
     * Dispatch request to controller action.
     * GET/POST params: controller, action. Optional: id, etc.
     */
    public function dispatch(): void
    {
        $controllerName = $this->getControllerName();
        $actionName = $this->getActionName();

        if ($controllerName === '' || $actionName === '') {
            $this->notFound('Missing controller or action.');
            return;
        }

        $className = $this->controllersNamespace . '\\' . $controllerName . 'Controller';
        $method = $this->actionToMethod($actionName);

        if (!file_exists($this->controllersPath . $controllerName . 'Controller.php')) {
            $this->notFound('Controller not found: ' . $controllerName);
            return;
        }

        if (!class_exists($className)) {
            $this->notFound('Controller class not found: ' . $className);
            return;
        }

        $controller = new $className($this->db);

        if (!method_exists($controller, $method)) {
            $this->notFound('Action not found: ' . $actionName);
            return;
        }

        $controller->$method();
    }

    private function getControllerName(): string
    {
        $c = $_GET['controller'] ?? $_POST['controller'] ?? '';
        return trim(preg_replace('/[^a-zA-Z0-9_]/', '', $c));
    }

    private function getActionName(): string
    {
        $a = $_GET['action'] ?? $_POST['action'] ?? '';
        return trim(preg_replace('/[^a-zA-Z0-9_]/', '', $a));
    }

    /** Convert action name to method name: login_form → loginForm */
    private function actionToMethod(string $action): string
    {
        $parts = explode('_', strtolower($action));
        $method = '';
        foreach ($parts as $p) {
            $method .= ucfirst($p);
        }
        return lcfirst($method) ?: 'index';
    }

    private function notFound(string $message): void
    {
        http_response_code(404);
        if ($this->isAjaxOrJson()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            return;
        }
        echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>Not Found</h1><p>' . htmlspecialchars($message) . '</p></body></html>';
    }

    private function isAjaxOrJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || strpos($accept, 'application/json') !== false;
    }

    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }
}
