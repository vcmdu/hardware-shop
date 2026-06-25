<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Model;
use App\Models\Setting;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

class ReportController extends Controller
{
    private function getDb()
    {
        return \App\Core\Database::getConnection();
    }

    public function sales(Request $request, Response $response)
    {
        $this->requireAuth();
        $this->render('reports/sales', ['title' => 'Sales Reports']);
    }

    public function purchase(Request $request, Response $response)
    {
        $this->requireAuth();
        $this->render('reports/purchase', ['title' => 'Purchase Reports']);
    }

    public function inventory(Request $request, Response $response)
    {
        $this->requireAuth();
        $this->render('reports/inventory', ['title' => 'Inventory Reports']);
    }

    public function financial(Request $request, Response $response)
    {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $this->render('reports/financial', ['title' => 'Financial Reports']);
    }

    public function apiSales(Request $request, Response $response)
    {
        $this->requireAuth();
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));
        $groupBy = $request->get('group', 'daily');
        $db = $this->getDb();

        $dateTrunc = match ($groupBy) {
            'weekly' => "DATE_TRUNC('week', date)",
            'monthly' => "DATE_TRUNC('month', date)",
            'yearly' => "DATE_TRUNC('year', date)",
            default => 'date',
        };

        $stmt = $db->prepare("
            SELECT $dateTrunc as period,
                   COUNT(*) as invoice_count,
                   SUM(grand_total) as total_sales,
                   SUM(gst_total) as total_gst,
                   SUM(discount) as total_discount
            FROM sales
            WHERE date BETWEEN :from AND :to
            GROUP BY period ORDER BY period
        ");
        $stmt->execute(['from' => $from, 'to' => $to]);
        $response->json(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    public function apiPurchase(Request $request, Response $response)
    {
        $this->requireAuth();
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));
        $db = $this->getDb();
        $stmt = $db->prepare("
            SELECT p.purchase_number, p.date, p.status, p.grand_total, s.supplier_name
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.date BETWEEN :from AND :to
            ORDER BY p.date DESC
        ");
        $stmt->execute(['from' => $from, 'to' => $to]);
        $response->json(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    public function apiInventory(Request $request, Response $response)
    {
        $this->requireAuth();
        $type = $request->get('type', 'current');
        $db = $this->getDb();

        if ($type === 'valuation') {
            $data = $db->query("
                SELECT product_name, product_code, current_stock, purchase_price,
                       (current_stock * purchase_price) as stock_value,
                       selling_price, (current_stock * selling_price) as sale_value
                FROM products WHERE status = 'active' ORDER BY stock_value DESC
            ")->fetchAll();
        } elseif ($type === 'dead_stock') {
            $data = $db->query("
                SELECT p.*, COALESCE(SUM(si.quantity), 0) as sold_qty
                FROM products p
                LEFT JOIN sale_items si ON si.product_id = p.id
                LEFT JOIN sales s ON si.sale_id = s.id AND s.date >= CURRENT_DATE - INTERVAL '90 days'
                WHERE p.status = 'active'
                GROUP BY p.id HAVING COALESCE(SUM(si.quantity), 0) = 0
                ORDER BY p.product_name
            ")->fetchAll();
        } elseif ($type === 'fast_moving') {
            $data = $db->query("
                SELECT p.product_name, p.product_code, SUM(si.quantity) as sold_qty
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id AND s.date >= CURRENT_DATE - INTERVAL '30 days'
                GROUP BY p.id, p.product_name, p.product_code
                ORDER BY sold_qty DESC LIMIT 20
            ")->fetchAll();
        } else {
            $data = $db->query("
                SELECT p.*, c.name as category_name FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.product_name
            ")->fetchAll();
        }
        $response->json(['success' => true, 'data' => $data]);
    }

    public function apiFinancial(Request $request, Response $response)
    {
        $this->requireRoles(['super_admin', 'admin', 'manager']);
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));
        $db = $this->getDb();

        $salesStmt = $db->prepare("SELECT COALESCE(SUM(grand_total),0) as revenue, COALESCE(SUM(gst_total),0) as gst_collected FROM sales WHERE date BETWEEN :from AND :to");
        $salesStmt->execute(['from' => $from, 'to' => $to]);
        $sales = $salesStmt->fetch();

        $purchaseStmt = $db->prepare("SELECT COALESCE(SUM(grand_total),0) as cost FROM purchases WHERE status='approved' AND date BETWEEN :from AND :to");
        $purchaseStmt->execute(['from' => $from, 'to' => $to]);
        $purchases = $purchaseStmt->fetch();

        $profit = (float) $sales['revenue'] - (float) $purchases['cost'];

        $gstStmt = $db->prepare("
            SELECT EXTRACT(MONTH FROM date) as month_num,
                   SUM(gst_total) as gst_collected
            FROM sales WHERE date BETWEEN :from AND :to
            GROUP BY month_num ORDER BY month_num
        ");
        $gstStmt->execute(['from' => $from, 'to' => $to]);

        $response->json([
            'success' => true,
            'data' => [
                'revenue' => (float) $sales['revenue'],
                'cost' => (float) $purchases['cost'],
                'profit' => $profit,
                'gst_collected' => (float) $sales['gst_collected'],
                'gst_breakdown' => $gstStmt->fetchAll(),
            ]
        ]);
    }

    public function export(Request $request, Response $response)
    {
        $this->requireAuth();
        $type = $request->get('type', 'sales');
        $format = $request->get('format', 'excel');
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));

        // Reuse API data
        $fakeReq = clone $request;
        $db = $this->getDb();

        if ($format === 'excel') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            if ($type === 'sales') {
                $sheet->setTitle('Sales Report');
                $sheet->fromArray(['Period', 'Invoices', 'Total Sales', 'GST', 'Discount'], null, 'A1');
                $stmt = $db->prepare("SELECT date, COUNT(*) as c, SUM(grand_total) as t, SUM(gst_total) as g, SUM(discount) as d FROM sales WHERE date BETWEEN :from AND :to GROUP BY date ORDER BY date");
                $stmt->execute(['from' => $from, 'to' => $to]);
                $row = 2;
                foreach ($stmt->fetchAll() as $r) {
                    $sheet->fromArray([$r['date'], $r['c'], $r['t'], $r['g'], $r['d']], null, "A$row");
                    $row++;
                }
            } elseif ($type === 'inventory') {
                $sheet->setTitle('Inventory');
                $sheet->fromArray(['Code', 'Name', 'Category', 'Stock', 'Min Stock', 'Purchase Price', 'Selling Price', 'Stock Value'], null, 'A1');
                $data = $db->query("SELECT p.product_code, p.product_name, c.name, p.current_stock, p.minimum_stock, p.purchase_price, p.selling_price, (p.current_stock * p.purchase_price) as val FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.product_name")->fetchAll();
                $row = 2;
                foreach ($data as $r) {
                    $sheet->fromArray(array_values($r), null, "A$row");
                    $row++;
                }
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Ymd') . '.xlsx"');
            (new Xlsx($spreadsheet))->save('php://output');
            exit;
        }

        // PDF export
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle(ucfirst($type) . ' Report');
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, ucfirst($type) . ' Report (' . $from . ' to ' . $to . ')', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $html = '<table border="1" cellpadding="3"><tr style="background:#003566;color:white;"><th>Date</th><th>Invoices</th><th>Sales Total</th><th>GST</th><th>Discount</th></tr>';
        $stmt = $db->prepare("SELECT date, COUNT(*) as c, SUM(grand_total) as t, SUM(gst_total) as g, SUM(discount) as d FROM sales WHERE date BETWEEN :from AND :to GROUP BY date ORDER BY date");
        $stmt->execute(['from' => $from, 'to' => $to]);
        foreach ($stmt->fetchAll() as $r) {
            $html .= "<tr><td>{$r['date']}</td><td>{$r['c']}</td><td>₹{$r['t']}</td><td>₹{$r['g']}</td><td>₹{$r['d']}</td></tr>";
        }
        $html .= '</table>';
        $pdf->writeHTML($html);
        $pdf->Output('report_' . $type . '_' . date('Ymd') . '.pdf', 'D');
        exit;
    }
}
