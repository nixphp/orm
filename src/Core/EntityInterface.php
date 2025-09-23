<?php

declare(strict_types=1);

namespace NixPHP\ORM\Core;

interface EntityInterface
{
    public function getPrimaryKey(): string;
    public function getFields(): array;
    public function getRelations(): array;
    public function getId(): int|string|null;
    public function setId(int|string $id): void;
    public function getTableName(bool $singular = false): string;
}