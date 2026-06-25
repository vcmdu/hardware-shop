<?php
namespace App\Core;

use PDO;

class Model {
    protected PDO $db;
    protected string $table = '';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function query(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    public function all(): array {
        return $this->fetchAll("SELECT * FROM {$this->table} ORDER BY id DESC");
    }

    public function find(int $id): array|false {
        return $this->fetchOne("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->query("DELETE FROM {$this->table} WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
