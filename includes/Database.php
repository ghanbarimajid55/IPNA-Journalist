<?php

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(string $host, string $dbname, string $username, string $password, string $charset = 'utf8mb4')
    {
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $configPath = BASE_PATH . '/config/database.php';
            if (!file_exists($configPath)) {
                throw new RuntimeException('Database configuration not found. Please run the installer.');
            }

            $config = require $configPath;

            if (!isset($config['host'], $config['dbname'], $config['username'], $config['password'])) {
                throw new RuntimeException('Incomplete database configuration. Please run the installer.');
            }

            self::$instance = new self(
                $config['host'],
                $config['dbname'],
                $config['username'],
                $config['password'],
                $config['charset'] ?? 'utf8mb4'
            );
        }

        return self::$instance;
    }

    public static function init(string $host, string $db, string $user, string $pass): self
    {
        self::$instance = new self($host, $db, $user, $pass);
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function prepare(string $statement): PDOStatement
    {
        return $this->pdo->prepare($statement);
    }

    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->execute($sql, array_merge(array_values($data), $whereParams));

        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $params);

        return $stmt->rowCount();
    }

    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$where}";
        $result = $this->fetch($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }
}
