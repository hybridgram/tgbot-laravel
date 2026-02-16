<?php

declare(strict_types=1);

use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\UpdateHelper;
use HybridGram\Core\UpdateMode\UpdateTypeEnum;
use Phptg\BotApi\Type\BusinessConnection;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\ChatBoost;
use Phptg\BotApi\Type\ChatBoostSourcePremium;
use Phptg\BotApi\Type\ChatBoostUpdated;
use Phptg\BotApi\Type\ChatJoinRequest;
use Phptg\BotApi\Type\ChatMemberMember;
use Phptg\BotApi\Type\ChatMemberUpdated;
use Phptg\BotApi\Type\Inline\ChosenInlineResult;
use Phptg\BotApi\Type\Inline\InlineQuery;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\MessageReactionCountUpdated;
use Phptg\BotApi\Type\MessageReactionUpdated;
use Phptg\BotApi\Type\Payment\PreCheckoutQuery;
use Phptg\BotApi\Type\Payment\ShippingAddress;
use Phptg\BotApi\Type\Payment\ShippingQuery;
use Phptg\BotApi\Type\Poll;
use Phptg\BotApi\Type\PollAnswer;
use Phptg\BotApi\Type\PollOption;
use Phptg\BotApi\Type\Sticker\Sticker;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

describe('UpdateHelper::getChatFromUpdate', function () {
    it('extracts chat from message', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, message: $message);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from callbackQuery', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $callbackQuery = new CallbackQuery(
            id: 'cb1',
            from: new User(id: 456, isBot: false, firstName: 'Test'),
            chatInstance: '0',
            message: $message,
        );
        $update = new Update(updateId: 1, callbackQuery: $callbackQuery);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from editedMessage', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedMessage: $message);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from channelPost', function () {
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, channelPost: $message);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from editedChannelPost', function () {
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedChannelPost: $message);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from businessMessage', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, businessMessage: $message);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from editedBusinessMessage', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedBusinessMessage: $message);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from messageReaction', function () {
        $chat = new Chat(id: 123, type: 'private');
        $messageReaction = new MessageReactionUpdated(
            chat: $chat,
            messageId: 1,
            date: new DateTimeImmutable,
            oldReaction: [],
            newReaction: [],
        );
        $update = new Update(updateId: 1, messageReaction: $messageReaction);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from messageReactionCount', function () {
        $chat = new Chat(id: 123, type: 'private');
        $messageReactionCount = new MessageReactionCountUpdated(
            chat: $chat,
            messageId: 1,
            date: new DateTimeImmutable,
            reactions: [],
        );
        $update = new Update(updateId: 1, messageReactionCount: $messageReactionCount);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from myChatMember', function () {
        $chat = new Chat(id: 123, type: 'group');
        $myChatMember = new ChatMemberUpdated(
            chat: $chat,
            from: new User(id: 456, isBot: false, firstName: 'Test'),
            date: new DateTimeImmutable,
            oldChatMember: new ChatMemberMember(user: new User(id: 789, isBot: true, firstName: 'Bot')),
            newChatMember: new ChatMemberMember(user: new User(id: 789, isBot: true, firstName: 'Bot')),
        );
        $update = new Update(updateId: 1, myChatMember: $myChatMember);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from chatMember', function () {
        $chat = new Chat(id: 123, type: 'group');
        $chatMemberUpdate = new ChatMemberUpdated(
            chat: $chat,
            from: new User(id: 456, isBot: false, firstName: 'Test'),
            date: new DateTimeImmutable,
            oldChatMember: new ChatMemberMember(user: new User(id: 789, isBot: false, firstName: 'Other')),
            newChatMember: new ChatMemberMember(user: new User(id: 789, isBot: false, firstName: 'Other')),
        );
        $update = new Update(updateId: 1, chatMember: $chatMemberUpdate);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from chatJoinRequest', function () {
        $chat = new Chat(id: 123, type: 'supergroup');
        $chatJoinRequest = new ChatJoinRequest(
            chat: $chat,
            from: new User(id: 456, isBot: false, firstName: 'Test'),
            userChatId: 789,
            date: new DateTimeImmutable,
        );
        $update = new Update(updateId: 1, chatJoinRequest: $chatJoinRequest);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('extracts chat from chatBoost', function () {
        $chat = new Chat(id: 123, type: 'channel');
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chatBoost = new ChatBoostUpdated(
            chat: $chat,
            boost: new ChatBoost(
                boostId: 'boost1',
                addDate: new DateTimeImmutable,
                expirationDate: new DateTimeImmutable,
                source: new ChatBoostSourcePremium(user: $user),
            ),
        );
        $update = new Update(updateId: 1, chatBoost: $chatBoost);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBe($chat);
    });

    it('returns null when no chat is found', function () {
        $update = new Update(updateId: 1);

        $result = UpdateHelper::getChatFromUpdate($update);

        expect($result)->toBeNull();
    });
});

