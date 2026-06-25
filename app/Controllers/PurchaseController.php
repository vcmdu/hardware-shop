<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Helpers\AuditLogger;
use App\Core\Session;
use TCPDF;

class PurchaseController extends Controller {
    public function index(Request $request, Response $response) {
        $this->requireAuth();
        $this->render('purchases/index', ['title' => 'Purchase Management']);
    }

    public function create(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $supplierModel = new Supplier();
        $suppliers = $supplierModel->fetchAll("SELECT id, supplier_name, supplier_code FROM suppliers WHERE status = 'active' ORDER BY supplier_name");
        $this->render('purchases/create', ['title' => 'New Purchase Order', 'suppliers' => $suppliers]);
    }

    public function apiList(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Purchase();
        $response->json(['success' => true, 'data' => $model->allWithSupplier()]);
    }

    public function apiCreate(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $body = $request->getBody();

        if (empty($body['supplier_id']) || empty($body['items'])) {
            $response->json(['success' => false, 'message' => 'Supplier and items are required.'], 400);
        }

        $model = new Purchase();
        $count = $model->query("SELECT COUNT(*) FROM purchases")->fetchColumn();
        $purchaseNumber = 'PO-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        $items = is_string($body['items']) ? json_decode($body['items'], true) : $body['items'];
        $gstTotal = 0;
        $grandTotal = 0;
        foreach ($items as &$item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $gstAmt = $lineTotal * ($item['gst_percentage'] / 100);
            $item['total'] = $lineTotal + $gstAmt - ($item['discount'] ?? 0);
            $gstTotal += $gstAmt;
            $grandTotal += $item['total'];
        }

        $purchaseId = $model->createPurchase([
            'purchase_number' => $purchaseNumber,
            'supplier_id' => $body['supplier_id'],
            'date' => $body['date'] ?? date('Y-m-d'),
            'discount' => $body['discount'] ?? 0,
            'gst_total' => $gstTotal,
            'grand_total' => $grandTotal - ($body['discount'] ?? 0),
            'status' => 'pending'
        ], $items);

        AuditLogger::log('Purchase order created', ['po' => $purchaseNumber, 'total' => $grandTotal]);
        $response->json(['success' => true, 'message' => 'Purchase Order created.', 'purchase_id' => $purchaseId]);
    }

    public function apiApprove(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $model = new Purchase();
        $purchase = $model->find($id);
        if (!$purchase) {
            $response->json(['success' => false, 'message' => 'Purchase not found.'], 404);
        }
        if ($purchase['status'] !== 'pending') {
            $response->json(['success' => false, 'message' => 'Only pending purchases can be approved.'], 400);
        }
        $model->updateStatus($id, 'approved');
        AuditLogger::log('Purchase approved - stock updated', ['id' => $id, 'po' => $purchase['purchase_number']]);
        $response->json(['success' => true, 'message' => 'Purchase approved and stock updated.']);
    }

    public function apiReturn(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $model = new Purchase();
        $purchase = $model->find($id);
        if (!$purchase || $purchase['status'] !== 'approved') {
            $response->json(['success' => false, 'message' => 'Only approved purchases can be returned.'], 400);
        }
        $model->updateStatus($id, 'returned');
        AuditLogger::log('Purchase returned - stock reversed', ['id' => $id]);
        $response->json(['success' => true, 'message' => 'Purchase marked as returned and stock reversed.']);
    }

    public function pdf(Request $request, Response $response, array $params) {
        $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $model = new Purchase();
        $purchase = $model->findWithSupplier($id);
        $items = $model->getItems($id);
        if (!$purchase) { http_response_code(404); die('Not found.'); }

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetTitle('Purchase Order #' . $purchase['purchase_number']);
        $pdf->AddPage();
        $html = "<h2>Purchase Order: {$purchase['purchase_number']}</h2>";
        $html .= "<p>Supplier: {$purchase['supplier_name']}<br>Date: {$purchase['date']}<br>Status: {$purchase['status']}</p>";
        $html .= "<table border='1'><tr><th>Product</th><th>Qty</th><th>Unit Cost</th><th>GST%</th><th>Total</th></tr>";
        foreach ($items as $item) {
            $html .= "<tr><td>{$item['product_name']}</td><td>{$item['quantity']}</td><td>{$item['unit_cost']}</td><td>{$item['gst_percentage']}%</td><td>{$item['total']}</td></tr>";
        }
        $html .= "</table><p><strong>Grand Total: ₹{$purchase['grand_total']}</strong></p>";
        $pdf->writeHTML($html);
        $pdf->Output('PO_' . $purchase['purchase_number'] . '.pdf', 'D');
        exit;
    }
}
