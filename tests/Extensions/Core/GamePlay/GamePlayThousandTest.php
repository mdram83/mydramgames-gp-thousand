<?php

namespace Tests\Extensions\Core\GamePlay;

use MyDramGames\Core\Exceptions\GamePlayException;
use MyDramGames\Core\GameBox\GameBoxGeneric;
use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GameInvite\GameInviteGeneric;
use MyDramGames\Core\GameOption\GameOptionCollectionPowered;
use MyDramGames\Core\GameOption\GameOptionConfigurationCollectionPowered;
use MyDramGames\Core\GameOption\GameOptionConfigurationGeneric;
use MyDramGames\Core\GameOption\GameOptionValueCollectionPowered;
use MyDramGames\Core\GameOption\Values\GameOptionValueAutostartGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueForfeitAfterGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueNumberOfPlayersGeneric;
use MyDramGames\Core\GamePlay\GamePlay;
use MyDramGames\Core\GamePlay\GamePlayStorableBase;
use MyDramGames\Core\GamePlay\Services\GamePlayServicesProviderGeneric;
use MyDramGames\Core\GamePlay\Storage\GamePlayStorage;
use MyDramGames\Core\GamePlay\Storage\GamePlayStorageInMemory;
use MyDramGames\Core\GameRecord\GameRecordCollectionPowered;
use MyDramGames\Games\Thousand\Extensions\Core\Exceptions\GamePlayThousandException;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandDeclaration;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandPlayCard;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandSorting;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousandStockDistribution;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPointsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandNumberOfBombsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandBidding;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCollectTricks;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandCountPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandDeclaration;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayFirstCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlaySecondCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandPlayThirdCard;
use MyDramGames\Games\Thousand\Extensions\Core\GamePhase\GamePhaseThousandStockDistribution;
use MyDramGames\Games\Thousand\Extensions\Core\GamePlay\GamePlayThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameSetup\GameSetupThousand;
use MyDramGames\Games\Thousand\Extensions\Utils\ThousandDeckProvider;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardDealerGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardFactoryGeneric;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollectionPoweredUnique;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDealer;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDeckProvider;
use MyDramGames\Utils\Php\Collection\CollectionEnginePhpArray;
use MyDramGames\Utils\Player\Player;
use MyDramGames\Utils\Player\PlayerCollection;
use MyDramGames\Utils\Player\PlayerCollectionPowered;
use PHPUnit\Framework\TestCase;
use Tests\TestingHelper;

class GamePlayThousandTest extends TestCase
{
    private GamePlayThousand $play;

    private GameInvite $invite;
    private GamePlayStorage $storage;
    private PlayerCollection $players;

    private GamePhaseThousand $phase;
    private PlayingCardDeckProvider $deckProvider;

    public function setUp(): void
    {
        $this->players = $this->getPlayers(true);
        $this->invite = $this->getGameInvite();
        $this->storage = $this->getGamePlayStorage();
        $this->play = $this->getGamePlay();

        $this->phase = new GamePhaseThousandBidding();
        $this->deckProvider = new ThousandDeckProvider(
            new PlayingCardCollectionPoweredUnique(),
            new PlayingCardFactoryGeneric(),
        );
    }

    protected function getPlayers(bool $fourPlayers = false): PlayerCollection
    {
        $players = new PlayerCollectionPowered();

        for ($i = 1; $i <= ($fourPlayers ? 4 : 3); $i++) {
            $player = $this->createMock(Player::class);
            $player->method('getId')->willReturn($i);
            $player->method('getName')->willReturn("Player $i");

            $players->add($player);
        }

        return $players;
    }

    protected function getGameInvite(bool $fourPlayers = false, bool $barrel = true): GameInvite
    {
        $setup = new GameSetupThousand(new GameOptionCollectionPowered(), new GameOptionValueCollectionPowered());
        $box = new GameBoxGeneric('thousand', 'Thousand', $setup, true, false, null, null, null);
        $configurations = new GameOptionConfigurationCollectionPowered(null, [
            new GameOptionConfigurationGeneric(
                'numberOfPlayers',
                $fourPlayers
                    ? GameOptionValueNumberOfPlayersGeneric::Players004
                    : GameOptionValueNumberOfPlayersGeneric::Players003
            ),
            new GameOptionConfigurationGeneric('autostart', GameOptionValueAutostartGeneric::Disabled),
            new GameOptionConfigurationGeneric('forfeitAfter', GameOptionValueForfeitAfterGeneric::Disabled),
            new GameOptionConfigurationGeneric(
                'thousand-barrel-points',
                $barrel
                    ? GameOptionValueThousandBarrelPointsGeneric::EightHundred
                    : GameOptionValueThousandBarrelPointsGeneric::Disabled
            ),
            new GameOptionConfigurationGeneric('thousand-number-of-bombs', GameOptionValueThousandNumberOfBombsGeneric::One),
        ]);

        $invite = new GameInviteGeneric(1, $box, $configurations, $this->players->clone());
        $invite->addPlayer($this->players->getOne(1), true);
        $invite->addPlayer($this->players->getOne(2));
        $invite->addPlayer($this->players->getOne(3));
        if ($fourPlayers) {
            $invite->addPlayer($this->players->getOne(4));
        }

        return $invite;
    }

    protected function getGamePlayStorage(): GamePlayStorage
    {
        return new GamePlayStorageInMemory($this->invite);
    }

    protected function getGamePlay(): GamePlayThousand
    {
        return new GamePlayThousand($this->storage, new GamePlayServicesProviderGeneric(
            new CollectionEnginePhpArray(),
            new PlayerCollectionPowered(),
            TestingHelper::getGameRecordFactory(),
            new GameRecordCollectionPowered(),
        ));
    }

    protected function restartForFourPlayers(): void
    {
        $this->invite = $this->getGameInvite(true);
        $this->storage = $this->getGamePlayStorage();
        $this->play = $this->getGamePlay();
    }

    protected function getHand(Player $player): array
    {
        $situation = $this->play->getSituation($player);
        return $situation['orderedPlayers'][$player->getName()]['hand'];
    }

    protected function getDealNoMarriage(): array
    {
        return [
            ['A-H', 'K-H', 'J-H', '10-H', '9-H', 'A-S', 'K-S'],
            ['A-D', 'K-D', 'J-D', '10-D', '9-D', 'Q-S', 'J-S'],
            ['A-C', 'K-C', 'J-C', '10-C', '9-C', '10-S', '9-S'],
            ['Q-H', 'Q-D', 'Q-C'],
        ];
    }

    protected function getDealMarriages(): array
    {
        return [
            ['A-H', 'K-H', 'Q-H', 'J-H', '10-H', '9-H', 'A-S'],
            ['A-D', 'K-D', 'Q-D', 'J-D', '10-D', '9-D', 'K-S'],
            ['A-C', 'K-C', 'Q-C', 'J-C', '10-C', '9-C', 'Q-S'],
            ['J-S', '10-S', '9-S']
        ];
    }

    protected function getDealTwoAcesInStock(): array
    {
        return [
            ['Q-H', 'K-H', 'J-H', '10-H', '9-H', 'A-S', 'K-S'],
            ['Q-D', 'K-D', 'J-D', '10-D', '9-D', 'Q-S', 'J-S'],
            ['A-C', 'K-C', 'J-C', '10-C', '9-C', '10-S', '9-S'],
            ['A-H', 'A-D', 'Q-C'],
        ];
    }

    protected function getDealMarriageInStock(): array
    {
        return [
            ['A-H', 'K-H', 'J-H', '10-H', '9-H', 'A-S', 'Q-D'],
            ['A-D', 'K-D', 'J-D', '10-D', '9-D', 'Q-H', 'J-S'],
            ['A-C', 'K-C', 'J-C', '10-C', '9-C', '10-S', '9-S'],
            ['K-S', 'Q-S', 'Q-C'],
        ];
    }

    protected function updateGameData(array $overwrite): void
    {
        $this->storage->setGameData(array_merge($this->storage->getGameData(), $overwrite));
        $this->play = $this->getGamePlay();
    }

    protected function updateGamePlayDeal(callable $getDeal): void
    {
        $data = $this->storage->getGameData();

        $numberOfPlayers = $this->play->getGameInvite()->getGameSetup()->getNumberOfPlayers()->getConfiguredValue()->getValue();

        if ($numberOfPlayers === 3) {
            $activePlayersNames = [
                $this->players->getOne(1)->getName(),
                $this->players->getOne(2)->getName(),
                $this->players->getOne(3)->getName(),
            ];
        } else {
            $activePlayers = $this->players->filter(fn($player) => $player->getName() !== $data['dealer'])->toArray();
            $activePlayersNames = array_values(array_map(fn($player) => $player->getName(), $activePlayers));
        }

        [$handOne, $handTwo, $handThree, $stock] = $getDeal();

        $data['orderedPlayers'][$activePlayersNames[0]]['hand'] = $handOne;
        $data['orderedPlayers'][$activePlayersNames[1]]['hand'] = $handTwo;
        $data['orderedPlayers'][$activePlayersNames[2]]['hand'] = $handThree;
        $data['stock'] = $stock;

        $this->storage->setGameData($data);
        $this->play = $this->getGamePlay();
    }

    protected function getPlayerByName(string $playerName): Player
    {
        return $this->players->filter(fn($player) => $player->getName() === $playerName)->pullFirst();
    }

    protected function processPhaseBidding(bool $fourPlayers = false, int $bidUntil = 110): void
    {
        if ($bidUntil >= 110) {
            for ($bidAmount = 110; $bidAmount <= $bidUntil; $bidAmount += 10) {
                $this->play->handleMove(new GameMoveThousandBidding(
                    $this->play->getActivePlayer(),
                    ['decision' => 'bid', 'bidAmount' => $bidAmount],
                    new GamePhaseThousandBidding()
                ));
            }
        }

        for ($i = 1; $i <= ($fourPlayers ? 4 : 3) - 1; $i++) {
            $this->play->handleMove(new GameMoveThousandBidding(
                $this->play->getActivePlayer(),
                ['decision' => 'pass'],
                new GamePhaseThousandBidding()
            ));
        }
    }

    protected function processPhaseStockDistribution(bool $fourPlayers = false, ?array $cards = null): void
    {
        $bidWinnerName = $this->play->getSituation($this->players->getOne(1))['bidWinner'];
        $bidWinner = $this->getPlayerByName($bidWinnerName);
        $situation = $this->play->getSituation($bidWinner);

        $distributionPlayerNames = array_filter(
            array_keys($situation['orderedPlayers']),
            fn($playerName) => $playerName !== $bidWinnerName && (!$fourPlayers || $playerName !== $situation['dealer'])
        );

        $distributionPlayerName1 = array_pop($distributionPlayerNames);
        $distributionPlayerName2 = array_pop($distributionPlayerNames);

        $binWinnerHand = $situation['orderedPlayers'][$bidWinnerName]['hand'];
        $distributionCards = $cards ?? ((in_array('K-H', $binWinnerHand)
            ? ['J-H', '9-H']
            : (in_array('K-D', $binWinnerHand)
                ? ['J-D', '9-D']
                : ['J-C', '9-C']
            )
        ));

        $distribution = ['distribution' => [
            $distributionPlayerName1 => $distributionCards[0],
            $distributionPlayerName2 => $distributionCards[1],
        ]];

        $this->play->handleMove(new GameMoveThousandStockDistribution(
            $bidWinner,
            $distribution,
            new GamePhaseThousandStockDistribution()
        ));
    }

