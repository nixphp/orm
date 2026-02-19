<?php

declare(strict_types=1);

namespace Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use function NixPHP\Database\database;

class NixPHPTestCase extends TestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$pdo = database();
        self::rebuildSchema();
    }

    protected static function rebuildSchema(): void
    {
        $pdo = self::$pdo;

        $pdo->exec('DROP TABLE IF EXISTS players');
        $pdo->exec('DROP TABLE IF EXISTS teams');
        $pdo->exec('DROP TABLE IF EXISTS player_team');

        $pdo->exec(
            'CREATE TABLE players (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                age INTEGER NOT NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE teams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE player_team (
                player_id INTEGER NOT NULL,
                team_id INTEGER NOT NULL,
                UNIQUE(player_id, team_id)
            )'
        );
    }

    protected function clearFixtures(): void
    {
        $pdo = self::$pdo;
        $pdo->exec('DELETE FROM player_team');
        $pdo->exec('DELETE FROM players');
        $pdo->exec('DELETE FROM teams');
    }
}