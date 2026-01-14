<?php

declare(strict_types=1);

namespace HybridGram\Core;

use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\UpdateMode\UpdateTypeEnum;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

final class UpdateHelper
{
    public static function getChatFromUpdate(Update $update): ?Chat
    {
        return $update->message?->chat
            ?? $update->callbackQuery?->message?->chat
            ?? $update->editedMessage?->chat
            ?? $update->channelPost?->chat
            ?? $update->editedChannelPost?->chat
            ?? $update->businessMessage?->chat
            ?? $update->editedBusinessMessage?->chat
            ?? $update->messageReaction?->chat
            ?? $update->messageReactionCount?->chat
            ?? $update->chosenInlineResult?->chat
            ?? $update->shippingQuery?->chat
            ?? $update->preCheckoutQuery?->chat
            ?? $update->pollAnswer?->chat
            ?? $update->myChatMember?->chat
            ?? $update->chatMember?->chat
            ?? $update->chatJoinRequest?->chat
            ?? $update->chatBoost?->chat;
    }

    public static function getUserFromUpdate(Update $update): ?User
    {
        return $update->message?->from
            ?? $update->callbackQuery?->from
            ?? $update->editedMessage?->from
            ?? $update->channelPost?->from
            ?? $update->editedChannelPost?->from
            ?? $update->businessMessage?->from
            ?? $update->editedBusinessMessage?->from
            ?? $update->businessConnection?->user
            ?? $update->messageReaction?->user
            ?? $update->inlineQuery?->from
            ?? $update->chosenInlineResult?->from
            ?? $update->shippingQuery?->from
            ?? $update->preCheckoutQuery?->from
            ?? $update->pollAnswer?->user
            ?? $update->myChatMember?->from
            ?? $update->chatMember?->from
            ?? $update->chatJoinRequest?->from;
    }

    public static function getUpdateTypeEnum(Update $update): UpdateTypeEnum
    {
        if ($update->editedMessage !== null) {
            return UpdateTypeEnum::EDITED_MESSAGE;
        }
        if ($update->channelPost !== null) {
            return UpdateTypeEnum::CHANNEL_POST;
        }
        if ($update->editedChannelPost !== null) {
            return UpdateTypeEnum::EDITED_CHANNEL_POST;
        }
        if ($update->businessConnection !== null) {
            return UpdateTypeEnum::BUSINESS_CONNECTION;
        }
        if ($update->businessMessage !== null) {
            return UpdateTypeEnum::BUSINESS_MESSAGE;
        }
        if ($update->editedBusinessMessage !== null) {
            return UpdateTypeEnum::EDITED_BUSINESS_MESSAGE;
        }
        if ($update->deletedBusinessMessages !== null) {
            return UpdateTypeEnum::DELETED_BUSINESS_MESSAGES;
        }
        if ($update->messageReaction !== null) {
            return UpdateTypeEnum::MESSAGE_REACTION;
        }
        if ($update->messageReactionCount !== null) {
            return UpdateTypeEnum::MESSAGE_REACTION_COUNT;
        }
        if ($update->inlineQuery !== null) {
            return UpdateTypeEnum::INLINE_QUERY;
        }
        if ($update->chosenInlineResult !== null) {
            return UpdateTypeEnum::CHOSEN_INLINE_RESULT;
        }
        if ($update->callbackQuery !== null) {
            return UpdateTypeEnum::CALLBACK_QUERY;
        }
        if ($update->shippingQuery !== null) {
            return UpdateTypeEnum::SHIPPING_QUERY;
        }
        if ($update->preCheckoutQuery !== null) {
            return UpdateTypeEnum::PRE_CHECKOUT_QUERY;
        }
        if ($update->pollAnswer !== null) {
            return UpdateTypeEnum::POLL_ANSWER;
        }
        if ($update->poll !== null) {
            return UpdateTypeEnum::POLL_CLOSED;
        }
        if ($update->message?->poll !== null) {
            return UpdateTypeEnum::POLL;
        }
        if ($update->myChatMember !== null) {
            return UpdateTypeEnum::MY_CHAT_MEMBER;
        }
        if ($update->chatMember !== null) {
            return UpdateTypeEnum::CHAT_MEMBER;
        }
        if ($update->chatJoinRequest !== null) {
            return UpdateTypeEnum::CHAT_JOIN_REQUEST;
        }
        if ($update->chatBoost !== null) {
            return UpdateTypeEnum::CHAT_BOOST;
        }
        if ($update->removedChatBoost !== null) {
            return UpdateTypeEnum::REMOVED_CHAT_BOOST;
        }
        if ($update->purchasedPaidMedia !== null) {
            return UpdateTypeEnum::PURCHASED_PAID_MEDIA;
        }
        if ($update->message !== null) {
            return UpdateTypeEnum::MESSAGE;
        }

        return UpdateTypeEnum::MESSAGE;
    }