    protected function processPhaseDeclaration(int $increaseBy = 0): void
    {
        $player = $this->play->getActivePlayer();
        $bidAmount = $this->play->getSituation($player)['bidAmount'];

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => ($bidAmount + $increaseBy)],
            new GamePhaseThousandDeclaration()
        ));
    }

    protected function processPhaseCollectTricks(Player $player): void
    {
        $this->play->handleMove(new GameMoveThousandCollectTricks(
            $player,
            ['collect' => true],
            new GamePhaseThousandCollectTricks(),
        ));
    }

    protected function processPhasePlayCard(): void
    {
        $orderedPlayers = $this->storage->getGameData()['orderedPlayers'];
        $hands = [];
        foreach ($orderedPlayers as $playerName => $data) {
            $hands[$playerName] = $data['hand'];
        }

        $phases = [
            1 => new GamePhaseThousandPlayFirstCard(),
            2 => new GamePhaseThousandPlaySecondCard(),
            3 => new GamePhaseThousandPlayThirdCard(),
        ];

        for ($i = 0; $i <= 7; $i++) {
            for ($phaseNumber = 1; $phaseNumber <= 3; $phaseNumber++) {

                $playerName = $this->play->getActivePlayer()->getName();
                $card = $hands[$playerName][$i];

                $this->play->handleMove(new GameMoveThousandPlayCard(
                    $this->play->getActivePlayer(),
                    ['card' => $card, 'marriage' => ($i === 1 && $phaseNumber === 1)],
                    $phases[$phaseNumber]
                ));
            }
            $this->processPhaseCollectTricks($this->play->getActivePlayer());
        }
    }

    // TEST SCENARIOS START

    public function testConstructor(): void
    {
        $this->assertInstanceOf(GamePlay::class, $this->play);
        $this->assertInstanceOf(GamePlayStorableBase::class, $this->play);
    }

    public function testGetSituationThrowExceptionWhenNotPlayer(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_NOT_PLAYER);

        $this->play->getSituation($this->createMock(Player::class));
    }

    public function testGetSituationAfterInitiationForThreePlayers(): void
    {
        $expectedPlayersNames = array_map(fn($player) => $player->getName(), $this->play->getPlayers()->toArray());
        $situation = $this->play->getSituation($this->players->getOne(1));

        // three players available
        $this->assertCount(3, $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(1)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(2)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(3)->getName(), $situation['orderedPlayers']);

        // player see his cards and not other players cards
        $this->assertCount(7, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['hand']);
        $this->assertEquals(7, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']);
        $this->assertEquals(7, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']);

        // player see his and other players tricks count but not cards
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['tricks']);

        // player see his and other players trumps count empty
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['trumps']);

        // players see stock count but not cards
        $this->assertEquals(3, $situation['stock']);

        // all players barrel false
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['barrel']);

        // all players ready true
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['ready']);

        // all players points []
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points']);

        // all players bombRounds []
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['bombRounds']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['bombRounds']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['bombRounds']);

        // all players bid null, except obligation player bid 100
        $this->assertEquals(
            ($this->players->getOne(1)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(2)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(3)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['bid']
        );

        // all players have different seat position
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat']);
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat']);
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat']);
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat']
        );
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat']
        );
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat']
        );

        // seat position reflect player roles
        $this->assertEquals(1, $situation['orderedPlayers'][$situation['dealer']]['seat']);
        $this->assertEquals(2, $situation['orderedPlayers'][$situation['obligation']]['seat']);
        $this->assertEquals(3, $situation['orderedPlayers'][$situation['activePlayer']]['seat']);

        // table empty
        $this->assertEquals([], $situation['table']);

        // trump suit null
        $this->assertNull($situation['trumpSuit']);

        // turn suit null
        $this->assertNull($situation['turnSuit']);

        // turn lead null
        $this->assertNull($situation['turnLead']);

        // bid winner null
        $this->assertNull($situation['bidWinner']);

        // bid amount 100
        $this->assertEquals(100, $situation['bidAmount']);

        // stockRecord empty
        $this->assertCount(0, $situation['stockRecord']);

        // active player <> obligation <> dealer and within 3 players
        $this->assertTrue(in_array($situation['dealer'], $expectedPlayersNames));
        $this->assertTrue(in_array($situation['obligation'], $expectedPlayersNames));
        $this->assertTrue(in_array($situation['activePlayer'], $expectedPlayersNames));
        $this->assertNotEquals($situation['dealer'], $situation['obligation']);
        $this->assertNotEquals($situation['dealer'], $situation['activePlayer']);
        $this->assertNotEquals($situation['obligation'], $situation['activePlayer']);

        // round 1
        $this->assertEquals(1, $situation['round']);

        // phase attributes equal to specific phase methods (check 3)
        $this->assertEquals($this->phase->getKey(), $situation['phase']['key']);
        $this->assertEquals($this->phase->getName(), $situation['phase']['name']);
        $this->assertEquals($this->phase->getDescription(), $situation['phase']['description']);

        // is Finished false
        $this->assertFalse($situation['isFinished']);

        // result not available
        $this->assertArrayNotHasKey('result', $situation);
    }

    public function testGetSituationAfterInitiationForFourPlayers(): void
    {
        $this->restartForFourPlayers();

        $expectedPlayersNames = array_map(fn($player) => $player->getName(), $this->play->getPlayers()->toArray());
        $situation = $this->play->getSituation($this->players->getOne(1));

        // three players available
        $this->assertCount(4, $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(1)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(2)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(3)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(4)->getName(), $situation['orderedPlayers']);

        // player see his cards and not other players cards
        $this->assertCount(
            $situation['dealer'] === $this->players->getOne(1)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['hand']
        );
        $this->assertEquals(
            $situation['dealer'] === $this->players->getOne(2)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']
        );
        $this->assertEquals(
            $situation['dealer'] === $this->players->getOne(3)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']
        );
        $this->assertEquals(
            $situation['dealer'] === $this->players->getOne(4)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['hand']
        );

        // player see his and other players tricks count but not cards
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['tricks']);

        // player see his and other players trumps count empty
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['trumps']);

        // players see stock count but not cards
        $this->assertEquals(3, $situation['stock']);

        // all players barrel false
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(4)->getName()]['barrel']);

        // all players ready true
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(4)->getName()]['ready']);

        // all players points []
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['points']);

        // all players bombRounds []
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['bombRounds']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['bombRounds']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['bombRounds']);
        $this->assertEquals([], $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['bombRounds']);

        // all players bid null, except obligation player bid 100
        $this->assertEquals(
            ($this->players->getOne(1)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(2)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(3)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(4)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['bid']
        );

        // all players have different seat position
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat']);
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat']);
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat']);
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat']
        );
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat']
        );
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat']
        );
        $this->assertIsInt($situation['orderedPlayers'][$this->players->getOne(4)->getName()]['seat']);
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['seat']
        );
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['seat']
        );
        $this->assertNotEquals(
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['seat'],
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['seat']
        );

        // seat position reflect player roles
        $this->assertEquals(1, $situation['orderedPlayers'][$situation['dealer']]['seat']);
        $this->assertEquals(2, $situation['orderedPlayers'][$situation['obligation']]['seat']);
        $this->assertEquals(3, $situation['orderedPlayers'][$situation['activePlayer']]['seat']);

        // table empty
        $this->assertEquals([], $situation['table']);

        // trump suit null
        $this->assertNull($situation['trumpSuit']);

        // turn suit null
        $this->assertNull($situation['turnSuit']);

        // turn lead null
        $this->assertNull($situation['turnLead']);

        // bid winner null
        $this->assertNull($situation['bidWinner']);

        // bid amount 100
        $this->assertEquals(100, $situation['bidAmount']);

        // stockRecord empty
        $this->assertCount(0, $situation['stockRecord']);

        // active player <> obligation <> dealer and within 3 players
        $this->assertTrue(in_array($situation['dealer'], $expectedPlayersNames));
        $this->assertTrue(in_array($situation['obligation'], $expectedPlayersNames));
        $this->assertTrue(in_array($situation['activePlayer'], $expectedPlayersNames));
        $this->assertNotEquals($situation['dealer'], $situation['obligation']);
        $this->assertNotEquals($situation['dealer'], $situation['activePlayer']);
        $this->assertNotEquals($situation['obligation'], $situation['activePlayer']);

        // round 1
        $this->assertEquals(1, $situation['round']);

        // phase attributes equal to specific phase methods (check 3)
        $this->assertEquals($this->phase->getKey(), $situation['phase']['key']);
        $this->assertEquals($this->phase->getName(), $situation['phase']['name']);
        $this->assertEquals($this->phase->getDescription(), $situation['phase']['description']);

        // is Finished false
        $this->assertFalse($situation['isFinished']);

        // result not available
        $this->assertArrayNotHasKey('result', $situation);
    }

    public function testHandleMoveThrowExceptionOnFinishedGame(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_MOVE_ON_FINISHED_GAME);

        $this->storage->setFinished();
        $this->play->handleMove($this->createMock(GameMoveThousand::class));
    }

    public function testGetSituationAfterSortingCards(): void
    {
        $player = $this->players->getOne(1);
        $currentSituationPlayerOne = $this->play->getSituation($player);
        $currentSituationPlayerTwo = $this->play->getSituation($this->players->getOne(2));
        $currentSituationPlayerThree = $this->play->getSituation($this->players->getOne(3));

        $hand = $this->getHand($player);

        $currentKeys = array_values($hand);
        while ($currentKeys === array_values($hand)) {
            shuffle($hand);
        }

        $this->play->handleMove(new GameMoveThousandSorting($player, ['hand' => $hand]));

        $newSituationPlayerOne = $this->play->getSituation($player);
        $newSituationPlayerTwo = $this->play->getSituation($this->players->getOne(2));
        $newSituationPlayerThree = $this->play->getSituation($this->players->getOne(3));
        $newHand = $this->getHand($player);

        unset($currentSituationPlayerOne['orderedPlayers'][$player->getName()]['hand']);
        unset($currentSituationPlayerTwo['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']);
        unset($currentSituationPlayerThree['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']);
        unset($newSituationPlayerOne['orderedPlayers'][$player->getName()]['hand']);
        unset($newSituationPlayerTwo['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']);
        unset($newSituationPlayerThree['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']);

        $this->assertEquals($hand, $newHand);

        $this->assertEquals($currentSituationPlayerOne, $newSituationPlayerOne);
        $this->assertEquals($currentSituationPlayerTwo, $newSituationPlayerTwo);
        $this->assertEquals($currentSituationPlayerThree, $newSituationPlayerThree);
    }

    public function testHandleMoveBiddingThrowExceptionWhenNotPlayerTurn(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_NOT_CURRENT_PLAYER);

        $player = $this->play->getActivePlayer()->getId() === $this->players->getOne(1)->getId() ? $this->players->getOne(2) : $this->players->getOne(1);

        $this->play->handleMove(new GameMoveThousandBidding(
            $player,
            ['decision' => 'bid', 'bidAmount' => 110],
            new GamePhaseThousandBidding()
        ));
    }

    public function testHandleMoveOtherThanSortingMoveThrowExceptionWhenInWrongPhase(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);

        $data = $this->storage->getGameData();
        $phase = new GamePhaseThousandDeclaration();
        $data['phase'] = [
            'key' => $phase->getKey(),
            'name' => $phase->getName(),
            'description' => $phase->getDescription(),
        ];

        $this->storage->setGameData($data);
        $this->play = $this->getGamePlay();

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'bid', 'bidAmount' => 110],
            new GamePhaseThousandBidding()
        ));
    }

    public function testBidStepOtherThanTenThrowException(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_BID_STEP_INVALID);

        $player = $this->play->getActivePlayer();
        $bidAmount = $this->play->getSituation($player)['bidAmount'] + 11;

        $this->play->handleMove(new GameMoveThousandBidding(
            $player,
            ['decision' => 'bid', 'bidAmount' => $bidAmount],
            new GamePhaseThousandBidding()
        ));
    }

    public function testBidOver120ThrowExceptionWithoutMarriageAtHand(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_BID_NO_MARRIAGE);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);

        for ($i = 110; $i <= 130; $i = $i + 10) {
            $this->play->handleMove(new GameMoveThousandBidding(
                $this->play->getActivePlayer(),
                ['decision' => 'bid', 'bidAmount' => $i],
                new GamePhaseThousandBidding()
            ));
        }
    }

    public function testThrowExceptionWhenBiddingAfterPassing(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_NOT_CURRENT_PLAYER);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);

        $player2pass = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandBidding(
            $player2pass,
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $player3bid = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandBidding(
            $player3bid,
            ['decision' => 'bid', 'bidAmount' => 110],
            new GamePhaseThousandBidding()
        ));

        $player1bid = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandBidding(
            $player1bid,
            ['decision' => 'bid', 'bidAmount' => 120],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $player2pass,
            ['decision' => 'bid', 'bidAmount' => 130],
            new GamePhaseThousandBidding()
        ));
    }

    public function testGetSituationAfterBiddingFinishedAt300(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);

        $initialSituation0 = $this->play->getSituation($this->players->getOne(1));
        $initialSituation1 = $this->play->getSituation($this->players->getOne(2));
        $initialSituation2 = $this->play->getSituation($this->players->getOne(3));

        for ($i = 110; $i <= 300; $i = $i + 10) {
            $bidWinner = $this->play->getActivePlayer();

            $lastBiddingSituation0 = $this->play->getSituation($this->players->getOne(1));
            $lastBiddingSituation1 = $this->play->getSituation($this->players->getOne(2));
            $lastBiddingSituation2 = $this->play->getSituation($this->players->getOne(3));
            $lastBiddingPhase = $lastBiddingSituation0['phase'];
            $lastBiddingBidAmount = $lastBiddingSituation0['bidAmount'];

            $this->play->handleMove(new GameMoveThousandBidding(
                $bidWinner,
                ['decision' => 'bid', 'bidAmount' => $i],
                new GamePhaseThousandBidding()
            ));
        }

        $setsToRemoveIncomparableData = [
            &$initialSituation0,
            &$initialSituation1,
            &$initialSituation2,
            &$lastBiddingSituation0,
            &$lastBiddingSituation1,
            &$lastBiddingSituation2,
        ];

        foreach ($setsToRemoveIncomparableData as &$set) {
            unset($set['bidWinner'], $set['bidAmount'], $set['activePlayer']);
            foreach ($set['orderedPlayers'] as &$orderedPlayer) {
                unset($orderedPlayer['bid']);
            }
        }

        $finalSituation0 = $this->play->getSituation($this->players->getOne(1));
        $finalSituation1 = $this->play->getSituation($this->players->getOne(2));
        $finalSituation2 = $this->play->getSituation($this->players->getOne(3));

        $bidWinnerHand = $this->play->getSituation($bidWinner)['orderedPlayers'][$bidWinner->getName()]['hand'];

        $this->assertEquals((new GamePhaseThousandBidding())->getKey(), $lastBiddingPhase['key']);
        $this->assertEquals(290, $lastBiddingBidAmount);
        $this->assertEquals($initialSituation0, $lastBiddingSituation0);
        $this->assertEquals($initialSituation1, $lastBiddingSituation1);
        $this->assertEquals($initialSituation2, $lastBiddingSituation2);

        $this->assertEquals((new GamePhaseThousandStockDistribution())->getKey(), $finalSituation0['phase']['key']);
        $this->assertEquals($bidWinner->getName(), $finalSituation0['bidWinner']);
        $this->assertEquals(300, $finalSituation0['bidAmount']);
        $this->assertEquals(0, $finalSituation0['stock']);
        $this->assertEquals($bidWinner->getName(), $finalSituation0['activePlayer']);
        $this->assertCount(0, $lastBiddingSituation0['stockRecord']);
        $this->assertCount(3, $finalSituation0['stockRecord']);
        $this->assertCount(0, array_diff($finalSituation0['stockRecord'], $bidWinnerHand));
        $this->assertNull($finalSituation0['orderedPlayers'][$this->players->getOne(1)->getName()]['bid']);
        $this->assertNull($finalSituation0['orderedPlayers'][$this->players->getOne(2)->getName()]['bid']);
        $this->assertNull($finalSituation0['orderedPlayers'][$this->players->getOne(3)->getName()]['bid']);
        $this->assertNull($finalSituation1['orderedPlayers'][$this->players->getOne(1)->getName()]['bid']);
        $this->assertNull($finalSituation1['orderedPlayers'][$this->players->getOne(2)->getName()]['bid']);
        $this->assertNull($finalSituation1['orderedPlayers'][$this->players->getOne(3)->getName()]['bid']);
        $this->assertNull($finalSituation2['orderedPlayers'][$this->players->getOne(1)->getName()]['bid']);
        $this->assertNull($finalSituation2['orderedPlayers'][$this->players->getOne(2)->getName()]['bid']);
        $this->assertNull($finalSituation2['orderedPlayers'][$this->players->getOne(3)->getName()]['bid']);

        $this->assertCount(
            $bidWinner->getName() === $this->players->getOne(1)->getName() ? 10 : 7,
            $finalSituation0['orderedPlayers'][$this->players->getOne(1)->getName()]['hand']
        );
        $this->assertCount(
            $bidWinner->getName() === $this->players->getOne(2)->getName() ? 10 : 7,
            $finalSituation1['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']
        );
        $this->assertCount(
            $bidWinner->getName() === $this->players->getOne(3)->getName() ? 10 : 7,
            $finalSituation2['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']
        );
    }

    public function testGetSituationAfterBiddingFinishedNoBidIncrease(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $this->play->handleMove(new GameMoveThousandBidding(
                $this->play->getActivePlayer(),
                ['decision' => 'pass'],
                new GamePhaseThousandBidding()
            ));
        }
        $situation = $this->play->getSituation($this->players->getOne(1));

        $this->assertEquals((new GamePhaseThousandStockDistribution())->getKey(), $situation['phase']['key']);
        $this->assertEquals(0, $situation['stock']);
        $this->assertCount(0, $situation['stockRecord']);
        $this->assertEquals(100, $situation['bidAmount']);
        $this->assertEquals($situation['obligation'], $situation['bidWinner']);
        $this->assertEquals($situation['obligation'], $situation['activePlayer']);

        $this->assertEquals(
            $situation['obligation'] === $this->players->getOne(1)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(1))['orderedPlayers'][$this->players->getOne(1)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['obligation'] === $this->players->getOne(2)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(2))['orderedPlayers'][$this->players->getOne(2)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['obligation'] === $this->players->getOne(3)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(3))['orderedPlayers'][$this->players->getOne(3)->getName()]['hand'])
        );
    }

    public function testGetSituationAfterBiddingThirdWinAt110(): void
    {
        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $bidWinner = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandBidding(
            $bidWinner,
            ['decision' => 'bid', 'bidAmount' => 110],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $situation = $this->play->getSituation($bidWinner);

        $this->assertEquals((new GamePhaseThousandStockDistribution())->getKey(), $situation['phase']['key']);
        $this->assertEquals(0, $situation['stock']);
        $this->assertCount(3, $situation['stockRecord']);
        $this->assertEquals(110, $situation['bidAmount']);
        $this->assertNotEquals($situation['obligation'], $situation['bidWinner']);
        $this->assertCount(10, $situation['orderedPlayers'][$bidWinner->getName()]['hand']);
        $this->assertEquals($bidWinner->getName(), $situation['activePlayer']);

        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(1)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(1))['orderedPlayers'][$this->players->getOne(1)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(2)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(2))['orderedPlayers'][$this->players->getOne(2)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(3)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(3))['orderedPlayers'][$this->players->getOne(3)->getName()]['hand'])
        );
    }

    public function testGetSituationAfterBiddingThirdWinAt120(): void
    {
        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'bid', 'bidAmount' => 110],
            new GamePhaseThousandBidding()
        ));

        $bidWinner = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandBidding(
            $bidWinner,
            ['decision' => 'bid', 'bidAmount' => 120],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $situation = $this->play->getSituation($bidWinner);

        $this->assertEquals((new GamePhaseThousandStockDistribution())->getKey(), $situation['phase']['key']);
        $this->assertEquals(0, $situation['stock']);
        $this->assertCount(3, $situation['stockRecord']);
        $this->assertEquals(120, $situation['bidAmount']);
        $this->assertNotEquals($situation['obligation'], $situation['bidWinner']);
        $this->assertCount(10, $situation['orderedPlayers'][$bidWinner->getName()]['hand']);
        $this->assertEquals($bidWinner->getName(), $situation['activePlayer']);

        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(1)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(1))['orderedPlayers'][$this->players->getOne(1)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(2)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(2))['orderedPlayers'][$this->players->getOne(2)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(3)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(3))['orderedPlayers'][$this->players->getOne(3)->getName()]['hand'])
        );
    }

    public function testGetSituationAfterBiddingFirstWinAt130(): void
    {

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'bid', 'bidAmount' => 110],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'bid', 'bidAmount' => 120],
            new GamePhaseThousandBidding()
        ));

        $bidWinner = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandBidding(
            $bidWinner,
            ['decision' => 'bid', 'bidAmount' => 130],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $this->play->handleMove(new GameMoveThousandBidding(
            $this->play->getActivePlayer(),
            ['decision' => 'pass'],
            new GamePhaseThousandBidding()
        ));

        $situation = $this->play->getSituation($bidWinner);

        $this->assertEquals((new GamePhaseThousandStockDistribution())->getKey(), $situation['phase']['key']);
        $this->assertEquals(0, $situation['stock']);
        $this->assertCount(3, $situation['stockRecord']);
        $this->assertEquals(130, $situation['bidAmount']);
        $this->assertEquals($situation['obligation'], $situation['bidWinner']);
        $this->assertCount(10, $situation['orderedPlayers'][$bidWinner->getName()]['hand']);

        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(1)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(1))['orderedPlayers'][$this->players->getOne(1)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(2)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(2))['orderedPlayers'][$this->players->getOne(2)->getName()]['hand'])
        );
        $this->assertEquals(
            $situation['bidWinner'] === $this->players->getOne(3)->getName() ? 10 : 7,
            count($this->play->getSituation($this->players->getOne(3))['orderedPlayers'][$this->players->getOne(3)->getName()]['hand'])
        );
    }

    public function testGetSituationAfterBiddingFewTimesFourPlayers(): void
    {
        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);

        $activePlayersNames = [];
        for ($i = 110; $i <= 200; $i += 10) {
            $activePlayer = $this->play->getActivePlayer();
            $activePlayersNames[] = $activePlayer->getName();
            $this->play->handleMove(new GameMoveThousandBidding(
                $activePlayer,
                ['decision' => 'bid', 'bidAmount' => $i],
                new GamePhaseThousandBidding()
            ));
        }

        $dealerName = $this->play->getSituation($this->play->getActivePlayer())['dealer'];

        $this->assertFalse(in_array($dealerName, $activePlayersNames, true));
    }

    public function testThrowExceptionWhenHandleMoveStockDistributionToSelf(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();

        $bidWinnerName = $this->play->getSituation($this->players->getOne(1))['bidWinner'];
        $bidWinner = $this->getPlayerByName($bidWinnerName);
        $situation = $this->play->getSituation($bidWinner);

        $distribution = ['distribution' => [
            $bidWinnerName => $situation['orderedPlayers'][$bidWinnerName]['hand'][0],
            (
                $this->players->getOne(1)->getName() !== $bidWinnerName
                    ? $this->players->getOne(1)->getName()
                    : $this->players->getOne(2)->getName()
            ) => $situation['orderedPlayers'][$bidWinnerName]['hand'][1],
        ]];

        $this->play->handleMove(new GameMoveThousandStockDistribution(
            $bidWinner,
            $distribution,
            new GamePhaseThousandStockDistribution()
        ));
    }

    public function testThrowExceptionWhenHandleMoveStockDistributionSameCard(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();

        $bidWinnerName = $this->play->getSituation($this->players->getOne(1))['bidWinner'];
        $bidWinner = $this->getPlayerByName($bidWinnerName);
        $situation = $this->play->getSituation($bidWinner);

        $distributionPlayerNames = array_filter(
            array_keys($situation['orderedPlayers']),
            fn($playerName) => $playerName !== $bidWinnerName
        );

        $distribution = ['distribution' => [
            array_pop($distributionPlayerNames) => $situation['orderedPlayers'][$bidWinnerName]['hand'][0],
            array_pop($distributionPlayerNames) => $situation['orderedPlayers'][$bidWinnerName]['hand'][0],
        ]];

        $this->play->handleMove(new GameMoveThousandStockDistribution(
            $bidWinner,
            $distribution,
            new GamePhaseThousandStockDistribution()
        ));
    }

    public function testHandleMoveStockDistributionThrowExceptionWhenToDealerFourPlayers(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();

        $bidWinnerName = $this->play->getSituation($this->players->getOne(1))['bidWinner'];
        $bidWinner = $this->getPlayerByName($bidWinnerName);
        $situation = $this->play->getSituation($bidWinner);

        $distributionPlayerNames = array_filter(
            array_keys($situation['orderedPlayers']),
            fn($playerName) => $playerName !== $bidWinnerName && $playerName !== $situation['dealer']
        );

        $distribution = ['distribution' => [
            array_pop($distributionPlayerNames) => $situation['orderedPlayers'][$bidWinnerName]['hand'][0],
            $situation['dealer'] => $situation['orderedPlayers'][$bidWinnerName]['hand'][1],
        ]];

        $this->play->handleMove(new GameMoveThousandStockDistribution(
            $bidWinner,
            $distribution,
            new GamePhaseThousandStockDistribution()
        ));
    }

    public function testThrowExceptionWhenHandleMoveStockDistributionCardsNotInHand(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();

        $bidWinnerName = $this->play->getSituation($this->players->getOne(1))['bidWinner'];
        $bidWinner = $this->getPlayerByName($bidWinnerName);
        $situation = $this->play->getSituation($bidWinner);

        $distributionPlayerNames = array_filter(
            array_keys($situation['orderedPlayers']),
            fn($playerName) => $playerName !== $bidWinnerName
        );

        $distributionPlayerOne = array_pop($distributionPlayerNames);
        $situationNotWinner = $this->play->getSituation($this->getPlayerByName($distributionPlayerOne));

        $distribution = ['distribution' => [
            $distributionPlayerOne => $situationNotWinner['orderedPlayers'][$distributionPlayerOne]['hand'][0],
            array_pop($distributionPlayerNames) => $situation['orderedPlayers'][$bidWinnerName]['hand'][1],
        ]];

        $this->play->handleMove(new GameMoveThousandStockDistribution(
            $bidWinner,
            $distribution,
            new GamePhaseThousandStockDistribution()
        ));
    }

    public function testGetSituationAfterStockDistributionMove(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();

        $bidWinnerName = $this->play->getSituation($this->players->getOne(1))['bidWinner'];
        $bidWinner = $this->getPlayerByName($bidWinnerName);
        $situationSD = $this->play->getSituation($bidWinner);

        $distributionPlayerNames = array_filter(
            array_keys($situationSD['orderedPlayers']),
            fn($playerName) => $playerName !== $bidWinnerName
        );
        $distributionPlayerName1 = array_pop($distributionPlayerNames);
        $distributionPlayerName2 = array_pop($distributionPlayerNames);

        $distribution = ['distribution' => [
            $distributionPlayerName1 => $situationSD['orderedPlayers'][$bidWinnerName]['hand'][0],
            $distributionPlayerName2 => $situationSD['orderedPlayers'][$bidWinnerName]['hand'][1],
        ]];

        $this->play->handleMove(new GameMoveThousandStockDistribution(
            $bidWinner,
            $distribution,
            new GamePhaseThousandStockDistribution()
        ));

        $situation = $this->play->getSituation($bidWinner);

        $this->assertCount(8, $situation['orderedPlayers'][$bidWinnerName]['hand']);
        $this->assertEquals(8, $situation['orderedPlayers'][$distributionPlayerName1]['hand']);
        $this->assertEquals(8, $situation['orderedPlayers'][$distributionPlayerName2]['hand']);
        $this->assertCount(3, $situation['stockRecord']);
        $this->assertEquals($bidWinnerName, $situation['activePlayer']);
        $this->assertEquals((new GamePhaseThousandDeclaration())->getKey(), $situation['phase']['key']);
    }

    public function testThrowExceptionHandleMoveDeclarationNotBidWinner(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_NOT_CURRENT_PLAYER);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();

        $situation = $this->play->getSituation($this->players->getOne(1));
        $player = $situation['bidWinner'] === $this->players->getOne(1)->getName() ? $this->players->getOne(2) : $this->players->getOne(1);
        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 120],
            new GamePhaseThousandDeclaration()
        ));
    }

    public function testThrowExceptionWhenHandleMoveDeclarationLowerThanBid(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_WRONG_DECLARATION);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();

        $situation = $this->play->getSituation($this->players->getOne(1));
        $player = $this->getPlayerByName($situation['bidWinner']);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 100],
            new GamePhaseThousandDeclaration()
        ));
    }

    public function testThrowExceptionWhenHandleMoveDeclarationHigherThan300(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_WRONG_DECLARATION);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();

        $situation = $this->play->getSituation($this->players->getOne(1));
        $player = $this->getPlayerByName($situation['bidWinner']);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 310],
            new GamePhaseThousandDeclaration()
        ));
    }

    public function testThrowExceptionWhenHandleMoveDeclarationNot10PointsStep(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_WRONG_DECLARATION);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();

        $situation = $this->play->getSituation($this->players->getOne(1));
        $player = $this->getPlayerByName($situation['bidWinner']);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 125],
            new GamePhaseThousandDeclaration()
        ));
    }

    public function testGetSituationAfterDeclarationMove(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();

        $situationI0 = $this->play->getSituation($this->players->getOne(1));
        $situationI1 = $this->play->getSituation($this->players->getOne(2));
        $situationI2 = $this->play->getSituation($this->players->getOne(3));

        $player = $this->getPlayerByName($situationI0['bidWinner']);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 130],
            new GamePhaseThousandDeclaration()
        ));

        $situationF0 = $this->play->getSituation($this->players->getOne(1));
        $situationF1 = $this->play->getSituation($this->players->getOne(2));
        $situationF2 = $this->play->getSituation($this->players->getOne(3));
        $phaseKey = $situationF1['phase']['key'];
        $turnLead = $situationF1['turnLead'];
        $bidAmount = $situationF1['bidAmount'];
        unset(
            $situationI0['phase'], $situationI0['bidAmount'], $situationI0['turnLead'],
            $situationI1['phase'], $situationI1['bidAmount'], $situationI1['turnLead'],
            $situationI2['phase'], $situationI2['bidAmount'], $situationI2['turnLead'],
            $situationF0['phase'], $situationF0['bidAmount'], $situationF0['turnLead'],
            $situationF1['phase'], $situationF1['bidAmount'], $situationF1['turnLead'],
            $situationF2['phase'], $situationF2['bidAmount'], $situationF2['turnLead'],
        );

        $this->assertEquals((new GamePhaseThousandPlayFirstCard())->getKey(), $phaseKey);
        $this->assertEquals(130, $bidAmount);
        $this->assertEquals($player->getName(), $turnLead);
        $this->assertEquals($situationI0, $situationF0);
        $this->assertEquals($situationI1, $situationF1);
        $this->assertEquals($situationI2, $situationF2);
    }

    public function testThrowExceptionWhenHandleMoveDeclarationBombAfterBidding(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_BOMB_ON_BID);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();

        $situation = $this->play->getSituation($this->players->getOne(1));
        $player = $this->getPlayerByName($situation['bidWinner']);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));
    }

    public function testThrowExceptionWhenHandleMoveDeclarationNoMoreBombs(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_BOMB_USED);

        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution();

        $player = $this->play->getActivePlayer();

        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        $overwrite[$player->getName()]['bombRounds'] = [1];
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));
    }

    public function testGetSituationAfterDeclarationBombMoveRound1(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution();

        $bidWinner = $this->play->getActivePlayer();
        $situationI0 = $this->play->getSituation($this->players->getOne(1));
        $situationI1 = $this->play->getSituation($this->players->getOne(2));
        $situationI2 = $this->play->getSituation($this->players->getOne(3));

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $bidWinner,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        $situationF0 = $this->play->getSituation($this->players->getOne(1));
        $situationF1 = $this->play->getSituation($this->players->getOne(2));
        $situationF2 = $this->play->getSituation($this->players->getOne(3));

        $phase = $situationF0['phase']['key'];
        $points0 = $situationF0['orderedPlayers'][$this->players->getOne(1)->getName()]['points'];
        $points1 = $situationF1['orderedPlayers'][$this->players->getOne(2)->getName()]['points'];
        $points2 = $situationF2['orderedPlayers'][$this->players->getOne(3)->getName()]['points'];
        $ready0 = $situationF0['orderedPlayers'][$this->players->getOne(1)->getName()]['ready'];
        $ready1 = $situationF1['orderedPlayers'][$this->players->getOne(2)->getName()]['ready'];
        $ready2 = $situationF2['orderedPlayers'][$this->players->getOne(3)->getName()]['ready'];
        $bombRounds0 = $situationF0['orderedPlayers'][$this->players->getOne(1)->getName()]['bombRounds'];
        $bombRounds1 = $situationF1['orderedPlayers'][$this->players->getOne(2)->getName()]['bombRounds'];
        $bombRounds2 = $situationF2['orderedPlayers'][$this->players->getOne(3)->getName()]['bombRounds'];

        unset($situationI0['phase'], $situationI1['phase'], $situationI2['phase'], $situationF0['phase'], $situationF1['phase'], $situationF2['phase']);

        for ($i = 0; $i <= 2; $i++) {
            unset(
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
            );
        }

        $this->assertEquals((new GamePhaseThousandCountPoints())->getKey(), $phase);
        $this->assertEquals([1 => $this->players->getOne(1)->getId() === $bidWinner->getId() ? 0 : 60], $points0);
        $this->assertEquals([1 => $this->players->getOne(2)->getId() === $bidWinner->getId() ? 0 : 60], $points1);
        $this->assertEquals([1 => $this->players->getOne(3)->getId() === $bidWinner->getId() ? 0 : 60], $points2);
        $this->assertFalse($ready0);
        $this->assertFalse($ready1);
        $this->assertFalse($ready2);
        $this->assertEquals($this->players->getOne(1)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds0);
        $this->assertEquals($this->players->getOne(2)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds1);
        $this->assertEquals($this->players->getOne(3)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds2);
        $this->assertEquals($situationI0, $situationF0);
        $this->assertEquals($situationI1, $situationF1);
        $this->assertEquals($situationI2, $situationF2);
        $this->assertNull($situationF0['turnLead']);
    }

    public function testGetSituationAfterDeclarationBombMoveRound1FourPlayersNoStockPoints(): void
    {
        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution(true);

        $bidWinner = $this->play->getActivePlayer();
        $situationI0 = $this->play->getSituation($this->players->getOne(1));
        $situationI1 = $this->play->getSituation($this->players->getOne(2));
        $situationI2 = $this->play->getSituation($this->players->getOne(3));
        $situationI3 = $this->play->getSituation($this->players->getOne(4));
        $dealer = $this->getPlayerByName($situationI0['dealer']);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $bidWinner,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        $situationF0 = $this->play->getSituation($this->players->getOne(1));
        $situationF1 = $this->play->getSituation($this->players->getOne(2));
        $situationF2 = $this->play->getSituation($this->players->getOne(3));
        $situationF3 = $this->play->getSituation($this->players->getOne(4));

        $phase = $situationF0['phase']['key'];
        $stockRecord = $situationF0['stockRecord'];
        $points0 = $situationF0['orderedPlayers'][$this->players->getOne(1)->getName()]['points'];
        $points1 = $situationF1['orderedPlayers'][$this->players->getOne(2)->getName()]['points'];
        $points2 = $situationF2['orderedPlayers'][$this->players->getOne(3)->getName()]['points'];
        $points3 = $situationF3['orderedPlayers'][$this->players->getOne(4)->getName()]['points'];
        $ready0 = $situationF0['orderedPlayers'][$this->players->getOne(1)->getName()]['ready'];
        $ready1 = $situationF1['orderedPlayers'][$this->players->getOne(2)->getName()]['ready'];
        $ready2 = $situationF2['orderedPlayers'][$this->players->getOne(3)->getName()]['ready'];
        $ready3 = $situationF3['orderedPlayers'][$this->players->getOne(4)->getName()]['ready'];
        $bombRounds0 = $situationF0['orderedPlayers'][$this->players->getOne(1)->getName()]['bombRounds'];
        $bombRounds1 = $situationF1['orderedPlayers'][$this->players->getOne(2)->getName()]['bombRounds'];
        $bombRounds2 = $situationF2['orderedPlayers'][$this->players->getOne(3)->getName()]['bombRounds'];
        $bombRounds3 = $situationF3['orderedPlayers'][$this->players->getOne(4)->getName()]['bombRounds'];

        unset(
            $situationI0['phase'], $situationI0['stockRecord'],
            $situationI1['phase'], $situationI1['stockRecord'],
            $situationI2['phase'], $situationI2['stockRecord'],
            $situationI3['phase'], $situationI3['stockRecord'],
            $situationF0['phase'], $situationF0['stockRecord'],
            $situationF1['phase'], $situationF1['stockRecord'],
            $situationF2['phase'], $situationF2['stockRecord'],
            $situationF3['phase'], $situationF3['stockRecord'],
        );

        for ($i = 0; $i <= 3; $i++) {
            unset(
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationI3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationI3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationI3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationI3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF0['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF1['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF2['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
                $situationF3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'],
                $situationF3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['ready'],
                $situationF3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['bombRounds'],
                $situationF3['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['hand'],
            );
        }

        $expectedPoints0 = in_array($this->players->getOne(1)->getId(), [$bidWinner->getId(), $dealer->getId()]) ? 0 : 60;
        $expectedPoints1 = in_array($this->players->getOne(2)->getId(), [$bidWinner->getId(), $dealer->getId()]) ? 0 : 60;
        $expectedPoints2 = in_array($this->players->getOne(3)->getId(), [$bidWinner->getId(), $dealer->getId()]) ? 0 : 60;
        $expectedPoints3 = in_array($this->players->getOne(4)->getId(), [$bidWinner->getId(), $dealer->getId()]) ? 0 : 60;

        $this->assertEquals((new GamePhaseThousandCountPoints())->getKey(), $phase);
        $this->assertEquals([1 => $expectedPoints0], $points0);
        $this->assertEquals([1 => $expectedPoints1], $points1);
        $this->assertEquals([1 => $expectedPoints2], $points2);
        $this->assertEquals([1 => $expectedPoints3], $points3);
        $this->assertFalse($ready0);
        $this->assertFalse($ready1);
        $this->assertFalse($ready2);
        $this->assertFalse($ready3);
        $this->assertEquals($this->players->getOne(1)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds0);
        $this->assertEquals($this->players->getOne(2)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds1);
        $this->assertEquals($this->players->getOne(3)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds2);
        $this->assertEquals($this->players->getOne(4)->getId() === $bidWinner->getId() ? [1] : [], $bombRounds3);
        $this->assertEquals($situationI0, $situationF0);
        $this->assertEquals($situationI1, $situationF1);
        $this->assertEquals($situationI2, $situationF2);
        $this->assertEquals($situationI3, $situationF3);
        $this->assertCount(3, $stockRecord);
    }

    public function testGetSituationAfterDeclarationBombMoveNotBidWinnerOnBarrelDontGetPoints(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution();

        $player = $this->play->getActivePlayer();

        $overwrite = $this->storage->getGameData();
        $preparedPoints = [
            [1 => 0, 2 => 0, 3 => 0, 4 => 0],
            [1 => 200, 2 => 400, 3 => 600, 4 => 750],
            [1 => 200, 2 => 410, 3 => 610, 4 => 820],
        ];
        for ($i = 0; $i <= 2; $i++) {
            $overwrite['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'] =
                $this->players->getOne($i + 1)->getID() === $player->getId()
                    ? array_shift($preparedPoints)
                    : array_pop($preparedPoints);
            $overwrite['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['barrel'] =
                $overwrite['orderedPlayers'][$this->players->getOne($i + 1)->getName()]['points'][4] >= 800;
        }
        $overwrite['round'] = 5;
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'round' => $overwrite['round']]);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        $situation = $this->play->getSituation($player);

        $points4R0 = $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points'][4];
        $points4R1 = $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points'][4];
        $points4R2 = $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points'][4];
        $points5R0 = $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points'][5];
        $points5R1 = $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points'][5];
        $points5R2 = $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points'][5];
        $barrel5R0 = $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['barrel'];
        $barrel5R1 = $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['barrel'];
        $barrel5R2 = $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['barrel'];

        $expectedPoints5R0 = $this->players->getOne(1)->getId() === $player->getId() ? $points4R0 : ($points4R0 === 820 ? 820 : $points4R0 + 60);
        $expectedPoints5R1 = $this->players->getOne(2)->getId() === $player->getId() ? $points4R1 : ($points4R1 === 820 ? 820 : $points4R1 + 60);
        $expectedPoints5R2 = $this->players->getOne(3)->getId() === $player->getId() ? $points4R2 : ($points4R2 === 820 ? 820 : $points4R2 + 60);

        $expectedBarrel5R0 = $expectedPoints5R0 >= 800;
        $expectedBarrel5R1 = $expectedPoints5R1 >= 800;
        $expectedBarrel5R2 = $expectedPoints5R2 >= 800;

        $this->assertEquals($expectedPoints5R0, $points5R0);
        $this->assertEquals($expectedPoints5R1, $points5R1);
        $this->assertEquals($expectedPoints5R2, $points5R2);
        $this->assertEquals($expectedBarrel5R0, $barrel5R0);
        $this->assertEquals($expectedBarrel5R1, $barrel5R1);
        $this->assertEquals($expectedBarrel5R2, $barrel5R2);
    }

    public function testGetSituationAfterDeclarationBombMoveFourPlayersAcesInStock(): void
    {
        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealTwoAcesInStock']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution(true);

        $player = $this->play->getActivePlayer();

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        $situation = $this->play->getSituation($player);
        $dealerName = $situation['dealer'];

        $this->assertEquals(100, $situation['orderedPlayers'][$dealerName]['points'][1]);
        $this->assertCount(3, $situation['stockRecord']);
    }

    public function testGetSituationAfterDeclarationBombMoveFourPlayersMarriageInStock(): void
    {
        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealMarriageInStock']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution(true);

        $player = $this->play->getActivePlayer();
        $dealerName = $this->play->getSituation($player)['dealer'];

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        $situation = $this->play->getSituation($player);

        $this->assertEquals(40, $situation['orderedPlayers'][$dealerName]['points'][1]);
        $this->assertCount(3, $situation['stockRecord']);
    }

    public function testGetSituationAfterDeclarationBombMoveFourPlayersMarriageInStockDealerOnBarrel(): void
    {
        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealMarriageInStock']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution(true);

        $player = $this->play->getActivePlayer();
        $dealerName = $this->play->getSituation($player)['dealer'];

        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        $overwrite[$dealerName]['barrel'] = true;
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $player,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        $situation = $this->play->getSituation($player);

        $this->assertEquals(0, $situation['orderedPlayers'][$dealerName]['points'][1]);
        $this->assertCount(3, $situation['stockRecord']);
    }

    public function testThrowExceptionWhenMoveFirstCardOutsideOfHand(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $player = $this->play->getActivePlayer();
        $notActivePlayer = $this->players->getOne(1)->getId() === $player->getId() ? $this->players->getOne(2) : $this->players->getOne(1);
        $notActivePlayerHand = $this->play->getSituation($notActivePlayer)['orderedPlayers'][$notActivePlayer->getName()]['hand'];

        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $notActivePlayerHand[0]],
            new GamePhaseThousandPlayFirstCard()
        ));
    }

    public function testThrowExceptionWhenMoveFirstCardMarriageWithoutOneAtHand(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_PLAY_TRUMP_PAIR);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['K-H', 'J-H'], ['K-D', 'J-D'], ['K-C', 'J-C']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = array_pop($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0], 'marriage' => true],
            new GamePhaseThousandPlayFirstCard()
        ));
    }

    public function testThrowExceptionWhenMoveFirstCardMarriageUsingNotKingOrQueen(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_PLAY_TRUMP_RANK);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['K-H', 'Q-H', 'J-H'], ['K-D', 'Q-D', 'J-D'], ['K-C', 'Q-C', 'J-C']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = array_pop($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][2], 'marriage' => true],
            new GamePhaseThousandPlayFirstCard()
        ));
    }

    public function testGetSituationAfterPlayFirstCardMove(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $player = $this->play->getActivePlayer();
        $cardKey = $this->play->getSituation($player)['orderedPlayers'][$player->getName()]['hand'][0];
        $suit = $this->deckProvider->getDeck()->getOne($cardKey)->getSuit();

        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $cardKey],
            new GamePhaseThousandPlayFirstCard()
        ));

        $nextPlayer = $this->play->getActivePlayer();
        $situationPlayer = $this->play->getSituation($player);
        $situationNext = $this->play->getSituation($nextPlayer);

        $this->assertCount(7, $situationPlayer['orderedPlayers'][$player->getName()]['hand']);
        $this->assertEquals(7, $situationNext['orderedPlayers'][$player->getName()]['hand']);
        $this->assertEquals((new GamePhaseThousandPlaySecondCard())->getKey(), $situationPlayer['phase']['key']);
        $this->assertEquals($cardKey, $situationPlayer['table'][0]);
        $this->assertCount(1, $situationPlayer['table']);
        $this->assertNotEquals($player->getId(), $nextPlayer->getId());
        $this->assertEquals($suit->getKey(), $situationPlayer['turnSuit']);
        $this->assertEquals($player->getName(), $situationPlayer['turnLead']);
    }

    public function testGetSituationAfterPlayFirstCardMoveWithTrump(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $player = $this->play->getActivePlayer();
        $situationI = $this->play->getSituation($player);
        $card = $situationI['orderedPlayers'][$player->getName()]['hand'][1];

        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        $overwrite[$player->getName()]['trumps'] = [0 => 'K-S'];
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $card, 'marriage' => true],
            new GamePhaseThousandPlayFirstCard()
        ));

        $situationF = $this->play->getSituation($player);

        $this->assertEquals(substr($card, 2, 1), $situationF['trumpSuit']);
        $this->assertCount(2, $situationF['orderedPlayers'][$player->getName()]['trumps']);
    }

    public function testThrowExceptionWhenMoveSecondCardNotTurnSuitWhileAtHand(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_PLAY_TURN_SUIT);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['A-H', 'A-D'], ['K-H', 'K-D'], ['Q-H', 'Q-D']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        for ($i = 0; $i <= 2; $i++) {
            $overwrite[$this->players->getOne($i + 1)->getName()]['hand'] = $hands[$i];
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));
    }

    public function testThrowExceptionWhenMoveSecondCardNotHigherRankWhileAtHand(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_PLAY_HIGH_RANK);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-H', 'A-H'], ['J-H', '10-H'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][1]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlaySecondCard()
        ));
    }

    public function testThrowExceptionWhenMoveSecondCardMarriageRequest(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['K-H', 'Q-H', 'J-H'], ['K-D', 'Q-D', 'J-D'], ['K-C', 'Q-C', 'J-C']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = array_pop($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0], 'marriage' => true],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0], 'marriage' => true],
            new GamePhaseThousandPlaySecondCard()
        ));
    }

    public function testHandleMoveSecondCardHigherRankAtHand(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-H', 'A-H'], ['J-H', '10-H'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $turnLead = $player;

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertEquals(GamePhaseThousandPlayThirdCard::PHASE_KEY, $situation['phase']['key']);
        $this->assertEquals($turnLead->getName(), $situation['turnLead']);
    }

    public function testHandleMoveSecondCardLowerRankTurnSuitNotAtHand(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-H', 'A-D'], ['J-H', '10-D'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertEquals(GamePhaseThousandPlayThirdCard::PHASE_KEY, $situation['phase']['key']);
    }

    public function testHandleMoveSecondCardTrumpCardTurnSuitNotAtHand(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-D', 'A-D'], ['J-D', '10-D'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $overwrite['trumpSuit'] = 'D';
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'trumpSuit' => $overwrite['trumpSuit']]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite['orderedPlayers'][$player->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite['orderedPlayers'][$player->getName()]['hand'][0]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertEquals(GamePhaseThousandPlayThirdCard::PHASE_KEY, $situation['phase']['key']);
    }

    public function testHandleMoveSecondCardNotRelatedCardTurnSuitNotAtHand(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-S', 'A-D'], ['J-S', '10-D'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $overwrite['trumpSuit'] = 'D';
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'trumpSuit' => $overwrite['trumpSuit']]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite['orderedPlayers'][$player->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite['orderedPlayers'][$player->getName()]['hand'][0]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertEquals(GamePhaseThousandPlayThirdCard::PHASE_KEY, $situation['phase']['key']);
    }

    public function testGetSituationAfterHandleMovePlaySecondCard(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration();

        $playerFirst = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $playerFirst,
            ['card' => $this->play->getSituation($playerFirst)['orderedPlayers'][$playerFirst->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $situationInitial = $this->play->getSituation($this->play->getActivePlayer());

        $playerSecond = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $playerSecond,
            ['card' => $this->play->getSituation($playerSecond)['orderedPlayers'][$playerSecond->getName()]['hand'][0]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $situation1 = $this->play->getSituation($playerFirst);
        $situation2 = $this->play->getSituation($playerSecond);
        $table1 = $situation1['table'];
        $table2 = $situation2['table'];
        $hand11 = $situation1['orderedPlayers'][$playerFirst->getName()]['hand'];
        $hand12 = $situation1['orderedPlayers'][$playerSecond->getName()]['hand'];
        $hand21 = $situation2['orderedPlayers'][$playerFirst->getName()]['hand'];
        $hand22 = $situation2['orderedPlayers'][$playerSecond->getName()]['hand'];
        $phase = $situation1['phase']['key'];

        unset(
            $situation1['table'], $situation2['table'], $situationInitial['table'],
            $situation1['orderedPlayers'], $situation2['orderedPlayers'], $situationInitial['orderedPlayers'],
            $situation1['phase'], $situation2['phase'], $situationInitial['phase'],
            $situation1['activePlayer'], $situation2['activePlayer'], $situationInitial['activePlayer'],
        );

        $this->assertCount(2, $table1);
        $this->assertCount(2, $table2);
        $this->assertCount(7, $hand11);
        $this->assertCount(7, $hand22);
        $this->assertEquals(7, $hand12);
        $this->assertEquals(7, $hand21);
        $this->assertEquals(GamePhaseThousandPlayThirdCard::PHASE_KEY, $phase);
        $this->assertEquals($situationInitial, $situation1);
        $this->assertEquals($situationInitial, $situation2);
        $this->assertNotEquals($playerFirst->getId(), $this->play->getActivePlayer()->getId());
        $this->assertNotEquals($playerSecond->getId(), $this->play->getActivePlayer()->getId());
    }

    public function testThrowExceptionWhenMoveThirdCardNotFollowingTurnSuitWhileAtHand(): void
    {
        $this->expectException(GamePlayThousandException::class);
        $this->expectExceptionMessage(GamePlayThousandException::MESSAGE_RULE_PLAY_TURN_SUIT);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['A-H', 'A-D'], ['K-H', 'K-D'], ['Q-H', 'Q-D']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        for ($i = 0; $i <= 2; $i++) {
            $overwrite[$this->players->getOne($i + 1)->getName()]['hand'] = $hands[$i];
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][0]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player,
            ['card' => $overwrite[$player->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));
    }

    public function testHandleMoveThirdCardLowerRankGoBackToFirstCardWhileMoreAtHand(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-H', 'A-H'], ['J-H', '10-H'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $situationI = $this->play->getSituation($this->play->getActivePlayer());

        $player1 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player1,
            ['card' => $overwrite[$player1->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player2 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player2,
            ['card' => $overwrite[$player2->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player3 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player3,
            ['card' => $overwrite[$player3->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));

        $expectedTurnLead = $overwrite[$player2->getName()]['hand'][1] === 'A-H' ? $player2 : $player3;
        $expectedTricks2 = $overwrite[$player2->getName()]['hand'][1] === 'A-H' ? 3 : 0;
        $expectedTricks3 = $overwrite[$player2->getName()]['hand'][1] === 'A-H' ? 0 : 3;

        $this->processPhaseCollectTricks($expectedTurnLead);

        $situationF = $this->play->getSituation($player1);

        $phase = $situationF['phase']['key'];
        $table = $situationF['table'];
        $turnSuit = $situationF['turnSuit'];
        $turnLead = $situationF['turnLead'];
        $hand1 = $situationF['orderedPlayers'][$player1->getName()]['hand'];
        $hand2 = $situationF['orderedPlayers'][$player2->getName()]['hand'];
        $hand3 = $situationF['orderedPlayers'][$player3->getName()]['hand'];
        $tricks1 = $situationF['orderedPlayers'][$player1->getName()]['tricks'];
        $tricks2 = $situationF['orderedPlayers'][$player2->getName()]['tricks'];
        $tricks3 = $situationF['orderedPlayers'][$player3->getName()]['tricks'];

        unset(
            $situationI['phase'], $situationF['phase'],
            $situationI['turnSuit'], $situationF['turnSuit'],
            $situationI['turnLead'], $situationF['turnLead'],
            $situationI['activePlayer'], $situationF['activePlayer'],
            $situationI['orderedPlayers'][$player1->getName()]['hand'],
            $situationI['orderedPlayers'][$player2->getName()]['hand'],
            $situationI['orderedPlayers'][$player3->getName()]['hand'],
            $situationF['orderedPlayers'][$player1->getName()]['hand'],
            $situationF['orderedPlayers'][$player2->getName()]['hand'],
            $situationF['orderedPlayers'][$player3->getName()]['hand'],
            $situationI['orderedPlayers'][$player1->getName()]['tricks'],
            $situationI['orderedPlayers'][$player2->getName()]['tricks'],
            $situationI['orderedPlayers'][$player3->getName()]['tricks'],
            $situationF['orderedPlayers'][$player1->getName()]['tricks'],
            $situationF['orderedPlayers'][$player2->getName()]['tricks'],
            $situationF['orderedPlayers'][$player3->getName()]['tricks'],
        );

        $this->assertEquals(GamePhaseThousandPlayFirstCard::PHASE_KEY, $phase);
        $this->assertCount(0, $table);
        $this->assertNull($turnSuit);
        $this->assertEquals($expectedTurnLead->getName(), $turnLead);
        $this->assertCount(1, $hand1);
        $this->assertEquals(1, $hand2);
        $this->assertEquals(1, $hand3);
        $this->assertEquals(0, $tricks1);
        $this->assertEquals($expectedTricks2, $tricks2);
        $this->assertEquals($expectedTricks3, $tricks3);
        $this->assertEquals($situationI, $situationF);
    }

    public function testHandleMoveThirdCardThreeCardsOnTable(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['9-H', 'A-H'], ['J-H', '10-H'], ['Q-H', 'K-H']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player1 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player1,
            ['card' => $overwrite[$player1->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player2 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player2,
            ['card' => $overwrite[$player2->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player3 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player3,
            ['card' => $overwrite[$player3->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));

        $expectedActivePlayer = $overwrite[$player2->getName()]['hand'][1] === 'A-H' ? $player2 : $player3;

        $situation = $this->play->getSituation($player1);

        $this->assertEquals(GamePhaseThousandCollectTricks::PHASE_KEY, $situation['phase']['key']);
        $this->assertCount(3, $situation['table']);
        $this->assertNotNull($situation['turnSuit']);
        $this->assertEquals($expectedActivePlayer->getName(), $situation['activePlayer']);
        $this->assertCount(1, $situation['orderedPlayers'][$player1->getName()]['hand']);
        $this->assertEquals(1, $situation['orderedPlayers'][$player2->getName()]['hand']);
        $this->assertEquals(1, $situation['orderedPlayers'][$player3->getName()]['hand']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player1->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player2->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player3->getName()]['tricks']);
    }

    public function testHandleMoveThirdCardTrickDistributionOnlyFirstTurnSuit(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['A-D', 'K-D'], ['10-D', 'Q-D'], ['9-H', '10-H']];
        $overwrite = $this->storage->getGameData()['orderedPlayers'];
        foreach ($overwrite as $playerName => $playerData) {
            $overwrite[$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite]);

        $player1 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player1,
            ['card' => $overwrite[$player1->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player2 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player2,
            ['card' => $overwrite[$player2->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player3 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player3,
            ['card' => $overwrite[$player3->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));

        $this->processPhaseCollectTricks($player1);

        $situation = $this->play->getSituation($player1);

        $this->assertEquals($player1->getName(), $situation['turnLead']);
        $this->assertEquals(3, $situation['orderedPlayers'][$player1->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player2->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player2->getName()]['tricks']);
    }

    public function testHandleMoveThirdCardTrickDistributionOnlySecondOrThirdTrump(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['A-H', 'K-H'], ['10-D', 'Q-D'], ['9-H', '10-H']];
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'trumpSuit' => 'D']);

        $player1 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player1,
            ['card' => $overwrite['orderedPlayers'][$player1->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player2 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player2,
            ['card' => $overwrite['orderedPlayers'][$player2->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player3 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player3,
            ['card' => $overwrite['orderedPlayers'][$player3->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));

        $expectedTricks2 = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'Q-D' ? 3 : 0;
        $expectedTricks3 = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'Q-D' ? 0 : 3;
        $expectedTurnLead = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'Q-D'
            ? $player2->getName()
            : $player3->getName();

        $this->processPhaseCollectTricks($this->getPlayerByName($expectedTurnLead));

        $situation = $this->play->getSituation($player1);

        $this->assertEquals($expectedTurnLead, $situation['turnLead']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player1->getName()]['tricks']);
        $this->assertEquals($expectedTricks2, $situation['orderedPlayers'][$player2->getName()]['tricks']);
        $this->assertEquals($expectedTricks3, $situation['orderedPlayers'][$player3->getName()]['tricks']);
    }

    public function testHandleMoveThirdCardTrickDistributionSecondAndThirdTrump(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['J-D', 'A-D'], ['9-D', '10-D'], ['9-H', '10-H']];
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'trumpSuit' => 'D']);

        $player1 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player1,
            ['card' => $overwrite['orderedPlayers'][$player1->getName()]['hand'][0]],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player2 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player2,
            ['card' => $overwrite['orderedPlayers'][$player2->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player3 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player3,
            ['card' => $overwrite['orderedPlayers'][$player3->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));

        $expectedTricks2 = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'A-D' ? 3 : 0;
        $expectedTricks3 = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'A-D' ? 0 : 3;
        $expectedTurnLead = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'A-D'
            ? $player2->getName()
            : $player3->getName();

        $this->processPhaseCollectTricks($this->getPlayerByName($expectedTurnLead));

        $situation = $this->play->getSituation($player1);

        $this->assertEquals($expectedTurnLead, $situation['turnLead']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player1->getName()]['tricks']);
        $this->assertEquals($expectedTricks2, $situation['orderedPlayers'][$player2->getName()]['tricks']);
        $this->assertEquals($expectedTricks3, $situation['orderedPlayers'][$player3->getName()]['tricks']);
    }

    public function testHandleMoveThirdCardTrickDistributionAllTrump(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $hands = [['J-D', 'A-D'], ['9-D', '10-D'], ['K-D', 'Q-D']];
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_pop($hands)
                : array_shift($hands);
        }
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'trumpSuit' => 'D']);

        $player1 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player1,
            ['card' => $overwrite['orderedPlayers'][$player1->getName()]['hand'][0], 'marriage' => true],
            new GamePhaseThousandPlayFirstCard()
        ));

        $player2 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player2,
            ['card' => $overwrite['orderedPlayers'][$player2->getName()]['hand'][1]],
            new GamePhaseThousandPlaySecondCard()
        ));

        $player3 = $this->play->getActivePlayer();
        $this->play->handleMove(new GameMoveThousandPlayCard(
            $player3,
            ['card' => $overwrite['orderedPlayers'][$player3->getName()]['hand'][1]],
            new GamePhaseThousandPlayThirdCard()
        ));

        $expectedTricks2 = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'A-D' ? 3 : 0;
        $expectedTricks3 = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'A-D' ? 0 : 3;
        $expectedTurnLead = $overwrite['orderedPlayers'][$player2->getName()]['hand'][1] === 'A-D'
            ? $player2->getName()
            : $player3->getName();

        $this->processPhaseCollectTricks($this->getPlayerByName($expectedTurnLead));

        $situation = $this->play->getSituation($player1);

        $this->assertEquals($expectedTurnLead, $situation['turnLead']);
        $this->assertEquals(0, $situation['orderedPlayers'][$player1->getName()]['tricks']);
        $this->assertEquals($expectedTricks2, $situation['orderedPlayers'][$player2->getName()]['tricks']);
        $this->assertEquals($expectedTricks3, $situation['orderedPlayers'][$player3->getName()]['tricks']);
    }

    public function testGetSituationAfterHandleMoveThirdCardLastCard(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration();

        $orderedPlayers = $this->storage->getGameData()['orderedPlayers'];
        $hands = [];
        foreach ($orderedPlayers as $playerName => $data) {
            $hands[$playerName] = $data['hand'];
        }

        $phases = [
            1 => new GamePhaseThousandPlayFirstCard(),
            2 => new GamePhaseThousandPlaySecondCard(),
            3 => new GamePhaseThousandPlayThirdCard(),
        ];

        for ($i = 0; $i <= 7; $i++) {
            for ($phaseNumber = 1; $phaseNumber <= 3; $phaseNumber++) {
                $playerName = $this->play->getActivePlayer()->getName();
                $this->play->handleMove(new GameMoveThousandPlayCard(
                    $this->play->getActivePlayer(),
                    ['card' => $hands[$playerName][$i], 'marriage' => ($i === 1 && $phaseNumber === 1)],
                    $phases[$phaseNumber]
                ));
            }
            $this->processPhaseCollectTricks($this->play->getActivePlayer());
        }

        $player = $this->play->getActivePlayer();
        $situation = $this->play->getSituation($player);

        $this->assertCount(0, $situation['table']);
        $this->assertNull($situation['trumpSuit']);
        $this->assertNull($situation['turnSuit']);
        $this->assertNull($situation['turnLead']);
        $this->assertCount(0, $situation['stockRecord']);
        $this->assertEquals($situation['bidWinner'], $situation['activePlayer']);
        $this->assertEquals(GamePhaseThousandCountPoints::PHASE_KEY, $situation['phase']['key']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['tricks']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['trumps']);
        $this->assertEquals(
            $player->getId() === $this->players->getOne(1)->getId() ? [] : 0,
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['hand']
        );
        $this->assertEquals(
            $player->getId() === $this->players->getOne(2)->getId() ? [] : 0,
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']
        );
        $this->assertEquals(
            $player->getId() === $this->players->getOne(3)->getId() ? [] : 0,
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']
        );
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['ready']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['ready']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['ready']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['barrel']);
        $this->assertCount(1, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points']);
        $this->assertCount(1, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points']);
        $this->assertCount(1, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points']);
    }

    public function testGetSituationAfterLastCardPointsFor1MarriageMetDeclaration(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration(40);

        $orderedPlayers = $this->storage->getGameData()['orderedPlayers'];
        $hands = [];
        foreach ($orderedPlayers as $playerName => $data) {
            $hands[$playerName] = $data['hand'];
        }

        $phases = [
            1 => new GamePhaseThousandPlayFirstCard(),
            2 => new GamePhaseThousandPlaySecondCard(),
            3 => new GamePhaseThousandPlayThirdCard(),
        ];

        $expectedPoints = [];

        for ($i = 0; $i <= 7; $i++) {
            for ($phaseNumber = 1; $phaseNumber <= 3; $phaseNumber++) {
                $playerName = $this->play->getActivePlayer()->getName();
                $card = $hands[$playerName][$i];

                if ($i === 0 && $phaseNumber === 1) {
                    $expectedPoints[$playerName] = 150;
                }

                if ($i === 0 && $phaseNumber === 2) {
                    $expectedPoints[$playerName] = match ($card) {
                        'A-H' => 20,
                        'A-D', 'A-C' => 0,
                    };
                }

                if ($i === 0 && $phaseNumber === 3) {
                    $expectedPoints[$playerName] = match ($card) {
                        'A-H' => 20,
                        'A-D', 'A-C' => 0,
                    };
                }

                $this->play->handleMove(new GameMoveThousandPlayCard(
                    $this->play->getActivePlayer(),
                    ['card' => $card, 'marriage' => ($i === 1 && $phaseNumber === 1)],
                    $phases[$phaseNumber]
                ));
            }
            $this->processPhaseCollectTricks($this->play->getActivePlayer());
        }

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertEquals(
            $expectedPoints[$this->players->getOne(1)->getName()],
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points'][1]
        );
        $this->assertEquals(
            $expectedPoints[$this->players->getOne(2)->getName()],
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points'][1]
        );
        $this->assertEquals(
            $expectedPoints[$this->players->getOne(3)->getName()],
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points'][1]
        );
    }

    public function testGetSituationAfterLastCardPointsFor1MarriageFromNotBidWinner40PointsPreviousRound(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution();
        $this->processPhaseDeclaration();

        $handsTakeOver = [
            ['9-S', 'A-H', 'K-H', 'Q-H', 'J-H', '10-H', '9-H', 'A-S'],
            ['J-S', 'A-D', 'K-D', 'Q-D', 'J-D', '10-D', '9-D', 'K-S'],
            ['10-S', 'A-C', 'K-C', 'Q-C', 'J-C', '10-C', '9-C', 'Q-S'],
        ];
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['hand'] = $playerName === $this->play->getActivePlayer()->getName()
                ? array_shift($handsTakeOver)
                : array_pop($handsTakeOver);
            $overwrite['orderedPlayers'][$playerName]['points'][1] = 40;
        }
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'round' => 2]);

        $orderedPlayers = $this->storage->getGameData()['orderedPlayers'];
        $hands = [];
        foreach ($orderedPlayers as $playerName => $data) {
            $hands[$playerName] = $data['hand'];
        }

        $phases = [
            1 => new GamePhaseThousandPlayFirstCard(),
            2 => new GamePhaseThousandPlaySecondCard(),
            3 => new GamePhaseThousandPlayThirdCard(),
        ];

        $expectedPoints = [];

        for ($i = 0; $i <= 7; $i++) {
            for ($phaseNumber = 1; $phaseNumber <= 3; $phaseNumber++) {
                $playerName = $this->play->getActivePlayer()->getName();
                $card = $hands[$playerName][$i];

                if ($i === 0 && $phaseNumber === 1) {
                    $expectedPoints[$playerName] = 40 - 110;
                }

                if ($i === 0 && $phaseNumber === 2) {
                    $expectedPoints[$playerName] = 40 + match ($card) {
                        'J-S' => 0,
                        '10-S' => 160,
                    };
                }

                if ($i === 0 && $phaseNumber === 3) {
                    $expectedPoints[$playerName] = 40 + match ($card) {
                        'J-S' => 0,
                        '10-S' => 160,
                    };
                }

                $this->play->handleMove(new GameMoveThousandPlayCard(
                    $this->play->getActivePlayer(),
                    ['card' => $card, 'marriage' => ($i === 2 && $phaseNumber === 1)],
                    $phases[$phaseNumber]
                ));
            }
            $this->processPhaseCollectTricks($this->play->getActivePlayer());
        }

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertEquals(
            $expectedPoints[$this->players->getOne(1)->getName()],
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points'][2]
        );
        $this->assertEquals(
            $expectedPoints[$this->players->getOne(2)->getName()],
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points'][2]
        );
        $this->assertEquals(
            $expectedPoints[$this->players->getOne(3)->getName()],
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points'][2]
        );
    }

    public function testGetSituationAfterLastCardPointsForEveryoneReachingBarrelWhenDisabled(): void
    {
        $this->play = $this->getGamePlay($this->getGameInvite(false, false));
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration();

        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['points'][1] = 790;
        }
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers']]);

        $orderedPlayers = $this->storage->getGameData()['orderedPlayers'];
        $hands = [];
        foreach ($orderedPlayers as $playerName => $data) {
            $hands[$playerName] = $data['hand'];
        }

        $phases = [
            1 => new GamePhaseThousandPlayFirstCard(),
            2 => new GamePhaseThousandPlaySecondCard(),
            3 => new GamePhaseThousandPlayThirdCard(),
        ];

        for ($i = 0; $i <= 7; $i++) {
            for ($phaseNumber = 1; $phaseNumber <= 3; $phaseNumber++) {

                $playerName = $this->play->getActivePlayer()->getName();
                $card = $hands[$playerName][$i];

                $this->play->handleMove(new GameMoveThousandPlayCard(
                    $this->play->getActivePlayer(),
                    ['card' => $card, 'marriage' => ($i === 1 && $phaseNumber === 1)],
                    $phases[$phaseNumber]
                ));
            }
            $this->processPhaseCollectTricks($this->play->getActivePlayer());
        }

        $situation = $this->play->getSituation($this->play->getActivePlayer());

        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['barrel']);
        $this->assertFalse($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['barrel']);
    }

    public function testThrowExceptionWhenHandleMoveCountPointsAlredyReady(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_INCOMPATIBLE_MOVE);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration();
        $this->processPhasePlayCard();

        for ($i = 1; $i <= 2; $i++) {
            $this->play->handleMove(new GameMoveThousandCountPoints(
                $this->players->getOne(1),
                ['ready' => true],
                new GamePhaseThousandCountPoints(),
            ));
        }
    }

    public function testGetSituationAfterHandleMoveCountPointsForOnePlayer(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration();
        $this->processPhasePlayCard();

        $this->play->handleMove(new GameMoveThousandCountPoints(
            $this->players->getOne(1),
            ['ready' => true],
            new GamePhaseThousandCountPoints(),
        ));

        $situation0 = $this->play->getSituation($this->players->getOne(1));
        $situation1 = $this->play->getSituation($this->players->getOne(2));

        foreach ($this->play->getPlayers()->toArray() as $player) {
            unset (
                $situation0['orderedPlayers'][$player->getName()]['hand'],
                $situation1['orderedPlayers'][$player->getName()]['hand'],
            );
        }

        $this->assertEquals($situation0, $situation1);
        $this->assertTrue($situation0['orderedPlayers'][$this->players->getOne(1)->getName()]['ready']);
        $this->assertFalse($situation0['orderedPlayers'][$this->players->getOne(2)->getName()]['ready']);
        $this->assertFalse($situation0['orderedPlayers'][$this->players->getOne(3)->getName()]['ready']);
    }

    public function testGetSituationAfterHandleMoveCountPointsAllPlayersFourPlayersGame(): void
    {
        $this->restartForFourPlayers();
        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding();
        $this->processPhaseStockDistribution(true, ['J-S', '9-S']);
        $this->processPhaseDeclaration();
        $this->processPhasePlayCard();

        $initialSituation = $this->play->getSituation($this->players->getOne(1));

        foreach ($this->play->getPlayers()->toArray() as $player) {
            $this->play->handleMove(new GameMoveThousandCountPoints(
                $player,
                ['ready' => true],
                new GamePhaseThousandCountPoints(),
            ));
        }

        $situation = $this->play->getSituation($this->players->getOne(1));
        $expectedPlayersNames = array_map(fn($player) => $player->getName(), $this->play->getPlayers()->toArray());

        // all players available
        $this->assertCount(4, $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(1)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(2)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(3)->getName(), $situation['orderedPlayers']);
        $this->assertArrayHasKey($this->players->getOne(4)->getName(), $situation['orderedPlayers']);

        // player see his cards and not other players cards
        $this->assertCount(
            $situation['dealer'] === $this->players->getOne(1)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['hand']
        );
        $this->assertEquals(
            $situation['dealer'] === $this->players->getOne(2)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['hand']
        );
        $this->assertEquals(
            $situation['dealer'] === $this->players->getOne(3)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['hand']
        );
        $this->assertEquals(
            $situation['dealer'] === $this->players->getOne(4)->getName() ? 0 : 7,
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['hand']
        );

        // player see his and other players tricks count but not cards
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['tricks']);
        $this->assertEquals(0, $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['tricks']);

        // player see his and other players trumps count empty
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['trumps']);
        $this->assertCount(0, $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['trumps']);

        // players see stock count but not cards
        $this->assertEquals(3, $situation['stock']);

        // all players ready true
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['ready']);
        $this->assertTrue($situation['orderedPlayers'][$this->players->getOne(4)->getName()]['ready']);

        // all players points counts > 0
        $this->assertGreaterThan(0, count($situation['orderedPlayers'][$this->players->getOne(1)->getName()]['points']));
        $this->assertGreaterThan(0, count($situation['orderedPlayers'][$this->players->getOne(2)->getName()]['points']));
        $this->assertGreaterThan(0, count($situation['orderedPlayers'][$this->players->getOne(3)->getName()]['points']));
        $this->assertGreaterThan(0, count($situation['orderedPlayers'][$this->players->getOne(4)->getName()]['points']));

        // all players bid null, except obligation player bid 100
        $this->assertEquals(
            ($this->players->getOne(1)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(1)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(2)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(2)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(3)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(3)->getName()]['bid']
        );
        $this->assertEquals(
            ($this->players->getOne(4)->getName() === $situation['obligation'] ? 100 : null),
            $situation['orderedPlayers'][$this->players->getOne(4)->getName()]['bid']
        );

        // seat position reflect player roles
        $this->assertEquals(1 + 1, $situation['orderedPlayers'][$situation['dealer']]['seat']);
        $this->assertEquals(2 + 1, $situation['orderedPlayers'][$situation['obligation']]['seat']);
        $this->assertEquals(3 + 1, $situation['orderedPlayers'][$situation['activePlayer']]['seat']);

        // table empty
        $this->assertEquals([], $situation['table']);

        // trump suit null
        $this->assertNull($situation['trumpSuit']);

        // turn suit null
        $this->assertNull($situation['turnSuit']);

        // turn lead null
        $this->assertNull($situation['turnLead']);

        // bid winner null
        $this->assertNull($situation['bidWinner']);

        // bid amount 100
        $this->assertEquals(100, $situation['bidAmount']);

        // stockRecord empty
        $this->assertCount(0, $situation['stockRecord']);

        // active player <> obligation <> dealer and within 3 players
        $this->assertTrue(in_array($situation['dealer'], $expectedPlayersNames));
        $this->assertTrue(in_array($situation['obligation'], $expectedPlayersNames));
        $this->assertTrue(in_array($situation['activePlayer'], $expectedPlayersNames));
        $this->assertNotEquals($situation['dealer'], $situation['obligation']);
        $this->assertNotEquals($situation['dealer'], $situation['activePlayer']);
        $this->assertNotEquals($situation['obligation'], $situation['activePlayer']);

        // round added
        $this->assertEquals($initialSituation['round'] + 1, $situation['round']);

        // phase key updated
        $this->assertEquals(GamePhaseThousandBidding::PHASE_KEY, $situation['phase']['key']);

        // is Finished false
        $this->assertFalse($situation['isFinished']);
    }

    public function testGetSituationAfterHandleMovePlayLastCardWin(): void
    {
        $overwrite = $this->storage->getGameData();
        foreach ($overwrite['orderedPlayers'] as $playerName => $playerData) {
            $overwrite['orderedPlayers'][$playerName]['points'][1] = 900;
            $overwrite['orderedPlayers'][$playerName]['barrel'] = true;
        }
        $this->updateGameData(['orderedPlayers' => $overwrite['orderedPlayers'], 'round' => 2]);

        $this->updateGamePlayDeal([$this, 'getDealMarriages']);
        $this->processPhaseBidding(false, 120);
        $this->processPhaseStockDistribution(false, ['J-S', '9-S']);
        $this->processPhaseDeclaration(10);
        $this->processPhasePlayCard();

        $situation = $this->play->getSituation($this->players->getOne(1));

        $this->assertTrue($this->play->isFinished());
        $this->assertTrue($situation['isFinished']);
        $this->assertArrayHasKey('result', $situation);
    }

    public function testThrowExceptionWhenForfeitOnFinishedGame(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_MOVE_ON_FINISHED_GAME);

        $this->storage->setFinished();
        $this->play = $this->getGamePlay();

        $this->play->handleForfeit($this->players->getOne(1));
    }

    public function testThrowExceptionWhenForfeitNotPlayer(): void
    {
        $this->expectException(GamePlayException::class);
        $this->expectExceptionMessage(GamePlayException::MESSAGE_NOT_PLAYER);

        $this->play->handleForfeit($this->players->getOne(4));
    }

    public function testGetSituationAfterForfeitMove(): void
    {
        $this->play->handleForfeit($this->players->getOne(1));
        $situation = $this->play->getSituation($this->players->getOne(1));

        $this->assertTrue($this->play->isFinished());
        $this->assertTrue($situation['isFinished']);
        $this->assertArrayHasKey('result', $situation);
    }

    public function testGetSituationAfterBombAndCountPoints(): void
    {
        $this->updateGamePlayDeal([$this, 'getDealNoMarriage']);
        $this->processPhaseBidding(false, 100);
        $this->processPhaseStockDistribution();

        $bidWinner = $this->play->getActivePlayer();

        $this->play->handleMove(new GameMoveThousandDeclaration(
            $bidWinner,
            ['declaration' => 0],
            new GamePhaseThousandDeclaration()
        ));

        for ($i = 0; $i <= 2; $i++) {
            $this->play->handleMove(new GameMoveThousandCountPoints(
                $this->players->getOne($i + 1),
                ['ready' => true],
                new GamePhaseThousandCountPoints()
            ));
        }

        $this->assertEquals((new GamePhaseThousandBidding())->getKey(), $this->play->getSituation($bidWinner)['phase']['key']);
    }
}
