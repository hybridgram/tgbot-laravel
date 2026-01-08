<?php

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\User;

interface RouteDataInterface
{
    public function getChat(): ?Chat;
    public function getUser(): ?User;
}