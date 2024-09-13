<?php

namespace Tests\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandBidding;
use MyDramGames\Utils\Player\Player;
use PHPUnit\Framework\TestCase;

class GameMoveThousandBiddingTest extends TestCase
{
    private Player $player;
    private array $detailsBid = ['decision' => 'bid', 'bidAmount' => 110];
    private array $detailsPass = ['decision' => 'pass'];
    private GamePhaseThousandBidding $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->player = $this->createMock(Player::class);
        $this->phase = new GamePhaseThousandBidding();
    }

    public function testThrowExceptionWhenPhaseMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandBidding($this->player, $this->detailsBid);
    }

    public function testThrowExceptionWhenDecisionMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandBidding($this->player, ['no-decision' => 'here'], $this->phase);
    }

    public function testThrowExceptionWhenDecisionWrong(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandBidding($this->player, ['decision' => 'wrong'], $this->phase);
    }

    public function testThrowExceptionWhenBidWithoutAmount(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandBidding($this->player, ['decision' => 'bid'], $this->phase);
    }

    public function testThrowExceptionWhenBidWithInvalidAmount(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandBidding($this->player, ['decision' => 'bid', 'bidAmount' => 'invalid'], $this->phase);
    }

    public function testCreateGameMoveThousandBiddingForBid(): void
    {
        $move = new GameMoveThousandBidding($this->player, $this->detailsBid, $this->phase);
        $this->assertEquals(array_merge($this->detailsBid, ['phase' => $this->phase]), $move->getDetails());
    }

    public function testCreateGameMoveThousandBiddingForPass(): void
    {
        $move = new GameMoveThousandBidding($this->player, $this->detailsPass, $this->phase);
        $this->assertEquals(array_merge($this->detailsPass, ['phase' => $this->phase]), $move->getDetails());
    }
}
