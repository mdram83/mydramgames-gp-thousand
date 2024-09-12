<?php

namespace Tests\Extensions\Core\GameOption\Values;

use MyDramGames\Core\GameOption\GameOptionValue;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPointsGeneric;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameOptionValueThousandBarrelPointsGenericTest extends TestCase
{
    public function testInstanceOfGameOptions(): void
    {
        $reflection = new ReflectionClass(GameOptionValueThousandBarrelPointsGeneric::class);
        $this->assertTrue($reflection->implementsInterface(GameOptionValue::class));
    }

    public function testGetValue(): void
    {
        $value1 = GameOptionValueThousandBarrelPointsGeneric::Disabled;
        $value2 = GameOptionValueThousandBarrelPointsGeneric::EightHundred;
        $value3 = GameOptionValueThousandBarrelPointsGeneric::EightHundredEighty;
        $value4 = GameOptionValueThousandBarrelPointsGeneric::NineHundred;

        $this->assertEquals($value1->value, $value1->getValue());
        $this->assertEquals($value2->value, $value2->getValue());
        $this->assertEquals($value3->value, $value3->getValue());
        $this->assertEquals($value4->value, $value4->getValue());
    }

    public function testGetLabel(): void
    {
        $value1 = GameOptionValueThousandBarrelPointsGeneric::Disabled;
        $value2 = GameOptionValueThousandBarrelPointsGeneric::EightHundred;
        $value3 = GameOptionValueThousandBarrelPointsGeneric::EightHundredEighty;
        $value4 = GameOptionValueThousandBarrelPointsGeneric::NineHundred;

        $this->assertEquals('Disabled', $value1->getLabel());
        $this->assertEquals('Eight Hundred', $value2->getLabel());
        $this->assertEquals('Eight Hundred Eighty', $value3->getLabel());
        $this->assertEquals('Nine Hundred', $value4->getLabel());
    }

    public function testFromValue(): void
    {
        $this->assertInstanceOf(
            GameOptionValueThousandBarrelPointsGeneric::class,
            GameOptionValueThousandBarrelPointsGeneric::fromValue(GameOptionValueThousandBarrelPointsGeneric::Disabled->value)
        );
    }
}
