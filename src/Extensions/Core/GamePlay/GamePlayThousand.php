<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GamePlay;

use MyDramGames\Core\Exceptions\GameMoveException;
use MyDramGames\Core\Exceptions\GameOptionException;
use MyDramGames\Core\Exceptions\GamePhaseException;
use MyDramGames\Core\Exceptions\GamePlayException;
use MyDramGames\Core\Exceptions\GamePlayStorageException;
use MyDramGames\Core\Exceptions\GameResultException;
use MyDramGames\Core\Exceptions\GameResultProviderException;
use MyDramGames\Core\GameMove\GameMove;
use MyDramGames\Core\GamePlay\GamePlay;
use MyDramGames\Core\GamePlay\GamePlayStorableBase;
use MyDramGames\Games\Thousand\Extensions\Core\Exceptions\GamePlayThousandException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandDeclaration;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandPlayCard;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandSorting;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandStockDistribution;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandRepository;
use MyDramGames\Games\Thousand\Extensions\Core\GameResult\GameResultProviderThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameResult\GameResultThousand;
use MyDramGames\Games\Thousand\Extensions\Utils\ThousandDeckProvider;
use MyDramGames\Games\Thousand\Tools\GameDataThousand;
use MyDramGames\Games\Thousand\Tools\GameStewardThousand;
use MyDramGames\Games\Thousand\Tools\PlayerDataCollectionThousand;
use MyDramGames\Games\Thousand\Tools\PlayerDataThousand;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardDealerGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardFactoryGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardSuitRepositoryGeneric;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollection;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollectionPoweredUnique;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardStockCollectionPowered;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardSuitRepository;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDealer;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDeckProvider;
use MyDramGames\Utils\Exceptions\CollectionException;
use MyDramGames\Utils\Exceptions\DealDefinitionItemException;
use MyDramGames\Utils\Exceptions\PlayingCardCollectionException;
use MyDramGames\Utils\Exceptions\PlayingCardDealerException;
use MyDramGames\Utils\Exceptions\PlayingCardException;
use MyDramGames\Utils\Player\Player;

class GamePlayThousand extends GamePlayStorableBase implements GamePlay
{
    protected const ?string GAME_MOVE_CLASS = GameMoveThousand::class;

    protected PlayingCardDeckProvider $deckProvider;
    protected PlayingCardSuitRepository $suitRepository;
    protected PlayingCardDealer $cardDealer;
    protected GamePhaseThousandRepository $phaseRepository;
    protected GameStewardThousand $steward;

    protected ?GameResultThousand $result = null;
    private GameDataThousand $gameData;
    private PlayerDataCollectionThousand $playersData;

    /**
     * @param GameMove $move
     * @throws CollectionException
     * @throws DealDefinitionItemException
     * @throws GameMoveException
     * @throws GameOptionException
     * @throws GamePlayException
     * @throws GamePlayStorageException
     * @throws GamePlayThousandException
     * @throws GameResultException
     * @throws GameResultProviderException
     * @throws PlayingCardCollectionException
     */
    public function handleMove(GameMove $move): void
    {
        $this->validateNotFinished();

        switch ($move::class) {

            case GameMoveThousandSorting::class:
                $this->handleMoveSorting($move);
                break;

            case GameMoveThousandCountPoints::class:
                $this->handleMoveCountPoints($move);
                break;

            default:
                $this->validateMove($move);
                $this->validatePhase($move);
                $this->handleMoveByPhase($move);
        }

        $this->saveData();
        $this->checkAndSetWinResult();
    }

    /**
     * @throws CollectionException
     * @throws GamePlayException
     * @throws GameResultException
     * @throws GameResultProviderException
     * @throws GamePlayStorageException
     */
    private function checkAndSetWinResult(): void
    {
        if ($this->gameData->round > 1) {

            $resultProvider = new GameResultProviderThousand(
                $this->gamePlayServicesProvider->getGameRecordFactory(),
                $this->gamePlayServicesProvider->getGameRecordCollection()
            );

            if ($this->result = $resultProvider->getResult([
                'players' => $this->getPlayers(),
                'playersData' => array_map(fn($playerData) => $playerData->toArray(), $this->playersData->toArray())
            ])) {
                $resultProvider->createGameRecords($this->getGameInvite());
                $this->storage->setFinished();
            }
        }
    }

