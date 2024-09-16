<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameIndex;

use MyDramGames\Core\Exceptions\GameOptionException;
use MyDramGames\Core\Exceptions\GamePlayException;
use MyDramGames\Core\GameIndex\GameIndex;
use MyDramGames\Core\GameIndex\GameIndexStorableBase;
use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GameMove\GameMove;
use MyDramGames\Core\GameMove\GameMoveFactory;
use MyDramGames\Core\GamePlay\GamePlay;
use MyDramGames\Core\GameSetup\GameSetup;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveFactoryThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GamePlay\GamePlayThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameSetup\GameSetupThousand;
use MyDramGames\Utils\Exceptions\CollectionException;
use MyDramGames\Utils\Player\Player;

class GameIndexThousand extends GameIndexStorableBase implements GameIndex
{
    public const string SLUG = 'thousand';
    public const string NAME = 'Thousand';
    public const ?string DESCRIPTION = 'Another Classic, a Thousand Schnapsen playing card game.';
    public const ?int DURATION_IN_MINUTES = 120;
    public const ?int MIN_PLAYER_AGE = 10;
    public const bool IS_ACTIVE = true;
    public const bool IS_PREMIUM = false;

    protected GameMoveFactory $gameMoveFactory;

    protected function configureGameIndex(): void
    {
        $this->gameMoveFactory = new GameMoveFactoryThousand();
    }

    /**
     * @inheritDoc
     * @throws GameOptionException|CollectionException
     */
    public function getGameSetup(): GameSetup
    {
        return new GameSetupThousand(
            $this->optionsHandler->clone(),
            $this->valuesHandler->clone(),
        );
    }

    /**
     * @inheritDoc
     */
    public function createGameMove(Player $player, array $inputs): GameMove
    {
        return $this->gameMoveFactory->create($player, $inputs);
    }

    /**
     * @inheritDoc
     * @throws GamePlayException
     */
    public function createGamePlay(GameInvite $gameInvite): GamePlay
    {
        $storage = $this->gamePlayStorageFactory->create($gameInvite);
        return new GamePlayThousand($storage, $this->gamePlayServicesProvider);
    }
}