describe('UpdateHelper::getUserFromUpdate', function () {
    it('extracts user from message', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            from: $user,
        );
        $update = new Update(updateId: 1, message: $message);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from callbackQuery', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $callbackQuery = new CallbackQuery(
            id: 'cb1',
            from: $user,
            chatInstance: '0',
        );
        $update = new Update(updateId: 1, callbackQuery: $callbackQuery);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from editedMessage', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            from: $user,
        );
        $update = new Update(updateId: 1, editedMessage: $message);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from channelPost', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            from: $user,
        );
        $update = new Update(updateId: 1, channelPost: $message);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from editedChannelPost', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            from: $user,
        );
        $update = new Update(updateId: 1, editedChannelPost: $message);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from businessMessage', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            from: $user,
        );
        $update = new Update(updateId: 1, businessMessage: $message);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from editedBusinessMessage', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            from: $user,
        );
        $update = new Update(updateId: 1, editedBusinessMessage: $message);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from businessConnection', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $businessConnection = new BusinessConnection(
            id: 'bc1',
            user: $user,
            userChatId: 789,
            date: new DateTimeImmutable,
            isEnabled: true,
        );
        $update = new Update(updateId: 1, businessConnection: $businessConnection);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from messageReaction', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'private');
        $messageReaction = new MessageReactionUpdated(
            chat: $chat,
            messageId: 1,
            date: new DateTimeImmutable,
            user: $user,
            oldReaction: [],
            newReaction: [],
        );
        $update = new Update(updateId: 1, messageReaction: $messageReaction);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from inlineQuery', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $inlineQuery = new InlineQuery(
            id: 'iq1',
            from: $user,
            query: 'test',
            offset: '0',
        );
        $update = new Update(updateId: 1, inlineQuery: $inlineQuery);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from chosenInlineResult', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chosenInlineResult = new ChosenInlineResult(
            resultId: 'cir1',
            from: $user,
            query: 'test',
            inlineMessageId: 'msg1',
        );
        $update = new Update(updateId: 1, chosenInlineResult: $chosenInlineResult);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from shippingQuery', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $shippingQuery = new ShippingQuery(
            id: 'sq1',
            from: $user,
            invoicePayload: 'payload',
            shippingAddress: new ShippingAddress(
                countryCode: 'US',
                state: 'NY',
                city: 'New York',
                streetLine1: '123 Main St',
                streetLine2: '',
                postCode: '10001',
            ),
        );
        $update = new Update(updateId: 1, shippingQuery: $shippingQuery);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from preCheckoutQuery', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $preCheckoutQuery = new PreCheckoutQuery(
            id: 'pcq1',
            from: $user,
            currency: 'USD',
            totalAmount: 1000,
            invoicePayload: 'payload',
        );
        $update = new Update(updateId: 1, preCheckoutQuery: $preCheckoutQuery);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from pollAnswer', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $pollAnswer = new PollAnswer(
            pollId: 'poll1',
            user: $user,
            optionIds: [0],
        );
        $update = new Update(updateId: 1, pollAnswer: $pollAnswer);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from myChatMember', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'group');
        $myChatMember = new ChatMemberUpdated(
            chat: $chat,
            from: $user,
            date: new DateTimeImmutable,
            oldChatMember: new ChatMemberMember(user: new User(id: 789, isBot: true, firstName: 'Bot')),
            newChatMember: new ChatMemberMember(user: new User(id: 789, isBot: true, firstName: 'Bot')),
        );
        $update = new Update(updateId: 1, myChatMember: $myChatMember);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from chatMember', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'group');
        $chatMemberUpdate = new ChatMemberUpdated(
            chat: $chat,
            from: $user,
            date: new DateTimeImmutable,
            oldChatMember: new ChatMemberMember(user: new User(id: 789, isBot: false, firstName: 'Other')),
            newChatMember: new ChatMemberMember(user: new User(id: 789, isBot: false, firstName: 'Other')),
        );
        $update = new Update(updateId: 1, chatMember: $chatMemberUpdate);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('extracts user from chatJoinRequest', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $chat = new Chat(id: 123, type: 'supergroup');
        $chatJoinRequest = new ChatJoinRequest(
            chat: $chat,
            from: $user,
            userChatId: 789,
            date: new DateTimeImmutable,
        );
        $update = new Update(updateId: 1, chatJoinRequest: $chatJoinRequest);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBe($user);
    });

    it('returns null when no user is found', function () {
        $update = new Update(updateId: 1);

        $result = UpdateHelper::getUserFromUpdate($update);

        expect($result)->toBeNull();
    });
});