    /**
     * @param Player $player
     * @throws CollectionException
     * @throws GamePlayException
     * @throws GamePlayStorageException
     * @throws GameResultException
     * @throws GameResultProviderException
     */
    public function handleForfeit(Player $player): void
    {
        $this->validateGamePlayer($player);
        $this->validateNotFinished();
        $this->setForfeitResult($player);
    }

    /**
     * @param Player $player
     * @throws CollectionException
     * @throws GamePlayException
     * @throws GamePlayStorageException
     * @throws GameResultException
     * @throws GameResultProviderException
     */
    private function setForfeitResult(Player $player): void
    {
        $resultProvider = new GameResultProviderThousand(
            $this->gamePlayServicesProvider->getGameRecordFactory(),
            $this->gamePlayServicesProvider->getGameRecordCollection()
        );

        $this->result = $resultProvider->getResult([
            'players' => $this->getPlayers(),
            'playersData' => array_map(fn($playerData) => $playerData->toArray(), $this->playersData->toArray()),
            'forfeited' => $player,
        ]);

        $resultProvider->createGameRecords($this->getGameInvite());
        $this->storage->setFinished();
    }

    /**
     * @throws GamePlayException|CollectionException
     */
    public function getSituation(Player $player): array
    {
        $this->validateGamePlayer($player);

        $data = $this->getSituationData();

        foreach ($data['orderedPlayers'] as $playerName => $playerData) {
            if ($playerName !== $player->getName()) {
                $data['orderedPlayers'][$playerName]['hand'] = count($data['orderedPlayers'][$playerName]['hand']);
            }
            $data['orderedPlayers'][$playerName]['tricks'] = count($data['orderedPlayers'][$playerName]['tricks']);
        }

        $data['stock'] = count($data['stock']);
        $data['stockRecord'] = (
            $this->gameData->bidAmount > 100
            || ($this->gameData->phase->getKey() === (new GamePhaseThousandCountPoints())->getKey()) && $this->getPlayers()->count() === 4)
                ? $data['stockRecord']
                : [];

        if (isset($this->result)) {
            $data['result'] = $this->result->toArray();
        }

        return $data;
    }

    /**
     * @throws GamePlayException
     * @throws CollectionException
     */
    private function getSituationData(): array
    {
        $orderedPlayers = [];

        foreach ($this->playersData->toArray() as $playerId => $playerData) {
            $orderedPlayers[$this->getPlayers()->getOne($playerId)->getName()] = [
                'hand' => $playerData->hand->keys(),
                'tricks' => $playerData->tricks->keys(),
                'barrel' => $playerData->barrel,
                'points' => $playerData->points,
                'ready' => $playerData->ready,
                'bid' => $playerData->bid,
                'seat' => $playerData->seat,
                'bombRounds' => $playerData->bombRounds,
                'trumps' => $playerData->trumps,
            ];
        }

        return [
            'orderedPlayers' => $orderedPlayers,

            'stock' => $this->gameData->stock->keys(),
            'stockRecord' => $this->gameData->stockRecord->keys(),
            'table' => $this->gameData->table->keys(),
            'trumpSuit' => $this->gameData->trumpSuit?->getKey(),
            'turnSuit' => $this->gameData->turnSuit?->getKey(),
            'turnLead' => $this->gameData->turnLead?->getName(),
            'bidWinner' => $this->gameData->bidWinner?->getName(),
            'bidAmount' => $this->gameData->bidAmount,
            'activePlayer' => $this->activePlayer->getName(),
            'dealer' => $this->gameData->dealer->getName(),
            'obligation' => $this->gameData->obligation->getName(),
            'round' => $this->gameData->round,
            'phase' => [
                'key' => $this->gameData->phase->getKey(),
                'name' => $this->gameData->phase->getName(),
                'description' => $this->gameData->phase->getDescription(),
            ],
            'isFinished' => $this->isFinished(),
        ];
    }

