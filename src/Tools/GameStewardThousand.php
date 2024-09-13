<?php

namespace MyDramGames\Games\Thousand\Tools;

use MyDramGames\Core\Exceptions\GameOptionException;
use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GamePhase\GamePhase;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayFirstCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlaySecondCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayThirdCard;
use MyDramGames\Utils\Decks\PlayingCard\Generic\DealDefinitionItemGeneric;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCard;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollection;
use MyDramGames\Utils\Decks\PlayingCard\Support\DealDefinitionCollectionPowered;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDealer;
use MyDramGames\Utils\Exceptions\CollectionException;
use MyDramGames\Utils\Exceptions\DealDefinitionItemException;
use MyDramGames\Utils\Exceptions\PlayingCardCollectionException;
use MyDramGames\Utils\Player\Player;
use MyDramGames\Utils\Player\PlayerCollection;

class GameStewardThousand
{
    private array $acesKeys = [['A-H'], ['A-D'], ['A-C'], ['A-S']];
    private array $marriagesKeys = [
        100 => ['K-H', 'Q-H'],
        80  => ['K-D', 'Q-D'],
        60  => ['K-C', 'Q-C'],
        40  => ['K-S', 'Q-S'],
    ];

    public function __construct(
        readonly private PlayerCollection $players,
        readonly private GameInvite $invite,
        readonly private PlayingCardDealer $dealer,
    )
    {

    }

    public function isFirstCardPhase(GamePhase $phase): bool
    {
        return $phase->getKey() === GamePhaseThousandPlayFirstCard::PHASE_KEY;
    }

    public function isSecondCardPhase(GamePhase $phase): bool
    {
        return $phase->getKey() === GamePhaseThousandPlaySecondCard::PHASE_KEY;
    }

    public function isThirdCardPhase(GamePhase $phase): bool
    {
        return $phase->getKey() === GamePhaseThousandPlayThirdCard::PHASE_KEY;
    }

    /**
     * @throws CollectionException
     */
    public function getNextOrderedPlayer(Player $player, Player $dealer, PlayerDataCollectionThousand $playersData): Player
    {
        $playerSeat = $playersData->getOne($player->getId())->seat;
        $nextPlayerSeat = ($playerSeat % $this->players->count()) + 1;

        $nextPlayerId = $playersData->filter(fn($playerData) => $playerData->seat === $nextPlayerSeat)->pullFirst()->getId();
        $nextPlayer = $this->players->getOne($nextPlayerId);

        return
            (
                $playersData->getOne($nextPlayerId)->bid === 'pass'
                || $this->isFourPlayersDealer($nextPlayer, $dealer)
            )
                ? $this->getNextOrderedPlayer($nextPlayer, $dealer, $playersData)
                : $nextPlayer;
    }

    /**
     * @throws CollectionException
     * @throws DealDefinitionItemException
     */
    public function shuffleAndDealCards(GameDataThousand $gameData, PlayerDataCollectionThousand $playersData): void
    {
        $definitions = new DealDefinitionCollectionPowered();
        $definitions->add(new DealDefinitionItemGeneric($gameData->stock, 3));

        $nextPlayer = $gameData->dealer;

        for ($i = 1; $i <= 3; $i++) {
            $nextPlayer = $this->getNextOrderedPlayer($nextPlayer, $gameData->dealer, $playersData);
            $definitions->add(new DealDefinitionItemGeneric(
                $playersData->getOne($nextPlayer->getId())->hand,
                7
            ));
        }

        $this->dealer->dealCards($gameData->deck, $definitions);
    }

    /**
     * @throws PlayingCardCollectionException
     */
    public function hasMarriageAtHand(PlayingCardCollection $hand): bool
    {
        return $hand->countMatchingKeysCombinations($this->marriagesKeys) > 0;
    }

    /**
     * @throws CollectionException
     */
    public function isLastBiddingMove(int $bidAmount, PlayerDataCollectionThousand $playersData): bool
    {
        return
            $bidAmount === 300
            || $playersData->filter(fn($playerData) => $playerData->bid === 'pass')->count() === 2;
    }

    /**
     * @throws CollectionException
     */
    public function getHighestBiddingPlayer(PlayerDataCollectionThousand $playersData): Player
    {
        $bids = array_filter(
            array_map(fn($playerData) => $playerData->bid, $playersData->toArray()),
            fn($bid) => ($bid !== null && $bid !== 'pass')
        );

        return $this->players->getOne(array_search(max($bids), $bids));
    }

    public function isFourPlayersDealer(Player $player, Player $dealer): bool
    {
        return ($this->players->count() === 4 && $player->getId() === $dealer->getId());
    }

    /**
     * @throws GameOptionException
     */
    public function hasPlayerUsedMaxBombMoves(array $bombRounds): bool
    {
        $allowedBombMoves = $this->invite
            ->getGameSetup()
            ->getOption('thousand-number-of-bombs')
            ->getConfiguredValue()
            ->getValue();

        return count($bombRounds) >= $allowedBombMoves;
    }

    public function getCardPoints(PlayingCard $card): int
    {
        return match($card->getRank()->getKey()) {
            'A' => 11,
            '10' => 10,
            'K' => 4,
            'Q' => 3,
            'J' => 2,
            '9' => 0,
        };
    }

