<?php

declare(strict_types=1);

namespace NixPHP\ORM\Model;

use NixPHP\ORM\Core\EntityInterface;
use NixPHP\ORM\Core\EntityTrait;

abstract class AbstractModel implements EntityInterface
{

    use EntityTrait;

    protected ?int $id;

    public function __construct(?array $data = [])
    {
        $this->id = $data['id'] ?? null;
        foreach ($data as $key => $value) {
            $ref = new \ReflectionClass($this);
            if ($ref->hasProperty($key)) {
                $this->$key = $value;
            }
        }
    }

}