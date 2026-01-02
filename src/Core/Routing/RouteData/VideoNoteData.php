<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\VideoNote;

final readonly class VideoNoteData extends AbstractRouteData
{
    public function __construct(
        Update       $update,
        public VideoNote $videoNote,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

