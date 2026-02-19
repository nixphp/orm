<?php

declare(strict_types=1);

namespace NixPHP\ORM\Repository;

use PDO;
use InvalidArgumentException;
use function NixPHP\app;

class RepositoryFactory
{
    /**
     * @var array<class-string<AbstractRepository>, AbstractRepository>
     */
    protected array $instances = [];

    public function __construct(
        protected PDO $pdo
    ) {}

    /**
     * @template T of AbstractRepository
     * @param class-string<T> $repository
     *
     * @return T
     */
    public function create(string $repository): AbstractRepository
    {
        if (!class_exists($repository)) {
            throw new InvalidArgumentException("Repository {$repository} does not exist.");
        }

        if (!is_subclass_of($repository, AbstractRepository::class)) {
            throw new InvalidArgumentException("{$repository} must extend " . AbstractRepository::class);
        }

        return $this->instances[$repository] ??= app()->container()->make($repository, [$this->pdo]);
    }
}