    public static function mapToRouteType(Update $update): RouteType
    {
        $updateType = self::getUpdateTypeEnum($update);

        return match ($updateType) {
            UpdateTypeEnum::MESSAGE => self::mapMessageType($update->message),
            UpdateTypeEnum::EDITED_MESSAGE => RouteType::EDITED_MESSAGE,
            UpdateTypeEnum::CHANNEL_POST => RouteType::CHANNEL_POST,
            UpdateTypeEnum::EDITED_CHANNEL_POST => RouteType::EDITED_CHANNEL_POST,
            UpdateTypeEnum::INLINE_QUERY => RouteType::INLINE_QUERY,
            UpdateTypeEnum::BUSINESS_CONNECTION => RouteType::BUSINESS_CONNECTION,
            UpdateTypeEnum::BUSINESS_MESSAGE => RouteType::BUSINESS_MESSAGE_TEXT,
            UpdateTypeEnum::EDITED_BUSINESS_MESSAGE => RouteType::EDITED_BUSINESS_MESSAGE,
            UpdateTypeEnum::DELETED_BUSINESS_MESSAGES => RouteType::DELETED_BUSINESS_MESSAGES,
            UpdateTypeEnum::MESSAGE_REACTION => RouteType::MESSAGE_REACTION,
            UpdateTypeEnum::MESSAGE_REACTION_COUNT => RouteType::MESSAGE_REACTION_COUNT,
            UpdateTypeEnum::CHOSEN_INLINE_RESULT => RouteType::CHOSEN_INLINE_RESULT,
            UpdateTypeEnum::CALLBACK_QUERY => RouteType::CALLBACK_QUERY,
            UpdateTypeEnum::SHIPPING_QUERY => RouteType::SHIPPING_QUERY,
            UpdateTypeEnum::PRE_CHECKOUT_QUERY => RouteType::PRE_CHECKOUT_QUERY,
            UpdateTypeEnum::PURCHASED_PAID_MEDIA => RouteType::PURCHASED_PAID_MEDIA,
            UpdateTypeEnum::POLL => RouteType::POLL,
            UpdateTypeEnum::POLL_ANSWER => RouteType::POLL_ANSWER,
            UpdateTypeEnum::POLL_CLOSED => RouteType::POLL_CLOSED,
            UpdateTypeEnum::CHAT_MEMBER => RouteType::CHAT_MEMBER,
            UpdateTypeEnum::CHAT_JOIN_REQUEST => RouteType::CHAT_JOIN_REQUEST,
            UpdateTypeEnum::MY_CHAT_MEMBER => RouteType::MY_CHAT_MEMBER,
            UpdateTypeEnum::CHAT_BOOST => RouteType::CHAT_BOOST,
            UpdateTypeEnum::REMOVED_CHAT_BOOST => RouteType::REMOVED_CHAT_BOOST,
            default => RouteType::UNKNOWN,
        };
    }

