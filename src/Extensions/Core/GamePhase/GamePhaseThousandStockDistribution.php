<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Core\GamePhase\GamePhase;

class GamePhaseThousandStockDistribution extends GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = 'stock-distribution';
    protected const string PHASE_NAME = 'Cards Sharing';
    protected const string PHASE_DESCRIPTION = 'Bidding winner is sharing cards now';

    /**
     * @throws GamePhaseException
     */
    public function getNextPhase(bool $lastAttempt): GamePhase
    {
        if (!$lastAttempt) {
            throw new GamePhaseException(GamePhaseException::MESSAGE_PHASE_SINGLE_ATTEMPT);
        }
        return new GamePhaseThousandDeclaration();
    }
}
