<?php
// database.php - PDO connection wrapper
class Database {
    private $pdo;

    public function __construct($config) {
        if ($config['driver'] === 'sqlite') {
            $this->pdo = new PDO('sqlite:' . $config['sqlite']['path']);
        } else {
            $dsn = "mysql:host={$config['mysql']['host']};port={$config['mysql']['port']};dbname={$config['mysql']['database']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password']);
            $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function exec($sql) {
        return $this->pdo->exec($sql);
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return count($result) > 0 ? $result[0] : null;
    }

    public function insert($table, $data) {
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($cols) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
        $sql = "UPDATE $table SET $set WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_merge(array_values($data), $whereParams));
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($whereParams);
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function pdo() {
        return $this->pdo;
    }
}
