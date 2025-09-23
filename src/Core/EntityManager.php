<?php

declare(strict_types=1);

namespace NixPHP\ORM\Core;

use Exception;
use PDO;

class EntityManager
{
    protected array $stored = [];

    public function __construct(
        protected PDO $pdo
    ) {}

    public function save(EntityInterface $root): void
    {
        $this->pdo->beginTransaction();

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

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

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

    protected function isOneToMany(EntityInterface $parent, EntityInterface $child): bool
    {
        $fk = $parent->getTableName(true) . '_id';
        $ref = new \ReflectionClass($child);
        return $ref->hasProperty($fk);
    }

    protected function injectForeignKey(EntityInterface $child, EntityInterface $parent): void
    {
        $fk = $parent->getTableName(true) . '_id';
        if (!$parent->getId()) {
            throw new \RuntimeException("Cannot inject foreign key: parent entity has no ID.");
        }
        $child->{$fk} = $parent->getId();
    }

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

    protected function isStored(EntityInterface $entity): bool
    {
        return isset($this->stored[spl_object_hash($entity)]);
    }

    protected function markStored(EntityInterface $entity): void
    {
        $this->stored[spl_object_hash($entity)] = true;
    }
}