<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Poll;

enum PollType: string
{
    case REGULAR = 'regular';
    case QUIZ = 'quiz';
}
