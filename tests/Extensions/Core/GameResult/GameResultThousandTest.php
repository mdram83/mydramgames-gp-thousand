<?php

namespace Tests\Extensions\Core\GameResult;

use MyDramGames\Core\Exceptions\GameResultException;
use MyDramGames\Core\GameResult\GameResult;
use MyDramGames\Games\Thousand\Extensions\Core\GameResult\GameResultThousand;
use MyDramGames\Utils\Player\Player;
use PHPUnit\Framework\TestCase;

class GameResultThousandTest extends TestCase
{
    private array $players;
    private array $points;
    private array $pointsForfeit;

    public function setUp(): void
    {
        parent::setUp();
        for ($i = 0; $i <= 2; $i++) {
            $player = $this->createMock(Player::class);
            $player->method('getName')->willReturn('Player' . ($i + 1));
            $this->players[$i] = $player;
            $this->points[$i + 1] = ['playerName' => $player->getName(), 'points' => $i * 350];
            $this->pointsForfeit[$i + 1] = ['playerName' => $player->getName(), 'points' => $i * 100];
        }
    }

    public function testInterface(): void
    {
        $result = new GameResultThousand($this->points, $this->players[0]);
        $this->assertInstanceOf(GameResult::class, $result);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataKeysNotStrings(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points['asd'] = $this->points[1];
        unset($this->points[1]);
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataKeysNotOneToFour(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[5] = $this->points[1];
        unset($this->points[1]);
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataMissingPlayerName(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['noPlayerNameKey' => $this->players[0]->getName(), 'points' => 1150];
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataMissingPoints(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['playerName' => $this->players[0]->getName(), 'missingPoints' => 1150];
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataPlayerNameNotString(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['playerName' => 123, 'points' => 1150];
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataPlayerNameEmptyString(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['playerName' => '', 'points' => 1150];
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithInvalidDataPointsNotInt(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['playerName' => $this->players[0]->getName(), 'points' => 'asd'];
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithWinnerNotInPointsList(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['playerName' => 'WrongName', 'points' => 1150];
        new GameResultThousand($this->points, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithForfeiterNotInPointsList(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        $this->points[1] = ['playerName' => 'WrongName', 'points' => 0];
        new GameResultThousand($this->points, null, $this->players[0]);
    }

    public function testThrowExceptionWhenNewResultWithoutWinnerAndForfeiter(): void
    {
        $this->expectException(GameResultException::class);
        $this->expectExceptionMessage(GameResultException::MESSAGE_INCORRECT_PARAMETER);

        new GameResultThousand($this->points);
    }

    public function testGetMessageOnWin(): void
    {
        $result = new GameResultThousand($this->points, $this->players[2]);

        $this->assertStringContainsString($this->players[2]->getName(), $result->getMessage());
        $this->assertStringContainsString($result::MESSAGE_WIN, $result->getMessage());
    }

    public function testGetMessageOnForfeit(): void
    {
        $result = new GameResultThousand($this->pointsForfeit, null, $this->players[0]);

        $this->assertStringContainsString($this->players[0]->getName(), $result->getMessage());
        $this->assertStringContainsString($result::MESSAGE_FORFEIT, $result->getMessage());
    }

    public function testGetDetailsOnWin(): void
    {
        $result = new GameResultThousand($this->points, $this->players[2]);
        $expectedDetails = [
            'winnerName' => $this->players[2]->getName(),
            'points' => $this->points,
            'forfeitedName' => null,
        ];

        $this->assertEquals($expectedDetails, $result->getDetails());
    }

    public function testGetDetailsOnForfeit(): void
    {
        $result = new GameResultThousand($this->pointsForfeit, null, $this->players[2]);
        $expectedDetails = [
            'winnerName' => null,
            'points' => $this->pointsForfeit,
            'forfeitedName' => $this->players[2]->getName(),
        ];

        $this->assertEquals($expectedDetails, $result->getDetails());
    }

    public function testToArray(): void
    {
        $resultWin = new GameResultThousand($this->points, $this->players[2]);
        $resultForfeit = new GameResultThousand($this->pointsForfeit, null, $this->players[2]);

        $this->assertEquals($resultWin->getDetails(), $resultWin->toArray());
        $this->assertEquals($resultForfeit->getDetails(), $resultForfeit->toArray());
    }
}
