<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Helpers\AuditLogger;

class CustomerController extends Controller {
    public function index(Request $request, Response $response) {
        $this->requireAuth();
        $this->render('customers/index', ['title' => 'Customer Management']);
    }

    public function apiList(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Customer();
        $response->json(['success' => true, 'data' => $model->all()]);
    }

    public function apiCreate(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager', 'sales_staff']);
        $this->validateCsrf($request);
        $body = $request->getBody();
        if (empty($body['name'])) {
            $response->json(['success' => false, 'message' => 'Customer name is required.'], 400);
        }
        $model = new Customer();
        $count = $model->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $body['customer_code'] = $body['customer_code'] ?? ('CUST' . str_pad($count + 1, 4, '0', STR_PAD_LEFT));
        $ok = $model->create($body);
        if ($ok) {
            AuditLogger::log('Customer created', ['name' => $body['name']]);
            $response->json(['success' => true, 'message' => 'Customer created successfully.']);
        }
        $response->json(['success' => false, 'message' => 'Failed to create customer.'], 400);
    }

    public function apiUpdate(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $body = $request->getBody();
        $model = new Customer();
        $model->update($id, $body);
        AuditLogger::log('Customer updated', ['id' => $id]);
        $response->json(['success' => true, 'message' => 'Customer updated.']);
    }

    public function apiDelete(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $model = new Customer();
        try {
            $model->delete($id);
            AuditLogger::log('Customer deleted', ['id' => $id]);
            $response->json(['success' => true, 'message' => 'Customer deleted.']);
        } catch (\Exception $e) {
            $response->json(['success' => false, 'message' => 'Cannot delete: customer has linked sales.'], 400);
        }
    }
}
