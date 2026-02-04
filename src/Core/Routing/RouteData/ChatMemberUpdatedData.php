<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\RouteOptions\ChatMemberOptions;
use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\ChatMemberUpdated;
use Phptg\BotApi\Type\Update\Update;

final readonly class ChatMemberUpdatedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ChatMemberUpdated $chatMemberUpdated,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ChatMemberUpdatedData
    {
        $chatMemberUpdated = $update->myChatMember ?? $update->chatMember;

        if ($chatMemberUpdated === null) {
            return null;
        }

        $options = $route->chatMemberOptions;

        if ($options instanceof ChatMemberOptions) {
            if ($options->isBot !== null) {
                if ($chatMemberUpdated->newChatMember->getUser()->isBot !== $options->isBot) {
                    return null;
                }
            }

            if ($options->allowedStatuses !== null && $options->allowedStatuses !== []) {
                $newStatus = $chatMemberUpdated->newChatMember->getStatus();
                $allowed = false;

                foreach ($options->allowedStatuses as $allowedStatus) {
                    if ($allowedStatus->value === $newStatus) {
                        $allowed = true;
                        break;
                    }
                }

                if (! $allowed) {
                    return null;
                }
            }
        }

        return new ChatMemberUpdatedData($update, $chatMemberUpdated, $route->botId);
    }
}
