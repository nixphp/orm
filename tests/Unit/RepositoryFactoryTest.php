<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use NixPHP\ORM\Repository\RepositoryFactory;
use Tests\Fixtures\DummyRepository;
use Tests\NixPHPTestCase;
use function NixPHP\app;

class RepositoryFactoryTest extends NixPHPTestCase
{
    private RepositoryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = app()->container()->get(RepositoryFactory::class);
    }

    public function testCreateReturnsRepository(): void
    {
        $repository = $this->factory->create(DummyRepository::class);
        $this->assertInstanceOf(DummyRepository::class, $repository);
    }

    public function testCreateCachesInstances(): void
    {
        $first = $this->factory->create(DummyRepository::class);
        $second = $this->factory->create(DummyRepository::class);
        $this->assertSame($first, $second);
    }

    public function testCreateRejectsNonRepositoryClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->create(\stdClass::class);
    }

    public function testCreateRejectsMissingClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->create('Tests\\Fixtures\\MissingRepository');
    }
}