    /**
     * @throws CollectionException
     * @throws GamePlayStorageException
     * @throws DealDefinitionItemException|GamePlayException
     */
    protected function initialize(): void
    {
        $this->initializePlayersData();

        $this->gameData = new GameDataThousand();
        $this->gameData->dealer = $this->getPlayers()->getOne(array_keys($this->playersData->toArray())[0]);
        $this->setRoundStartParameters();

        $this->gameData->stock = $this->deckProvider->getDeck()->reset();
        $this->gameData->stockRecord = $this->deckProvider->getDeck()->reset();
        $this->gameData->table = $this->deckProvider->getDeck()->reset();
        $this->gameData->deck = $this->deckProvider->getDeck();

        $this->steward->shuffleAndDealCards($this->gameData, $this->playersData);

        $this->gameData->round = 1;
        $this->gameData->trumpSuit = null;
        $this->gameData->turnSuit = null;
        $this->gameData->turnLead = null;
        $this->gameData->phase = new GamePhaseThousandBidding();

        $this->saveData();
    }

    /**
     * @throws CollectionException
     * @throws GamePlayException
     */
    private function initializePlayersData(): void
    {
        $seat = 1;
        $this->playersData = new PlayerDataCollectionThousand();

        foreach ($this->getPlayers()->shuffle()->toArray() as $player) {
            $playerData = new PlayerDataThousand($player);
            $playerData->hand = $this->deckProvider->getDeck()->reset();
            $playerData->tricks = $this->deckProvider->getDeck()->reset();
            $playerData->seat = $seat;
            $this->playersData->add($playerData);
            $seat++;
        }
    }

    /**
     * @throws CollectionException
     */
    private function setRoundStartParameters(): void
    {
        $this->gameData->obligation = $this->steward->getNextOrderedPlayer($this->gameData->dealer, $this->gameData->dealer, $this->playersData);
        $this->activePlayer = $this->steward->getNextOrderedPlayer($this->gameData->obligation, $this->gameData->dealer, $this->playersData);

        $this->gameData->bidWinner = null;
        $this->gameData->bidAmount = 100;
        $this->playersData->getFor($this->gameData->obligation)->bid = $this->gameData->bidAmount;
    }

    /**
     * @throws CollectionException
     * @throws GamePlayException
     * @throws GamePlayStorageException
     */
    protected function saveData(): void
    {
        $this->storage->setGameData($this->getSituationData());
    }

    /**
     * @throws CollectionException
     * @throws GamePlayException
     * @throws GamePlayStorageException
     * @throws GamePhaseException
     * @throws PlayingCardCollectionException
     */
    protected function loadData(): void
    {
        $data = $this->storage->getGameData();
        $this->gameData = new GameDataThousand();
        $this->gameData->deck = $this->deckProvider->getDeck();

        $this->playersData = new PlayerDataCollectionThousand();
        foreach ($data['orderedPlayers'] as $playerName => $dataPlayer) {
            $playerData = new PlayerDataThousand($this->getPlayerByName($playerName));
            $playerData->hand = $this->gameData->deck->getMany($dataPlayer['hand'])->sortByKeys($dataPlayer['hand']);
            $playerData->tricks = $this->gameData->deck->getMany($dataPlayer['tricks'])->sortByKeys($dataPlayer['tricks']);
            $playerData->barrel = $dataPlayer['barrel'];
            $playerData->points = $dataPlayer['points'];
            $playerData->ready = $dataPlayer['ready'];
            $playerData->bid = $dataPlayer['bid'];
            $playerData->seat = $dataPlayer['seat'];
            $playerData->bombRounds = $dataPlayer['bombRounds'];
            $playerData->trumps = $dataPlayer['trumps'];
            $this->playersData->add($playerData);
        }

        $this->activePlayer = $this->getPlayerByName($data['activePlayer']);

        $this->gameData->dealer = $this->getPlayerByName($data['dealer']);
        $this->gameData->obligation = $this->getPlayerByName($data['obligation']);
        $this->gameData->bidWinner = $this->getPlayerByName($data['bidWinner']);
        $this->gameData->bidAmount = $data['bidAmount'];
        $this->gameData->stock = $this->gameData->deck->getMany($data['stock']);
        $this->gameData->table = $this->gameData->deck->getMany($data['table'])->sortByKeys($data['table']);
        $this->gameData->stockRecord = $this->gameData->deck->getMany($data['stockRecord']);
        $this->gameData->round = $data['round'];
        $this->gameData->trumpSuit = isset($data['trumpSuit']) ? $this->suitRepository->getOne($data['trumpSuit']) : null;
        $this->gameData->turnSuit = isset($data['turnSuit']) ? $this->suitRepository->getOne($data['turnSuit']) : null;
        $this->gameData->turnLead = $this->getPlayerByName($data['turnLead']);
        $this->gameData->phase = $this->phaseRepository->getOne($data['phase']['key']);
    }

