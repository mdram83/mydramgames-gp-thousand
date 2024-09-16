<?php

namespace Tests\Extensions\Core\GameIndex;

use MyDramGames\Core\GameBox\GameBox;
use MyDramGames\Core\GameBox\GameBoxGeneric;
use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GameInvite\GameInviteGeneric;
use MyDramGames\Core\GameOption\GameOptionCollection;
use MyDramGames\Core\GameOption\GameOptionCollectionPowered;
use MyDramGames\Core\GameOption\GameOptionConfigurationCollectionPowered;
use MyDramGames\Core\GameOption\GameOptionConfigurationGeneric;
use MyDramGames\Core\GameOption\GameOptionValueCollection;
use MyDramGames\Core\GameOption\GameOptionValueCollectionPowered;
use MyDramGames\Core\GameOption\Values\GameOptionValueAutostartGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueForfeitAfterGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueNumberOfPlayersGeneric;
use MyDramGames\Core\GamePlay\Services\GamePlayServicesProviderGeneric;
use MyDramGames\Core\GamePlay\Storage\GamePlayStorageFactoryInMemory;
use MyDramGames\Core\GameRecord\GameRecordCollectionPowered;
use MyDramGames\Games\Thousand\Extensions\Core\GameIndex\GameIndexThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameMove\GameMoveThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPointsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandNumberOfBombsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GamePlay\GamePlayThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameSetup\GameSetupThousand;
use MyDramGames\Utils\Php\Collection\CollectionEnginePhpArray;
use MyDramGames\Utils\Player\Player;
use MyDramGames\Utils\Player\PlayerCollectionPowered;
use PHPUnit\Framework\TestCase;
use Tests\TestingHelper;

class GameIndexThousandTest extends TestCase
{
    protected GameIndexThousand $index;

    protected GameOptionCollection $optionsHandler;
    protected GameOptionValueCollection $valuesHandler;

    public function setUp(): void
    {
        $this->index = new GameIndexThousand(
            new GameOptionCollectionPowered(),
            new GameOptionValueCollectionPowered(),
            new GamePlayStorageFactoryInMemory(),
            new GamePlayServicesProviderGeneric(
                new CollectionEnginePhpArray(),
                new PlayerCollectionPowered(),
                TestingHelper::getGameRecordFactory(),
                new GameRecordCollectionPowered()
            ),
        );
    }

    protected function getGameInvite(): GameInvite
    {
        $setup = new GameSetupThousand(new GameOptionCollectionPowered(), new GameOptionValueCollectionPowered());
        $box = new GameBoxGeneric('thousand', 'Thousand', $setup, true, false, null, null, null);
        $configurations = new GameOptionConfigurationCollectionPowered(null, [
            new GameOptionConfigurationGeneric('numberOfPlayers', GameOptionValueNumberOfPlayersGeneric::Players003),
            new GameOptionConfigurationGeneric('autostart', GameOptionValueAutostartGeneric::Disabled),
            new GameOptionConfigurationGeneric('forfeitAfter', GameOptionValueForfeitAfterGeneric::Disabled),
            new GameOptionConfigurationGeneric('thousand-barrel-points', GameOptionValueThousandBarrelPointsGeneric::EightHundred),
            new GameOptionConfigurationGeneric('thousand-number-of-bombs', GameOptionValueThousandNumberOfBombsGeneric::One),
        ]);

        $invite = new GameInviteGeneric(1, $box, $configurations, new PlayerCollectionPowered());

        $playerOne = $this->createMock(Player::class);
        $playerOne->method('getId')->willReturn(1);
        $playerOne->method('getName')->willReturn('Player 1');

        $playerTwo = $this->createMock(Player::class);
        $playerTwo->method('getId')->willReturn(2);
        $playerTwo->method('getName')->willReturn('Player 2');

        $playerThree = $this->createMock(Player::class);
        $playerThree->method('getId')->willReturn(3);
        $playerThree->method('getName')->willReturn('Player 3');

        $invite->addPlayer($playerOne, true);
        $invite->addPlayer($playerTwo);
        $invite->addPlayer($playerThree);

        return $invite;
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(GameIndexThousand::class, $this->index);
    }

    public function testGetSlug(): void
    {
        $this->assertNotEquals('', $this->index->getSlug());
    }

    public function testGetGameSetup(): void
    {
        $this->assertInstanceOf(GameSetupThousand::class, $this->index->getGameSetup());
    }

    public function testGetGameBox(): void
    {
        $this->assertInstanceOf(GameBox::class, $this->index->getGameBox());
    }

    public function testCreateGameMove(): void
    {
        $this->assertInstanceOf(GameMoveThousand::class, $this->index->createGameMove(
            $this->createMock(Player::class),
            ['phase' => 'bidding', 'data' => ['decision' => 'bid', 'bidAmount' => 110]]
        ));
    }

    public function testCreateGamePlay(): void
    {
        $invite = $this->getGameInvite();
        $this->assertInstanceOf(GamePlayThousand::class, $this->index->createGamePlay($invite));
    }
}
