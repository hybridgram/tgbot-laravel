<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use HybridGram\Core\Middleware\MiddlewareManager;
use HybridGram\Core\Middleware\MiddlewarePipeline;
use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
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
use HybridGram\Core\Routing\RouteData\RouteDataInterface;
use HybridGram\Core\Routing\RouteData\StickerData;
use HybridGram\Core\Routing\RouteData\StoryData;
use HybridGram\Core\Routing\RouteData\SuccessfulPaymentData;
use HybridGram\Core\Routing\RouteData\TextMessageData;
use HybridGram\Core\Routing\RouteData\VenueData;
use HybridGram\Core\Routing\RouteData\VideoNoteData;
use HybridGram\Core\Routing\RouteData\VoiceData;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface;
use HybridGram\Telegram\Document\MimeType;
use Illuminate\Support\Facades\App;
use Phptg\BotApi\Type\Update\Update;

final class TelegramRoute
{
    protected ?TelegramRouter $router = null;

    public function __construct(
        public RouteType $type = RouteType::ANY,
        public string $botId = '*',
        /** @var \Closure|string|array<string>|null array — list of action names (strings) */
        public string|array|\Closure|null $action = null,
        public \Closure|string|null $pattern = null,
        /** @var array<TelegramRouteMiddlewareInterface> */
        public array $middlewares = [],
        /** @var string|string[]|null $fromChatState */
        public string|array|null $fromChatState = null,
        /** @var string|string[]|null $fromUserState */
        public string|array|null $fromUserState = null,
        /** @var string|string[]|null $exceptChatState */
        public string|array|null $exceptChatState = null,
        /** @var string|string[]|null $exceptUserState */
        public string|array|null $exceptUserState = null,
        public ?string $toState = null,
        /** @var ChatType[]|null $chatTypes null means all chat types, default [ChatType::PRIVATE] */
        public ?array $chatTypes = [ChatType::PRIVATE],
        public ?ActionType $actionType = null,
        public ?int $actionTimeout = null,
        public ?int $cacheTtl = null,
        public ?string $cacheKey = null,
        public ?PollOptions $pollOptions = null,
        /** @var array<MimeType|string>|null array of allowed MIME types (values — MimeType or string) */
        public ?array $documentOptions = null,
        public ?RouteOptions\ChatMemberOptions $chatMemberOptions = null,
        public ?RouteDataInterface $data = null,
        /** @var array<int, QueryParamInterface>|null */
        public ?array $callbackQueryOptions = null,
        public ?\Closure $commandParamOptions = null,
    ) {
        $this->router = App::get(TelegramRouter::class);
    }

