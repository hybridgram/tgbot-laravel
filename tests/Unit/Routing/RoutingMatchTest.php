<?php

declare(strict_types=1);

use HybridGram\Core\Routing\CallbackQueryDataString;
use HybridGram\Core\Routing\RouteData\AnimationData;
use HybridGram\Core\Routing\RouteData\AnyData;
use HybridGram\Core\Routing\RouteData\AudioData;
use HybridGram\Core\Routing\RouteData\AutoDeleteTimerChangedData;
use HybridGram\Core\Routing\RouteData\BoostAddedData;
use HybridGram\Core\Routing\RouteData\BusinessConnectionData;
use HybridGram\Core\Routing\RouteData\BusinessMessageTextData;
use HybridGram\Core\Routing\RouteData\CallbackQueryData;
use HybridGram\Core\Routing\RouteData\ChatMemberUpdatedData;
use HybridGram\Core\Routing\RouteData\ChecklistData;
use HybridGram\Core\Routing\RouteData\CommandData;
use HybridGram\Core\Routing\RouteData\ContactData;
use HybridGram\Core\Routing\RouteData\DeleteChatPhotoData;
use HybridGram\Core\Routing\RouteData\DiceData;
use HybridGram\Core\Routing\RouteData\DocumentData;
use HybridGram\Core\Routing\RouteData\ExternalReplyData;
use HybridGram\Core\Routing\RouteData\FallbackData;
use HybridGram\Core\Routing\RouteData\ForumTopicClosedData;
use HybridGram\Core\Routing\RouteData\ForumTopicCreatedData;
use HybridGram\Core\Routing\RouteData\ForumTopicEditedData;
use HybridGram\Core\Routing\RouteData\ForumTopicReopenedData;
use HybridGram\Core\Routing\RouteData\GameData;
use HybridGram\Core\Routing\RouteData\GeneralForumTopicEventData;
use HybridGram\Core\Routing\RouteData\InlineQueryData;
use HybridGram\Core\Routing\RouteData\InvoiceData;
use HybridGram\Core\Routing\RouteData\LocationData;
use HybridGram\Core\Routing\RouteData\NewChatPhotoData;
use HybridGram\Core\Routing\RouteData\NewChatTitleData;
use HybridGram\Core\Routing\RouteData\PaidMediaData;
use HybridGram\Core\Routing\RouteData\PassportData;
use HybridGram\Core\Routing\RouteData\PhotoData;
use HybridGram\Core\Routing\RouteData\PhotoMediaGroupData;
use HybridGram\Core\Routing\RouteData\PinnedMessageData;
use HybridGram\Core\Routing\RouteData\PollAnswerData;
use HybridGram\Core\Routing\RouteData\PollClosedData;
use HybridGram\Core\Routing\RouteData\PollData;
use HybridGram\Core\Routing\RouteData\QuoteData;
use HybridGram\Core\Routing\RouteData\ReplyData;
use HybridGram\Core\Routing\RouteData\ReplyToStoryData;
use HybridGram\Core\Routing\RouteData\StickerData;
use HybridGram\Core\Routing\RouteData\StoryData;
use HybridGram\Core\Routing\RouteData\SuccessfulPaymentData;
use HybridGram\Core\Routing\RouteData\TextMessageData;
use HybridGram\Core\Routing\RouteData\VenueData;
use HybridGram\Core\Routing\RouteData\VideoNoteData;
use HybridGram\Core\Routing\RouteData\VoiceData;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\Routing\TelegramRoute;
use HybridGram\Telegram\Document\MimeType;
use HybridGram\Telegram\Poll\PollType;
use Illuminate\Support\Facades\Cache;
use Phptg\BotApi\Type\Animation;
use Phptg\BotApi\Type\Audio;
use Phptg\BotApi\Type\BusinessConnection;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\ChatBoostAdded;
use Phptg\BotApi\Type\ChatMemberUpdated;
use Phptg\BotApi\Type\Checklist;
use Phptg\BotApi\Type\ChecklistTask;
use Phptg\BotApi\Type\Contact;
use Phptg\BotApi\Type\Dice;
use Phptg\BotApi\Type\Document;
use Phptg\BotApi\Type\ExternalReplyInfo;
use Phptg\BotApi\Type\ForumTopicClosed;
use Phptg\BotApi\Type\ForumTopicCreated;
use Phptg\BotApi\Type\ForumTopicEdited;
use Phptg\BotApi\Type\ForumTopicReopened;
use Phptg\BotApi\Type\Game\Game;
use Phptg\BotApi\Type\GeneralForumTopicHidden;
use Phptg\BotApi\Type\Inline\InlineQuery;
use Phptg\BotApi\Type\Location;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\MessageAutoDeleteTimerChanged;
use Phptg\BotApi\Type\MessageOriginUser;
use Phptg\BotApi\Type\PaidMediaInfo;
use Phptg\BotApi\Type\PaidMediaPreview;
use Phptg\BotApi\Type\Passport\EncryptedCredentials;
use Phptg\BotApi\Type\Passport\PassportData as TelegramPassportData;
use Phptg\BotApi\Type\Payment\Invoice;
use Phptg\BotApi\Type\Payment\SuccessfulPayment;
use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Poll;
use Phptg\BotApi\Type\PollAnswer;
use Phptg\BotApi\Type\Sticker\Sticker;
use Phptg\BotApi\Type\Story;
use Phptg\BotApi\Type\TextQuote;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;
use Phptg\BotApi\Type\Venue;
use Phptg\BotApi\Type\VideoNote;
use Phptg\BotApi\Type\Voice;

