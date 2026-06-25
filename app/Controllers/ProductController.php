<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Models\Category;
use App\Helpers\AuditLogger;
use App\Helpers\FileUpload;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ProductController extends Controller {
    public function index(Request $request, Response $response) {
        $this->requireAuth();
        $catModel = new Category();
        $categories = $catModel->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
        $this->render('products/index', ['title' => 'Product Management', 'categories' => $categories]);
    }

    public function apiList(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Product();
        $data = $model->allWithCategory();
        $response->json(['success' => true, 'data' => $data]);
    }

    public function apiSearch(Request $request, Response $response) {
        $this->requireAuth();
        $q = trim($request->get('q', ''));
        if (strlen($q) < 1) {
            $response->json(['success' => true, 'data' => []]);
            return;
        }
        $model = new Product();
        $response->json(['success' => true, 'data' => $model->search($q)]);
    }

    public function apiCreate(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $body = $request->getBody();

        $required = ['product_name', 'category_id', 'purchase_price', 'selling_price', 'barcode'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                $response->json(['success' => false, 'message' => "Field '$field' is required."], 400);
            }
        }

        $model = new Product();
        // Auto-generate product code if not provided
        if (empty($body['product_code'])) {
            $count = $model->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $body['product_code'] = 'PRD' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        }

        // Handle image upload
        $files = $request->getFiles();
        if (!empty($files['image']['tmp_name'])) {
            $path = FileUpload::uploadProductImage($files['image']);
            if ($path) $body['image_path'] = $path;
        }

        $ok = $model->create($body);
        if ($ok) {
            AuditLogger::log('Product created', ['name' => $body['product_name'], 'code' => $body['product_code']]);
            $response->json(['success' => true, 'message' => 'Product created successfully.']);
        }
        $response->json(['success' => false, 'message' => 'Failed to create product. Code or barcode may already exist.'], 400);
    }

    public function apiUpdate(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $body = $request->getBody();

        $model = new Product();
        $files = $request->getFiles();
        if (!empty($files['image']['tmp_name'])) {
            $path = FileUpload::uploadProductImage($files['image']);
            if ($path) $body['image_path'] = $path;
        }

        $model->update($id, $body);
        AuditLogger::log('Product updated', ['id' => $id]);
        $response->json(['success' => true, 'message' => 'Product updated.']);
    }

    public function apiDelete(Request $request, Response $response, array $params) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->validateCsrf($request);
        $id = (int)($params['id'] ?? 0);
        $model = new Product();
        try {
            $model->delete($id);
            AuditLogger::log('Product deleted', ['id' => $id]);
            $response->json(['success' => true, 'message' => 'Product deleted.']);
        } catch (\Exception $e) {
            $response->json(['success' => false, 'message' => 'Cannot delete: product is in use.'], 400);
        }
    }

    public function export(Request $request, Response $response) {
        $this->requireAuth();
        $model = new Product();
        $products = $model->allWithCategory();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');
        $headers = ['Code', 'Barcode', 'Name', 'Category', 'Brand', 'Unit', 'Purchase Price', 'Selling Price', 'GST%', 'Stock', 'Min Stock', 'Rack', 'Status'];
        $sheet->fromArray($headers, null, 'A1');
        $row = 2;
        foreach ($products as $p) {
            $sheet->fromArray([
                $p['product_code'], $p['barcode'], $p['product_name'], $p['category_name'], $p['brand'],
                $p['unit'], $p['purchase_price'], $p['selling_price'], $p['gst_percentage'],
                $p['current_stock'], $p['minimum_stock'], $p['rack_location'], $p['status']
            ], null, "A$row");
            $row++;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="products_export_' . date('Ymd') . '.xlsx"');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function apiImport(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->validateCsrf($request);
        $files = $request->getFiles();
        if (empty($files['import_file']['tmp_name'])) {
            $response->json(['success' => false, 'message' => 'No file uploaded.'], 400);
        }
        try {
            $reader = new XlsxReader();
            $spreadsheet = $reader->load($files['import_file']['tmp_name']);
            $data = $spreadsheet->getActiveSheet()->toArray();
            array_shift($data); // Remove header row
            $model = new Product();
            $catModel = new Category();
            $cats = $catModel->fetchAll("SELECT id, name FROM categories");
            $catMap = array_column($cats, 'id', 'name');
            $imported = 0;
            foreach ($data as $row) {
                if (empty($row[0])) continue;
                $catId = $catMap[$row[3]] ?? null;
                $model->create([
                    'product_code' => $row[0], 'barcode' => $row[1], 'product_name' => $row[2],
                    'category_id' => $catId, 'brand' => $row[4], 'unit' => $row[5] ?? 'pcs',
                    'purchase_price' => $row[6] ?? 0, 'selling_price' => $row[7] ?? 0,
                    'gst_percentage' => $row[8] ?? 18, 'current_stock' => $row[9] ?? 0,
                    'minimum_stock' => $row[10] ?? 5, 'rack_location' => $row[11], 'status' => $row[12] ?? 'active'
                ]);
                $imported++;
            }
            AuditLogger::log('Products imported via Excel', ['count' => $imported]);
            $response->json(['success' => true, 'message' => "$imported products imported successfully."]);
        } catch (\Exception $e) {
            $response->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
