<?php

namespace MyDramGames\Games\Thousand\Tools;

use MyDramGames\Utils\Exceptions\CollectionException;
use MyDramGames\Utils\Php\Collection\CollectionPoweredExtendable;
use MyDramGames\Utils\Player\Player;

class PlayerDataCollectionThousand extends CollectionPoweredExtendable
{
    public const ?string TYPE_CLASS = PlayerDataThousand::class;
    protected const int KEY_MODE = self::KEYS_METHOD;

    protected function getItemKey(mixed $item): mixed
    {
        return $item->getId();
    }

    /**
     * @throws CollectionException
     */
    public function getFor(Player $player): PlayerDataThousand
    {
        return $this->getOne($player->getId());
    }
}
