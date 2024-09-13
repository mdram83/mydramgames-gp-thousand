<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\GameMove\GameMove;

class GameMoveThousandPlayCard extends GameMoveThousand implements GameMove
{
    protected function isValidInput(): bool
    {
        if (!$this->hasPhase() || !$this->hasValidCard() || !$this->hasNoOrValidMarriage()) {
            return false;
        }

        return true;
    }

    private function hasValidCard(): bool
    {
        return
            isset($this->details['card'])
            && is_string($this->details['card'])
            && $this->details['card'] !== '';
    }

    private function hasNoOrValidMarriage(): bool
    {
        return !isset($this->details['marriage']) || is_bool($this->details['marriage']);
    }
}
