<?php

namespace NixPHP\ORM;

use NixPHP\ORM\Core\EntityManager;
use NixPHP\ORM\Repository\AbstractRepository;
use NixPHP\ORM\Repository\RepositoryFactory;
use function NixPHP\app;

function em(): EntityManager
{
    return app()->container()->get(EntityManager::class);
}

/**
 * @template T of AbstractRepository
 * @param class-string<T> $repository
 *
 * @return T
 */
function repo(string $repository): AbstractRepository
{
    return app()->container()->get(RepositoryFactory::class)->create($repository);
}