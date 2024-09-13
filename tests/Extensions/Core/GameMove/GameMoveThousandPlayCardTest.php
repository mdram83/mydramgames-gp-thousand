<?php

namespace Tests\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandPlayCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayFirstCard;
use MyDramGames\Utils\Player\Player;
use PHPUnit\Framework\TestCase;
use stdClass;

class GameMoveThousandPlayCardTest extends TestCase
{
    private Player $player;
    private array $details = ['card' => '123'];
    private GamePhaseThousandPlayFirstCard $phase;

    public function setUp(): void
    {
        parent::setUp();
        $this->player = $this->createMock(Player::class);
        $this->phase = new GamePhaseThousandPlayFirstCard();
    }

    public function testThrowExceptionWhenPhaseMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandPlayCard($this->player, $this->details);
    }

    public function testThrowExceptionWhenCardMissing(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandPlayCard($this->player, ['wrong' => 'structure'], $this->phase);
    }

    public function testThrowExceptionWhenCardInvalid(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandPlayCard($this->player, ['card' => (new stdClass())], $this->phase);
    }

    public function testThrowExceptionWhenCardEmptyString(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandPlayCard($this->player, ['card' => ''], $this->phase);
    }

    public function testThrowExceptionWhenMarriageNotBoolean(): void
    {
        $this->expectException(GameMoveException::class);
        $this->expectExceptionMessage(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);

        new GameMoveThousandPlayCard($this->player, array_merge($this->details, ['marriage' => 'notBool']), $this->phase);
    }

    public function testCreateGameMoveThousandDeclarationWithoutMarriage(): void
    {
        $move = new GameMoveThousandPlayCard($this->player, $this->details, $this->phase);
        $this->assertEquals(array_merge($this->details, ['phase' => $this->phase]), $move->getDetails());
    }

    public function testCreateGameMoveThousandDeclarationWithMarriage(): void
    {
        $move = new GameMoveThousandPlayCard(
            $this->player,
            array_merge($this->details, ['marriage' => true]),
            $this->phase
        );

        $this->assertEquals(
            array_merge($this->details, ['phase' => $this->phase, 'marriage' => true]),
            $move->getDetails()
        );
    }
}
