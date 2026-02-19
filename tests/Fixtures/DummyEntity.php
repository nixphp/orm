<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use NixPHP\ORM\Core\EntityInterface;
use NixPHP\ORM\Core\EntityTrait;

class DummyEntity implements EntityInterface
{
    use EntityTrait;

    protected ?int $id = null;
    protected string $name = 'dummy';
}