describe('UpdateHelper::getUpdateTypeEnum', function () {
    it('detects MESSAGE type', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            text: 'test',
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::MESSAGE);
    });

    it('detects EDITED_MESSAGE type', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedMessage: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::EDITED_MESSAGE);
    });

    it('detects CHANNEL_POST type', function () {
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, channelPost: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::CHANNEL_POST);
    });

    it('detects EDITED_CHANNEL_POST type', function () {
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedChannelPost: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::EDITED_CHANNEL_POST);
    });

    it('detects BUSINESS_CONNECTION type', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $businessConnection = new BusinessConnection(
            id: 'bc1',
            user: $user,
            userChatId: 789,
            date: new DateTimeImmutable,
            isEnabled: true,
        );
        $update = new Update(updateId: 1, businessConnection: $businessConnection);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::BUSINESS_CONNECTION);
    });

    it('detects BUSINESS_MESSAGE type', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, businessMessage: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::BUSINESS_MESSAGE);
    });

    it('detects EDITED_BUSINESS_MESSAGE type', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedBusinessMessage: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::EDITED_BUSINESS_MESSAGE);
    });

    it('detects CALLBACK_QUERY type', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $callbackQuery = new CallbackQuery(
            id: 'cb1',
            from: $user,
            chatInstance: '0',
        );
        $update = new Update(updateId: 1, callbackQuery: $callbackQuery);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::CALLBACK_QUERY);
    });

    it('detects INLINE_QUERY type', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $inlineQuery = new InlineQuery(
            id: 'iq1',
            from: $user,
            query: 'test',
            offset: '0',
        );
        $update = new Update(updateId: 1, inlineQuery: $inlineQuery);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::INLINE_QUERY);
    });

    it('detects POLL type', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            poll: new Poll(
                id: 'poll1',
                question: 'What?',
                options: [
                    new PollOption(text: 'Option 1', voterCount: 0),
                ],
                totalVoterCount: 0,
                isClosed: false,
                isAnonymous: true,
                type: 'regular',
                allowsMultipleAnswers: false,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::POLL);
    });

    it('detects MESSAGE type when no more specific type matches', function () {
        $update = new Update(updateId: 1);

        expect(UpdateHelper::getUpdateTypeEnum($update))->toBe(UpdateTypeEnum::MESSAGE);
    });
});

