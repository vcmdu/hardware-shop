<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class Inventory extends Model {
    protected string $table = 'inventory_adjustments';

    public function getLedger(): array {
        return $this->fetchAll("
            SELECT sl.*, pr.product_name, pr.product_code, pr.unit, 
                   CASE 
                     WHEN sl.transaction_type = 'purchase' THEN 'PO #' || COALESCE((SELECT purchase_number FROM purchases WHERE id = sl.reference_id), sl.reference_id::text)
                     WHEN sl.transaction_type = 'purchase_return' THEN 'Purchase Return #' || sl.reference_id
                     WHEN sl.transaction_type = 'sale' THEN 'Invoice #' || COALESCE((SELECT invoice_number FROM sales WHERE id = sl.reference_id), sl.reference_id::text)
                     ELSE 'Adjustment Ref #' || COALESCE((SELECT reference_number FROM inventory_adjustments WHERE id = sl.reference_id), sl.reference_id::text)
                   END as ref_desc
            FROM stock_ledger sl
            LEFT JOIN products pr ON sl.product_id = pr.id
            ORDER BY sl.id DESC
        ");
    }

    public function allAdjustments(): array {
        return $this->fetchAll("
            SELECT ia.*, u.username as creator_name 
            FROM inventory_adjustments ia
            LEFT JOIN users u ON ia.created_by = u.id
            ORDER BY ia.id DESC
        ");
    }

    public function getAdjustmentItems(int $adjustmentId): array {
        return $this->fetchAll("
            SELECT iai.*, pr.product_name, pr.product_code, pr.unit
            FROM inventory_adjustment_items iai
            LEFT JOIN products pr ON iai.product_id = pr.id
            WHERE iai.adjustment_id = :adjustment_id
        ", ['adjustment_id' => $adjustmentId]);
    }

    public function createAdjustment(array $adjData, array $items): int {
        try {
            $this->db->beginTransaction();

            // Insert adjustment header
            $sql = "INSERT INTO inventory_adjustments (reference_number, type, description, date, created_by) 
                    VALUES (:reference_number, :type, :description, :date, :created_by)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'reference_number' => $adjData['reference_number'],
                'type' => $adjData['type'],
                'description' => $adjData['description'] ?? null,
                'date' => $adjData['date'],
                'created_by' => $adjData['created_by']
            ]);

            $adjId = (int)$this->db->lastInsertId();

            // Insert adjustment items
            $itemSql = "INSERT INTO inventory_adjustment_items (adjustment_id, product_id, quantity_before, quantity_after, quantity_adjusted, reason) 
                        VALUES (:adjustment_id, :product_id, :quantity_before, :quantity_after, :quantity_adjusted, :reason)";
            $itemStmt = $this->db->prepare($itemSql);

            foreach ($items as $item) {
                $itemStmt->execute([
                    'adjustment_id' => $adjId,
                    'product_id' => $item['product_id'],
                    'quantity_before' => $item['quantity_before'],
                    'quantity_after' => $item['quantity_after'],
                    'quantity_adjusted' => $item['quantity_adjusted'],
                    'reason' => $item['reason'] ?? null
                ]);
            }

            $this->db->commit();
            return $adjId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
