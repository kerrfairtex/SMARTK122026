<?php
/**
 * BBNIHS DataAccess
 *
 * Real BBNIHS-owned data-access boundary. NOT a clone of database.inc.php.
 *
 * Source-grounded facts:
 *   - pg_connect with search_path=kerrfairtex,public, sslmode=require
 *     database.inc.php:59-87
 *   - db_start / db_query / DBGet / DBGetOne / DBQuery / DBInsert / DBUpdate
 *     database.inc.php:25-185
 *   - Transaction primitives: db_trans_start/commit/rollback
 *     database.inc.php:422-498
 *   - Error display: db_show_error -> ErrorMessage
 *     database.inc.php:91-100
 *
 * BBNIHS does NOT use the legacy global $DatabaseType / $db_connection / $DatabaseServer
 * pattern. It uses an injected Connection and a typed Statement layer.
 *
 * Phase 1 build — additive. The runtime database is NOT contacted in
 * Phase 1; this is a contract-only class. Real connection arrives in a
 * later phase once DB access is authorized.
 */

declare(strict_types=1);

namespace BBNIHS\DataAccess;

use BBNIHS\Error\ErrorHandler;
use BBNIHS\Config\Config;

final class DataAccess
{
    private ?\PDO $pdo = null;
    private bool $inTransaction = false;

    public function __construct(
        private readonly Config $config,
        private readonly ErrorHandler $errors,
    ) {}

    public function connect(): \PDO
    {
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }
        $db = $this->config->get('database');
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $db['host'], $db['port'], $db['name'], $db['sslmode']
        );
        $pdo = new \PDO($dsn, $db['user'], $db['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // search_path lives in the connection string options on libpq.
        // Source: database.inc.php:80  --search_path=kerrfairtex,public
        $pdo->exec("SET search_path TO " . $db['search_path']);
        $this->pdo = $pdo;
        return $pdo;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
        $this->inTransaction = false;
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function insert(string $table, array $columns): int
    {
        $cols = array_keys($columns);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
            $this->ident($table),
            implode(',', array_map([$this, 'ident'], $cols)),
            implode(',', $placeholders)
        );
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($columns);
        $row = $stmt->fetch();
        return (int)($row['id'] ?? 0);
    }

    public function update(string $table, array $columns, array $where): int
    {
        $set = [];
        $params = [];
        foreach ($columns as $col => $val) {
            $set[] = $this->ident($col) . ' = :set_' . $col;
            $params['set_' . $col] = $val;
        }
        $whereSql = [];
        foreach ($where as $col => $val) {
            $whereSql[] = $this->ident($col) . ' = :wh_' . $col;
            $params['wh_' . $col] = $val;
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->ident($table),
            implode(',', $set),
            implode(' AND ', $whereSql)
        );
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $whereSql = [];
        $params = [];
        foreach ($where as $col => $val) {
            $whereSql[] = $this->ident($col) . ' = :wh_' . $col;
            $params['wh_' . $col] = $val;
        }
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->ident($table), implode(' AND ', $whereSql));
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function begin(): void
    {
        if ($this->inTransaction) { return; }
        $this->connect()->beginTransaction();
        $this->inTransaction = true;
    }

    public function commit(): void
    {
        if (!$this->inTransaction) { return; }
        $this->pdo->commit();
        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        if (!$this->inTransaction) { return; }
        $this->pdo->rollBack();
        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * Identifier quoting (Postgres).
     * Source: rosariosis.sql — table/column names use lowercase + underscore.
     */
    private function ident(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: $name");
        }
        return '"' . $name . '"';
    }
}
