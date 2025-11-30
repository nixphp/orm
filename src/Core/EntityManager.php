<?php

declare(strict_types=1);

namespace NixPHP\ORM\Core;

use Exception;
use PDO;

class EntityManager
{
    protected array $stored = [];

    private int $transactionLevel = 0;

    /**
     * @param PDO $pdo
     */
    public function __construct(
        protected PDO $pdo
    ) {}

    /**
     * @return void
     */
    public function begin(): void
    {
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
        }

        $this->transactionLevel++;
    }

    /**
     * @return void
     */
    public function commit(): void
    {
        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->pdo->commit();
        } else {
            $this->pdo->exec("RELEASE SAVEPOINT LEVEL{$this->transactionLevel}");
        }
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->pdo->rollBack();
        } else {
            $this->pdo->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionLevel}");
        }
    }

    /**
     * @param EntityInterface $root
     *
     * @return void
     * @throws Exception
     */
    public function save(EntityInterface $root): void
    {
        $this->begin();

        try {
            $entities = $this->collectEntities($root);

            // 1. Eltern speichern (ohne Relationen)
            foreach ($entities as $entity) {
                if (!$this->isStored($entity)) {
                    $this->upsert($entity);
                    $this->markStored($entity);
                }
            }

            // 2. Relationen auflösen
            foreach ($entities as $entity) {
                foreach ($entity->getRelations() as $related) {
                    $relatedItems = is_array($related) ? $related : [$related];

                    foreach ($relatedItems as $relatedEntity) {
                        if (!$relatedEntity instanceof EntityInterface) continue;

                        if ($this->isOneToMany($entity, $relatedEntity)) {
                            $this->injectForeignKey($relatedEntity, $entity);
                            $this->upsert($relatedEntity);
                        } else {
                            $this->insertPivot($entity, $relatedEntity);
                        }
                    }
                }
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * @param EntityInterface $entity
     * @param array           $seen
     *
     * @return array|EntityInterface[]
     */
    protected function collectEntities(EntityInterface $entity, array &$seen = []): array
    {
        $id = spl_object_hash($entity);
        if (isset($seen[$id])) return [];

        $seen[$id] = $entity;
        $result = [$entity];

        foreach ($entity->getRelations() as $related) {
            $relatedItems = is_array($related) ? $related : [$related];

            foreach ($relatedItems as $rel) {
                if ($rel instanceof EntityInterface) {
                    $result = array_merge($result, $this->collectEntities($rel, $seen));
                }
            }
        }

        return $result;
    }

    /**
     * @param EntityInterface $entity
     *
     * @return void
     */
    protected function upsert(EntityInterface $entity): void
    {
        $table = $entity->getTableName();
        $fields = $entity->getFields();
        $primary = $entity->getPrimaryKey();
        $id = $entity->getId();

        if ($id === null) {
            $columns = implode(', ', array_keys($fields));
            $placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($fields)));
            $stmt = $this->pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($fields);
            $lastId = $this->pdo->lastInsertId();
            $entity->setId(is_numeric($lastId) ? (int) $lastId : $lastId);
        } else {
            $assignments = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
            $fields[$primary] = $id;
            $stmt = $this->pdo->prepare("UPDATE {$table} SET {$assignments} WHERE {$primary} = :{$primary}");
            $stmt->execute($fields);
        }
    }

    /**
     * @param EntityInterface $parent
     * @param EntityInterface $child
     *
     * @return bool
     */
    protected function isOneToMany(EntityInterface $parent, EntityInterface $child): bool
    {
        $fk = $parent->getTableName(true) . '_id';
        $ref = new \ReflectionClass($child);
        return $ref->hasProperty($fk);
    }

    /**
     * @param EntityInterface $child
     * @param EntityInterface $parent
     *
     * @return void
     */
    protected function injectForeignKey(EntityInterface $child, EntityInterface $parent): void
    {
        $fk = $parent->getTableName(true) . '_id';
        if (!$parent->getId()) {
            throw new \RuntimeException("Cannot inject foreign key: parent entity has no ID.");
        }
        $child->{$fk} = $parent->getId();
    }

    /**
     * @param EntityInterface $a
     * @param EntityInterface $b
     *
     * @return void
     */
    protected function insertPivot(EntityInterface $a, EntityInterface $b): void
    {
        $tables = [$a->getTableName(true), $b->getTableName(true)];
        sort($tables);
        $pivot = implode('_', $tables);

        $aCol = $a->getTableName(true) . '_id';
        $bCol = $b->getTableName(true) . '_id';

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO {$pivot} ({$aCol}, {$bCol}) VALUES (:a, :b)");
        $stmt->execute([
            ':a' => $a->getId(),
            ':b' => $b->getId(),
        ]);
    }

    /**
     * @param EntityInterface $entity
     *
     * @return bool
     */
    protected function isStored(EntityInterface $entity): bool
    {
        return isset($this->stored[spl_object_hash($entity)]);
    }

    /**
     * @param EntityInterface $entity
     *
     * @return void
     */
    protected function markStored(EntityInterface $entity): void
    {
        $this->stored[spl_object_hash($entity)] = true;
    }
}