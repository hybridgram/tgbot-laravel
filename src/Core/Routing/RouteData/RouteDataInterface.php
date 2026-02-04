<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

interface RouteDataInterface
{
    public function getChat(): ?Chat;

    public function getUser(): ?User;

    public static function match(Update $update, TelegramRoute $route): ?RouteDataInterface;
}
