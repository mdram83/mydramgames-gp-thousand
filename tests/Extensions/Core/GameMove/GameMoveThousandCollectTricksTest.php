<?php

namespace Tests\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCollectTricks;
use MyDramGames\Utils\Player\Player;
use PHPUnit\Framework\TestCase;

class GameMoveThousandCollectTricksTest extends TestCase
{
    private Player $player;
    private array $details = ['collect' => true];
    private GamePhaseThousandCollectTricks $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->player = $this->createMock(Player::class);
        $this->phase = new GamePhaseThousandCollectTricks();
    }

    public function testThrowExceptionWhenPhaseMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandCollectTricks($this->player, $this->details);
    }

    public function testThrowExceptionWhenCollectMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandCollectTricks($this->player, ['no-collect-flag' => 'here'], $this->phase);
    }

    public function testThrowExceptionWhenCollectNotTrue(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandCollectTricks($this->player, ['collect' => 'true'], $this->phase);
    }

    public function testCreateGameMoveThousandCollectTricks(): void
    {
        $move = new GameMoveThousandCollectTricks($this->player, $this->details, $this->phase);
        $this->assertEquals(array_merge($this->details, ['phase' => $this->phase]), $move->getDetails());
    }
}
