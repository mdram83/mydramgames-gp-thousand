<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandDeclaration extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'declaration';
    protected const string PHASE_NAME = 'Declaring points to play';
    protected const string PHASE_DESCRIPTION = 'Bidding winner to declare points to play now';

    /**
     * @throws GamePhaseException
     */
    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if (!$lastAttempt) {
            throw new GamePhaseException(GamePhaseException::MESSAGE_PHASE_SINGLE_ATTEMPT);
        }
        return new GamePhaseThousandPlayFirstCard();
    }
}
