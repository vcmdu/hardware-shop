<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected string $table = 'users';

    public function findByUsername(string $username): array|false {
        return $this->fetchOne("SELECT * FROM {$this->table} WHERE username = :username", ['username' => $username]);
    }

    public function create(string $username, string $password, string $role, string $status = 'active'): bool {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO {$this->table} (username, password, role, status) VALUES (:username, :password, :role, :status)";
        $stmt = $this->query($sql, [
            'username' => $username,
            'password' => $hash,
            'role' => $role,
            'status' => $status
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE {$this->table} SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->query($sql, [
            'password' => $hash,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $id, string $status): bool {
        $sql = "UPDATE {$this->table} SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->query($sql, [
            'status' => $status,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }
}
