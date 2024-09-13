<?php

namespace Tests\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandStockDistribution;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandStockDistribution;
use MyDramGames\Utils\Player\Player;
use PHPUnit\Framework\TestCase;

class GameMoveThousandStockDistributionTest extends TestCase
{
    private Player $player;
    private array $details = ['distribution' => ['playerA' => '123', 'playerB' => '234']];
    private GamePhaseThousandStockDistribution $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->player = $this->createMock(Player::class);
        $this->phase = new GamePhaseThousandStockDistribution();
    }

    public function testThrowExceptionWhenPhaseMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandStockDistribution($this->player, $this->details);
    }

    public function testThrowExceptionWhenWrongDataStructure(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandStockDistribution($this->player, ['wrong' => 'structure'], $this->phase);
    }

    public function testThrowExceptionWhenJustOnePlayer(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandStockDistribution($this->player, ['distribution' => ['player1' => '123']], $this->phase);
    }

    public function testThrowExceptionWhenMissingCard(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandStockDistribution($this->player, ['distribution' => ['player1' => '123', 'player2' => '']], $this->phase);
    }

    public function testThrowExceptionWhenCardNotString(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandStockDistribution($this->player, ['distribution' => ['player1' => 123, 'player2' => '123']], $this->phase);
    }

    public function testCreateGameMoveThousandStockDistribution(): void
    {
        $move = new GameMoveThousandStockDistribution($this->player, $this->details, $this->phase);
        $this->assertEquals(array_merge($this->details, ['phase' => $this->phase]), $move->getDetails());
    }
}