test('command route delegates to command data match', function (): void {
    $update = new Update(
        updateId: 1,
        message: new Message(
            messageId: 10,
            date: PHP_VERSION_ID >= 80400
                ? DateTimeImmutable::createFromTimestamp(time())
                : (new DateTimeImmutable)->setTimestamp(time()),
            chat: new Chat(id: 123, type: 'private'),
            text: '/start',
        ),
    );

    $route = new TelegramRoute(
        type: RouteType::COMMAND,
        botId: 'test-bot',
        pattern: '/start',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(CommandData::class)
        ->and($data->botId)->toBe('test-bot')
        ->and($data->command)->toBe('start')
        ->and($data->commandParams)->toBe([]);
});

test('command route supports inline underscore arguments', function (): void {
    $update = new Update(
        updateId: 2,
        message: new Message(
            messageId: 11,
            date: PHP_VERSION_ID >= 80400
                ? DateTimeImmutable::createFromTimestamp(time())
                : (new DateTimeImmutable)->setTimestamp(time()),
            chat: new Chat(id: 123, type: 'private'),
            text: '/start_arg1_arg2',
        ),
    );

    $route = new TelegramRoute(
        type: RouteType::COMMAND,
        botId: 'test-bot',
        pattern: '/start',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(CommandData::class)
        ->and($data->command)->toBe('start')
        ->and($data->botId)->toBe('test-bot')
        ->and($data->commandParams)->toBe(['arg1', 'arg2']);
});

test('any route delegates to AnyData and keeps bot id', function (): void {
    $chat = new Chat(id: 123, type: 'private');
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $chat,
        text: 'hello',
    );
    $update = new Update(updateId: 1, message: $message);

    $route = new TelegramRoute(
        type: RouteType::ANY,
        botId: 'any-bot',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(AnyData::class)
        ->and($data->botId)->toBe('any-bot')
        ->and($data->update)->toBe($update);
});

test('text message route matches with wildcard pattern', function (): void {
    $chat = new Chat(id: 123, type: 'private');
    $message = new Message(
        messageId: 2,
        date: new DateTimeImmutable,
        chat: $chat,
        text: 'hello world',
    );
    $update = new Update(updateId: 2, message: $message);

    $route = new TelegramRoute(
        type: RouteType::TEXT_MESSAGE,
        botId: 'text-bot',
        pattern: '*',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(TextMessageData::class)
        ->and($data->botId)->toBe('text-bot')
        ->and($data->text)->toBe('hello world')
        ->and($data->message)->toBe($message);
});

test('text message route does not match when text is missing', function (): void {
    $chat = new Chat(id: 123, type: 'private');
    $message = new Message(
        messageId: 3,
        date: new DateTimeImmutable,
        chat: $chat,
    );
    $update = new Update(updateId: 3, message: $message);

    $route = new TelegramRoute(
        type: RouteType::TEXT_MESSAGE,
        botId: 'text-bot',
        pattern: '*',
    );

    $data = $route->matches($update);

    expect($data)->toBeNull();
});

test('text message route respects string pattern', function (): void {
    $chat = new Chat(id: 123, type: 'private');
    $message = new Message(
        messageId: 4,
        date: new DateTimeImmutable,
        chat: $chat,
        text: 'order:123',
    );
    $update = new Update(updateId: 4, message: $message);

    $route = new TelegramRoute(
        type: RouteType::TEXT_MESSAGE,
        botId: 'text-bot',
        pattern: 'order:*',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(TextMessageData::class)
        ->and($data->text)->toBe('order:123');

    $nonMatchingRoute = new TelegramRoute(
        type: RouteType::TEXT_MESSAGE,
        botId: 'text-bot',
        pattern: 'invoice:*',
    );

    $nonMatching = $nonMatchingRoute->matches($update);

    expect($nonMatching)->toBeNull();
});

test('inline query route matches and respects pattern', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $inlineQuery = new InlineQuery(
        id: 'query-id',
        from: $user,
        query: 'search term',
        offset: '',
    );
    $update = new Update(updateId: 5, inlineQuery: $inlineQuery);

    $route = new TelegramRoute(
        type: RouteType::INLINE_QUERY,
        botId: 'inline-bot',
        pattern: 'search*',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(InlineQueryData::class)
        ->and($data->botId)->toBe('inline-bot')
        ->and($data->inlineQuery)->toBe($inlineQuery);

    $nonMatchingRoute = new TelegramRoute(
        type: RouteType::INLINE_QUERY,
        botId: 'inline-bot',
        pattern: 'other*',
    );

    $nonMatching = $nonMatchingRoute->matches($update);

    expect($nonMatching)->toBeNull();
});

test('callback query route matches valid data without params', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $callbackData = (string) CallbackQueryDataString::make('do');

    $query = new CallbackQuery(
        id: 'cq1',
        from: $user,
        chatInstance: 'ci1',
        data: $callbackData,
    );

    $update = new Update(updateId: 6, callbackQuery: $query);

    $route = new TelegramRoute(
        type: RouteType::CALLBACK_QUERY,
        botId: 'cb-bot',
        pattern: 'do',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(CallbackQueryData::class)
        ->and($data->botId)->toBe('cb-bot')
        ->and($data->action)->toBe('do')
        ->and($data->params)->toBe([]);
});

test('callback query route respects pattern and params/options', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $callbackData = (string) CallbackQueryDataString::make('filter')
        ->add('id', 42);

    $query = new CallbackQuery(
        id: 'cq2',
        from: $user,
        chatInstance: 'ci2',
        data: $callbackData,
    );

    $update = new Update(updateId: 7, callbackQuery: $query);

    $matchingOption = new class implements QueryParamInterface
    {
        public function getKey(): string
        {
            return 'id';
        }

        public function matches(array $params): bool
        {
            return ($params['id'] ?? null) === '42';
        }
    };

    $nonMatchingOption = new class implements QueryParamInterface
    {
        public function getKey(): string
        {
            return 'id';
        }

        public function matches(array $params): bool
        {
            return ($params['id'] ?? null) === '0';
        }
    };

    $routeWithMatchingOption = new TelegramRoute(
        type: RouteType::CALLBACK_QUERY,
        botId: 'cb-bot',
        pattern: 'filter',
        callbackQueryOptions: [$matchingOption],
    );

    $data = $routeWithMatchingOption->matches($update);

    expect($data)->toBeInstanceOf(CallbackQueryData::class)
        ->and($data->params)->toBe(['id' => '42']);

    $routeWithNonMatchingOption = new TelegramRoute(
        type: RouteType::CALLBACK_QUERY,
        botId: 'cb-bot',
        pattern: 'filter',
        callbackQueryOptions: [$nonMatchingOption],
    );

    $nonMatching = $routeWithNonMatchingOption->matches($update);

    expect($nonMatching)->toBeNull();

    $routeWithoutOptions = new TelegramRoute(
        type: RouteType::CALLBACK_QUERY,
        botId: 'cb-bot',
        pattern: 'filter',
    );

    $withoutOptions = $routeWithoutOptions->matches($update);

    expect($withoutOptions)->toBeNull();
});

test('external reply route matches only when external reply is present', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Actor');
    $chat = new Chat(id: 42, type: 'private');

    $origin = new MessageOriginUser(
        date: new DateTimeImmutable,
        senderUser: $user,
    );

    $externalReply = new ExternalReplyInfo(origin: $origin);

    $messageWithExternalReply = new Message(
        messageId: 8,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        externalReply: $externalReply,
    );

    $updateWithExternalReply = new Update(
        updateId: 8,
        message: $messageWithExternalReply,
    );

    $route = new TelegramRoute(
        type: RouteType::EXTERNAL_REPLY_MESSAGE,
        botId: 'ext-bot',
        pattern: '*',
    );

    $data = $route->matches($updateWithExternalReply);

    expect($data)->toBeInstanceOf(ExternalReplyData::class)
        ->and($data->externalReply)->toBe($externalReply);

    $messageWithoutExternalReply = new Message(
        messageId: 9,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
    );

    $updateWithoutExternalReply = new Update(
        updateId: 9,
        message: $messageWithoutExternalReply,
    );

    $noMatch = $route->matches($updateWithoutExternalReply);

    expect($noMatch)->toBeNull();
});

test('reply to story route respects text pattern', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Actor');
    $chat = new Chat(id: 42, type: 'private');
    $story = new Story(chat: $chat, id: 100);

    $message = new Message(
        messageId: 10,
        date: new DateTimeImmutable,
        chat: $chat,
        from: $user,
        text: 'order:42',
        replyToStory: $story,
    );

    $update = new Update(updateId: 10, message: $message);

    $matchingRoute = new TelegramRoute(
        type: RouteType::REPLY_TO_STORY,
        botId: 'story-bot',
        pattern: 'order:*',
    );

    $matchingData = $matchingRoute->matches($update);

    expect($matchingData)->toBeInstanceOf(ReplyToStoryData::class)
        ->and($matchingData->replyToStory)->toBe($story);

    $nonMatchingRoute = new TelegramRoute(
        type: RouteType::REPLY_TO_STORY,
        botId: 'story-bot',
        pattern: 'invoice:*',
    );

    $nonMatchingData = $nonMatchingRoute->matches($update);

    expect($nonMatchingData)->toBeNull();
});

test('fallback route always produces FallbackData', function (): void {
    $chat = new Chat(id: 123, type: 'private');
    $message = new Message(
        messageId: 11,
        date: new DateTimeImmutable,
        chat: $chat,
        text: 'unknown',
    );
    $update = new Update(updateId: 11, message: $message);

    $route = new TelegramRoute(
        type: RouteType::FALLBACK,
        botId: 'fallback-bot',
    );

    $data = $route->matches($update);

    expect($data)->toBeInstanceOf(FallbackData::class)
        ->and($data->botId)->toBe('fallback-bot')
        ->and($data->update)->toBe($update);
});

// --- Media / message content RouteData (success + no-match) ---

test('animation route matches when message has animation', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $animation = new Animation(fileId: 'a1', fileUniqueId: 'u1', width: 100, height: 100, duration: 5);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, animation: $animation);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::ANIMATION, botId: 'bot');
    $data = $route->matches($update);
    expect($data)->toBeInstanceOf(AnimationData::class)->and($data->botId)->toBe('bot');
    $noAnimation = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'hi');
    expect($route->matches(new Update(updateId: 2, message: $noAnimation)))->toBeNull();
});

