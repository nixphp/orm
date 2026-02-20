<?php

declare(strict_types=1);

namespace Tests\Integration;

use RuntimeException;
use Tests\Fixtures\Player;
use Tests\Fixtures\Team;
use Tests\NixPHPTestCase;
use function NixPHP\ORM\em;

class EntityManagerTest extends NixPHPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearFixtures();
    }

    public function testSavePersistsEntitiesAndPivot(): void
    {
        $player = new Player(['name' => 'Walker', 'age' => 28]);
        $team = new Team(['name' => 'Striders']);
        $player->addTeam($team);
        $team->addPlayer($player);

        em()->save($player);

        $this->assertNotNull($player->getId());
        $this->assertNotNull($team->getId());
        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM players')->fetchColumn());
        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM teams')->fetchColumn());
        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM player_team')->fetchColumn());
    }

    public function testSavingTwiceDoesNotDuplicatePivot(): void
    {
        $player = new Player(['name' => 'Walker', 'age' => 28]);
        $team = new Team(['name' => 'Striders']);
        $player->addTeam($team);
        $team->addPlayer($player);

        em()->save($player);

        em()->save($player);

        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM players')->fetchColumn());
        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM player_team')->fetchColumn());
        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM teams')->fetchColumn());
    }

    public function testClearThrowsWhenTransactionActive(): void
    {
        $this->expectException(RuntimeException::class);
        em()->begin();

        try {
            em()->clear();
        } finally {
            em()->rollback();
        }
    }
}
