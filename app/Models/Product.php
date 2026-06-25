<?php
namespace App\Models;

use App\Core\Model;

class Product extends Model {
    protected string $table = 'products';

    public function allWithCategory(): array {
        return $this->fetchAll("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.id DESC
        ");
    }

    public function findWithCategory(int $id): array|false {
        return $this->fetchOne("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id
        ", ['id' => $id]);
    }

    public function create(array $data): bool {
        $sql = "INSERT INTO {$this->table} 
            (product_code, barcode, product_name, category_id, brand, unit, purchase_price, selling_price, gst_percentage, current_stock, minimum_stock, rack_location, image_path, status) 
            VALUES 
            (:product_code, :barcode, :product_name, :category_id, :brand, :unit, :purchase_price, :selling_price, :gst_percentage, :current_stock, :minimum_stock, :rack_location, :image_path, :status)";
        
        $stmt = $this->query($sql, [
            'product_code' => $data['product_code'],
            'barcode' => $data['barcode'],
            'product_name' => $data['product_name'],
            'category_id' => $data['category_id'],
            'brand' => $data['brand'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'purchase_price' => $data['purchase_price'],
            'selling_price' => $data['selling_price'],
            'gst_percentage' => $data['gst_percentage'] ?? 18.00,
            'current_stock' => $data['current_stock'] ?? 0,
            'minimum_stock' => $data['minimum_stock'] ?? 5,
            'rack_location' => $data['rack_location'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);

        return $stmt->rowCount() > 0;
    }

    public function update(int $id, array $data): bool {
        $fields = [
            'product_name = :product_name',
            'category_id = :category_id',
            'brand = :brand',
            'unit = :unit',
            'purchase_price = :purchase_price',
            'selling_price = :selling_price',
            'gst_percentage = :gst_percentage',
            'minimum_stock = :minimum_stock',
            'rack_location = :rack_location',
            'status = :status',
            'updated_at = CURRENT_TIMESTAMP'
        ];
        
        $params = [
            'product_name' => $data['product_name'],
            'category_id' => $data['category_id'],
            'brand' => $data['brand'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'purchase_price' => $data['purchase_price'],
            'selling_price' => $data['selling_price'],
            'gst_percentage' => $data['gst_percentage'],
            'minimum_stock' => $data['minimum_stock'],
            'rack_location' => $data['rack_location'] ?? null,
            'status' => $data['status'],
            'id' => $id
        ];

        // Only update code/barcode if unique checks pass
        if (isset($data['product_code'])) {
            $fields[] = 'product_code = :product_code';
            $params['product_code'] = $data['product_code'];
        }
        if (isset($data['barcode'])) {
            $fields[] = 'barcode = :barcode';
            $params['barcode'] = $data['barcode'];
        }
        if (isset($data['image_path'])) {
            $fields[] = 'image_path = :image_path';
            $params['image_path'] = $data['image_path'];
        }
        if (isset($data['current_stock'])) {
            $fields[] = 'current_stock = :current_stock';
            $params['current_stock'] = $data['current_stock'];
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function search(string $q): array {
        $sql = "
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE (p.product_name ILIKE :q 
               OR p.product_code ILIKE :q 
               OR p.barcode = :exact_q)
              AND p.status = 'active'
            ORDER BY p.product_name ASC
            LIMIT 15
        ";
        return $this->fetchAll($sql, [
            'q' => '%' . $q . '%',
            'exact_q' => $q
        ]);
    }

    public function getLowStock(): array {
        return $this->fetchAll("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.current_stock <= p.minimum_stock AND p.current_stock > 0 AND p.status = 'active'
            ORDER BY p.current_stock ASC
        ");
    }

    public function getOutOfStock(): array {
        return $this->fetchAll("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.current_stock <= 0 AND p.status = 'active'
            ORDER BY p.product_name ASC
        ");
    }
}
