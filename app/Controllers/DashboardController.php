<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\AuditTrail;

class DashboardController extends Controller {
    public function index(Request $request, Response $response) {
        $user = $this->requireAuth();

        $productModel = new Product();
        $categoryModel = new Category();
        $supplierModel = new Supplier();
        $customerModel = new Customer();
        $saleModel = new Sale();
        $purchaseModel = new Purchase();

        // 1. Core counters
        $totalProducts = $productModel->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $totalCategories = $categoryModel->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $totalSuppliers = $supplierModel->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
        $totalCustomers = $customerModel->query("SELECT COUNT(*) FROM customers")->fetchColumn();

        // 2. Stock counts
        $lowStockCount = $productModel->query("SELECT COUNT(*) FROM products WHERE current_stock <= minimum_stock AND current_stock > 0 AND status = 'active'")->fetchColumn();
        $outOfStockCount = $productModel->query("SELECT COUNT(*) FROM products WHERE current_stock <= 0 AND status = 'active'")->fetchColumn();

        // 3. Sales statistics
        $todaySales = $saleModel->getTodaySales();
        $monthlySales = $saleModel->getMonthlySales();

        // 4. Purchases summary
        $purchaseSummary = $purchaseModel->query("
            SELECT 
                COALESCE(SUM(CASE WHEN status = 'approved' THEN grand_total ELSE 0 END), 0) as approved_total,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN grand_total ELSE 0 END), 0) as pending_total
            FROM purchases
        ")->fetch();

        // 5. Recent lists
        $topSelling = $saleModel->getTopSelling();
        $recentSales = $saleModel->getRecentSales();
        
        // Low stock items list for alerts
        $lowStockItems = $productModel->getLowStock();
        $outStockItems = $productModel->getOutOfStock();

        // 6. Chart data: Monthly Sales Trend (12 Months)
        $monthlyTrendSql = "
            SELECT 
                m.month_num,
                TO_CHAR(TO_DATE(m.month_num::text, 'MM'), 'Month') as month_name,
                COALESCE(SUM(s.grand_total), 0) as total_sales
            FROM (SELECT generate_series(1, 12) as month_num) m
            LEFT JOIN sales s ON EXTRACT(MONTH FROM s.date) = m.month_num AND EXTRACT(YEAR FROM s.date) = EXTRACT(YEAR FROM CURRENT_DATE)
            GROUP BY m.month_num, month_name
            ORDER BY m.month_num
        ";
        $monthlyTrend = $saleModel->fetchAll($monthlyTrendSql);

        $this->render('dashboard/index', [
            'title' => 'Dashboard - Hardware Inventory',
            'user' => $user,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
            'totalCustomers' => $totalCustomers,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'todaySales' => $todaySales,
            'monthlySales' => $monthlySales,
            'approvedPurchases' => $purchaseSummary['approved_total'],
            'pendingPurchases' => $purchaseSummary['pending_total'],
            'topSelling' => $topSelling,
            'recentSales' => $recentSales,
            'lowStockItems' => $lowStockItems,
            'outStockItems' => $outStockItems,
            'monthlyTrend' => $monthlyTrend
        ]);
    }

    public function auditLogPage(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin']);
        $this->render('audit/index', ['title' => 'Audit Trail - Security logs']);
    }

    public function apiAuditLog(Request $request, Response $response) {
        $this->requireRoles(['super_admin', 'admin']);
        $auditModel = new AuditTrail();
        $logs = $auditModel->getLogs();
        $response->json(['success' => true, 'data' => $logs]);
    }
}
