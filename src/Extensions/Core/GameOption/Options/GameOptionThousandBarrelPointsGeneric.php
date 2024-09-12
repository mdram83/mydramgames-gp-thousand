<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameOption\Options;

use MyDramGames\Core\GameOption\GameOptionBase;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPoints;

class GameOptionThousandBarrelPointsGeneric extends GameOptionBase implements GameOptionThousandBarrelPoints
{
    protected const ?string OPTION_KEY = 'thousand-barrel-points';
    protected const ?string OPTION_NAME = 'Barrel Points';
    protected const ?string OPTION_DESCRIPTION = 'Number of points after which player needs to win bidding to get any points added';
    protected const ?string VALUE_CLASS = GameOptionValueThousandBarrelPoints::class;
}
