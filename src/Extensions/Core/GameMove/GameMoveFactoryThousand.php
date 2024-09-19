<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameMove;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Core\GameMove\GameMove;
use MyDramGames\Core\GameMove\GameMoveFactory;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandDeclaration;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayFirstCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlaySecondCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayThirdCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandRepository;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandStockDistribution;
use MyDramGames\Utils\Player\Player;

class GameMoveFactoryThousand implements GameMoveFactory
{
    /**
     * @throws GameMoveException
     */
    public static function create(Player $player, array $inputs): GameMove
    {
        [$phaseKey, $data, $phase] = self::getValidatedInputs($inputs);

        if ($phaseKey === 'sorting') {
            return new GameMoveThousandSorting($player, $data, $phase);
        }

        $className = self::getPhaseRelatedMoveClass($phase);
        return new $className($player, $data, $phase);
    }

    /**
     * @throws GameMoveException
     */
    protected static function getValidatedInputs(array $inputs): array
    {
        $phaseKey = $inputs['phase'] ?? null;
        $data = $inputs['data'] ?? null;

        if (!isset($phaseKey) || $phaseKey === '' || !isset($data) || !is_array($data)) {
            throw new GameMoveException(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);
        }

        try {
            $phase = (new GamePhaseThousandRepository())->getOne($phaseKey);
        } catch (GamePhaseException) {

        }

        if (!isset($phase) && $phaseKey !== 'sorting') {
            throw new GameMoveException(GameMoveException::MESSAGE_INVALID_MOVE_PARAMS);
        }

        return [$phaseKey, $data, $phase ?? null];
    }

    protected static function getPhaseRelatedMoveClass(GamePhaseThousand $phase): string
    {
        $moves = [
            GamePhaseThousandBidding::PHASE_KEY => GameMoveThousandBidding::class,
            GamePhaseThousandStockDistribution::PHASE_KEY => GameMoveThousandStockDistribution::class,
            GamePhaseThousandDeclaration::PHASE_KEY => GameMoveThousandDeclaration::class,
            GamePhaseThousandPlayFirstCard::PHASE_KEY  => GameMoveThousandPlayCard::class,
            GamePhaseThousandPlaySecondCard::PHASE_KEY  => GameMoveThousandPlayCard::class,
            GamePhaseThousandPlayThirdCard::PHASE_KEY  => GameMoveThousandPlayCard::class,
            GamePhaseThousandCollectTricks::PHASE_KEY  => GameMoveThousandCollectTricks::class,
            GamePhaseThousandCountPoints::PHASE_KEY  => GameMoveThousandCountPoints::class,
        ];

        return $moves[$phase->getKey()];
    }
}