    /**
     * @throws CollectionException
     */
    public function getTrickWinner(
        GameDataThousand $gameData,
        PlayerDataCollectionThousand $playersData,
    ): Player
    {
        $tableWithPlayers = [];
        $player = $gameData->turnLead;
        $trumpSuitKey = $gameData->trumpSuit?->getKey();
        $turnSuitKey = $gameData->turnSuit?->getKey();

        foreach ($gameData->table->toArray() as $card) {
            $tableWithPlayers[] = ['player' => $player, 'card' => $card];
            $player = $this->getNextOrderedPlayer($player, $gameData->dealer, $playersData);
        }

        $winningCard = $tableWithPlayers[0]['card'];
        $winningIndex = 0;

        for ($i = 1; $i <= 2; $i++) {

            $nextCard = $tableWithPlayers[$i]['card'];
            $isNextBetter = false;

            $nextCardSuite = $nextCard->getSuit()->getKey();
            $winningCardSuit = $winningCard->getSuit()->getKey();

            // next in trumpSuit beat previous not in trumpSuit
            if ($nextCardSuite === $trumpSuitKey && $winningCardSuit !== $trumpSuitKey) {
                $isNextBetter = true;
            }

            // next in trumpSuit beat previous in trumpSuit by Rank
            if (
                $nextCardSuite === $trumpSuitKey
                && $winningCardSuit === $trumpSuitKey
                && $this->getCardPoints($nextCard) > $this->getCardPoints($winningCard)
            ) {
                $isNextBetter = true;
            }

            // next not in trumpSuite but in turnSuite beat previous not in trumpSuit BY RANK
            if (
                $nextCardSuite !== $trumpSuitKey
                && $winningCardSuit !== $trumpSuitKey
                && $nextCardSuite === $turnSuitKey
                && $this->getCardPoints($nextCard) > $this->getCardPoints($winningCard)
            ) {
                $isNextBetter = true;
            }

            if ($isNextBetter) {
                $winningCard = $nextCard;
                $winningIndex = $i;
            }
        }

        return $tableWithPlayers[$winningIndex]['player'];
    }

    /**
     * @throws CollectionException|PlayingCardCollectionException
     */
    public function setRoundPoints(
        Player $player,
        Player $activePlayer,
        GameDataThousand $gameData,
        PlayerDataCollectionThousand $playersData,
        bool $isBombMove = false,
    ): void
    {
        $playerId = $player->getId();
        $playerData = $playersData->getOne($playerId);
        $isOnBarrel = $playerData->barrel;
        $isFourPlayersDealer = $this->isFourPlayersDealer($player, $gameData->dealer);

        $pointsCurrentRound =  $isBombMove
            ? (($playerId === $activePlayer->getId() || $isFourPlayersDealer || $isOnBarrel) ? 0 : 60)
            : $this->countPlayedPoints(
                $playerId === $gameData->bidWinner->getId(),
                $gameData->bidAmount,
                $playerData->tricks,
                $playerData->trumps
            );

        $stockPoints = ($isFourPlayersDealer && !$isOnBarrel)
            ? (
                $this->countStockAcesPoints($gameData->stockRecord)
                + $this->countStockMarriagePoints($gameData->stockRecord)
            )
            : 0;

        $playerData->points[$gameData->round] =
            ($playerData->points[$gameData->round - 1] ?? 0)
            + (
            !$this->isPlayerEligibleForPoints($player, $gameData->bidWinner, $playerData->barrel)
                ? 0
                : ($pointsCurrentRound + $stockPoints)
            );
    }

    private function isPlayerEligibleForPoints(Player $player, Player $bidWinner, bool $onBarrel): bool
    {
        return !$onBarrel || $player->getId() === $bidWinner->getId();
    }

    /**
     * @throws PlayingCardCollectionException
     */
    private function countStockMarriagePoints(PlayingCardCollection $stock): int
    {
        $cumulatedPoints = 0;

        foreach ($this->marriagesKeys as $points => $pair) {
            $cumulatedPoints += (
                $stock->countMatchingKeysCombinations([$this->marriagesKeys[$points]]) > 0 ? $points : 0
            );
        }

        return $cumulatedPoints;
    }

    /**
     * @throws PlayingCardCollectionException
     */
    private function countStockAcesPoints(PlayingCardCollection $stock): int
    {
        return $stock->countMatchingKeysCombinations($this->acesKeys) * 50;
    }

    public function countPlayedPoints(
        bool $isBidWinner,
        int $bidAmount,
        PlayingCardCollection $tricks,
        array $trumps,
    ): int
    {
        $points = $this->countTricksPoints($tricks) + $this->countTrumpsPoints($trumps);

        if ($isBidWinner) {
            $points = $bidAmount * ($points < $bidAmount ? -1 : 1);
        }

        return (int)round($points, -1);
    }

    private function countTricksPoints(PlayingCardCollection $tricks): int
    {
        $cards = $tricks->toArray();
        return array_reduce($cards, fn($points, $card) => $points + $this->getCardPoints($card), 0);
    }

    private function countTrumpsPoints(array $trumpDeclarationKeys): int
    {
        return
            array_reduce($trumpDeclarationKeys, function($points, $cardKey) {
                foreach ($this->marriagesKeys as $trumpPoints => $marriageKeys) {
                    $points += in_array($cardKey, $marriageKeys) ? $trumpPoints : 0;
                }
                return $points;
        }, 0);
    }

    /**
     * @throws GameOptionException
     */
    public function setBarrelStatus(PlayerDataThousand $playerData): void
    {
        $pointsLimit = $this->invite
            ->getGameSetup()
            ->getOption('thousand-barrel-points')
            ->getConfiguredValue()
            ->getValue();

        $roundKeys = count($playerData->points) > 0 ? array_keys($playerData->points) : [0];
        $lastRoundNumber = max($roundKeys);

        $playerData->barrel =
            $pointsLimit > 0
            && ($playerData->points[$lastRoundNumber] ?? 0) >= $pointsLimit;
    }
}
