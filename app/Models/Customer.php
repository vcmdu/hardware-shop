<?php
namespace App\Models;

use App\Core\Model;

class Customer extends Model {
    protected string $table = 'customers';

    public function create(array $data): bool {
        $sql = "INSERT INTO {$this->table} (customer_code, name, mobile, email, address, gst_number, credit_limit, outstanding_balance) 
                VALUES (:customer_code, :name, :mobile, :email, :address, :gst_number, :credit_limit, :outstanding_balance)";
        $stmt = $this->query($sql, [
            'customer_code' => $data['customer_code'],
            'name' => $data['name'],
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0.00,
            'outstanding_balance' => $data['outstanding_balance'] ?? 0.00
        ]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->table} SET 
                name = :name,
                mobile = :mobile,
                email = :email,
                address = :address,
                gst_number = :gst_number,
                credit_limit = :credit_limit,
                outstanding_balance = :outstanding_balance,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->query($sql, [
            'name' => $data['name'],
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'credit_limit' => $data['credit_limit'],
            'outstanding_balance' => $data['outstanding_balance'],
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function adjustBalance(int $id, float $amount): bool {
        $sql = "UPDATE {$this->table} SET outstanding_balance = outstanding_balance + :amount, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->query($sql, ['amount' => $amount, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
