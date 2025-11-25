<?php

declare(strict_types=1);

namespace NixPHP\ORM\Repository;

use NixPHP\ORM\Core\EntityInterface;
use PDO;
use function NixPHP\Database\database;
use function NixPHP\ORM\em;

abstract class AbstractRepository
{
    protected ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? database();

        if (null === $this->pdo) {
            throw new \RuntimeException('No database connection available.');
        }
    }

    abstract protected function getEntityClass(): string;

    protected function getEntity(): EntityInterface
    {
        $class = $this->getEntityClass();
        return new $class();
    }

    protected function getTable(bool $singular = false): string
    {
        $entity = $this->getEntity();

        $table = $entity->table
            ?? strtolower(basename(str_replace('\\', '/', $this->getEntityClass()))) . 's';

        if ($singular && str_ends_with($table, 's')) {
            return substr($table, 0, -1); // naive Singularform
        }

        return $table;
    }

    protected function getPivotTable(string $relatedClass, string $selfTable, string $relatedTable): string
    {
        $entity = $this->getEntity();

        if (!empty($entity->pivotTables[$relatedClass])) {
            return $entity->pivotTables[$relatedClass];
        }

        $relatedEntity = new $relatedClass();
        if (!empty($relatedEntity->pivotTables[$this->getEntityClass()])) {
            return $relatedEntity->pivotTables[$this->getEntityClass()];
        }

        $items = [$selfTable, $relatedTable];
        sort($items);
        return implode('_', $items);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->getTable()}";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return $this->hydrateMany($rows);
    }

    public function findBy(
        array|string $criteria,
        mixed $value = null,
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $sql = "SELECT * FROM {$this->getTable()} WHERE 1=1";
        $params = [];

        if (is_string($criteria)) {
            $criteria = [$criteria => $value];
        }

        foreach ($criteria as $field => $val) {
            $sql .= " AND {$field} = :{$field}";
            $params[":{$field}"] = $val;
        }

        if (!empty($orderBy)) {
            $parts = [];
            foreach ($orderBy as $field => $dir) {
                $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                $parts[] = "{$field} {$dir}";
            }
            $sql .= " ORDER BY " . implode(', ', $parts);
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findOneBy(
        array|string $criteria,
        mixed $value = null,
        array $orderBy = []
    ): ?EntityInterface {
        $results = $this->findBy($criteria, $value, $orderBy, limit: 1);
        return $results[0] ?? null;
    }

    public function findByPivot(string $pivotWithClass, int $pivotId): array
    {
        $thisTable     = $this->getTable(true);
        $relatedTable  = strtolower(basename(str_replace('\\', '/', $pivotWithClass)));

        $pivotTable    = $this->getPivotTable($pivotWithClass, $thisTable, $relatedTable);

        $colSelf       = $thisTable . '_id';
        $colPivot      = $relatedTable . '_id';

        $sql = "SELECT {$thisTable}s.* FROM {$thisTable}s
            INNER JOIN {$pivotTable} ON {$pivotTable}.{$colSelf} = {$thisTable}s.id
            WHERE {$pivotTable}.{$colPivot} = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $pivotId]);

        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findOrCreateBy(string $field, mixed $value): EntityInterface
    {
        return $this->findOneBy($field, $value) ?? $this->create($field, $value);
    }

    public function findOrCreateManyBy(string $field, array $values): array
    {
        if (empty($values)) return [];

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$field} IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $rows = $stmt->fetchAll();

        $existing = [];
        foreach ($rows as $row) {
            $key = $row[$field];
            $existing[$key] = $this->hydrate($row);
        }

        $missing = array_diff($values, array_keys($existing));

        foreach ($missing as $value) {
            $entity = $this->create($field, $value);
            $existing[$value] = $entity;
        }

        return array_map(fn($val) => $existing[$val], $values);
    }

    protected function create(string $field, mixed $value): EntityInterface
    {
        $class = $this->getEntityClass();
        $entity = new $class([$field => $value]);
        em()->save($entity);
        return $entity;
    }

    protected function hydrate(array $row): EntityInterface
    {
        $class = $this->getEntityClass();
        return new $class($row);
    }

    protected function hydrateMany(array $rows): array
    {
        return array_map(fn($row) => $this->hydrate($row), $rows);
    }
}
