<?php

namespace Tests\Extensions\Core\GameResult;

use MyDramGames\Core\Exceptions\GameResultProviderException;
use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GameRecord\GameRecord;
use MyDramGames\Core\GameRecord\GameRecordCollection;
use MyDramGames\Core\GameRecord\GameRecordCollectionPowered;
use MyDramGames\Core\GameRecord\GameRecordFactory;
use MyDramGames\Core\GameResult\GameResultProvider;
use MyDramGames\Games\Thousand\Extensions\Core\GameResult\GameResultProviderThousand;
use MyDramGames\Games\Thousand\Extensions\Core\GameResult\GameResultThousand;
use MyDramGames\Utils\Player\Player;
use MyDramGames\Utils\Player\PlayerCollection;
use MyDramGames\Utils\Player\PlayerCollectionPowered;
use PHPUnit\Framework\TestCase;

class GameResultProviderThousandTest extends TestCase
{
    private GameRecordFactory $recordFactory;
    private GameInvite $invite;
    private GameResultProviderThousand $provider;

    private PlayerCollection $players;
    private array $playersDataWin;
    private array $playersDataNoWin;

    public function setUp(): void
    {
        $this->recordFactory = $this->createMock(GameRecordFactory::class);
        $this->recordFactory->method('create')->willReturn($this->createMock(GameRecord::class));
        $this->invite = $this->createMock(GameInvite::class);
        $this->provider = new GameResultProviderThousand($this->recordFactory, new GameRecordCollectionPowered());

        $players = [];
        for ($i = 0; $i <= 2; $i++) {
            $player = $this->createMock(Player::class);
            $player->method('getId')->willReturn("Id$i");
            $player->method('getName')->willReturn("Player_$i");
            $players[] = $player;

            $this->playersDataWin[$player->getId()]['points'] = [1 => ($i + 1) * 100, 2 => ($i + 1) * 350];
            $this->playersDataNoWin[$player->getId()]['points'] = [1 => ($i + 1) * 10, 2 => ($i + 1) * 100];

            $this->playersDataWin[$player->getId()]['seat'] = $i + 1;
            $this->playersDataNoWin[$player->getId()]['seat'] = $i + 1;
        }

        $this->players = new PlayerCollectionPowered(null, $players);
    }

    public function testInterface(): void
    {
        $this->assertInstanceOf(GameResultProvider::class, $this->provider);
    }

    public function testThrowExceptionWhenGetResultWithoutPlayersCollection(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_INCORRECT_DATA_PARAMETER);

        $this->provider->getResult(['players' => [], 'playersData' => $this->playersDataWin]);
    }

    public function testThrowExceptionWhenGetResultPlayersCollectionNotMatchingPlayersData(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_INCORRECT_DATA_PARAMETER);

        $this->playersDataWin['wrongId'] = $this->playersDataWin['Id0'];
        unset($this->playersDataWin['Id0']);

        $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
    }

    public function testThrowExceptionWhenGetResultPlayersDataMissPoints(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_INCORRECT_DATA_PARAMETER);

        unset($this->playersDataWin['Id0']['points']);
        $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
    }

    public function testThrowExceptionWhenGetResultsCalledSecondTime(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_RESULTS_ALREADY_SET);

        $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
        $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
    }

    public function testThrowExceptionWhenCreateGameRecordWithoutResult(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_RESULT_NOT_SET);

        $this->provider->createGameRecords($this->invite);
    }

    public function testThrowExceptionWhenCreateGameRecordSecondTime(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_RECORD_ALREADY_SET);

        $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
        $this->provider->createGameRecords($this->invite);
        $this->provider->createGameRecords($this->invite);
    }

    public function testGetResultWithWin(): void
    {
        $result = $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
        $this->assertInstanceOf(GameResultThousand::class, $result);
    }

    public function testGetResultWithNoWin(): void
    {
        $result = $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataNoWin]);
        $this->assertNull($result);
    }

    public function testCreateGameRecordsWin(): void
    {
        $this->provider->getResult(['players' => $this->players, 'playersData' => $this->playersDataWin]);
        $records = $this->provider->createGameRecords($this->invite);

        $this->assertInstanceOf(GameRecordCollection::class, $records);
        $this->assertEquals(3, $records->count());
    }

    public function testThrowExceptionWhenGetResultInvalidForfeited(): void
    {
        $this->expectException(GameResultProviderException::class);
        $this->expectExceptionMessage(GameResultProviderException::MESSAGE_INCORRECT_DATA_PARAMETER);

        $this->provider->getResult([
            'players' => $this->players,
            'playersData' => $this->playersDataNoWin,
            'forfeited' => 'not-player',
        ]);
    }

    public function testGetResultWithForfeited(): void
    {
        $result = $this->provider->getResult([
            'players' => $this->players,
            'playersData' => $this->playersDataNoWin,
            'forfeited' => $this->players->getOne('Id0'),
        ]);
        $this->assertNotNull($result);
    }
}
