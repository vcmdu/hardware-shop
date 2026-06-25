<?php
namespace App\Models;

use App\Core\Model;

class AuditTrail extends Model {
    protected string $table = 'audit_trails';

    public function getLogs(): array {
        return $this->fetchAll("
            SELECT a.*, u.username, u.role
            FROM audit_trails a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.id DESC
        ");
    }
}
