<?php

namespace Tests\Extensions\Core\GameSetup;

use MyDramGames\Core\GameOption\GameOptionCollectionPowered;
use MyDramGames\Core\GameOption\GameOptionValueCollectionPowered;
use MyDramGames\Core\GameOption\Values\GameOptionValueAutostartGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueForfeitAfterGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPointsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandNumberOfBombsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameSetup\GameSetupThousand;
use PHPUnit\Framework\TestCase;

class GameSetupThousandTest extends TestCase
{
    protected GameSetupThousand $setup;

    public function setUp(): void
    {
        $this->setup = new GameSetupThousand(
            new GameOptionCollectionPowered(),
            new GameOptionValueCollectionPowered()
        );
    }

    public function testGetAllOptions(): void
    {
        $allOptions = $this->setup->getAllOptions();

        $numberOfPlayers = $allOptions->getOne('numberOfPlayers');
        $autostart = $allOptions->getOne('autostart');
        $forfeitAfter = $allOptions->getOne('forfeitAfter');
        $barrelPoints = $allOptions->getOne('thousand-barrel-points');
        $numberOfBombs = $allOptions->getONe('thousand-number-of-bombs');

        $this->assertEquals(5, $allOptions->count());

        $this->assertEquals(2, $numberOfPlayers->getAvailableValues()->count());
        $this->assertEquals(3, $numberOfPlayers->getAvailableValues()->pullFirst()->getValue());
        $this->assertEquals(3, $numberOfPlayers->getDefaultValue()->getValue());

        $this->assertEquals(2, $autostart->getAvailableValues()->count());
        $this->assertEquals(GameOptionValueAutostartGeneric::Enabled->value, $autostart->getAvailableValues()->pullFirst()->getValue());
        $this->assertEquals(GameOptionValueAutostartGeneric::Enabled->value, $autostart->getDefaultValue()->getValue());

        $this->assertEquals(2, $forfeitAfter->getAvailableValues()->count());
        $this->assertEquals(GameOptionValueForfeitAfterGeneric::Disabled->value, $forfeitAfter->getAvailableValues()->pullFirst()->getValue());
        $this->assertEquals(GameOptionValueForfeitAfterGeneric::Disabled->value, $forfeitAfter->getDefaultValue()->getValue());

        $this->assertEquals(4, $barrelPoints->getAvailableValues()->count());
        $this->assertEquals(
            GameOptionValueThousandBarrelPointsGeneric::EightHundred->value,
            $barrelPoints->getDefaultValue()->getValue(),
        );

        $this->assertEquals(3, $numberOfBombs->getAvailableValues()->count());
        $this->assertEquals(
            GameOptionValueThousandNumberOfBombsGeneric::One->value,
            $numberOfBombs->getDefaultValue()->getValue(),
        );
    }
}
