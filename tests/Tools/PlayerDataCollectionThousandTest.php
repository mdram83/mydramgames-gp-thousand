<?php

namespace Tests\Tools;

use MyDramGames\Games\Thousand\Tools\PlayerDataCollectionThousand;
use MyDramGames\Games\Thousand\Tools\PlayerDataThousand;
use MyDramGames\Utils\Exceptions\CollectionException;
use PHPUnit\Framework\TestCase;

class PlayerDataCollectionThousandTest extends TestCase
{
    protected PlayerDataCollectionThousand $collection;
    protected PlayerDataThousand $dataPlayerOne;
    protected PlayerDataThousand $dataPlayerTwo;

    public function setUp(): void
    {
        $this->collection = new PlayerDataCollectionThousand();

        $this->dataPlayerOne = $this->createMock(PlayerDataThousand::class);
        $this->dataPlayerOne->method('getId')->willReturn(1);

        $this->dataPlayerTwo = $this->createMock(PlayerDataThousand::class);
        $this->dataPlayerTwo->method('getId')->willReturn(2);
    }

    public function testAddThrowExceptionWhenDuplicate(): void
    {
        $this->expectException(CollectionException::class);
        $this->expectExceptionMessage(CollectionException::MESSAGE_DUPLICATE);

        $this->collection->add($this->dataPlayerOne);
        $this->collection->add($this->dataPlayerOne);
    }

    public function testAdd(): void
    {
        $this->collection->add($this->dataPlayerOne);
        $this->collection->add($this->dataPlayerTwo);
        $this->assertEquals(2, $this->collection->count());
    }
}
