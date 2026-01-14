<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

enum RouteType
{
    case ANY;
    case COMMAND;
    case TEXT_MESSAGE;
    case POLL;
    case POLL_ANSWER;
    case POLL_CLOSED;
    case PHOTO;
    case PHOTO_MEDIA_GROUP;
    case DOCUMENT;
    case DOCUMENT_MEDIA_GROUP;
    case VENUE;
    case LOCATION;

    case ANIMATION;
    case AUDIO;
    case STICKER;
    case VIDEO;
    case VIDEO_NOTE;
    case VOICE;
    case STORY;
    case PAID_MEDIA;
    case CONTACT;
    case CHECKLIST;
    case DICE;
    case GAME;
    case TEXT;
    case INVOICE;
    case SUCCESSFUL_PAYMENT;
    case PASSPORT_DATA;
    case EXTERNAL_REPLY_MESSAGE;
    case QUOTED_MESSAGE;
    case REPLY_TO_STORY;
    case NEW_CHAT_TITLE;
    case NEW_CHAT_PHOTO;
    case DELETE_CHAT_PHOTO;
    case AUTO_DELETE_TIMER_CHANGED;
    case PINNED_MESSAGE;
    case FORUM_TOPIC_EVENT;
    case FORUM_TOPIC_CREATED;
    case FORUM_TOPIC_EDITED;
    case FORUM_TOPIC_CLOSED;
    case FORUM_TOPIC_REOPENED;
    case GENERAL_FORUM_TOPIC_EVENT;
    case BOOST_ADDED;
    case REPLY_TO_MESSAGE;
    case EDITED_MESSAGE;
    case CALLBACK_QUERY_TEXT;
    case CALLBACK_QUERY_DATA;
    case CALLBACK_QUERY;
    case SHIPPING_QUERY;
    case PRE_CHECKOUT_QUERY;
    case SUCCESSFULLY_PAYMENT;
    case PASSPORT_DATE;
    case INLINE_QUERY;
    case CHOSEN_INLINE_RESULT;
    case CHANNEL_POST;
    case EDITED_CHANNEL_POST;
    case CHAT_JOIN_REQUEST;
    case CHAT_MEMBER_UPDATED;
    case MY_CHAT_MEMBER;
    case WEBAPP_DATA;
    case USER_SHARED;
    case CHAT_SHARED;
    case UPDATE;
    case BUSINESS_CONNECTION;
    case BUSINESS_MESSAGE_COMMAND;
    case BUSINESS_MESSAGE_TEXT;
    case EDITED_BUSINESS_MESSAGE;
    case REMOVED_CHAT_BOOST;
    case CHAT_BOOST;
    case CHAT_MEMBER;
    case PURCHASED_PAID_MEDIA;
    case MESSAGE_REACTION_COUNT;
    case MESSAGE_REACTION;
    case DELETED_BUSINESS_MESSAGES;

    case UNKNOWN;

    case FALLBACK;
}
