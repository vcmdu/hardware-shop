<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Helpers\AuditLogger;

class CategoryController extends Controller {
    public function index(Request $request, Response $response) {
        $this->requireAuth();
        $this->render('categories/index', ['title' => 'Category Management']);
    }

    public function apiList(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Category();
        $data = $model->all();
        $response->json(['success' => true, 'data' => $data]);
    }

    public function apiCreate(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $body = $request->getBody();
        $name = trim($body['name'] ?? '');
        $desc = trim($body['description'] ?? '');
        $status = $body['status'] ?? 'active';
        if (empty($name)) {
            $response->json(['success' => false, 'message' => 'Category name is required.'], 400);
        }
        $model = new Category();
        $ok = $model->create($name, $desc ?: null, $status);
        if ($ok) {
            AuditLogger::log('Category created', ['name' => $name]);
            $response->json(['success' => true, 'message' => 'Category created successfully.']);
        }
        $response->json(['success' => false, 'message' => 'Failed to create category. Name may already exist.'], 400);
    }

    public function apiUpdate(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $body = $request->getBody();
        $name = trim($body['name'] ?? '');
        $desc = trim($body['description'] ?? '');
        $status = $body['status'] ?? 'active';
        if (!$id || empty($name)) {
            $response->json(['success' => false, 'message' => 'Invalid request.'], 400);
        }
        $model = new Category();
        $ok = $model->update($id, $name, $desc ?: null, $status);
        AuditLogger::log('Category updated', ['id' => $id, 'name' => $name]);
        $response->json(['success' => true, 'message' => 'Category updated.']);
    }

    public function apiDelete(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        if (!$id) { $response->json(['success' => false, 'message' => 'Invalid ID.'], 400); }
        $model = new Category();
        try {
            $model->delete($id);
            AuditLogger::log('Category deleted', ['id' => $id]);
            $response->json(['success' => true, 'message' => 'Category deleted.']);
        } catch (\Exception $e) {
            $response->json(['success' => false, 'message' => 'Cannot delete: category is in use by products.'], 400);
        }
    }
}
