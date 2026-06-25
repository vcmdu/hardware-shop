<?php
namespace App\Models;

use App\Core\Model;

class Category extends Model {
    protected string $table = 'categories';

    public function create(string $name, ?string $description, string $status = 'active'): bool {
        $sql = "INSERT INTO {$this->table} (name, description, status) VALUES (:name, :description, :status)";
        $stmt = $this->query($sql, [
            'name' => $name,
            'description' => $description,
            'status' => $status
        ]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, string $name, ?string $description, string $status): bool {
        $sql = "UPDATE {$this->table} SET name = :name, description = :description, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->query($sql, [
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }
}