    /**
     * @throws GamePlayException
     */
    protected function configureGamePlayServices(): void
    {
        $this->deckProvider = new ThousandDeckProvider(
            new PlayingCardCollectionPoweredUnique(),
            new PlayingCardFactoryGeneric(),
        );

        $this->suitRepository = new PlayingCardSuitRepositoryGeneric();
        $this->cardDealer = new PlayingCardDealerGeneric();
        $this->phaseRepository = new GamePhaseThousandRepository();

        $this->steward = new GameStewardThousand($this->getPlayers(), $this->getGameInvite(), $this->cardDealer);
    }

    protected function runConfigurationAfterHooks(): void
    {

    }

    /**
     * @throws GamePlayException
     */
    protected function validatePhase(GameMove $move): void
    {
        if ($this->gameData->phase->getKey() !== $move->getDetails()['phase']->getKey()) {
            throw new GamePlayException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
        }
    }

    /**
     * @param GameMove $move
     * @throws CollectionException
     * @throws GameMoveException
     * @throws GameOptionException
     * @throws GamePlayException
     * @throws GamePlayThousandException
     * @throws PlayingCardCollectionException
     */
    private function handleMoveByPhase(GameMove $move): void
    {
        switch ($move::class) {

            case GameMoveThousandBidding::class:
                $this->handleMoveBidding($move);
                break;

            case GameMoveThousandStockDistribution::class:
                $this->handleMoveStockDistribution($move);
                break;

            case GameMoveThousandDeclaration::class;
                $this->handleMoveDeclaration($move);
                break;

            case GameMoveThousandPlayCard::class:
                $this->handleMovePlayCard($move);
                break;

            case GameMoveThousandCollectTricks::class:
                $this->handleMoveCollectTricks($move);
                break;

            default:
                throw new GameMoveException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
        }
    }

    /**
     * @throws CollectionException|PlayingCardCollectionException
     */
    private function handleMoveSorting(GameMove $move): void
    {
        $this->playersData->getFor($move->getPlayer())->hand->sortByKeys($move->getDetails()['hand']);
    }

    /**
     * @throws GamePlayThousandException|CollectionException|PlayingCardCollectionException
     */
    private function handleMoveBidding(GameMove $move): void
    {
        if ($move->getDetails()['decision'] === 'bid') {

            $newBidAmount = $move->getDetails()['bidAmount'];

            if ($newBidAmount !== ($this->gameData->bidAmount + 10)) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_BID_STEP_INVALID);
            }