test('audio route matches when message has audio', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $audio = new Audio(fileId: 'a1', fileUniqueId: 'u1', duration: 120);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, audio: $audio);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::AUDIO, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(AudioData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('sticker route matches when message has sticker', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $sticker = new Sticker(fileId: 's1', fileUniqueId: 'u1', type: 'regular', width: 512, height: 512, isAnimated: false, isVideo: false);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, sticker: $sticker);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::STICKER, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(StickerData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('video note route matches when message has video note', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $videoNote = new VideoNote(fileId: 'v1', fileUniqueId: 'u1', length: 240, duration: 5);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, videoNote: $videoNote);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::VIDEO_NOTE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(VideoNoteData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('voice route matches when message has voice', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $voice = new Voice(fileId: 'v1', fileUniqueId: 'u1', duration: 10);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, voice: $voice);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::VOICE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(VoiceData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('story route matches when message has story', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $story = new Story(chat: $chat, id: 42);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, story: $story);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::STORY, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(StoryData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('paid media route matches when message has paid media', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $paidMedia = new PaidMediaInfo(starCount: 10, paidMedia: [new PaidMediaPreview(100, 100, 100)]);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, paidMedia: $paidMedia);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::PAID_MEDIA, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PaidMediaData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('contact route matches when message has contact', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $contact = new Contact(phoneNumber: '+79001234567', firstName: 'John');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, contact: $contact);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::CONTACT, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ContactData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('checklist route matches when message has checklist', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $checklist = new Checklist(title: 'Todo', tasks: [new ChecklistTask(id: 1, text: 'Item')]);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, checklist: $checklist);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::CHECKLIST, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ChecklistData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('dice route matches when message has dice', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $dice = new Dice(emoji: '🎲', value: 4);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, dice: $dice);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::DICE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(DiceData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('game route matches when message has game', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $game = new Game(title: 'Game', description: 'Desc', photo: []);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, game: $game);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::GAME, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(GameData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('invoice route matches when message has invoice', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $invoice = new Invoice(title: 'Order', description: 'Desc', startParameter: 'sp', currency: 'USD', totalAmount: 1000);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, invoice: $invoice);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::INVOICE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(InvoiceData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('successful payment route matches when message has successful payment', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $payment = new SuccessfulPayment(currency: 'USD', totalAmount: 1000, invoicePayload: 'p', telegramPaymentChargeId: 't', providerPaymentChargeId: 'pr');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, successfulPayment: $payment);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::SUCCESSFUL_PAYMENT, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(SuccessfulPaymentData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('passport data route matches when message has passport data', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $creds = new EncryptedCredentials(data: 'd', hash: 'h', secret: 's');
    $passportData = new TelegramPassportData(data: [], credentials: $creds);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, passportData: $passportData);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::PASSPORT_DATA, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PassportData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('reply route matches when message has reply to message', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $original = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'Original');
    $reply = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'Reply', replyToMessage: $original);
    $update = new Update(updateId: 1, message: $reply);
    $route = new TelegramRoute(type: RouteType::REPLY_TO_MESSAGE, botId: 'bot', pattern: '*');
    expect($route->matches($update))->toBeInstanceOf(ReplyData::class);
});

test('reply route does not match when message has no reply', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'No reply');
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::REPLY_TO_MESSAGE, botId: 'bot', pattern: '*');
    expect($route->matches($update))->toBeNull();
});

test('quote route matches when message has quote', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $original = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'Original');
    $quote = new TextQuote(text: 'Orig', position: 0);
    $reply = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'Reply', replyToMessage: $original, quote: $quote);
    $update = new Update(updateId: 1, message: $reply);
    $route = new TelegramRoute(type: RouteType::QUOTED_MESSAGE, botId: 'bot', pattern: '*');
    expect($route->matches($update))->toBeInstanceOf(QuoteData::class);
    $noQuote = new Message(messageId: 3, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'x');
    expect($route->matches(new Update(updateId: 2, message: $noQuote)))->toBeNull();
});

