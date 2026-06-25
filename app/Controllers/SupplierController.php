<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Supplier;
use App\Helpers\AuditLogger;

class SupplierController extends Controller {
    public function index(Request $request, Response $response) {
        $this->requireAuth();
        $this->render('suppliers/index', ['title' => 'Supplier Management']);
    }

    public function apiList(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Supplier();
        $response->json(['success' => true, 'data' => $model->all()]);
    }

    public function apiCreate(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $body = $request->getBody();
        if (empty($body['supplier_name'])) {
            $response->json(['success' => false, 'message' => 'Supplier name is required.'], 400);
        }
        // Auto-generate supplier code
        $model = new Supplier();
        $count = $model->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
        $body['supplier_code'] = $body['supplier_code'] ?? ('SUP' . str_pad($count + 1, 4, '0', STR_PAD_LEFT));
        $ok = $model->create($body);
        if ($ok) {
            AuditLogger::log('Supplier created', ['name' => $body['supplier_name']]);
            $response->json(['success' => true, 'message' => 'Supplier created successfully.']);
        }
        $response->json(['success' => false, 'message' => 'Failed to create supplier.'], 400);
    }

    public function apiUpdate(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $body = $request->getBody();
        $model = new Supplier();
        $model->update($id, $body);
        AuditLogger::log('Supplier updated', ['id' => $id]);
        $response->json(['success' => true, 'message' => 'Supplier updated.']);
    }

    public function apiDelete(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $model = new Supplier();
        try {
            $model->delete($id);
            AuditLogger::log('Supplier deleted', ['id' => $id]);
            $response->json(['success' => true, 'message' => 'Supplier deleted.']);
        } catch (\Exception $e) {
            $response->json(['success' => false, 'message' => 'Cannot delete: supplier has linked purchases.'], 400);
        }
    }
}
