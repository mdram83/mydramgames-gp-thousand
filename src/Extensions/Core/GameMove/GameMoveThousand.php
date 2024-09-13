<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Core\GameMove\GameMove;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousand;
use MyDramGames\Utils\Player\Player;

abstract class GameMoveThousand implements GameMove
{
    /**
     * @throws GameMoveException
     */
    public function __construct(
        readonly protected Player $player,
        readonly protected array $details,
        readonly protected ?GamePhaseThousand $phase = null
    )
    {
        if (!$this->isValidInput()) {
            throw new GameMoveException(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);
        }
    }

    abstract protected function isValidInput(): bool;

    final public function getPlayer(): Player
    {
        return $this->player;
    }

    final public function getDetails(): array
    {
        return array_merge($this->details, ['phase' => $this->phase]);
    }

    protected function hasPhase(): bool
    {
        return isset($this->phase);
    }
}