test('new chat title route matches when message has new chat title', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, newChatTitle: 'New Title');
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::NEW_CHAT_TITLE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(NewChatTitleData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('new chat photo route matches when message has new chat photo', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $photoSize = new PhotoSize(fileId: 'p1', fileUniqueId: 'u1', width: 100, height: 100);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, newChatPhoto: [$photoSize]);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::NEW_CHAT_PHOTO, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(NewChatPhotoData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('delete chat photo route matches when message has delete chat photo', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, deleteChatPhoto: true);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::DELETE_CHAT_PHOTO, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(DeleteChatPhotoData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('auto delete timer changed route matches when message has timer changed', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $timer = new MessageAutoDeleteTimerChanged(messageAutoDeleteTime: 60);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, messageAutoDeleteTimerChanged: $timer);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::AUTO_DELETE_TIMER_CHANGED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(AutoDeleteTimerChangedData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('pinned message route matches when message has pinned message', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $pinned = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'Pinned');
    $message = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, pinnedMessage: $pinned);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::PINNED_MESSAGE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PinnedMessageData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 3, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('forum topic created route matches when message has forum topic created', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $payload = new ForumTopicCreated(name: 'Topic', iconColor: 0x6FB9F0, iconCustomEmojiId: null);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, forumTopicCreated: $payload);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::FORUM_TOPIC_CREATED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ForumTopicCreatedData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('forum topic edited route matches when message has forum topic edited', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $payload = new ForumTopicEdited(name: 'New Name', iconCustomEmojiId: null);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, forumTopicEdited: $payload);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::FORUM_TOPIC_EDITED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ForumTopicEditedData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('forum topic closed route matches when message has forum topic closed', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $payload = new ForumTopicClosed;
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, forumTopicClosed: $payload);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::FORUM_TOPIC_CLOSED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ForumTopicClosedData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('forum topic reopened route matches when message has forum topic reopened', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $payload = new ForumTopicReopened;
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, forumTopicReopened: $payload);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::FORUM_TOPIC_REOPENED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ForumTopicReopenedData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('general forum topic event route matches when message has general forum topic event', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $payload = new GeneralForumTopicHidden;
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, generalForumTopicHidden: $payload);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::GENERAL_FORUM_TOPIC_EVENT, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(GeneralForumTopicEventData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('boost added route matches when message has boost added', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $boost = new ChatBoostAdded(boostCount: 2);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, boostAdded: $boost, senderBoostCount: 1);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::BOOST_ADDED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(BoostAddedData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('document route matches when message has document', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $document = new Document(fileId: 'd1', fileUniqueId: 'u1');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, document: $document);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::DOCUMENT, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(DocumentData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('document route with document options matches only allowed mime type', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $documentPdf = new Document(fileId: 'd1', fileUniqueId: 'u1', mimeType: 'application/pdf');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, document: $documentPdf);
    $update = new Update(updateId: 1, message: $message);
    $routeAllowed = new TelegramRoute(type: RouteType::DOCUMENT, botId: 'bot', documentOptions: [MimeType::PDF]);
    expect($routeAllowed->matches($update))->toBeInstanceOf(DocumentData::class);
    $documentTxt = new Document(fileId: 'd2', fileUniqueId: 'u2', mimeType: 'text/plain');
    $messageTxt = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, document: $documentTxt);
    $routePdfOnly = new TelegramRoute(type: RouteType::DOCUMENT, botId: 'bot', documentOptions: [MimeType::PDF]);
    expect($routePdfOnly->matches(new Update(updateId: 2, message: $messageTxt)))->toBeNull();
});

test('poll route matches when message has poll', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $poll = new Poll(id: 'p1', question: 'Q?', options: [], totalVoterCount: 0, isClosed: false, isAnonymous: true, type: 'regular', allowsMultipleAnswers: false);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, poll: $poll);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::POLL, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PollData::class);
    expect($route->matches(new Update(updateId: 2, message: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user))))->toBeNull();
});

