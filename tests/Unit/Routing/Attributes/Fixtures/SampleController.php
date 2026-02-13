<?php

declare(strict_types=1);

namespace Tests\Unit\Routing\Attributes\Fixtures;

use HybridGram\Core\Routing\ActionType;
use HybridGram\Core\Routing\Attributes\Cache;
use HybridGram\Core\Routing\Attributes\ChatTypes;
use HybridGram\Core\Routing\Attributes\ForBot;
use HybridGram\Core\Routing\Attributes\FromUserState;
use HybridGram\Core\Routing\Attributes\OnCallbackQuery;
use HybridGram\Core\Routing\Attributes\OnCommand;
use HybridGram\Core\Routing\Attributes\OnTextMessage;
use HybridGram\Core\Routing\Attributes\SendAction;
use HybridGram\Core\Routing\Attributes\TgMiddlewares;
use HybridGram\Core\Routing\Attributes\ToUserState;
use HybridGram\Core\Routing\ChatType;

#[ForBot('main')]
#[ChatTypes([ChatType::PRIVATE])]
#[TgMiddlewares([SampleMiddleware::class])]
class SampleController
{
    #[OnCommand(pattern: '/start')]
    public function start(): void {}

    #[OnTextMessage(pattern: 'hello*')]
    #[FromUserState(['greeting'])]
    #[ToUserState(state: 'welcomed', ttl: 3600)]
    public function greet(): void {}

    #[OnCallbackQuery(pattern: 'confirm_*')]
    #[SendAction(action: ActionType::TYPING)]
    #[TgMiddlewares([AnotherMiddleware::class])]
    public function confirm(): void {}

    #[OnTextMessage]
    #[Cache(ttl: 60, key: 'cached_text')]
    public function cachedText(): void {}
}
