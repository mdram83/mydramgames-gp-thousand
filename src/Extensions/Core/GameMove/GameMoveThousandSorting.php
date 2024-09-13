<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\GameMove\GameMove;

class GameMoveThousandSorting extends GameMoveThousand implements GameMove
{
    protected function isValidInput(): bool
    {
        if (
            $this->hasPhase()
            || !$this->hasDetailsHand()
            || !$this->isDetailsArrayOfStrings()
            || !$this->validateDetailsCount()
        ) {
            return false;
        }

        return true;
    }

    private function hasDetailsHand(): bool
    {
        return isset($this->details['hand']);
    }

    private function isDetailsArrayOfStrings(): bool
    {
        $notStrings = array_filter($this->details['hand'], fn($element) => !is_string($element));
        return !(count($notStrings) > 0);
    }

    private function validateDetailsCount(): bool
    {
        return count($this->details['hand']) <= 11 && count($this->details['hand']) > 1;
    }
}
