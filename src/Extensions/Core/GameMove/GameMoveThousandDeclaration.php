<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\GameMove\GameMove;

class GameMoveThousandDeclaration extends GameMoveThousand implements GameMove
{
    protected function isValidInput(): bool
    {
        if (!$this->hasPhase() || !$this->hasValidDeclaration()) {
            return false;
        }

        return true;
    }

    private function hasValidDeclaration(): bool
    {
        return isset($this->details['declaration']) && is_int($this->details['declaration']);
    }
}
