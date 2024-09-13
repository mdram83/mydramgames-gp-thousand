<?php

namespace Tests\Extensions\Core\GamePhase;

use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayThirdCard;
use PHPUnit\Framework\TestCase;

class GamePhaseThousandPlayThirdCardTest extends TestCase
{
    private GamePhaseThousandPlayThirdCard $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->phase = new GamePhaseThousandPlayThirdCard();
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
        $this->assertInstanceOf(GamePhaseThousandCollectTricks::class, $this->phase->getNextPhase(true));
    }

    public function testThrowExceptionWhenGettingNextPhaseWithLastAttemptFalse(): void
    {
        $this->expectException(GamePhaseException::class);
        $this->expectExceptionMessage(GamePhaseException::MESSAGE_PHASE_SINGLE_ATTEMPT);

        $this->phase->getNextPhase(false);
    }
}
