<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Inventory;
use App\Models\Product;
use App\Core\Session;
use App\Helpers\AuditLogger;

class InventoryController extends Controller {
    public function ledger(Request $request, Response $response) {
        $this->requireAuth();
        $this->render('inventory/ledger', ['title' => 'Stock Ledger']);
    }

    public function apiLedger(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Inventory();
        $response->json(['success' => true, 'data' => $model->getLedger()]);
    }

    public function adjustment(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $productModel = new Product();
        $products = $productModel->fetchAll("SELECT id, product_name, product_code, current_stock, unit FROM products WHERE status = 'active' ORDER BY product_name");
        $this->render('inventory/adjustment', ['title' => 'Stock Adjustment', 'products' => $products]);
    }

    public function apiCreateAdjustment(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $body = $request->getBody();

        if (empty($body['type']) || empty($body['items'])) {
            $response->json(['success' => false, 'message' => 'Adjustment type and items are required.'], 400);
        }

        $user = Session::get('user');
        $model = new Inventory();
        $count = $model->query("SELECT COUNT(*) FROM inventory_adjustments")->fetchColumn();
        $refNumber = 'ADJ-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $items = is_string($body['items']) ? json_decode($body['items'], true) : $body['items'];

        // Fetch current stock for each product
        $productModel = new Product();
        $preparedItems = [];
        foreach ($items as $item) {
            $product = $productModel->find((int)$item['product_id']);
            if (!$product) continue;
            $qtyBefore = (int)$product['current_stock'];
            $qtyAfter  = (int)$item['quantity_after'];
            $preparedItems[] = [
                'product_id'        => $item['product_id'],
                'quantity_before'   => $qtyBefore,
                'quantity_after'    => $qtyAfter,
                'quantity_adjusted' => $qtyAfter - $qtyBefore,
                'reason'            => $item['reason'] ?? null,
            ];
        }

        $adjId = $model->createAdjustment([
            'reference_number' => $refNumber,
            'type'             => $body['type'],
            'description'      => $body['description'] ?? null,
            'date'             => $body['date'] ?? date('Y-m-d'),
            'created_by'       => $user['id'],
        ], $preparedItems);

        AuditLogger::log('Inventory adjustment created', ['ref' => $refNumber, 'type' => $body['type']]);
        $response->json(['success' => true, 'message' => 'Adjustment recorded successfully.', 'adjustment_id' => $adjId]);
    }

    public function apiAdjustmentsList(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Inventory();
        $id = (int)$request->get('id', 0);
        if ($id > 0) {
            $response->json(['success' => true, 'data' => $model->getAdjustmentItems($id)]);
        } else {
            $response->json(['success' => true, 'data' => $model->allAdjustments()]);
        }
    }
}