describe('UpdateHelper::mapToRouteType', function () {
    it('maps text message to TEXT_MESSAGE', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            text: 'hello world',
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::TEXT_MESSAGE);
    });

    it('maps command message to COMMAND', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            text: '/start',
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::COMMAND);
    });

    it('maps photo message to PHOTO', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            photo: [
                new Phptg\BotApi\Type\PhotoSize(
                    fileId: 'file1',
                    fileUniqueId: 'unique1',
                    width: 800,
                    height: 600,
                ),
            ],
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::PHOTO);
    });

    it('maps photo with media group to PHOTO_MEDIA_GROUP', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            photo: [
                new Phptg\BotApi\Type\PhotoSize(
                    fileId: 'file1',
                    fileUniqueId: 'unique1',
                    width: 800,
                    height: 600,
                ),
            ],
            mediaGroupId: 'group1',
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::PHOTO_MEDIA_GROUP);
    });

    it('maps document message to DOCUMENT', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            document: new Phptg\BotApi\Type\Document(
                fileId: 'file1',
                fileUniqueId: 'unique1',
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::DOCUMENT);
    });

    it('maps document with media group to DOCUMENT_MEDIA_GROUP', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            document: new Phptg\BotApi\Type\Document(
                fileId: 'file1',
                fileUniqueId: 'unique1',
            ),
            mediaGroupId: 'group1',
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::DOCUMENT_MEDIA_GROUP);
    });

    it('maps audio message to AUDIO', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            audio: new Phptg\BotApi\Type\Audio(
                fileId: 'file1',
                fileUniqueId: 'unique1',
                duration: 120,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::AUDIO);
    });

    it('maps voice message to VOICE', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            voice: new Phptg\BotApi\Type\Voice(
                fileId: 'file1',
                fileUniqueId: 'unique1',
                duration: 10,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::VOICE);
    });

    it('maps sticker message to STICKER', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            sticker: new Sticker(
                fileId: 'file1',
                fileUniqueId: 'unique1',
                type: 'regular',
                width: 512,
                height: 512,
                isAnimated: false,
                isVideo: false,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::STICKER);
    });

    it('maps location message to LOCATION', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            location: new Phptg\BotApi\Type\Location(
                latitude: 40.7128,
                longitude: -74.0060,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::LOCATION);
    });

    it('maps contact message to CONTACT', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            contact: new Phptg\BotApi\Type\Contact(
                phoneNumber: '+1234567890',
                firstName: 'John',
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::CONTACT);
    });

    it('maps pinned message to PINNED_MESSAGE', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            pinnedMessage: new Message(
                messageId: 2,
                date: new DateTimeImmutable,
                chat: $chat,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::PINNED_MESSAGE);
    });

    it('maps reply to message to REPLY_TO_MESSAGE', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            replyToMessage: new Message(
                messageId: 2,
                date: new DateTimeImmutable,
                chat: $chat,
            ),
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::REPLY_TO_MESSAGE);
    });

    it('maps edited message to EDITED_MESSAGE', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, editedMessage: $message);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::EDITED_MESSAGE);
    });

    it('maps callback query to CALLBACK_QUERY', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $callbackQuery = new CallbackQuery(
            id: 'cb1',
            from: $user,
            chatInstance: '0',
        );
        $update = new Update(updateId: 1, callbackQuery: $callbackQuery);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::CALLBACK_QUERY);
    });

    it('maps inline query to INLINE_QUERY', function () {
        $user = new User(id: 456, isBot: false, firstName: 'Test');
        $inlineQuery = new InlineQuery(
            id: 'iq1',
            from: $user,
            query: 'test',
            offset: '0',
        );
        $update = new Update(updateId: 1, inlineQuery: $inlineQuery);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::INLINE_QUERY);
    });

    it('maps my chat member to MY_CHAT_MEMBER', function () {
        $chat = new Chat(id: 123, type: 'group');
        $myChatMember = new ChatMemberUpdated(
            chat: $chat,
            from: new User(id: 456, isBot: false, firstName: 'Test'),
            date: new DateTimeImmutable,
            oldChatMember: new ChatMemberMember(user: new User(id: 789, isBot: true, firstName: 'Bot')),
            newChatMember: new ChatMemberMember(user: new User(id: 789, isBot: true, firstName: 'Bot')),
        );
        $update = new Update(updateId: 1, myChatMember: $myChatMember);

        expect(UpdateHelper::mapToRouteType($update))->toBe(RouteType::MY_CHAT_MEMBER);
    });
});