test('poll route with poll options filters by type and anonymous', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $pollQuiz = new Poll(id: 'p1', question: 'Q?', options: [], totalVoterCount: 0, isClosed: false, isAnonymous: true, type: 'quiz', allowsMultipleAnswers: false);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, poll: $pollQuiz);
    $update = new Update(updateId: 1, message: $message);
    $routeQuiz = new TelegramRoute(type: RouteType::POLL, botId: 'bot', pollOptions: new PollOptions(isAnonymous: true, pollType: PollType::QUIZ));
    expect($routeQuiz->matches($update))->toBeInstanceOf(PollData::class);
    $routeRegular = new TelegramRoute(type: RouteType::POLL, botId: 'bot', pollOptions: new PollOptions(isAnonymous: null, pollType: PollType::REGULAR));
    expect($routeRegular->matches($update))->toBeNull();
});

test('poll closed route matches when update has poll', function (): void {
    $poll = new Poll(id: 'p1', question: 'Q?', options: [], totalVoterCount: 0, isClosed: true, isAnonymous: true, type: 'regular', allowsMultipleAnswers: false);
    $update = new Update(updateId: 1, poll: $poll);
    $route = new TelegramRoute(type: RouteType::POLL_CLOSED, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PollClosedData::class);
    expect($route->matches(new Update(updateId: 2)))->toBeNull();
});

