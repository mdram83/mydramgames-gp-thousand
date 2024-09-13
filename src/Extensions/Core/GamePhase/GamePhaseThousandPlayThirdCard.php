<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandPlayThirdCard extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'playing-third-card';
    protected const string PHASE_NAME = 'Play Third Card';
    protected const string PHASE_DESCRIPTION = 'Last player is playing his card now';

    /**
     * @throws GamePhaseException
     */
    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if (!$lastAttempt) {
            throw new GamePhaseException(GamePhaseException::MESSAGE_PHASE_SINGLE_ATTEMPT);
        }
        return new GamePhaseThousandCollectTricks();
    }
}
