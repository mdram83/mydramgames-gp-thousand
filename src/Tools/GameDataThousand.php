<?php

namespace MyDramGames\Games\Thousand\Tools;

use MyDramGames\Core\GamePhase\GamePhase;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollection;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardSuit;
use MyDramGames\Utils\Player\Player;

class GameDataThousand
{
    public Player $dealer;
    public Player $obligation;

    public ?Player $bidWinner;
    public int $bidAmount;

    public PlayingCardCollection $stock;
    public PlayingCardCollection $stockRecord;
    public PlayingCardCollection $table;
    public PlayingCardCollection $deck;

    public int $round;
    public ?PlayingCardSuit $trumpSuit;
    public ?PlayingCardSuit $turnSuit;
    public ?Player $turnLead;
    public GamePhase $phase;

    public function advanceGamePhase(bool $lastAttempt): void
    {
        $this->phase = $this->phase->getNextPhase($lastAttempt);
    }
}
