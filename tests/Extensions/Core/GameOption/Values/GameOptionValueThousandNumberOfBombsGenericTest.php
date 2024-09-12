<?php

namespace Tests\Extensions\Core\GameOption\Values;

use MyDramGames\Core\GameOption\GameOptionValue;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandNumberOfBombsGeneric;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameOptionValueThousandNumberOfBombsGenericTest extends TestCase
{
    public function testInstanceOfGameOptions(): void
    {
        $reflection = new ReflectionClass(GameOptionValueThousandNumberOfBombsGeneric::class);
        $this->assertTrue($reflection->implementsInterface(GameOptionValue::class));
    }

    public function testGetValue(): void
    {
        $value1 = GameOptionValueThousandNumberOfBombsGeneric::Disabled;
        $value2 = GameOptionValueThousandNumberOfBombsGeneric::One;
        $value3 = GameOptionValueThousandNumberOfBombsGeneric::Two;

        $this->assertEquals($value1->value, $value1->getValue());
        $this->assertEquals($value2->value, $value2->getValue());
        $this->assertEquals($value3->value, $value3->getValue());
    }

    public function testGetLabel(): void
    {
        $value1 = GameOptionValueThousandNumberOfBombsGeneric::Disabled;
        $value2 = GameOptionValueThousandNumberOfBombsGeneric::One;
        $value3 = GameOptionValueThousandNumberOfBombsGeneric::Two;

        $this->assertEquals('Disabled', $value1->getLabel());
        $this->assertEquals('One Bomb', $value2->getLabel());
        $this->assertEquals('Two Bombs', $value3->getLabel());
    }

    public function testFromValue(): void
    {
        $this->assertInstanceOf(
            GameOptionValueThousandNumberOfBombsGeneric::class,
            GameOptionValueThousandNumberOfBombsGeneric::fromValue(GameOptionValueThousandNumberOfBombsGeneric::Disabled->value)
        );
    }
}
