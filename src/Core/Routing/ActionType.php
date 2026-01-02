<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

/**
 * @see https://core.telegram.org/type/SendMessageAction
 */
enum ActionType
{
    case TYPING;
    case CANCEL;
    case RECORD_VIDEO;
    case UPLOAD_VIDEO;
    case RECORD_AUDIO;
    case UPLOAD_AUDIO;
    case UPLOAD_PHOTO;
    case UPLOAD_DOCUMENT;
    case GEOLOCATION;
    case CHOOSE_CONTACT;
    case GAME_PLAY;
    case RECORD_ROUND;
    case UPLOAD_ROUND;
    case SPEAKING_IN_GROUP_CALL;
    case CHAT_HISTORY_IMPORT;
    case CHOOSE_STICKER;
    case EMOJI_INTERACTION;
    case EMOJI_INTERACTION_SEEN;
}
