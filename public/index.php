<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

echo "App Started 🚀 <br>";

try {
    $db = Database::getConnection();
    echo "Database Connected Successfully ✅";
} catch (Exception $e) {
    echo "DB Connection Failed ❌";
}
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize requests
$request = new Request();
$response = new Response();
$router = new Router($request, $response);

// 1. Auth routes
$router->get('/login', [App\Controllers\AuthController::class, 'loginPage']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->post('/logout', [App\Controllers\AuthController::class, 'logout']);

// 2. Dashboard route
$router->get('/', [App\Controllers\DashboardController::class, 'index']);

// 3. Category Management
$router->get('/categories', [App\Controllers\CategoryController::class, 'index']);
$router->get('/api/categories', [App\Controllers\CategoryController::class, 'apiList']);
$router->post('/api/categories', [App\Controllers\CategoryController::class, 'apiCreate']);
$router->put('/api/categories/{id}', [App\Controllers\CategoryController::class, 'apiUpdate']);
$router->delete('/api/categories/{id}', [App\Controllers\CategoryController::class, 'apiDelete']);

// 4. Product Management
$router->get('/products', [App\Controllers\ProductController::class, 'index']);
$router->get('/api/products', [App\Controllers\ProductController::class, 'apiList']);
$router->get('/api/products/search', [App\Controllers\ProductController::class, 'apiSearch']);
$router->post('/api/products', [App\Controllers\ProductController::class, 'apiCreate']);
$router->post('/api/products/import', [App\Controllers\ProductController::class, 'apiImport']);
$router->post('/api/products/{id}', [App\Controllers\ProductController::class, 'apiUpdate']); // POST because of file uploads in PHP updates
$router->delete('/api/products/{id}', [App\Controllers\ProductController::class, 'apiDelete']);
$router->get('/products/export', [App\Controllers\ProductController::class, 'export']);

// 5. Supplier Management
$router->get('/suppliers', [App\Controllers\SupplierController::class, 'index']);
$router->get('/api/suppliers', [App\Controllers\SupplierController::class, 'apiList']);
$router->post('/api/suppliers', [App\Controllers\SupplierController::class, 'apiCreate']);
$router->put('/api/suppliers/{id}', [App\Controllers\SupplierController::class, 'apiUpdate']);
$router->delete('/api/suppliers/{id}', [App\Controllers\SupplierController::class, 'apiDelete']);

// 6. Customer Management
$router->get('/customers', [App\Controllers\CustomerController::class, 'index']);
$router->get('/api/customers', [App\Controllers\CustomerController::class, 'apiList']);
$router->post('/api/customers', [App\Controllers\CustomerController::class, 'apiCreate']);
$router->put('/api/customers/{id}', [App\Controllers\CustomerController::class, 'apiUpdate']);
$router->delete('/api/customers/{id}', [App\Controllers\CustomerController::class, 'apiDelete']);

// 7. Purchase Management
$router->get('/purchases', [App\Controllers\PurchaseController::class, 'index']);
$router->get('/purchases/create', [App\Controllers\PurchaseController::class, 'create']);
$router->get('/api/purchases', [App\Controllers\PurchaseController::class, 'apiList']);
$router->post('/api/purchases', [App\Controllers\PurchaseController::class, 'apiCreate']);
$router->post('/api/purchases/{id}/approve', [App\Controllers\PurchaseController::class, 'apiApprove']);
$router->post('/api/purchases/{id}/return', [App\Controllers\PurchaseController::class, 'apiReturn']);
$router->get('/purchases/{id}/pdf', [App\Controllers\PurchaseController::class, 'pdf']);

// 8. POS & Sales Module
$router->get('/pos', [App\Controllers\SalesController::class, 'pos']);
$router->get('/sales', [App\Controllers\SalesController::class, 'index']);
$router->get('/api/sales', [App\Controllers\SalesController::class, 'apiList']);
$router->post('/api/sales', [App\Controllers\SalesController::class, 'apiCreate']);
$router->get('/sales/{id}/pdf', [App\Controllers\SalesController::class, 'pdf']);

// 9. Inventory Management
$router->get('/inventory/ledger', [App\Controllers\InventoryController::class, 'ledger']);
$router->get('/api/inventory/ledger', [App\Controllers\InventoryController::class, 'apiLedger']);
$router->get('/inventory/adjustment', [App\Controllers\InventoryController::class, 'adjustment']);
$router->post('/api/inventory/adjustment', [App\Controllers\InventoryController::class, 'apiCreateAdjustment']);
$router->get('/api/inventory/adjustments', [App\Controllers\InventoryController::class, 'apiAdjustmentsList']);

// 10. Reports Module
$router->get('/reports/sales', [App\Controllers\ReportController::class, 'sales']);
$router->get('/reports/purchase', [App\Controllers\ReportController::class, 'purchase']);
$router->get('/reports/inventory', [App\Controllers\ReportController::class, 'inventory']);
$router->get('/reports/financial', [App\Controllers\ReportController::class, 'financial']);
$router->get('/api/reports/sales', [App\Controllers\ReportController::class, 'apiSales']);
$router->get('/api/reports/purchase', [App\Controllers\ReportController::class, 'apiPurchase']);
$router->get('/api/reports/inventory', [App\Controllers\ReportController::class, 'apiInventory']);
$router->get('/api/reports/financial', [App\Controllers\ReportController::class, 'apiFinancial']);
$router->get('/reports/export', [App\Controllers\ReportController::class, 'export']);

// 11. Settings Module
$router->get('/settings', [App\Controllers\SettingController::class, 'index']);
$router->post('/settings', [App\Controllers\SettingController::class, 'update']);

// 12. Audit Trail
$router->get('/audit', [App\Controllers\DashboardController::class, 'auditLogPage']);
$router->get('/api/audit', [App\Controllers\DashboardController::class, 'apiAuditLog']);

// Run router
$router->resolve();
