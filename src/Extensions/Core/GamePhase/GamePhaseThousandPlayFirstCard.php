<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandPlayFirstCard extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'playing-first-card';
    protected const string PHASE_NAME = 'Play First Card';
    protected const string PHASE_DESCRIPTION = 'Bidding or last trick winner play first card';

    /**
     * @throws GamePhaseException
     */
    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if (!$lastAttempt) {
            throw new GamePhaseException(GamePhaseException::MESSAGE_PHASE_SINGLE_ATTEMPT);
        }
        return new GamePhaseThousandPlaySecondCard();
    }
}
