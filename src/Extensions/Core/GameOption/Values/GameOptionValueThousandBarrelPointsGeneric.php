<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values;

use MyDramGames\Utils\Php\Enum\FromValueBackedEnumTrait;
use MyDramGames\Utils\Php\Enum\GetValueBackedEnumTrait;

enum GameOptionValueThousandBarrelPointsGeneric: int implements GameOptionValueThousandBarrelPoints
{
    use GetValueBackedEnumTrait;
    use FromValueBackedEnumTrait;

    case Disabled = 0;
    case EightHundred = 800;
    case EightHundredEighty = 880;
    case NineHundred = 900;

    public function getLabel(): string
    {
        return match($this) {
            self::Disabled => 'Disabled',
            self::EightHundred => 'Eight Hundred',
            self::EightHundredEighty => 'Eight Hundred Eighty',
            self::NineHundred => 'Nine Hundred',
        };
    }
}
