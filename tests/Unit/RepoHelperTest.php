<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Fixtures\DummyRepository;
use Tests\NixPHPTestCase;
use function NixPHP\ORM\repo;

class RepoHelperTest extends NixPHPTestCase
{
    public function testRepoReturnsRepositoryFromFactory(): void
    {
        $repository = repo(DummyRepository::class);
        $this->assertInstanceOf(DummyRepository::class, $repository);
    }

    public function testRepoReturnsSameInstance(): void
    {
        $first = repo(DummyRepository::class);
        $second = repo(DummyRepository::class);
        $this->assertSame($first, $second);
    }
}
