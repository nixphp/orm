<?php

namespace NixPHP\ORM\Core;

use ReflectionClass;

trait EntityTrait
{
    public function getPrimaryKey(): string
    {
        return 'id';
    }

    public function getId(): int|string|null
    {
        $property = $this->getPrimaryKey();
        return $this->{$property} ?? null;
    }

    public function setId(int|string $id): void
    {
        $property = $this->getPrimaryKey();
        $this->{$property} = $id;
    }

    public function getTableName(bool $singular = false): string
    {
        $result = strtolower(substr(static::class, strrpos(static::class, '\\') + 1));
        if ($singular) {
            return $result;
        }
        return $result . 's';
    }

    public function getFields(): array
    {
        $reflection = new ReflectionClass($this);
        $fields = [];

        foreach ($reflection->getProperties() as $prop) {
            $name = $prop->getName();
            $prop->setAccessible(true);

            if ($name === $this->getPrimaryKey()) continue;

            $value = $prop->getValue($this);

            if (is_scalar($value) || $value === null) {
                $fields[$name] = $value;
            }
        }

        return $fields;
    }

    public function getRelations(): array
    {
        $reflection = new ReflectionClass($this);
        $relations = [];

        foreach ($reflection->getProperties() as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($this);

            if ($value instanceof EntityInterface && !$this->isAbstract($value)) {
                $relations[$prop->getName()] = $value;
            } elseif ($this->isArrayOfEntities($value)) {
                $relations[$prop->getName()] = $value;
            }
        }

        return $relations;
    }

    private function isArrayOfEntities(mixed $value): bool
    {
        if (!is_array($value) || empty($value)) return false;

        foreach ($value as $v) {
            if (!$v instanceof EntityInterface || $this->isAbstract($v)) {
                return false;
            }
        }

        return true;
    }

    private function isAbstract(EntityInterface $entity): bool
    {
        return (new \ReflectionClass($entity))->isAbstract();
    }
}

