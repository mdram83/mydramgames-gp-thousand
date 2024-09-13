<?php

namespace Tests\Extensions\Core\GamePhase;

use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayFirstCard;
use PHPUnit\Framework\TestCase;

class GamePhaseThousandCollectTricksTest extends TestCase
{
    private GamePhaseThousandCollectTricks $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->phase = new GamePhaseThousandCollectTricks();
    }

    public function testGetKey(): void
    {
        $this->assertEquals($this->phase::PHASE_KEY, $this->phase->getKey());
        $this->assertNotNull($this->phase->getKey());
        $this->assertNotEquals('', $this->phase->getKey());
    }

    public function testGetName(): void
    {
        $this->assertNotNull($this->phase->getName());
        $this->assertNotEquals('', $this->phase->getName());
    }

    public function testGetDescription(): void
    {
        $this->assertNotNull($this->phase->getDescription());
        $this->assertNotEquals('', $this->phase->getDescription());
    }

    public function testGetNextPhase(): void
    {
        $this->assertInstanceOf(GamePhaseThousandPlayFirstCard::class, $this->phase->getNextPhase(false));
        $this->assertInstanceOf(GamePhaseThousandCountPoints::class, $this->phase->getNextPhase(true));
    }
}
