<?php

namespace NixPHP\ORM\Repository;

use NixPHP\ORM\Core\EntityInterface;
use PDO;
use function NixPHP\Database\database;
use function NixPHP\ORM\em;

abstract class AbstractRepository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? database();
    }

    abstract protected function getEntityClass(): string;

    protected function getTable(bool $singular = false): string
    {
        return (new ($this->getEntityClass()))->getTableName($singular);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->getTable()}";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return $this->hydrateMany($rows);
    }

    public function findBy(string $field, mixed $value): array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$field} = :value";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['value' => $value]);
        $rows = $stmt->fetchAll();

        return $this->hydrateMany($rows);
    }

    public function findOneBy(string $field, mixed $value): ?EntityInterface
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$field} = :value LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByPivot(string $pivotWithClass, int $pivotId): array
    {
        $thisTable     = (new ($this->getEntityClass()))->getTableName(true);
        $pivotWith     = (new $pivotWithClass())->getTableName(true);
        $pivotTable    = $this->buildPivotTable($thisTable, $pivotWith);

        $colSelf       = $thisTable . '_id';
        $colPivot      = $pivotWith . '_id';

        $sql = "SELECT {$thisTable}s.* FROM {$thisTable}s
            INNER JOIN {$pivotTable} ON {$pivotTable}.{$colSelf} = {$thisTable}s.id
            WHERE {$pivotTable}.{$colPivot} = :id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $pivotId]);

    return $this->hydrateMany($stmt->fetchAll());
    }
    
    protected function buildPivotTable(string $a, string $b): string
    {
        $items = [$a, $b];
        sort($items); // In-place sorting alphabetically
        return implode('_', $items);
    }

    public function findOrCreateBy(string $field, mixed $value): EntityInterface
    {
        return $this->findOneBy($field, $value) ?? $this->create($field, $value);
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
