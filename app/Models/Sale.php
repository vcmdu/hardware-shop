<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class Sale extends Model {
    protected string $table = 'sales';

    public function allWithCustomer(): array {
        return $this->fetchAll("
            SELECT s.*, c.name as customer_name, c.customer_code 
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            ORDER BY s.id DESC
        ");
    }

    public function findWithCustomer(int $id): array|false {
        return $this->fetchOne("
            SELECT s.*, c.name as customer_name, c.customer_code, c.mobile, c.email, c.gst_number, c.address
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = :id
        ", ['id' => $id]);
    }

    public function getItems(int $saleId): array {
        return $this->fetchAll("
            SELECT si.*, pr.product_name, pr.product_code, pr.unit
            FROM sale_items si
            LEFT JOIN products pr ON si.product_id = pr.id
            WHERE si.sale_id = :sale_id
        ", ['sale_id' => $saleId]);
    }

    public function createSale(array $saleData, array $items): int {
        try {
            $this->db->beginTransaction();

            // Insert sale
            $sql = "INSERT INTO sales (invoice_number, customer_id, date, discount, gst_total, grand_total, payment_method, payment_status, paid_amount) 
                    VALUES (:invoice_number, :customer_id, :date, :discount, :gst_total, :grand_total, :payment_method, :payment_status, :paid_amount)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'invoice_number' => $saleData['invoice_number'],
                'customer_id' => $saleData['customer_id'],
                'date' => $saleData['date'],
                'discount' => $saleData['discount'] ?? 0.00,
                'gst_total' => $saleData['gst_total'] ?? 0.00,
                'grand_total' => $saleData['grand_total'] ?? 0.00,
                'payment_method' => $saleData['payment_method'] ?? 'cash',
                'payment_status' => $saleData['payment_status'] ?? 'paid',
                'paid_amount' => $saleData['paid_amount'] ?? 0.00
            ]);

            $saleId = (int)$this->db->lastInsertId();

            // Insert sale items
            $itemSql = "INSERT INTO sale_items (sale_id, product_id, quantity, price, gst_percentage, discount, total) 
                        VALUES (:sale_id, :product_id, :quantity, :price, :gst_percentage, :discount, :total)";
            $itemStmt = $this->db->prepare($itemSql);

            // Prepare stock deduction & ledger statements
            $stockSql = "UPDATE products SET current_stock = current_stock - :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :product_id RETURNING current_stock";
            $stockStmt = $this->db->prepare($stockSql);

            $ledgerSql = "INSERT INTO stock_ledger (product_id, transaction_type, reference_id, quantity, balance_after) VALUES (:product_id, 'sale', :sale_id, :quantity, :balance_after)";
            $ledgerStmt = $this->db->prepare($ledgerSql);

            foreach ($items as $item) {
                $itemStmt->execute([
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'gst_percentage' => $item['gst_percentage'],
                    'discount' => $item['discount'] ?? 0.00,
                    'total' => $item['total']
                ]);

                // Deduct stock
                $stockStmt->execute([
                    'qty' => $item['quantity'],
                    'product_id' => $item['product_id']
                ]);
                $newStock = $stockStmt->fetchColumn();

                // Record in stock ledger
                $ledgerStmt->execute([
                    'product_id' => $item['product_id'],
                    'sale_id' => $saleId,
                    'quantity' => -$item['quantity'],
                    'balance_after' => $newStock
                ]);
            }

            // Update customer outstanding balance if not fully paid
            $unpaid = ($saleData['grand_total'] ?? 0) - ($saleData['paid_amount'] ?? 0);
            if ($unpaid > 0) {
                $custSql = "UPDATE customers SET outstanding_balance = outstanding_balance + :unpaid, updated_at = CURRENT_TIMESTAMP WHERE id = :customer_id";
                $custStmt = $this->db->prepare($custSql);
                $custStmt->execute([
                    'unpaid' => $unpaid,
                    'customer_id' => $saleData['customer_id']
                ]);
            }

            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getTodaySales(): float {
        $res = $this->fetchOne("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE date = CURRENT_DATE");
        return $res ? (float)$res['total'] : 0.00;
    }

    public function getMonthlySales(): float {
        $res = $this->fetchOne("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE date >= DATE_TRUNC('month', CURRENT_DATE)::DATE");
        return $res ? (float)$res['total'] : 0.00;
    }

    public function getTopSelling(): array {
        return $this->fetchAll("
            SELECT p.product_name, p.product_code, SUM(si.quantity) as total_qty, SUM(si.total) as total_revenue
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            GROUP BY p.id, p.product_name, p.product_code
            ORDER BY total_qty DESC
            LIMIT 5
        ");
    }

    public function getRecentSales(): array {
        return $this->fetchAll("
            SELECT s.*, c.name as customer_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            ORDER BY s.id DESC
            LIMIT 5
        ");
    }
}
