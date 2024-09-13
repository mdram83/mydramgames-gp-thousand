<?php

namespace MyDramGames\Games\Thousand\Tools;

use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollection;
use MyDramGames\Utils\Player\Player;

class PlayerDataThousand
{
    readonly private int|string $playerId;

    public int $seat;

    public PlayingCardCollection $hand;
    public PlayingCardCollection $tricks;

    public int|string|null $bid = null;
    public bool $ready = true;
    public bool $barrel = false;
    public array $points = [];
    public array $bombRounds = [];
    public array $trumps = [];

    public function __construct(Player $player)
    {
        $this->playerId = $player->getId();
    }

    public function getId(): int|string
    {
        return $this->playerId;
    }

    public function toArray(): array
    {
        return [
            'seat' => $this->seat,
            'hand' => $this->hand,
            'tricks' => $this->tricks,
            'bid' => $this->bid,
            'ready' => $this->ready,
            'barrel' => $this->barrel,
            'points' => $this->points,
            'bombRounds' => $this->bombRounds,
            'trumps' => $this->trumps,
        ];
    }
}
