<?php

declare(strict_types=1);

use HybridGram\Core\Routing\ActionType;
use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteData\BusinessMessageTextData;
use HybridGram\Core\Routing\RouteData\CommandData;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\Routing\TelegramRoute;
use HybridGram\Facades\TelegramRouter;
use Phptg\BotApi\Type\Animation;
use Phptg\BotApi\Type\Audio;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\ChatBoostAdded;
use Phptg\BotApi\Type\Checklist;
use Phptg\BotApi\Type\ChecklistTask;
use Phptg\BotApi\Type\Contact;
use Phptg\BotApi\Type\ExternalReplyInfo;
use Phptg\BotApi\Type\ForumTopicCreated;
use Phptg\BotApi\Type\ForumTopicEdited;
use Phptg\BotApi\Type\ForumTopicClosed;
use Phptg\BotApi\Type\ForumTopicReopened;
use Phptg\BotApi\Type\Game\Game;
use Phptg\BotApi\Type\GeneralForumTopicHidden;
use Phptg\BotApi\Type\Inline\InlineQuery;
use Phptg\BotApi\Type\MessageOriginUser;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\MessageAutoDeleteTimerChanged;
use Phptg\BotApi\Type\PaidMediaPreview;
use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Sticker\Sticker;
use Phptg\BotApi\Type\Story;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;
use Phptg\BotApi\Type\PaidMediaInfo;
use Phptg\BotApi\Type\Passport\EncryptedCredentials;
use Phptg\BotApi\Type\Passport\PassportData as TelegramPassportData;
use Phptg\BotApi\Type\TextQuote;
use Phptg\BotApi\Type\VideoNote;
use Phptg\BotApi\Type\Voice;

test('add route registered in router', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::PRIVATE)
        ->fromChatState(['state'])
        ->cache(3600)
        ->sendAction(ActionType::UPLOAD_DOCUMENT, 5)
        ->toChatState('new_state')
        ->onCommand(function (CommandData $commandData) {
            logger()->info("hello $commandData->command", $commandData->commandParams);
        }, 'start');

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: '/start'
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    expect(app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot'))->toBeInstanceOf(TelegramRoute::class);


    $groupChat = new Chat(1, 'group');
    $groupMessage = new Message(
        messageId: 2,
        date: new DateTimeImmutable,
        chat: $groupChat,
        from: $user,
        text: '/start'
    );
    $groupUpdate = new Update(
        updateId: 2,
        message: $groupMessage
    );

    expect(app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($groupUpdate, 'main_bot'))->toBeInstanceOf(TelegramRoute::class);
});

test('onAnimation routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onAnimation(function (HybridGram\Core\Routing\RouteData\AnimationData $animationData) {
            return 'animation_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $animation = new Animation(
        fileId: 'test_file_id',
        fileUniqueId: 'test_unique_id',
        width: 100,
        height: 100,
        duration: 5
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        animation: $animation
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::ANIMATION);
});

test('onAudio routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onAudio(function (HybridGram\Core\Routing\RouteData\AudioData $audioData) {
            return 'audio_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $audio = new Audio(
        fileId: 'test_file_id',
        fileUniqueId: 'test_unique_id',
        duration: 120
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        audio: $audio
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::AUDIO);
});

test('onSticker routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onSticker(function (HybridGram\Core\Routing\RouteData\StickerData $stickerData) {
            return 'sticker_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $sticker = new Sticker(
        fileId: 'test_file_id',
        fileUniqueId: 'test_unique_id',
        type: 'regular',
        width: 512,
        height: 512,
        isAnimated: true,
        isVideo: false,
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        sticker: $sticker
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::STICKER);
});

test('onVideoNote routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onVideoNote(function (HybridGram\Core\Routing\RouteData\VideoNoteData $videoNoteData) {
            return 'video_note_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $videoNote = new VideoNote(
        fileId: 'test_file_id',
        fileUniqueId: 'test_unique_id',
        length: 240,
        duration: 5
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        videoNote: $videoNote
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::VIDEO_NOTE);
});

