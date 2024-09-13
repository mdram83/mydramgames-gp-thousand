<?php

namespace MyDramGames\Games\Thousand\Extensions\Utils;

use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardRankGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardSuitGeneric;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollection;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardFactory;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDeckProvider;
use MyDramGames\Utils\Exceptions\CollectionException;

class ThousandDeckProvider implements PlayingCardDeckProvider
{
    public function __construct(
        protected PlayingCardCollection $playingCardCollection,
        protected PlayingCardFactory $playingCardFactory,
    )
    {

    }

    /**
     * @inheritDoc
     * @throws CollectionException
     */
    public function getDeck(): PlayingCardCollection
    {
        $deck = $this->playingCardCollection->clone()->reset();

        $ranks = [
            PlayingCardRankGeneric::Nine,
            PlayingCardRankGeneric::Ten,
            PlayingCardRankGeneric::Jack,
            PlayingCardRankGeneric::Queen,
            PlayingCardRankGeneric::King,
            PlayingCardRankGeneric::Ace,
        ];

        foreach (PlayingCardSuitGeneric::cases() as $suit) {
            foreach ($ranks as $rank) {
                $deck->add($this->playingCardFactory->create(
                    $rank->getKey() . PlayingCardGeneric::PLAYING_CARD_KEY_SEPARATOR . $suit->getKey()
                ));
            }
        }

        return $deck;
    }
}
