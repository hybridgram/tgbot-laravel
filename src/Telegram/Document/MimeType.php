<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Document;

enum MimeType: string
{
    case PNG = 'image/png';
    case JPEG = 'image/jpeg';
    case WEBP = 'image/webp';
    case GIF = 'image/gif';
    case PDF = 'application/pdf';
    case MP4 = 'video/mp4';
    case MPEG = 'video/mpeg';
    case MOV = 'video/quicktime';
    case HTML = 'text/html';
    case JSON = 'application/json';
}
