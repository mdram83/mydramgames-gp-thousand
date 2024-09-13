<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePhase;

use MyDramGames\Core\GamePhase\GamePhase;

abstract class GamePhaseThousand implements GamePhase
{
    public const string PHASE_KEY = '';
    protected const string PHASE_NAME = '';
    protected const string PHASE_DESCRIPTION = '';

    final public function getKey(): string
    {
        return $this::PHASE_KEY;
    }

    final public function getName(): string
    {
        return $this::PHASE_NAME;
    }

    final public function getDescription(): string
    {
        return $this::PHASE_DESCRIPTION;
    }

    abstract public function getNextPhase(bool $lastAttempt): GamePhase;
}
