<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Contact;
use Phptg\BotApi\Type\Update\Update;

final readonly class ContactData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Contact $contact,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ContactData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->contact)) {
            return null;
        }

        return new ContactData($update, $update->message->contact, $route->botId);
    }
}
