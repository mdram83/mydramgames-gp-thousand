<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\GameResult;

use MyDramGames\Core\Exceptions\GameResultException;
use MyDramGames\Core\Exceptions\GameResultProviderException;
use MyDramGames\Core\GameInvite\GameInvite;
use MyDramGames\Core\GameRecord\GameRecordCollection;
use MyDramGames\Core\GameRecord\GameRecordCollectionPowered;
use MyDramGames\Core\GameResult\GameResult;
use MyDramGames\Core\GameResult\GameResultProvider;
use MyDramGames\Core\GameResult\GameResultProviderBase;
use MyDramGames\Utils\Exceptions\CollectionException;
use MyDramGames\Utils\Player\Player;
use MyDramGames\Utils\Player\PlayerCollection;

class GameResultProviderThousand extends GameResultProviderBase implements GameResultProvider
{
    private bool $resultProvided = false;
    private bool $recordCreated = false;
    private ?Player $winner = null;
    private ?Player $forfeited = null;

    private PlayerCollection $players;
    private array $playersData;

    /**
     * @throws GameResultProviderException
     * @throws GameResultException
     */
    public function getResult(mixed $data): ?GameResult
    {
        if ($this->resultProvided) {
            throw new GameResultProviderException(GameResultProviderException::MESSAGE_RESULTS_ALREADY_SET);
        }

        $this->validateData($data);
        $this->players = $data['players'];
        $this->playersData = $data['playersData'];
        $this->forfeited = $data['forfeited'] ?? null;

        $this->resultProvided = true;

        return $this->getForfeitResult() ?? $this->getWinResult();
    }

    /**
     * @throws GameResultProviderException|CollectionException
     */
    public function createGameRecords(GameInvite $gameInvite): GameRecordCollection
    {
        if (!$this->resultProvided) {
            throw new GameResultProviderException(GameResultProviderException::MESSAGE_RESULT_NOT_SET);
        }

        if ($this->recordCreated) {
            throw new GameResultProviderException(GameResultProviderException::MESSAGE_RECORD_ALREADY_SET);
        }

        $this->gameRecordCollection->reset();

        foreach ($this->players->toArray() as $playerId => $player) {
            $record = $this->gameRecordFactory->create(
                $gameInvite,
                $player,
                $player->getId() === $this->winner?->getId(),
                array_merge(
                    ['pointsTable' => $this->playersData[$player->getId()]['points']],
                    !isset($this->forfeited) ? [] : ['forfeit' => $player->getId() === $this->forfeited->getId()]
                )

            );
            $this->gameRecordCollection->add($record);
        }

        $this->recordCreated = true;

        return $this->gameRecordCollection;
    }

    /**
     * @throws GameResultProviderException
     */
    private function validateData(mixed $data): void
    {
        if (
            !is_array($data)
            || !isset($data['players']) || !is_a($data['players'], PlayerCollection::class)
            || count(array_diff_key($data['players']->toArray(), $data['playersData'])) > 0
            || count(array_diff_key($data['playersData'], $data['players']->toArray())) > 0
            || (isset($data['forfeited']) && !is_a($data['forfeited'], Player::class))
        ) {
            throw new GameResultProviderException(GameResultProviderException::MESSAGE_INCORRECT_DATA_PARAMETER);
        }

        foreach ($data['playersData'] as $playerId => $playerData) {
            if (!isset($playerData['points'])) {
                throw new GameResultProviderException(GameResultProviderException::MESSAGE_INCORRECT_DATA_PARAMETER);
            }
        }
    }

    /**
     * @throws GameResultException
     */
    private function getWinResult(): ?GameResultThousand
    {
        [$points, $resultData] = $this->getLastRoundPointsAndResultData();

        $maxPoints = max($points);

        if ($maxPoints < 1000) {
            return null;
        }

        $winnerPoints = array_filter($points, fn($point) => $point === $maxPoints);
        $winnerId = array_keys($winnerPoints)[0];
        $this->winner = $this->players->getOne($winnerId);

        return new GameResultThousand($resultData, $this->winner);
    }

    /**
     * @throws GameResultException
     */
    private function getForfeitResult(): ?GameResultThousand
    {
        if (isset($this->forfeited)) {
            [$points, $resultData] = $this->getLastRoundPointsAndResultData();
            return new GameResultThousand($resultData, null, $this->forfeited);
        }
        return null;
    }

    private function getLastRoundPointsAndResultData(): array
    {
        $points = [];
        $resultData = [];

        foreach ($this->playersData as $playerId => $playerData) {

            $points[$playerId] =
                count($playerData['points']) > 0
                    ? $playerData['points'][max(array_keys($playerData['points']))]
                    : 0;
            $resultData[$this->playersData[$playerId]['seat']] = [
                'playerName' => $this->players->getOne($playerId)->getName(),
                'points' => $points[$playerId]
            ];
        }

        return [$points, $resultData];
    }
}
