<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandCollectTricks extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'collecting-tricks';
    protected const string PHASE_NAME = 'Collect Tricks';
    protected const string PHASE_DESCRIPTION = 'Trick winner pick the trick from the table-';

    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if ($lastAttempt) {
            return new GamePhaseThousandCountPoints();
        }
        return new GamePhaseThousandPlayFirstCard();
    }
}
