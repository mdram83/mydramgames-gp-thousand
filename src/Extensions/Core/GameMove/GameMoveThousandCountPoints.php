<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\GameMove\GameMove;

class GameMoveThousandCountPoints extends GameMoveThousand implements GameMove
{
    protected function isValidInput(): bool
    {
        if (!$this->hasPhase() || !$this->hasReadyFlagSetToTrue()) {
            return false;
        }

        return true;
    }

    private function hasReadyFlagSetToTrue(): bool
    {
        return (isset($this->details['ready']) && $this->details['ready'] === true);
    }
}
