<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandBidding extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'bidding';
    protected const string PHASE_NAME = 'Make your bids';
    protected const string PHASE_DESCRIPTION = 'Make your bidding or pass';

    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if ($lastAttempt) {
            return new GamePhaseThousandStockDistribution();
        }
        return $this;
    }
}
