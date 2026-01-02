<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

use Phptg\BotApi\Type\Update\Update;

interface UpdateModeInterface
{
    public function run(?Update $update = null): void;

    public function type(): UpdateModeEnum;
}