    private static function mapMessageType(?Message $message): RouteType
    {
        if ($message === null) {
            return RouteType::TEXT_MESSAGE;
        }

        if (!empty($message->newChatTitle)) {
            return RouteType::NEW_CHAT_TITLE;
        }

        if (!empty($message->newChatPhoto)) {
            return RouteType::NEW_CHAT_PHOTO;
        }

        if (!empty($message->deleteChatPhoto)) {
            return RouteType::DELETE_CHAT_PHOTO;
        }

        if ($message->messageAutoDeleteTimerChanged !== null) {
            return RouteType::AUTO_DELETE_TIMER_CHANGED;
        }

        if ($message->pinnedMessage !== null) {
            return RouteType::PINNED_MESSAGE;
        }

        if ($message->forumTopicCreated !== null) {
            return RouteType::FORUM_TOPIC_CREATED;
        }

        if ($message->forumTopicEdited !== null) {
            return RouteType::FORUM_TOPIC_EDITED;
        }

        if ($message->forumTopicClosed !== null) {
            return RouteType::FORUM_TOPIC_CLOSED;
        }

        if ($message->forumTopicReopened !== null) {
            return RouteType::FORUM_TOPIC_REOPENED;
        }

        if (
            $message->generalForumTopicHidden !== null
            || $message->generalForumTopicUnhidden !== null
        ) {
            return RouteType::GENERAL_FORUM_TOPIC_EVENT;
        }

        if ($message->boostAdded !== null) {
            return RouteType::BOOST_ADDED;
        }

        if ($message->animation) {
            return RouteType::ANIMATION;
        }

        if ($message->audio) {
            return RouteType::AUDIO;
        }

        if ($message->sticker) {
            return RouteType::STICKER;
        }

        if ($message->videoNote) {
            return RouteType::VIDEO_NOTE;
        }

        if ($message->voice) {
            return RouteType::VOICE;
        }

        if ($message->paidMedia) {
            return RouteType::PAID_MEDIA;
        }

        if ($message->externalReply !== null) {
            return RouteType::EXTERNAL_REPLY_MESSAGE;
        }

        if ($message->quote !== null) {
            return RouteType::QUOTED_MESSAGE;
        }

        if ($message->replyToStory !== null) {
            return RouteType::REPLY_TO_STORY;
        }

        if ($message->story) {
            return RouteType::STORY;
        }

        if ($message->text !== null && str_starts_with($message->text, '/')) {
            return RouteType::COMMAND;
        }

        if (!empty($message->photo)) {
            if (!is_null($message->mediaGroupId)) {
                return RouteType::PHOTO_MEDIA_GROUP;
            }
            return RouteType::PHOTO;
        }

        if (!empty($message->document)) {
            if (!is_null($message->mediaGroupId)) {
                return RouteType::DOCUMENT_MEDIA_GROUP;
            }
            return RouteType::DOCUMENT;
        }

        if (!empty($message->contact)) {
            return RouteType::CONTACT;
        }

        if (!empty($message->checklist)) {
            return RouteType::CHECKLIST;
        }

        if (!empty($message->dice)) {
            return RouteType::DICE;
        }

        if (!empty($message->game)) {
            return RouteType::GAME;
        }

        if (!empty($message->invoice)) {
            return RouteType::INVOICE;
        }

        if (!empty($message->successfulPayment)) {
            return RouteType::SUCCESSFUL_PAYMENT;
        }

        if (!empty($message->passportData)) {
            return RouteType::PASSPORT_DATA;
        }

        if ($message->replyToMessage !== null) {
            return RouteType::REPLY_TO_MESSAGE;
        }

        return RouteType::TEXT_MESSAGE;
    }

    private static function mapBusinessMessageType(?Message $message): RouteType
    {
        if ($message->text !== null && str_starts_with($message->text, '/')) {
            return RouteType::BUSINESS_MESSAGE_COMMAND;
        }

        return RouteType::BUSINESS_MESSAGE_TEXT;
    }

    public static function getChatType(Update $update): ChatType
    {
        $chat = self::getChatFromUpdate($update);

        if ($chat === null) {
            return ChatType::PRIVATE;
        }

        return match ($chat->type) {
            'private' => ChatType::PRIVATE,
            'group' => ChatType::GROUP,
            'supergroup' => ChatType::SUPERGROUP,
            'channel' => ChatType::CHANNEL,
            default => ChatType::PRIVATE,
        };
    }
}