            if (
                $newBidAmount > 120
                && !$this->steward->hasMarriageAtHand($this->playersData->getFor($move->getPlayer())->hand)
            ) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_BID_NO_MARRIAGE);
            }

            $this->gameData->bidAmount = $newBidAmount;
        }

        $this->playersData->getFor($move->getPlayer())->bid = $move->getDetails()['bidAmount'] ?? 'pass';

        if ($this->steward->isLastBiddingMove($this->gameData->bidAmount, $this->playersData)) {

            $this->gameData->advanceGamePhase(true);

            $this->activePlayer = $this->steward->getHighestBiddingPlayer($this->playersData);
            $this->gameData->bidWinner = $this->activePlayer;
            $this->gameData->stockRecord = $this->gameData->stockRecord->reset($this->gameData->stock->toArray());

            $this->cardDealer->collectCards(
                $this->playersData->getFor($this->gameData->bidWinner)->hand,
                new PlayingCardStockCollectionPowered(null, [$this->gameData->stock])
            );

            foreach ($this->playersData->toArray() as $playerData) {
                $playerData->bid = null;
            }

        } else {
            $this->gameData->advanceGamePhase(false);
            $this->activePlayer = $this->steward->getNextOrderedPlayer($move->getPlayer(), $this->gameData->dealer, $this->playersData);
        }
    }

    /**
     * @param GameMove $move
     * @throws CollectionException
     * @throws GamePlayException
     * @throws GamePlayThousandException
     * @throws GameOptionException
     */
    private function handleMoveStockDistribution(GameMove $move): void
    {
        $distribution = $move->getDetails()['distribution'];
        $this->validateMoveStockDistribution($distribution);

        try {
            foreach ($distribution as $distributionPlayerName => $distributionCardKey) {
                $this->cardDealer->moveCardsByKeys(
                    $this->playersData->getFor($move->getPlayer())->hand,
                    $this->playersData->getFor($this->getPlayerByName($distributionPlayerName))->hand,
                    [$distributionCardKey]
                );
            }
        } catch (PlayingCardDealerException) {
            throw new GamePlayThousandException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
        }


        $this->gameData->advanceGamePhase(true);
    }

    /**
     * @param GameMove $move
     * @throws CollectionException
     * @throws GamePlayThousandException
     * @throws PlayingCardCollectionException
     * @throws GameOptionException
     * @throws GamePlayException
     */
    private function handleMoveDeclaration(GameMove $move): void
    {
        $declaration = $move->getDetails()['declaration'];
        $this->validateMoveDeclaration($declaration);

        if ($declaration === 0) {

            foreach ($this->getPlayers()->toArray() as $player) {
                $this->steward->setRoundPoints($player, $this->activePlayer, $this->gameData, $this->playersData, true);
                $this->steward->setBarrelStatus($this->playersData->getFor($player));

                $this->playersData->getFor($player)->hand = $this->deckProvider->getDeck()->reset();
                $this->playersData->getFor($player)->ready = false;
            }

            $this->playersData->getFor($this->activePlayer)->bombRounds[] = $this->gameData->round;
            $this->gameData->phase = new GamePhaseThousandCountPoints();

            return;
        }

        $this->gameData->bidAmount = $declaration;
        $this->gameData->turnLead = $this->gameData->bidWinner;
        $this->gameData->advanceGamePhase(true);
    }

    /**
     * @throws GamePlayException|CollectionException
     */
    private function handleMovePlayCard(GameMove $move): void
    {
        $cardKey = $move->getDetails()['card'];
        $hand = $this->playersData->getFor($move->getPlayer())->hand;
        $hasMarriageRequest = $move->getDetails()['marriage'] ?? false;

        $this->validateMovePlayingCard($hand, $cardKey, $hasMarriageRequest);
        $this->cardDealer->moveCardsByKeys($hand, $this->gameData->table, [$cardKey]);

        if ($this->steward->isFirstCardPhase($this->gameData->phase)) {

            $this->gameData->turnSuit = $this->gameData->table->getOne($cardKey)->getSuit();

            if ($hasMarriageRequest) {
                $this->gameData->trumpSuit = $this->gameData->turnSuit;
                $this->playersData->getFor($this->activePlayer)->trumps[] = $cardKey;
            }
        }

        $this->activePlayer = $this->steward->isThirdCardPhase($this->gameData->phase)
            ? $this->steward->getTrickWinner($this->gameData, $this->playersData)
            : $this->steward->getNextOrderedPlayer($move->getPlayer(), $this->gameData->dealer, $this->playersData);

        $this->gameData->advanceGamePhase(true);
    }

    /**
     * @throws CollectionException
     * @throws GameOptionException
     * @throws PlayingCardCollectionException|GamePlayException
     */
    private function handleMoveCollectTricks(GameMove $move): void
    {
        $trickWinner = $move->getPlayer();

        $this->cardDealer->moveCardsTimes(
            $this->gameData->table,
            $this->playersData->getFor($trickWinner)->tricks,
            3
        );

        $this->gameData->turnLead = $trickWinner;
        $this->gameData->turnSuit = null;

        $hand = $this->playersData->getFor($trickWinner)->hand;

        if ($hand->count() === 0) {

            foreach ($this->getPlayers()->toArray() as $player) {

                $this->steward->setRoundPoints($player, $this->activePlayer, $this->gameData, $this->playersData);
                $this->steward->setBarrelStatus($this->playersData->getFor($player));

                $this->playersData->getFor($player)->tricks = $this->deckProvider->getDeck()->reset();
                $this->playersData->getFor($player)->trumps = [];
                $this->playersData->getFor($player)->ready = false;
            }

            $this->gameData->trumpSuit = null;
            $this->gameData->turnLead = null;
            $this->gameData->stockRecord = $this->deckProvider->getDeck()->reset();
        }

        $this->gameData->advanceGamePhase($hand->count() === 0);
    }

    /**
     * @param GameMove $move
     * @throws CollectionException
     * @throws DealDefinitionItemException
     * @throws GamePlayException
     */
    private function handleMoveCountPoints(GameMove $move): void
    {
        if ($this->arePlayersReady($move->getPlayer())) {
            throw new GamePlayException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
        }

        $this->playersData->getFor($move->getPlayer())->ready = true;

        if ($this->arePlayersReady()) {

            $this->gameData->dealer = $this->steward->getNextOrderedPlayer($this->gameData->dealer, $this->gameData->dealer, $this->playersData);
            $this->setRoundStartParameters();

            $this->steward->shuffleAndDealCards($this->gameData, $this->playersData);

            $this->gameData->round++;
            $this->gameData->advanceGamePhase(true);
        }
    }

    /**
     * @param array $distribution
     * @throws GameOptionException
     * @throws GamePlayException
     * @throws GamePlayThousandException
     */
    private function validateMoveStockDistribution(array $distribution): void
    {
        $numberOfPlayers = $this->getGameInvite()->getGameSetup()->getNumberOfPlayers()->getConfiguredValue()->getValue();

        if (
            in_array($this->activePlayer->getName(), array_keys($distribution))
            || count(array_unique($distribution)) !== 2
            || ($numberOfPlayers === 4 && in_array($this->gameData->dealer->getName(), array_keys($distribution)))
        ) {
            throw new GamePlayThousandException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
        }
    }

    /**
     * @param int $declaration
     * @throws CollectionException
     * @throws GameOptionException
     * @throws GamePlayThousandException
     */
    private function validateMoveDeclaration(int $declaration): void
    {
        if ($declaration === 0) {

            if ($this->gameData->bidAmount !== 100) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_BOMB_ON_BID);
            }

            if ($this->steward->hasPlayerUsedMaxBombMoves($this->playersData->getFor($this->activePlayer)->bombRounds)) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_BOMB_USED);
            }

        } elseif (($declaration < $this->gameData->bidAmount || $declaration > 300 || $declaration % 10 !== 0)) {
            throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_WRONG_DECLARATION);
        }
    }

    /**
     * @throws GamePlayException|CollectionException
     */
    private function validateMovePlayingCard(PlayingCardCollection $hand, string $cardKey, bool $marriage = false): void
    {
        if (!$hand->exist($cardKey)) {
            throw new GamePlayException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
        }

        if ($marriage) {
            $marriageCards = $hand->filter(fn($item) => (
                $item->getSuit() === $hand->getOne($cardKey)->getSuit()
                && in_array($item->getRank()->getKey(), ['K', 'Q'])
            ));

            if ($marriageCards->count() !== 2) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_PLAY_TRUMP_PAIR);
            }

            if (!in_array($hand->getOne($cardKey)->getRank()->getKey(), ['K', 'Q'])) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_PLAY_TRUMP_RANK);
            }

            if (!$this->steward->isFirstCardPhase($this->gameData->phase)) {
                throw new GamePlayException(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);
            }
        }

        if (!$this->steward->isFirstCardPhase($this->gameData->phase)) {
            if (
                $hand->filter(fn($item) => $item->getSuit() === $this->gameData->turnSuit)->count() > 0
                && $hand->getOne($cardKey)->getSuit() !== $this->gameData->turnSuit
            ) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_PLAY_TURN_SUIT);
            }
        }

        if ($this->steward->isSecondCardPhase($this->gameData->phase)) {

            $tableCard = $this->gameData->table->getOne($this->gameData->table->keys()[0]);
            $higherRankCardsAtHand = $hand->filter(fn($item) => (
                $this->steward->getCardPoints($item) > $this->steward->getCardPoints($tableCard)
                && $item->getSuit() === $this->gameData->turnSuit
            ));

            if ($higherRankCardsAtHand->count() > 0 && !$higherRankCardsAtHand->exist($cardKey)) {
                throw new GamePlayThousandException(GamePlayThousandException::MESSAGE_RULE_PLAY_HIGH_RANK);
            }
        }
    }

    /**
     * @throws GamePlayException
     * @throws CollectionException
     */
    private function arePlayersReady(?Player $player = null): bool
    {
        $readyPlayersData = $this->playersData->filter(fn($playerData) =>
            $playerData->ready === true && ($playerData->getId() === ($player?->getId() ?? $playerData->getId()))
        );

        return $readyPlayersData->count() === (isset($player) ? 1 : $this->getPlayers()->count());
    }
}