test('onVoice routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onVoice(function (HybridGram\Core\Routing\RouteData\VoiceData $voiceData) {
            return 'voice_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $voice = new Voice(
        fileId: 'test_file_id',
        fileUniqueId: 'test_unique_id',
        duration: 10
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        voice: $voice
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::VOICE);
});

test('onPaidMedia routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onPaidMedia(function (HybridGram\Core\Routing\RouteData\PaidMediaData $paidMediaData) {
            return 'paid_media_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $paidMedia = new PaidMediaInfo(
        starCount: 100,
        paidMedia: [new PaidMediaPreview(100, 100, 100)]
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        paidMedia: $paidMedia
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::PAID_MEDIA);
});

test('onContact routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onContact(function (HybridGram\Core\Routing\RouteData\ContactData $contactData) {
            return 'contact_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $contact = new Contact(
        phoneNumber: '+1234567890',
        firstName: 'John'
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        contact: $contact
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::CONTACT);
});

test('onChecklist routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onChecklist(function (HybridGram\Core\Routing\RouteData\ChecklistData $checklistData) {
            return 'checklist_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $task = new ChecklistTask(
        id: 1,
        text: 'Test task'
    );
    $checklist = new Checklist(
        title: 'Test Checklist',
        tasks: [$task]
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        checklist: $checklist
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::CHECKLIST);
});

test('onDice routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onDice(function (HybridGram\Core\Routing\RouteData\DiceData $diceData) {
            return 'dice_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $dice = new \Phptg\BotApi\Type\Dice(
        emoji: '🎲',
        value: 4
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        dice: $dice
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::DICE);
});

test('onGame routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onGame(function (HybridGram\Core\Routing\RouteData\GameData $gameData) {
            return 'game_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $game = new Game(
        title: 'Test Game',
        description: 'Test Description',
        photo: []
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        game: $game
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::GAME);
});

test('onInvoice routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onInvoice(function (HybridGram\Core\Routing\RouteData\InvoiceData $invoiceData) {
            return 'invoice_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $invoice = new \Phptg\BotApi\Type\Payment\Invoice(
        title: 'Test Invoice',
        description: 'Test Description',
        startParameter: 'test_param',
        currency: 'USD',
        totalAmount: 1000
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        invoice: $invoice
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class)
        ->and($route->type)->toBe(RouteType::INVOICE);
});

test('onSuccessfulPayment routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onSuccessfulPayment(function (HybridGram\Core\Routing\RouteData\SuccessfulPaymentData $successfulPaymentData) {
            return 'successful_payment_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $successfulPayment = new \Phptg\BotApi\Type\Payment\SuccessfulPayment(
        currency: 'USD',
        totalAmount: 1000,
        invoicePayload: 'test_payload',
        telegramPaymentChargeId: 'test_charge_id',
        providerPaymentChargeId: 'test_provider_charge_id'
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        successfulPayment: $successfulPayment
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class)
        ->and($route->type)->toBe(RouteType::SUCCESSFUL_PAYMENT);
});

test('onPassportData routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onPassportData(function (HybridGram\Core\Routing\RouteData\PassportData $passportData) {
            return 'passport_data_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $encryptedCredentials = new EncryptedCredentials(
        data: 'encrypted_data',
        hash: 'hash_value',
        secret: 'secret_value'
    );
    $passportData = new TelegramPassportData(
        data: [],
        credentials: $encryptedCredentials
    );
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        passportData: $passportData
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::PASSPORT_DATA);
});

test('onReply routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onReply(function (HybridGram\Core\Routing\RouteData\ReplyData $replyData) {
            return 'reply_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $originalMessage = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Original message'
    );
    $replyMessage = new Message(
        messageId: 2,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Reply message',
        replyToMessage: $originalMessage
    );
    $update = new Update(
        updateId: 1,
        message: $replyMessage
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::REPLY_TO_MESSAGE);
});

