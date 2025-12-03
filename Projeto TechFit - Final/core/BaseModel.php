<?php
require_once __DIR__ . '/../config/database.php';

abstract class BaseModel {
    protected $table;
    protected $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT * FROM `{$this->table}`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find($id) {
        if (!is_numeric($id)) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data) {
        if (empty($data)) {
            return false;
        }
        $cols = array_keys($data);
        $placeholders = ':' . implode(', :', $cols);
        $colsStr = '`' . implode('`, `', $cols) . '`';
        $sql = "INSERT INTO `{$this->table}` ({$colsStr}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data) ? $this->pdo->lastInsertId() : false;
    }

    public function update($id, array $data) {
        if (empty($data) || !is_numeric($id)) {
            return false;
        }
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = "`{$col}` = :{$col}";
        }
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set) . " WHERE id = :id";
        $data['id'] = (int)$id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id) {
        if (!is_numeric($id)) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }
}
