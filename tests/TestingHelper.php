<?php

namespace Tests;

use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GameRecord\GameRecord;
use MyDramGames\Core\GameRecord\GameRecordFactory;
use MyDramGames\Utils\Player\Player;

class TestingHelper
{
    public static function getGameRecordFactory(): GameRecordFactory
    {
        return new class() implements GameRecordFactory
        {
            public function create(GameInvite $invite, Player $player, bool $isWinner, array $score): GameRecord
            {
                return new readonly class($invite, $player, $isWinner, $score) implements GameRecord
                {
                    public function __construct(
                        private GameInvite $invite,
                        private Player $player,
                        private bool $isWinner,
                        private array $score,
                    )
                    {

                    }

                    public function getPlayer(): Player
                    {
                        return $this->player;
                    }

                    public function getGameInvite(): GameInvite
                    {
                        return $this->invite;
                    }

                    public function getScore(): array
                    {
                        return $this->score;
                    }

                    public function isWinner(): bool
                    {
                        return $this->isWinner;
                    }
                };
            }
        };
    }
}