test('onExternalReply routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onExternalReply(function (HybridGram\Core\Routing\RouteData\ExternalReplyData $externalReplyData) {
            return 'external_reply_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');

    $origin = new MessageOriginUser(
        date: new DateTimeImmutable(),
        senderUser: $user,
    );
    $externalReply = new ExternalReplyInfo(origin: $origin);

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        externalReply: $externalReply,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::EXTERNAL_REPLY_MESSAGE);
});

test('onQuote routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onQuote(function (HybridGram\Core\Routing\RouteData\QuoteData $quoteData) {
            return 'quote_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');

    $originalMessage = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Original message'
    );

    $quote = new TextQuote(
        text: 'Original',
        position: 0,
    );

    $replyMessage = new Message(
        messageId: 2,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Reply message',
        replyToMessage: $originalMessage,
        quote: $quote,
    );

    $update = new Update(
        updateId: 1,
        message: $replyMessage
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::QUOTED_MESSAGE);
});

test('onReplyToStory routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onReplyToStory(function (HybridGram\Core\Routing\RouteData\ReplyToStoryData $replyToStoryData) {
            return 'reply_to_story_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $story = new Story(chat: $chat, id: 42);

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Reply to story message',
        replyToStory: $story,
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::REPLY_TO_STORY);
});

test('onNewChatTitle routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onNewChatTitle(function (HybridGram\Core\Routing\RouteData\NewChatTitleData $newChatTitleData) {
            return 'new_chat_title_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        newChatTitle: 'New Group Title',
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::NEW_CHAT_TITLE);
});

test('onNewChatPhoto routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onNewChatPhoto(function (HybridGram\Core\Routing\RouteData\NewChatPhotoData $newChatPhotoData) {
            return 'new_chat_photo_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $photoSize = new PhotoSize(
        fileId: 'test_file_id',
        fileUniqueId: 'test_unique_id',
        width: 100,
        height: 100,
        fileSize: 12345,
    );

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        newChatPhoto: [$photoSize],
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::NEW_CHAT_PHOTO);
});

test('onDeleteChatPhoto routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onDeleteChatPhoto(function (HybridGram\Core\Routing\RouteData\DeleteChatPhotoData $deleteChatPhotoData) {
            return 'delete_chat_photo_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        deleteChatPhoto: true,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::DELETE_CHAT_PHOTO);
});

test('onMessageAutoDeleteTimerChanged routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onMessageAutoDeleteTimerChanged(function (HybridGram\Core\Routing\RouteData\AutoDeleteTimerChangedData $data) {
            return 'auto_delete_timer_changed_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $changed = new MessageAutoDeleteTimerChanged(messageAutoDeleteTime: 60);

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        messageAutoDeleteTimerChanged: $changed,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::AUTO_DELETE_TIMER_CHANGED);
});

test('onPinnedMessage routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onPinnedMessage(function (HybridGram\Core\Routing\RouteData\PinnedMessageData $data) {
            return 'pinned_message_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $pinned = new Message(
        messageId: 100,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        text: 'Pinned message',
    );

    $message = new Message(
        messageId: 101,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        pinnedMessage: $pinned,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::PINNED_MESSAGE);
});

test('onForumTopicEvent routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onForumTopicEvent(function (HybridGram\Core\Routing\RouteData\ForumTopicEventData $data) {
            return 'forum_topic_event_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $created = new ForumTopicCreated(
        name: 'Topic name',
        iconColor: 0x6FB9F0,
        iconCustomEmojiId: null,
    );

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        forumTopicCreated: $created,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::FORUM_TOPIC_EVENT);
});

test('onForumTopicCreated routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onForumTopicCreated(function (HybridGram\Core\Routing\RouteData\ForumTopicCreatedData $data) {
            return 'forum_topic_created_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $created = new ForumTopicCreated(
        name: 'Topic name',
        iconColor: 0x6FB9F0,
        iconCustomEmojiId: null,
    );

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        forumTopicCreated: $created,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::FORUM_TOPIC_CREATED);
});

