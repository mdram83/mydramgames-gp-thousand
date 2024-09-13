<?php

namespace Tests\Extensions\Core\GamePhase;

use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandStockDistribution;
use PHPUnit\Framework\TestCase;

class GamePhaseThousandBiddingTest extends TestCase
{
    private GamePhaseThousandBidding $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->phase = new GamePhaseThousandBidding();
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
        $this->assertInstanceOf(GamePhaseThousandStockDistribution::class, $this->phase->getNextPhase(true));
        $this->assertInstanceOf($this->phase::class, $this->phase->getNextPhase(false));
    }
}
