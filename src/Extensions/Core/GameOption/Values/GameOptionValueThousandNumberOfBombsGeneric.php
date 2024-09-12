<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values;

use MyDramGames\Utils\Php\Enum\FromValueBackedEnumTrait;
use MyDramGames\Utils\Php\Enum\GetValueBackedEnumTrait;

enum GameOptionValueThousandNumberOfBombsGeneric: int implements GameOptionValueThousandNumberOfBombs
{
    use GetValueBackedEnumTrait;
    use FromValueBackedEnumTrait;

    case Disabled = 0;
    case One = 1;
    case Two = 2;

    public function getLabel(): string
    {
        return match($this) {
            self::Disabled => 'Disabled',
            self::One => 'One Bomb',
            self::Two => 'Two Bombs',
        };
    }
}