describe('UpdateHelper::mapMessageType', function () {
    it('maps null message to TEXT_MESSAGE', function () {
        $reflection = new ReflectionClass(UpdateHelper::class);
        $method = $reflection->getMethod('mapMessageType');
        $method->setAccessible(true);

        expect($method->invoke(null, null))->toBe(RouteType::TEXT_MESSAGE);
    });

    it('detects animation', function () {
        $reflection = new ReflectionClass(UpdateHelper::class);
        $method = $reflection->getMethod('mapMessageType');
        $method->setAccessible(true);

        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            animation: new Phptg\BotApi\Type\Animation(
                fileId: 'file1',
                fileUniqueId: 'unique1',
                width: 800,
                height: 600,
                duration: 5,
            ),
        );

        expect($method->invoke(null, $message))->toBe(RouteType::ANIMATION);
    });

    it('detects video note', function () {
        $reflection = new ReflectionClass(UpdateHelper::class);
        $method = $reflection->getMethod('mapMessageType');
        $method->setAccessible(true);

        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            videoNote: new Phptg\BotApi\Type\VideoNote(
                fileId: 'file1',
                fileUniqueId: 'unique1',
                length: 120,
                duration: 10,
            ),
        );

        expect($method->invoke(null, $message))->toBe(RouteType::VIDEO_NOTE);
    });
});

describe('UpdateHelper::mapBusinessMessageType', function () {
    it('maps business message with command to BUSINESS_MESSAGE_COMMAND', function () {
        $reflection = new ReflectionClass(UpdateHelper::class);
        $method = $reflection->getMethod('mapBusinessMessageType');
        $method->setAccessible(true);

        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            text: '/start',
        );
        $update = new Update(updateId: 1, businessMessage: $message);

        expect($method->invoke(null, $update))->toBe(RouteType::BUSINESS_MESSAGE_COMMAND);
    });

    it('maps business message without command to BUSINESS_MESSAGE_TEXT', function () {
        $reflection = new ReflectionClass(UpdateHelper::class);
        $method = $reflection->getMethod('mapBusinessMessageType');
        $method->setAccessible(true);

        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
            text: 'hello',
        );
        $update = new Update(updateId: 1, businessMessage: $message);

        expect($method->invoke(null, $update))->toBe(RouteType::BUSINESS_MESSAGE_TEXT);
    });
});

describe('UpdateHelper::getChatType', function () {
    it('returns PRIVATE for private chat', function () {
        $chat = new Chat(id: 123, type: 'private');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getChatType($update))->toBe(ChatType::PRIVATE);
    });

    it('returns GROUP for group chat', function () {
        $chat = new Chat(id: 123, type: 'group');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getChatType($update))->toBe(ChatType::GROUP);
    });

    it('returns SUPERGROUP for supergroup chat', function () {
        $chat = new Chat(id: 123, type: 'supergroup');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getChatType($update))->toBe(ChatType::SUPERGROUP);
    });

    it('returns CHANNEL for channel chat', function () {
        $chat = new Chat(id: 123, type: 'channel');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getChatType($update))->toBe(ChatType::CHANNEL);
    });

    it('returns PRIVATE when no chat is found', function () {
        $update = new Update(updateId: 1);

        expect(UpdateHelper::getChatType($update))->toBe(ChatType::PRIVATE);
    });

    it('returns PRIVATE for unknown chat type', function () {
        $chat = new Chat(id: 123, type: 'unknown_type');
        $message = new Message(
            messageId: 1,
            date: new DateTimeImmutable,
            chat: $chat,
        );
        $update = new Update(updateId: 1, message: $message);

        expect(UpdateHelper::getChatType($update))->toBe(ChatType::PRIVATE);
    });
});
