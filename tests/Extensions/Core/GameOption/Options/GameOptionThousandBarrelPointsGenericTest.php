<?php

namespace Tests\Extensions\Core\GameOption\Options;

use MyDramGames\Core\Exceptions\GameOptionException;
use MyDramGames\Core\GameOption\GameOption;
use MyDramGames\Core\GameOption\GameOptionType;
use MyDramGames\Core\GameOption\GameOptionValue;
use MyDramGames\Core\GameOption\GameOptionValueCollection;
use MyDramGames\Core\GameOption\GameOptionValueCollectionPowered;
use MyDramGames\Core\GameOption\Options\GameOptionAutostartGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueAutostartGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Options\GameOptionThousandBarrelPointsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPointsGeneric;
use PHPUnit\Framework\TestCase;

class GameOptionThousandBarrelPointsGenericTest extends TestCase
{
    protected GameOptionValueCollection $available;
    protected GameOptionValue $default;
    protected GameOptionValue $configured;
    protected GameOption $option;
    protected GameOptionType $type;

    public function setUp(): void
    {
        $this->default = GameOptionValueThousandBarrelPointsGeneric::EightHundred;
        $this->configured = GameOptionValueThousandBarrelPointsGeneric::Disabled;
        $this->available = new GameOptionValueCollectionPowered(null, [$this->default, $this->configured]);
        $this->type = $this->createMock(GameOptionType::class);

        $this->option = new GameOptionThousandBarrelPointsGeneric($this->available, $this->default, $this->type);
    }

    public function testGetKey(): void
    {
        $this->assertNotNull($this->option->getKey());
        $this->assertIsString($this->option->getKey());
    }

    public function testGetName(): void
    {
        $this->assertNotNull($this->option->getName());
        $this->assertIsString($this->option->getName());
    }

    public function testGetDescription(): void
    {
        $this->assertNotNull($this->option->getDescription());
        $this->assertIsString($this->option->getDescription());
    }

    public function testConstructorThrowExceptionWhenUsingIncompatibleValues(): void
    {
        $this->expectExceptionMessage(GameOptionException::class);
        $this->expectExceptionMessage(GameOptionException::MESSAGE_INCOMPATIBLE_VALUE);

        $incompatibleOption = $this->createMock(GameOptionValue::class);
        new GameOptionThousandBarrelPointsGeneric($this->available, $incompatibleOption, $this->type);
    }

    public function testSetConfiguredValue(): void
    {
        $this->option->setConfiguredValue($this->default);
        $this->assertSame($this->default, $this->option->getConfiguredValue());
    }
}