test('poll answer route matches when update has poll answer', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $pollAnswer = new PollAnswer(pollId: 'p1', voterChat: null, user: $user, optionIds: [0]);
    $update = new Update(updateId: 1, pollAnswer: $pollAnswer);
    $route = new TelegramRoute(type: RouteType::POLL_ANSWER, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PollAnswerData::class);
    expect($route->matches(new Update(updateId: 2)))->toBeNull();
});

test('photo route matches when message has photo without media group', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $photoSize = new PhotoSize(fileId: 'p1', fileUniqueId: 'u1', width: 100, height: 100);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, photo: [$photoSize]);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::PHOTO, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(PhotoData::class);
    $withMediaGroup = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, photo: [$photoSize], mediaGroupId: 'mg1');
    expect($route->matches(new Update(updateId: 2, message: $withMediaGroup)))->toBeNull();
});

test('photo media group route returns null when cache has no grouped photos', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $photoSize = new PhotoSize(fileId: 'p1', fileUniqueId: 'u1', width: 100, height: 100);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, photo: [$photoSize], mediaGroupId: 'mg1');
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::PHOTO_MEDIA_GROUP, botId: 'bot');
    expect($route->matches($update))->toBeNull();
});

test('photo media group route matches when cache has grouped photos', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $photoSize = new PhotoSize(fileId: 'p1', fileUniqueId: 'u1', width: 100, height: 100);
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, photo: [$photoSize], mediaGroupId: 'mg2');
    $update = new Update(updateId: 1, message: $message);
    Cache::put('media_group_items_mg2', [[$photoSize]], 10);
    $route = new TelegramRoute(type: RouteType::PHOTO_MEDIA_GROUP, botId: 'bot');
    $data = $route->matches($update);
    expect($data)->toBeInstanceOf(PhotoMediaGroupData::class)->and($data->botId)->toBe('bot');
    Cache::forget('media_group_items_mg2');
});

