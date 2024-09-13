<?php

namespace Tests\Extensions\Utils;

use MyDramGames\Games\Thousand\Extensions\Utils\ThousandDeckProvider;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardFactoryGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardRankGeneric;
use MyDramGames\Utils\Decks\PlayingCard\Generic\PlayingCardSuitGeneric;
use MyDramGames\Utils\Decks\PlayingCard\PlayingCardCollectionPoweredUnique;
use MyDramGames\Utils\Decks\PlayingCard\Support\PlayingCardDeckProvider;
use PHPUnit\Framework\TestCase;

class ThousandDeckProviderTest extends TestCase
{
    private ThousandDeckProvider $provider;

    public function setUp(): void
    {
        $this->provider = new ThousandDeckProvider(
            new PlayingCardCollectionPoweredUnique(),
            new PlayingCardFactoryGeneric()
        );
    }
    public function testInterfaceImplementation(): void
    {
        $this->assertInstanceOf(PlayingCardDeckProvider::class, $this->provider);
    }

    public function testGetDeck(): void
    {
        $deck = $this->provider->getDeck();

        $this->assertEquals(24, $deck->count());

        $this->assertEquals(6, $deck->filter(fn($item) => $item->getSuit()->getKey() === PlayingCardSuitGeneric::Hearts->getKey())->count());
        $this->assertEquals(6, $deck->filter(fn($item) => $item->getSuit()->getKey() === PlayingCardSuitGeneric::Diamonds->getKey())->count());
        $this->assertEquals(6, $deck->filter(fn($item) => $item->getSuit()->getKey() === PlayingCardSuitGeneric::Spades->getKey())->count());
        $this->assertEquals(6, $deck->filter(fn($item) => $item->getSuit()->getKey() === PlayingCardSuitGeneric::Clubs->getKey())->count());

        $this->assertEquals(4, $deck->filter(fn($item) => $item->getRank()->getKey() === PlayingCardRankGeneric::Nine->getKey())->count());
        $this->assertEquals(4, $deck->filter(fn($item) => $item->getRank()->getKey() === PlayingCardRankGeneric::Ten->getKey())->count());
        $this->assertEquals(4, $deck->filter(fn($item) => $item->getRank()->getKey() === PlayingCardRankGeneric::Jack->getKey())->count());
        $this->assertEquals(4, $deck->filter(fn($item) => $item->getRank()->getKey() === PlayingCardRankGeneric::Queen->getKey())->count());
        $this->assertEquals(4, $deck->filter(fn($item) => $item->getRank()->getKey() === PlayingCardRankGeneric::King->getKey())->count());
        $this->assertEquals(4, $deck->filter(fn($item) => $item->getRank()->getKey() === PlayingCardRankGeneric::Ace->getKey())->count());
    }
}
