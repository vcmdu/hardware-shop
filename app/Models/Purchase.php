<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class Purchase extends Model {
    protected string $table = 'purchases';

    public function allWithSupplier(): array {
        return $this->fetchAll("
            SELECT p.*, s.supplier_name, s.supplier_code 
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            ORDER BY p.id DESC
        ");
    }

    public function findWithSupplier(int $id): array|false {
        return $this->fetchOne("
            SELECT p.*, s.supplier_name, s.supplier_code, s.contact_person, s.mobile, s.email, s.gst_number, s.address
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.id = :id
        ", ['id' => $id]);
    }

    public function getItems(int $purchaseId): array {
        return $this->fetchAll("
            SELECT pi.*, pr.product_name, pr.product_code, pr.unit
            FROM purchase_items pi
            LEFT JOIN products pr ON pi.product_id = pr.id
            WHERE pi.purchase_id = :purchase_id
        ", ['purchase_id' => $purchaseId]);
    }

    public function createPurchase(array $purchaseData, array $items): int {
        try {
            $this->db->beginTransaction();

            // Insert purchase
            $sql = "INSERT INTO purchases (purchase_number, supplier_id, date, discount, gst_total, grand_total, status) 
                    VALUES (:purchase_number, :supplier_id, :date, :discount, :gst_total, :grand_total, :status)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'purchase_number' => $purchaseData['purchase_number'],
                'supplier_id' => $purchaseData['supplier_id'],
                'date' => $purchaseData['date'],
                'discount' => $purchaseData['discount'] ?? 0.00,
                'gst_total' => $purchaseData['gst_total'] ?? 0.00,
                'grand_total' => $purchaseData['grand_total'] ?? 0.00,
                'status' => $purchaseData['status'] ?? 'pending'
            ]);

            $purchaseId = (int)$this->db->lastInsertId();

            // Insert purchase items
            $itemSql = "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, gst_percentage, discount, total) 
                        VALUES (:purchase_id, :product_id, :quantity, :unit_cost, :gst_percentage, :discount, :total)";
            $itemStmt = $this->db->prepare($itemSql);

            foreach ($items as $item) {
                $itemStmt->execute([
                    'purchase_id' => $purchaseId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'gst_percentage' => $item['gst_percentage'],
                    'discount' => $item['discount'] ?? 0.00,
                    'total' => $item['total']
                ]);
            }

            $this->db->commit();
            return $purchaseId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): bool {
        // This will update the status, triggering the stock trigger!
        $sql = "UPDATE purchases SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->query($sql, ['status' => $status, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
