<?php
namespace App\Models;

use App\Core\Model;

class Supplier extends Model {
    protected string $table = 'suppliers';

    public function create(array $data): bool {
        $sql = "INSERT INTO {$this->table} (supplier_code, supplier_name, contact_person, mobile, email, gst_number, address, status) 
                VALUES (:supplier_code, :supplier_name, :contact_person, :mobile, :email, :gst_number, :address, :status)";
        $stmt = $this->query($sql, [
            'supplier_code' => $data['supplier_code'],
            'supplier_name' => $data['supplier_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->table} SET 
                supplier_name = :supplier_name,
                contact_person = :contact_person,
                mobile = :mobile,
                email = :email,
                gst_number = :gst_number,
                address = :address,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->query($sql, [
            'supplier_name' => $data['supplier_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }
}