test('venue route matches when message has venue', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $location = new Location(latitude: 55.75, longitude: 37.62);
    $venue = new Venue(location: $location, title: 'Place', address: 'Addr');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, venue: $venue, location: $location);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::VENUE, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(VenueData::class);
});

test('location route matches when message has venue and location', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $location = new Location(latitude: 55.75, longitude: 37.62);
    $venue = new Venue(location: $location, title: 'Place', address: 'Addr');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, venue: $venue, location: $location);
    $update = new Update(updateId: 1, message: $message);
    $route = new TelegramRoute(type: RouteType::LOCATION, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(LocationData::class);
});

test('business message text route matches when update has business message with text', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'private');
    $businessMessage = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'Hello', businessConnectionId: 'conn1');
    $update = new Update(updateId: 1, businessMessage: $businessMessage);
    $route = new TelegramRoute(type: RouteType::BUSINESS_MESSAGE_TEXT, botId: 'bot', pattern: '*');
    expect($route->matches($update))->toBeInstanceOf(BusinessMessageTextData::class);
    $updateNoText = new Update(updateId: 2, businessMessage: new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user, businessConnectionId: 'c'));
    expect($route->matches($updateNoText))->toBeNull();
});

test('business connection route matches when update has business connection', function (): void {
    $connection = new BusinessConnection(id: 'bc1', user: new User(id: 1, isBot: false, firstName: 'U'), userChatId: 1, date: new DateTimeImmutable, isEnabled: true);
    $update = new Update(updateId: 1, businessConnection: $connection);
    $route = new TelegramRoute(type: RouteType::BUSINESS_CONNECTION, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(BusinessConnectionData::class);
    expect($route->matches(new Update(updateId: 2)))->toBeNull();
});

test('chat member updated route matches when update has my chat member or chat member', function (): void {
    $user = new User(id: 1, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 123, type: 'group');
    $oldMember = new Phptg\BotApi\Type\ChatMemberLeft(user: $user);
    $newMember = new Phptg\BotApi\Type\ChatMemberMember(user: $user);
    $chatMemberUpdated = new ChatMemberUpdated(chat: $chat, from: $user, date: new DateTimeImmutable, oldChatMember: $oldMember, newChatMember: $newMember);
    $update = new Update(updateId: 1, myChatMember: $chatMemberUpdated);
    $route = new TelegramRoute(type: RouteType::MY_CHAT_MEMBER, botId: 'bot');
    expect($route->matches($update))->toBeInstanceOf(ChatMemberUpdatedData::class);
});
