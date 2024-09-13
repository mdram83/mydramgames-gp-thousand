<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandCountPoints extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'counting-points';
    protected const string PHASE_NAME = 'Counting Points';
    protected const string PHASE_DESCRIPTION = 'See result of last round and for the whole game';

    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if ($lastAttempt) {
            return new GamePhaseThousandBidding();
        }
        return $this;
    }
}
