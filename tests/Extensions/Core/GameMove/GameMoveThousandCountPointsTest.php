<?php

namespace Tests\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCountPoints;
use MyDramGames\Utils\Player\Player;
use PHPUnit\Framework\TestCase;

class GameMoveThousandCountPointsTest extends TestCase
{
    private Player $player;
    private array $details = ['ready' => true];
    private GamePhaseThousandCountPoints $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->player = $this->createMock(Player::class);
        $this->phase = new GamePhaseThousandCountPoints();
    }

    public function testThrowExceptionWhenPhaseMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandCountPoints($this->player, $this->details);
    }

    public function testThrowExceptionWhenReadyMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandCountPoints($this->player, ['no-ready-flag' => 'here'], $this->phase);
    }

    public function testThrowExceptionWhenReadyNotTrue(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandCountPoints($this->player, ['ready' => 'true'], $this->phase);
    }

    public function testCreateGameMoveThousandCountPoints(): void
    {
        $move = new GameMoveThousandCountPoints($this->player, $this->details, $this->phase);
        $this->assertEquals(array_merge($this->details, ['phase' => $this->phase]), $move->getDetails());
    }
}
