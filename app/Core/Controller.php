<?php
namespace App\Core;

class Controller {
    public function __construct() {
        Session::start();
    }

    protected function render(string $view, array $data = [], string $layout = 'main'): void {
        // Extract variables to local scope
        extract($data);

        // Include CSRF helper
        $csrfToken = Session::getCsrfToken();
        $currentUser = Session::get('user');

        // Set page title default if not set
        $title = $title ?? 'Hardware Shop System';

        // 1. Get view content
        ob_start();
        include dirname(__DIR__) . "/Views/$view.php";
        $content = ob_get_clean();

        // 2. Include in layout and print
        include dirname(__DIR__) . "/Views/layouts/$layout.php";
    }

    protected function validateCsrf(Request $request): void {
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $token = $request->get('_csrf') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Session::validateCsrfToken($token)) {
                $response = new Response();
                if ($request->isAjax()) {
                    $response->json(['success' => false, 'message' => 'CSRF token validation failed.'], 403);
                } else {
                    $response->setStatusCode(403);
                    die('CSRF token validation failed.');
                }
            }
        }
    }

    protected function requireAuth(): array {
        $user = Session::get('user');
        if (!$user) {
            $response = new Response();
            $response->redirect('/login');
        }
        return $user;
    }

    protected function requireRoles(array $roles): array {
        $user = $this->requireAuth();
        if (!in_array($user['role'], $roles)) {
            $response = new Response();
            $request = new Request();
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Unauthorized access. Insufficient privileges.'], 403);
            } else {
                $response->setStatusCode(403);
                die('<h1>403 Access Denied</h1><p>You do not have the required permissions to access this page.</p>');
            }
        }
        return $user;
    }
}
