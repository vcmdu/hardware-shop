<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Helpers\AuditLogger;
use App\Core\Session;

class AuthController extends Controller
{
    public function loginPage(Request $request, Response $response)
    {
        if (Session::get('user')) {
            $response->redirect('/');
        }
        $this->render('auth/login', ['title' => 'Login - AMMAN TRADERS'], 'auth');
    }

    public function login(Request $request, Response $response)
    {
        $this->validateCsrf($request);

        $username = trim((string) $request->get('username'));
        $password = (string) $request->get('password');

        if (empty($username) || empty($password)) {
            $response->json(['success' => false, 'message' => 'Username and Password are required.'], 400);
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $response->json(['success' => false, 'message' => 'Invalid username or password.'], 400);
        }

        if ($user['status'] !== 'active') {
            $response->json(['success' => false, 'message' => 'Your account is inactive. Contact the administrator.'], 403);
        }

        // Set session user
        Session::set('user', [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);

        // Audit Trail Log
        AuditLogger::log("User login successful", null, $user['id']);

        $response->json(['success' => true, 'message' => 'Login successful! Redirecting...', 'redirect' => '/']);
    }

    public function logout(Request $request, Response $response)
    {
        $this->validateCsrf($request);
        $user = Session::get('user');
        if ($user) {
            AuditLogger::log("User logout", null, $user['id']);
        }
        Session::destroy();
        $response->redirect('/login');
    }
}
