<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\UpdateHelper;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

abstract readonly class AbstractRouteData implements RouteDataInterface
{
    public function __construct(
        public Update $update,
        public string $botId,
    ) {}

    public function getChat(): Chat
    {
        $chat = UpdateHelper::getChatFromUpdate($this->update);
        if ($chat === null) {
            throw new \RuntimeException('Chat not found in update');
        }
        return $chat;
    }

    public function getUser(): ?User
    {
        return UpdateHelper::getUserFromUpdate($this->update);
    }
}

