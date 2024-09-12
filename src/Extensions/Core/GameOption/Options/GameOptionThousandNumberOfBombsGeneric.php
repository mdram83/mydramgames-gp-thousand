<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameOption\Options;

use MyDramGames\Core\GameOption\GameOptionBase;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandNumberOfBombs;

class GameOptionThousandNumberOfBombsGeneric extends GameOptionBase implements GameOptionThousandNumberOfBombs
{
    protected const ?string OPTION_KEY = 'thousand-number-of-bombs';
    protected const ?string OPTION_NAME = 'Number Of Bombs';
    protected const ?string OPTION_DESCRIPTION = 'Number of Bomb moves available for player winning bid at 100 points';
    protected const ?string VALUE_CLASS = GameOptionValueThousandNumberOfBombs::class;
}
