<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use NixPHP\ORM\Repository\AbstractRepository;

class DummyRepository extends AbstractRepository
{
    protected function getEntityClass(): string
    {
        return DummyEntity::class;
    }
}
