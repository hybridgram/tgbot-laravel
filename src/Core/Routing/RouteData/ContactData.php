<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}

