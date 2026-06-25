<?php
namespace App\Models;

use App\Core\Model;

class Setting extends Model {
    protected string $table = 'settings';

    public function getAll(): array {
        $res = $this->fetchAll("SELECT * FROM {$this->table} ORDER BY key ASC");
        $settings = [];
        foreach ($res as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public function updateSettings(array $data): bool {
        $sql = "UPDATE {$this->table} SET value = :value, updated_at = CURRENT_TIMESTAMP WHERE key = :key";
        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->execute([
                'value' => (string)$value,
                'key' => $key
            ]);
        }
        return true;
    }
}
