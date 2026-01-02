<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

enum ChatType
{
    case PRIVATE;
    case GROUP;
    case SUPERGROUP;
    case CHANNEL;
}