    /**
     * Check pattern for specific route type
     */
    public function matches(Update $update): ?RouteDataInterface
    {
        return match ($this->type) {
            RouteType::ANY => AnyData::match($update, $this),
            RouteType::TEXT_MESSAGE => TextMessageData::match($update, $this),
            RouteType::BUSINESS_MESSAGE_TEXT => BusinessMessageTextData::match($update, $this), // todo split into command etc
            RouteType::BUSINESS_CONNECTION => BusinessConnectionData::match($update, $this),
            RouteType::COMMAND => CommandData::match($update, $this),
            RouteType::DOCUMENT => DocumentData::match($update, $this),
            RouteType::POLL => PollData::match($update, $this),
            RouteType::POLL_CLOSED => PollClosedData::match($update, $this),
            RouteType::POLL_ANSWER => PollAnswerData::match($update, $this),
            RouteType::PHOTO => PhotoData::match($update, $this),
            RouteType::PHOTO_MEDIA_GROUP => PhotoMediaGroupData::match($update, $this),
            RouteType::VENUE => VenueData::match($update, $this),
            RouteType::LOCATION => LocationData::match($update, $this),
            RouteType::ANIMATION => AnimationData::match($update, $this),
            RouteType::AUDIO => AudioData::match($update, $this),
            RouteType::STICKER => StickerData::match($update, $this),
            RouteType::VIDEO_NOTE => VideoNoteData::match($update, $this),
            RouteType::VOICE => VoiceData::match($update, $this),
            RouteType::STORY => StoryData::match($update, $this),
            RouteType::PAID_MEDIA => PaidMediaData::match($update, $this),
            RouteType::CONTACT => ContactData::match($update, $this),
            RouteType::CHECKLIST => ChecklistData::match($update, $this),
            RouteType::DICE => DiceData::match($update, $this),
            RouteType::GAME => GameData::match($update, $this),
            RouteType::INVOICE => InvoiceData::match($update, $this),
            RouteType::SUCCESSFUL_PAYMENT => SuccessfulPaymentData::match($update, $this),
            RouteType::PASSPORT_DATA => PassportData::match($update, $this),
            RouteType::REPLY_TO_MESSAGE => ReplyData::match($update, $this),
            RouteType::EXTERNAL_REPLY_MESSAGE => ExternalReplyData::match($update, $this),
            RouteType::QUOTED_MESSAGE => QuoteData::match($update, $this),
            RouteType::REPLY_TO_STORY => ReplyToStoryData::match($update, $this),
            RouteType::NEW_CHAT_TITLE => NewChatTitleData::match($update, $this),
            RouteType::NEW_CHAT_PHOTO => NewChatPhotoData::match($update, $this),
            RouteType::DELETE_CHAT_PHOTO => DeleteChatPhotoData::match($update, $this),
            RouteType::AUTO_DELETE_TIMER_CHANGED => AutoDeleteTimerChangedData::match($update, $this),
            RouteType::PINNED_MESSAGE => PinnedMessageData::match($update, $this),
            RouteType::FORUM_TOPIC_CREATED => ForumTopicCreatedData::match($update, $this),
            RouteType::FORUM_TOPIC_EDITED => ForumTopicEditedData::match($update, $this),
            RouteType::FORUM_TOPIC_CLOSED => ForumTopicClosedData::match($update, $this),
            RouteType::FORUM_TOPIC_REOPENED => ForumTopicReopenedData::match($update, $this),
            RouteType::GENERAL_FORUM_TOPIC_EVENT => GeneralForumTopicEventData::match($update, $this),
            RouteType::BOOST_ADDED => BoostAddedData::match($update, $this),
            RouteType::EDITED_MESSAGE => throw new \Exception('To be implemented'),
            RouteType::CALLBACK_QUERY_TEXT => throw new \Exception('To be implemented'),
            RouteType::CALLBACK_QUERY_DATA => throw new \Exception('To be implemented'),
            RouteType::CALLBACK_QUERY => CallbackQueryData::match($update, $this),
            RouteType::SHIPPING_QUERY => throw new \Exception('To be implemented'),
            RouteType::PRE_CHECKOUT_QUERY => throw new \Exception('To be implemented'),
            RouteType::SUCCESSFULLY_PAYMENT => throw new \Exception('To be implemented'),
            RouteType::PASSPORT_DATE => throw new \Exception('To be implemented'),
            RouteType::INLINE_QUERY => InlineQueryData::match($update, $this),
            RouteType::CHOSEN_INLINE_RESULT => throw new \Exception('To be implemented'),
            RouteType::CHANNEL_POST => throw new \Exception('To be implemented'),
            RouteType::EDITED_CHANNEL_POST => throw new \Exception('To be implemented'),
            RouteType::CHAT_JOIN_REQUEST => throw new \Exception('To be implemented'),
            RouteType::CHAT_MEMBER_UPDATED => throw new \Exception('To be implemented'),
            RouteType::MY_CHAT_MEMBER, RouteType::CHAT_MEMBER => ChatMemberUpdatedData::match($update, $this),
            RouteType::WEBAPP_DATA => throw new \Exception('To be implemented'),
            RouteType::USER_SHARED => throw new \Exception('To be implemented'),
            RouteType::CHAT_SHARED => throw new \Exception('To be implemented'),
            RouteType::UPDATE => throw new \Exception('To be implemented'),
            RouteType::FALLBACK => FallbackData::match($update, $this),
            RouteType::TEXT => throw new \Exception('To be implemented'),
            RouteType::DOCUMENT_MEDIA_GROUP => throw new \Exception('To be implemented'),
            RouteType::VIDEO => throw new \Exception('To be implemented'),
            RouteType::BUSINESS_MESSAGE_COMMAND => throw new \Exception('To be implemented'),
            RouteType::EDITED_BUSINESS_MESSAGE => throw new \Exception('To be implemented'),
            RouteType::REMOVED_CHAT_BOOST => throw new \Exception('To be implemented'),
            RouteType::CHAT_BOOST => throw new \Exception('To be implemented'),
            RouteType::PURCHASED_PAID_MEDIA => throw new \Exception('To be implemented'),
            RouteType::MESSAGE_REACTION_COUNT => throw new \Exception('To be implemented'),
            RouteType::MESSAGE_REACTION => throw new \Exception('To be implemented'),
            RouteType::DELETED_BUSINESS_MESSAGES => throw new \Exception('To be implemented'),
            RouteType::UNKNOWN => throw new \Exception('To be implemented'),
        };
    }

    public function executeWithMiddleware(Update $update, callable $finalHandler): mixed
    {
        $manager = App::get(MiddlewareManager::class);
        $pipeline = new MiddlewarePipeline;

        $pipeline->addMany($manager->getGlobalMiddlewares());
        $pipeline->addMany($this->middlewares);

        return $pipeline->process($update, $finalHandler);
    }
}