test('onForumTopicEdited routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onForumTopicEdited(function (HybridGram\Core\Routing\RouteData\ForumTopicEditedData $data) {
            return 'forum_topic_edited_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $edited = new ForumTopicEdited(
        name: 'Updated topic name',
        iconCustomEmojiId: null,
    );

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        forumTopicEdited: $edited,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::FORUM_TOPIC_EDITED);
});

test('onForumTopicClosed routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onForumTopicClosed(function (HybridGram\Core\Routing\RouteData\ForumTopicClosedData $data) {
            return 'forum_topic_closed_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $closed = new ForumTopicClosed();

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        forumTopicClosed: $closed,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::FORUM_TOPIC_CLOSED);
});

test('onForumTopicReopened routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onForumTopicReopened(function (HybridGram\Core\Routing\RouteData\ForumTopicReopenedData $data) {
            return 'forum_topic_reopened_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $reopened = new ForumTopicReopened();

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        forumTopicReopened: $reopened,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::FORUM_TOPIC_REOPENED);
});

test('onGeneralForumTopicEvent routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onGeneralForumTopicEvent(function (HybridGram\Core\Routing\RouteData\GeneralForumTopicEventData $data) {
            return 'general_forum_topic_event_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $payload = new GeneralForumTopicHidden();

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        generalForumTopicHidden: $payload,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::GENERAL_FORUM_TOPIC_EVENT);
});

test('onBoostAdded routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(ChatType::GROUP)
        ->onBoostAdded(function (HybridGram\Core\Routing\RouteData\BoostAddedData $data) {
            return 'boost_added_handler';
        });

    $actor = new User(1, false, 'Actor');
    $chat = new Chat(1, 'group');

    $boostAdded = new ChatBoostAdded(boostCount: 2);

    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $actor,
        boostAdded: $boostAdded,
        senderBoostCount: 5,
    );

    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::BOOST_ADDED);
});

test('onAny routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onAny(function (HybridGram\Core\Routing\RouteData\AnyData $anyData) {
            return 'any_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Any message'
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::ANY);
});

test('onInlineQuery routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onInlineQuery(function (HybridGram\Core\Routing\RouteData\InlineQueryData $inlineQueryData) {
            return 'inline_query_handler';
        });

    $user = new User(1, false, 'TestUser');
    $inlineQuery = new InlineQuery(
        id: 'test_query_id',
        from: $user,
        query: 'test query',
        offset: ''
    );
    $update = new Update(
        updateId: 1,
        inlineQuery: $inlineQuery
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::INLINE_QUERY);
});

test('onFallback routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onFallback(function (HybridGram\Core\Routing\RouteData\FallbackData $fallbackData) {
            return 'fallback_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Unknown command'
    );
    $update = new Update(
        updateId: 1,
        message: $message
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::FALLBACK);
});

test('onBusinessMessageText routes correctly', function () {
    TelegramRouter::forBot('main_bot')
        ->onBusinessMessageText(function (HybridGram\Core\Routing\RouteData\BusinessMessageTextData $businessMessageTextData) {
            return 'business_message_text_handler';
        });

    $user = new User(1, false, 'TestUser');
    $chat = new Chat(1, 'private');
    $businessMessage = new Message(
        messageId: 213629,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'Привет команда SVK 🥺, у меня вопрос. (Отправьте это сообщение нам и магия начнется)',
        businessConnectionId: 'JExJblLGWUuyFwAArZrLhOGnjeQ'
    );
    $update = new Update(
        updateId: 102669566,
        businessMessage: $businessMessage
    );

    $route = app(HybridGram\Core\Routing\TelegramRouter::class)->resolveActionsByUpdate($update, 'main_bot');
    expect($route)->toBeInstanceOf(TelegramRoute::class);
    expect($route->type)->toBe(RouteType::BUSINESS_MESSAGE_TEXT);
});