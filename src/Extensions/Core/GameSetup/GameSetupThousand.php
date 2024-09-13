<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameSetup;

use MyDramGames\Core\GameOption\GameOptionTypeGeneric;
use MyDramGames\Core\GameOption\GameOptionValueCollection;
use MyDramGames\Core\GameOption\Options\GameOptionAutostartGeneric;
use MyDramGames\Core\GameOption\Options\GameOptionForfeitAfterGeneric;
use MyDramGames\Core\GameOption\Options\GameOptionNumberOfPlayersGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueAutostartGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueForfeitAfterGeneric;
use MyDramGames\Core\GameOption\Values\GameOptionValueNumberOfPlayersGeneric;
use MyDramGames\Core\GameSetup\GameSetup;
use MyDramGames\Core\GameSetup\GameSetupBase;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Options\GameOptionThousandBarrelPointsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Options\GameOptionThousandNumberOfBombsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPoints;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandBarrelPointsGeneric;
use MyDramGames\Games\Thousand\Extensions\Core\GameOption\Values\GameOptionValueThousandNumberOfBombsGeneric;

class GameSetupThousand extends GameSetupBase implements GameSetup
{
    /**
     * @inheritDoc
     */
    protected function prepareDefaultOptions(GameOptionValueCollection $valuesHandler): array
    {
        return
            [
                new GameOptionNumberOfPlayersGeneric(
                    $valuesHandler->clone()->reset([
                        GameOptionValueNumberOfPlayersGeneric::Players003,
                        GameOptionValueNumberOfPlayersGeneric::Players004,
                    ]),
                    GameOptionValueNumberOfPlayersGeneric::Players003,
                    GameOptionTypeGeneric::Radio
                ),

                new GameOptionAutostartGeneric(
                    $valuesHandler->clone()->reset([
                        GameOptionValueAutostartGeneric::Enabled,
                        GameOptionValueAutostartGeneric::Disabled
                    ]),
                    GameOptionValueAutostartGeneric::Enabled,
                    GameOptionTypeGeneric::Checkbox
                ),

                new GameOptionForfeitAfterGeneric(
                    $valuesHandler->clone()->reset([
                        GameOptionValueForfeitAfterGeneric::Disabled,
                        GameOptionValueForfeitAfterGeneric::Minutes5,
                    ]),
                    GameOptionValueForfeitAfterGeneric::Disabled,
                    GameOptionTypeGeneric::Checkbox
                ),

                new GameOptionThousandBarrelPointsGeneric(
                    $valuesHandler->clone()->reset([
                        GameOptionValueThousandBarrelPointsGeneric::EightHundred,
                        GameOptionValueThousandBarrelPointsGeneric::EightHundredEighty,
                        GameOptionValueThousandBarrelPointsGeneric::NineHundred,
                        GameOptionValueThousandBarrelPointsGeneric::Disabled,
                    ]),
                    GameOptionValueThousandBarrelPointsGeneric::EightHundred,
                    GameOptionTypeGeneric::Radio
                ),

                new GameOptionThousandNumberOfBombsGeneric(
                    $valuesHandler->clone()->reset([
                        GameOptionValueThousandNumberOfBombsGeneric::One,
                        GameOptionValueThousandNumberOfBombsGeneric::Two,
                        GameOptionValueThousandNumberOfBombsGeneric::Disabled,
                    ]),
                    GameOptionValueThousandNumberOfBombsGeneric::One,
                    GameOptionTypeGeneric::Radio
                ),

            ];
    }
}
