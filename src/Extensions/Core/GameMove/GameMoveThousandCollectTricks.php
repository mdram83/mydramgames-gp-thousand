<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\GameMove\GameMove;

class GameMoveThousandCollectTricks extends GameMoveThousand implements GameMove
{
    protected function isValidInput(): bool
    {
        if (!$this->hasPhase() || !$this->hasCollectFlagSetToTrue()) {
            return false;
        }

        return true;
    }

    private function hasCollectFlagSetToTrue(): bool
    {
        return (isset($this->details['collect']) && $this->details['collect'] === true);
    }
}
