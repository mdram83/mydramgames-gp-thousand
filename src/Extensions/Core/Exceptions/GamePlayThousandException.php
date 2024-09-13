<?php

namespace MyDramGames\Games\Thousand\Extensions\Core\Exceptions;

use MyDramGames\Core\Exceptions\GamePlayException;

class GamePlayThousandException extends GamePlayException
{
    public const string MESSAGE_RULE_BID_STEP_INVALID = 'Bid amount do not follow expected sequence';
    public const string MESSAGE_RULE_BID_NO_MARRIAGE = 'Can not bid over 120 points without marriage at hand';
    public const string MESSAGE_RULE_WRONG_DECLARATION = 'Invalid declaration value';
    public const string MESSAGE_RULE_BOMB_ON_BID = 'Can not use bomb after bidding over 100 points';
    public const string MESSAGE_RULE_BOMB_USED = 'Can not use more bomb moves';
    public const string MESSAGE_RULE_PLAY_TURN_SUIT = 'Need to follow led Suit if available at hand';
    public const string MESSAGE_RULE_PLAY_HIGH_RANK = 'Need to play higher rank card if available at hand';
    public const string MESSAGE_RULE_PLAY_TRUMP_PAIR = 'Can not set Trump without King and Queen pair at hand';
    public const string MESSAGE_RULE_PLAY_TRUMP_RANK = 'Can not set Trump playing other card than King and Queen';
}
