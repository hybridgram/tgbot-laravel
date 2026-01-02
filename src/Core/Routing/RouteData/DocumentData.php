<?php

namespace HybridGram\Core\Routing\RouteData;


use Phptg\BotApi\Type\Document;
use Phptg\BotApi\Type\Update\Update;

final readonly class DocumentData extends AbstractRouteData
{
    public function __construct(
        Update       $update,
        public       Document $document,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}