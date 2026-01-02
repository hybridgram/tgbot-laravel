<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

enum UpdateTypeEnum: string
{
    case MESSAGE = 'message';
    case EDITED_MESSAGE = 'editedMessage';
    case CHANNEL_POST = 'channelPost';
    case EDITED_CHANNEL_POST = 'editedChannelPost';
    case BUSINESS_CONNECTION = 'businessConnection';
    case BUSINESS_MESSAGE = 'businessMessage';
    case EDITED_BUSINESS_MESSAGE = 'editedBusinessMessage';
    case DELETED_BUSINESS_MESSAGES = 'deletedBusinessMessages';
    case MESSAGE_REACTION = 'messageReaction';
    case MESSAGE_REACTION_COUNT = 'messageReactionCount';
    case INLINE_QUERY = 'inlineQuery';
    case CHOSEN_INLINE_RESULT = 'chosenInlineResult';
    case CALLBACK_QUERY = 'callbackQuery';
    case SHIPPING_QUERY = 'shippingQuery';
    case PRE_CHECKOUT_QUERY = 'preCheckoutQuery';
    case POLL = 'poll';
    case POLL_CLOSED = 'pollClosed';
    case POLL_ANSWER = 'pollAnswer';
    case MY_CHAT_MEMBER = 'myChatMember';
    case CHAT_MEMBER = 'chatMember';
    case CHAT_JOIN_REQUEST = 'chatJoinRequest';
    case CHAT_BOOST = 'chatBoost';
    case REMOVED_CHAT_BOOST = 'removedChatBoost';
    case PURCHASED_PAID_MEDIA = 'purchasedPaidMedia';
}
