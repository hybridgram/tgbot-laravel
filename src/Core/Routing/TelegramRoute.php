<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use HybridGram\Core\MediaGroup\MediaGroupGrouper;
use HybridGram\Core\Middleware\MiddlewareManager;
use HybridGram\Core\Middleware\MiddlewarePipeline;
use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use HybridGram\Core\Routing\RouteData\AnimationData;
use HybridGram\Core\Routing\RouteData\AnyData;
use HybridGram\Core\Routing\RouteData\AudioData;
use HybridGram\Core\Routing\RouteData\AutoDeleteTimerChangedData;
use HybridGram\Core\Routing\RouteData\BoostAddedData;
use HybridGram\Core\Routing\RouteData\CallbackQueryData;
use HybridGram\Core\Routing\RouteData\ChecklistData;
use HybridGram\Core\Routing\RouteData\CommandData;
use HybridGram\Core\Routing\RouteData\ContactData;
use HybridGram\Core\Routing\RouteData\DeleteChatPhotoData;
use HybridGram\Core\Routing\RouteData\DiceData;
use HybridGram\Core\Routing\RouteData\DocumentData;
use HybridGram\Core\Routing\RouteData\ExternalReplyData;
use HybridGram\Core\Routing\RouteData\FallbackData;
use HybridGram\Core\Routing\RouteData\ForumTopicEventData;
use HybridGram\Core\Routing\RouteData\GameData;
use HybridGram\Core\Routing\RouteData\GeneralForumTopicEventData;
use HybridGram\Core\Routing\RouteData\InvoiceData;
use HybridGram\Core\Routing\RouteData\LeftChatMemberData;
use HybridGram\Core\Routing\RouteData\LocationData;
use HybridGram\Core\Routing\RouteData\MessageData;
use HybridGram\Core\Routing\RouteData\NewChatMembersData;
use HybridGram\Core\Routing\RouteData\NewChatPhotoData;
use HybridGram\Core\Routing\RouteData\NewChatTitleData;
use HybridGram\Core\Routing\RouteData\PaidMediaData;
use HybridGram\Core\Routing\RouteData\PassportData;
use HybridGram\Core\Routing\RouteData\PhotoData;
use HybridGram\Core\Routing\RouteData\PhotoMediaGroupData;
use HybridGram\Core\Routing\RouteData\PinnedMessageData;
use HybridGram\Core\Routing\RouteData\PollClosedData;
use HybridGram\Core\Routing\RouteData\PollData;
use HybridGram\Core\Routing\RouteData\QuoteData;
use HybridGram\Core\Routing\RouteData\ReplyData;
use HybridGram\Core\Routing\RouteData\ReplyToStoryData;
use HybridGram\Core\Routing\RouteData\RouteDataInterface;
use HybridGram\Core\Routing\RouteData\StickerData;
use HybridGram\Core\Routing\RouteData\StoryData;
use HybridGram\Core\Routing\RouteData\SuccessfulPaymentData;
use HybridGram\Core\Routing\RouteData\VenueData;
use HybridGram\Core\Routing\RouteData\VideoNoteData;
use HybridGram\Core\Routing\RouteData\VoiceData;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface;
use HybridGram\Telegram\Document\MimeType;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\ForumTopicClosed;
use Phptg\BotApi\Type\ForumTopicCreated;
use Phptg\BotApi\Type\ForumTopicEdited;
use Phptg\BotApi\Type\ForumTopicReopened;
use Phptg\BotApi\Type\GeneralForumTopicHidden;
use Phptg\BotApi\Type\GeneralForumTopicUnhidden;
use Phptg\BotApi\Type\Update\Update;

final class TelegramRoute
{
    protected ?TelegramRouter $router = null;

    public function __construct(
        public RouteType $type = RouteType::ANY,
        public string            $botId = '*',
        public string|array|\Closure|null $action = null,
        public \Closure|string|null          $pattern = null,
        public array             $middlewares = [],
        public string|array|null $fromChatState = null,
        public string|array|null $fromUserState = null,
        public string|array|null $exceptChatState = null,
        public string|array|null $exceptUserState = null,
        public ?string           $toState = null,
        public ChatType          $chatType = ChatType::PRIVATE,
        public ?ActionType       $actionType = null,
        public ?int              $actionTimeout = null,
        public ?int              $cacheTtl = null,
        public ?string           $cacheKey = null,
        public ?PollOptions      $pollOptions = null,
        public ?array            $documentOptions = null,
        public ?RouteDataInterface        $data = null, // todo может private?
        /** @var array<int, QueryParamInterface>|null */
        public ?array            $callbackQueryOptions = null,
    ) {
        $this->router = App::get(TelegramRouter::class);
    }

    /**
     * Check pattern for specific route type
     */
    public function matches(Update $update): ?RouteDataInterface
    {
        if (is_callable($this->pattern)) {
            if (! call_user_func($this->pattern, $update)) {
                return null;
            }
        } // todo в идеале тоже прокидывать специфическую  dataObject вместо  или вместе с апдейтом.

        return match ($this->type) {
            RouteType::ANY => $this->matchesAny($update),
            RouteType::MESSAGE => $this->matchesMessage($update),
            RouteType::COMMAND => $this->matchesCommand($update),
            RouteType::DOCUMENT => $this->matchesDocument($update),
            RouteType::POLL => $this->matchesPoll($update),
            RouteType::POLL_CLOSED => $this->matchesPollClosed($update),
            RouteType::POLL_ANSWER => throw new \Exception('To be implemented'), // todo доделать
            RouteType::PHOTO => $this->matchesPhoto($update),
            RouteType::PHOTO_MEDIA_GROUP => $this->matchesPhotoMediaGroup($update),
            RouteType::VENUE => $this->matchesVenue($update),
            RouteType::LOCATION => $this->matchesLocation($update),
            RouteType::ANIMATION => $this->matchesAnimation($update),
            RouteType::AUDIO => $this->matchesAudio($update),
            RouteType::STICKER => $this->matchesSticker($update),
            RouteType::VIDEO_NOTE => $this->matchesVideoNote($update),
            RouteType::VOICE => $this->matchesVoice($update),
            RouteType::STORY => $this->matchesStory($update),
            RouteType::PAID_MEDIA => $this->matchesPaidMedia($update),
            RouteType::CONTACT => $this->matchesContact($update),
            RouteType::CHECKLIST => $this->matchesChecklist($update),
            RouteType::DICE => $this->matchesDice($update),
            RouteType::GAME => $this->matchesGame($update),
            RouteType::INVOICE => $this->matchesInvoice($update),
            RouteType::SUCCESSFUL_PAYMENT => $this->matchesSuccessfulPayment($update),
            RouteType::PASSPORT_DATA => $this->matchesPassportData($update),
            RouteType::REPLY_TO_MESSAGE => $this->matchesReply($update),
            RouteType::EXTERNAL_REPLY_MESSAGE => $this->matchesExternalReply($update),
            RouteType::QUOTED_MESSAGE => $this->matchesQuote($update),
            RouteType::REPLY_TO_STORY => $this->matchesReplyToStory($update),
            RouteType::NEW_CHAT_MEMBER => $this->matchesNewChatMembers($update),
            RouteType::LEFT_CHAT_MEMBER => $this->matchesLeftChatMember($update),
            RouteType::NEW_CHAT_TITLE => $this->matchesNewChatTitle($update),
            RouteType::NEW_CHAT_PHOTO => $this->matchesNewChatPhoto($update),
            RouteType::DELETE_CHAT_PHOTO => $this->matchesDeleteChatPhoto($update),
            RouteType::AUTO_DELETE_TIMER_CHANGED => $this->matchesAutoDeleteTimerChanged($update),
            RouteType::PINNED_MESSAGE => $this->matchesPinnedMessage($update),
            RouteType::FORUM_TOPIC_EVENT => $this->matchesForumTopicEvent($update),
            RouteType::GENERAL_FORUM_TOPIC_EVENT => $this->matchesGeneralForumTopicEvent($update),
            RouteType::BOOST_ADDED => $this->matchesBoostAdded($update),
            RouteType::EDITED_MESSAGE => throw new \Exception('To be implemented'),
            RouteType::CALLBACK_QUERY_TEXT => throw new \Exception('To be implemented'),
            RouteType::CALLBACK_QUERY_DATA => throw new \Exception('To be implemented'),
            RouteType::CALLBACK_QUERY => $this->matchesCallbackQuery($update),
            RouteType::SHIPPING_QUERY => throw new \Exception('To be implemented'),
            RouteType::PRE_CHECKOUT_QUERY => throw new \Exception('To be implemented'),
            RouteType::SUCCESSFULLY_PAYMENT => throw new \Exception('To be implemented'),
            RouteType::PASSPORT_DATE => throw new \Exception('To be implemented'),
            RouteType::INLINE_QUERY => throw new \Exception('To be implemented'),
            RouteType::CHOSEN_INLINE_RESULT => throw new \Exception('To be implemented'),
            RouteType::CHANNEL_POST => throw new \Exception('To be implemented'),
            RouteType::EDITED_CHANNEL_POST => throw new \Exception('To be implemented'),
            RouteType::CHAT_JOIN_REQUEST => throw new \Exception('To be implemented'),
            RouteType::CHAT_MEMBER_UPDATED => throw new \Exception('To be implemented'),
            RouteType::WEBAPP_DATA => throw new \Exception('To be implemented'),
            RouteType::USER_SHARED => throw new \Exception('To be implemented'),
            RouteType::CHAT_SHARED => throw new \Exception('To be implemented'),
            RouteType::UPDATE => throw new \Exception('To be implemented'),
            RouteType::FALLBACK => $this->matchesFallback($update),
            RouteType::TEXT => throw new \Exception('To be implemented'),
        };
    }

    protected function matchesLocation(Update $update): ?LocationData
    {
        return empty($update->message->venue) ? null : new LocationData($update, $update->message->location, $this->botId);
    }

    protected function matchesAnimation(Update $update): ?AnimationData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->animation)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new AnimationData($update, $update->message->animation, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new AnimationData($update, $update->message->animation, $this->botId);
        }

        return null;
    }

    protected function matchesAudio(Update $update): ?AudioData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->audio)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new AudioData($update, $update->message->audio, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new AudioData($update, $update->message->audio, $this->botId);
        }

        return null;
    }

    protected function matchesSticker(Update $update): ?StickerData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->sticker)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new StickerData($update, $update->message->sticker, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new StickerData($update, $update->message->sticker, $this->botId);
        }

        return null;
    }

    protected function matchesVideoNote(Update $update): ?VideoNoteData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->videoNote)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new VideoNoteData($update, $update->message->videoNote, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new VideoNoteData($update, $update->message->videoNote, $this->botId);
        }

        return null;
    }

    protected function matchesVoice(Update $update): ?VoiceData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->voice)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new VoiceData($update, $update->message->voice, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new VoiceData($update, $update->message->voice, $this->botId);
        }

        return null;
    }

    protected function matchesStory(Update $update): ?StoryData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->story)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new StoryData($update, $update->message->story, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new StoryData($update, $update->message->story, $this->botId);
        }

        return null;
    }

    protected function matchesPaidMedia(Update $update): ?PaidMediaData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->paidMedia)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new PaidMediaData($update, $update->message->paidMedia, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new PaidMediaData($update, $update->message->paidMedia, $this->botId);
        }

        return null;
    }

    protected function matchesVenue(Update $update): ?VenueData
    {
        return empty($update->message->venue) ? null : new VenueData($update, $update->message->venue, $update->message->location, $this->botId);
    }

    protected function matchesContact(Update $update): ?ContactData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->contact)) {
            return null;
        }

        return new ContactData($update, $update->message->contact, $this->botId);
    }

    protected function matchesChecklist(Update $update): ?ChecklistData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->checklist)) {
            return null;
        }

        return new ChecklistData($update, $update->message->checklist, $this->botId);
    }

    protected function matchesDice(Update $update): ?DiceData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->dice)) {
            return null;
        }

        return new DiceData($update, $update->message->dice, $this->botId);
    }

    protected function matchesGame(Update $update): ?GameData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->game)) {
            return null;
        }

        return new GameData($update, $update->message->game, $this->botId);
    }

    protected function matchesInvoice(Update $update): ?InvoiceData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->invoice)) {
            return null;
        }

        return new InvoiceData($update, $update->message->invoice, $this->botId);
    }

    protected function matchesSuccessfulPayment(Update $update): ?SuccessfulPaymentData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->successfulPayment)) {
            return null;
        }

        return new SuccessfulPaymentData($update, $update->message->successfulPayment, $this->botId);
    }

    protected function matchesPassportData(Update $update): ?PassportData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->passportData)) {
            return null;
        }

        return new PassportData($update, $update->message->passportData, $this->botId);
    }

    protected function matchesReply(Update $update): ?ReplyData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (is_null($update->message->replyToMessage)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new ReplyData($update, $update->message->replyToMessage, $this->botId);
        }

        if (is_callable($this->pattern)) {
            if (call_user_func($this->pattern, $update->message->replyToMessage)) {
                return new ReplyData($update, $update->message->replyToMessage, $this->botId);
            }
            return null;
        }

        if (is_string($this->pattern) && !is_null($update->message->text)) {
            if (Str::is($this->pattern, $update->message->text)) {
                return new ReplyData($update, $update->message->replyToMessage, $this->botId);
            }
        }

        return null;
    }

    protected function matchesExternalReply(Update $update): ?ExternalReplyData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (is_null($update->message->externalReply)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new ExternalReplyData($update, $update->message->externalReply, $this->botId);
        }

        if (is_callable($this->pattern)) {
            if (call_user_func($this->pattern, $update->message->externalReply)) {
                return new ExternalReplyData($update, $update->message->externalReply, $this->botId);
            }
            return null;
        }

        return null;
    }

    protected function matchesQuote(Update $update): ?QuoteData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (is_null($update->message->quote)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new QuoteData($update, $update->message->quote, $this->botId);
        }

        if (is_callable($this->pattern)) {
            if (call_user_func($this->pattern, $update->message->quote)) {
                return new QuoteData($update, $update->message->quote, $this->botId);
            }
            return null;
        }

        if (is_string($this->pattern) && Str::is($this->pattern, $update->message->quote->text)) {
            return new QuoteData($update, $update->message->quote, $this->botId);
        }

        return null;
    }

    protected function matchesReplyToStory(Update $update): ?ReplyToStoryData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (is_null($update->message->replyToStory)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new ReplyToStoryData($update, $update->message->replyToStory, $this->botId);
        }

        if (is_callable($this->pattern)) {
            if (call_user_func($this->pattern, $update->message->replyToStory)) {
                return new ReplyToStoryData($update, $update->message->replyToStory, $this->botId);
            }
            return null;
        }

        if (is_string($this->pattern) && !is_null($update->message->text)) {
            if (Str::is($this->pattern, $update->message->text)) {
                return new ReplyToStoryData($update, $update->message->replyToStory, $this->botId);
            }
        }

        return null;
    }

    protected function matchesNewChatMembers(Update $update): ?NewChatMembersData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->newChatMembers)) {
            return null;
        }

        return new NewChatMembersData($update, $update->message->newChatMembers, $this->botId);
    }

    protected function matchesLeftChatMember(Update $update): ?LeftChatMemberData
    {
        if (is_null($update->message)) {
            return null;
        }

        if ($update->message->leftChatMember === null) {
            return null;
        }

        return new LeftChatMemberData($update, $update->message->leftChatMember, $this->botId);
    }

    protected function matchesNewChatTitle(Update $update): ?NewChatTitleData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->newChatTitle)) {
            return null;
        }

        return new NewChatTitleData($update, $update->message->newChatTitle, $this->botId);
    }

    protected function matchesNewChatPhoto(Update $update): ?NewChatPhotoData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->newChatPhoto)) {
            return null;
        }

        return new NewChatPhotoData($update, $update->message->newChatPhoto, $this->botId);
    }

    protected function matchesDeleteChatPhoto(Update $update): ?DeleteChatPhotoData
    {
        if (is_null($update->message)) {
            return null;
        }

        if (empty($update->message->deleteChatPhoto)) {
            return null;
        }

        return new DeleteChatPhotoData($update, (bool) $update->message->deleteChatPhoto, $this->botId);
    }

    protected function matchesAutoDeleteTimerChanged(Update $update): ?AutoDeleteTimerChangedData
    {
        if (is_null($update->message)) {
            return null;
        }

        if ($update->message->messageAutoDeleteTimerChanged === null) {
            return null;
        }

        return new AutoDeleteTimerChangedData($update, $update->message->messageAutoDeleteTimerChanged, $this->botId);
    }

    protected function matchesPinnedMessage(Update $update): ?PinnedMessageData
    {
        if (is_null($update->message)) {
            return null;
        }

        if ($update->message->pinnedMessage === null) {
            return null;
        }

        return new PinnedMessageData($update, $update->message->pinnedMessage, $this->botId);
    }

    protected function matchesForumTopicEvent(Update $update): ?ForumTopicEventData
    {
        if (is_null($update->message)) {
            return null;
        }

        if ($update->message->forumTopicCreated instanceof ForumTopicCreated) {
            return new ForumTopicEventData($update, 'forum_topic_created', $update->message->forumTopicCreated, $this->botId);
        }

        if ($update->message->forumTopicEdited instanceof ForumTopicEdited) {
            return new ForumTopicEventData($update, 'forum_topic_edited', $update->message->forumTopicEdited, $this->botId);
        }

        if ($update->message->forumTopicClosed instanceof ForumTopicClosed) {
            return new ForumTopicEventData($update, 'forum_topic_closed', $update->message->forumTopicClosed, $this->botId);
        }

        if ($update->message->forumTopicReopened instanceof ForumTopicReopened) {
            return new ForumTopicEventData($update, 'forum_topic_reopened', $update->message->forumTopicReopened, $this->botId);
        }

        return null;
    }

    protected function matchesGeneralForumTopicEvent(Update $update): ?GeneralForumTopicEventData
    {
        if (is_null($update->message)) {
            return null;
        }

        if ($update->message->generalForumTopicHidden instanceof GeneralForumTopicHidden) {
            return new GeneralForumTopicEventData(
                $update,
                'general_forum_topic_hidden',
                $update->message->generalForumTopicHidden,
                $this->botId,
            );
        }

        if ($update->message->generalForumTopicUnhidden instanceof GeneralForumTopicUnhidden) {
            return new GeneralForumTopicEventData(
                $update,
                'general_forum_topic_unhidden',
                $update->message->generalForumTopicUnhidden,
                $this->botId,
            );
        }

        return null;
    }

    protected function matchesBoostAdded(Update $update): ?BoostAddedData
    {
        if (is_null($update->message)) {
            return null;
        }

        if ($update->message->boostAdded === null) {
            return null;
        }

        return new BoostAddedData(
            $update,
            $update->message->boostAdded,
            $update->message->senderBoostCount,
            $this->botId,
        );
    }

    protected function matchesPhoto(Update $update): ?PhotoData
    {
        if (empty($update->message->photo)) {
            return null;
        }

        if (!is_null($update->message->mediaGroupId)) {
            return null;
        }

        if (is_null($this->pattern) || $this->pattern === '*') {
            return new PhotoData($update, $update->message->photo, $this->botId);
        }

        if (!is_null($update->message?->caption) && $update->message->caption === $this->pattern) {
            return new PhotoData($update, $update->message->photo, $this->botId);
        }

        return null;
    }

    protected function matchesPhotoMediaGroup(Update $update): ?PhotoMediaGroupData
    {
        if (empty($update->message->photo)) {
            return null;
        }

        // Медиа-группы (с media_group_id)
        if (is_null($update->message->mediaGroupId)) {
            return null;
        }

        $mediaGroupId = $update->message->mediaGroupId;



        if (is_null($this->pattern) || $this->pattern === '*') {
            $allPhotos = MediaGroupGrouper::getGroupedPhotos($mediaGroupId);

            if (empty($allPhotos)) {
                return null;
            }

            return new PhotoMediaGroupData($update, $allPhotos, $this->botId);
        }

        $caption = $update->message->caption;
        if (!is_null($caption) && $caption === $this->pattern) {

            $allPhotos = MediaGroupGrouper::getGroupedPhotos($mediaGroupId);
            if (empty($allPhotos)) {
                return null;
            }
            return new PhotoMediaGroupData($update, $allPhotos, $this->botId);
        }

        return null;
    }

    protected function matchesMessage(Update $update): ?MessageData
    {
        if (! isset($update->message->text)) {
            return null;
        }

        $text = $update->message->text;

        if ($this->pattern === '*') {
            return new MessageData($update, $text, $this->botId);
        }

        if (Str::is($this->pattern, $text)) {
            return new MessageData($update, $text, $this->botId);
        }

        return null;
    }

    protected function matchesCommand(Update $update): ?CommandData
    {
        if (is_null($update->message->text)) {
            return null;
        }

        $text = $update->message->text;

        if (! str_starts_with($text, '/')) {
            return null;
        }

        $command = explode(' ', $text)[0];
        $command = mb_substr($command, 1);

        if (preg_replace('/\//', '', $this->pattern, 1) === $command) {
            return new CommandData($update, $command, $this->botId, $this->extractCommandArguments($text));
        }

        return null;
    }

    protected function matchesDocument(Update $update): ?DocumentData
    {
        if (!isset($update->message->document)) {
            return null;
        }

        if (! is_null($this->documentOptions)) {
            $mimeType = $update->message->document->mimeType;
            $matches = false;
            
            foreach ($this->documentOptions as $allowedType) {
                if ($allowedType instanceof MimeType) {
                    if ($allowedType->value === $mimeType) {
                        $matches = true;
                        break;
                    }
                } elseif (is_string($allowedType)) {
                    if ($allowedType === $mimeType) {
                        $matches = true;
                        break;
                    }
                }
            }
            
            if (!$matches) {
                return null;
            }
        }

        if (is_string($this->pattern) && $this->pattern !== '*' && $this->pattern !== $update->message->caption) {
            return null;
        }

        return new DocumentData($update, $update->message->document, $this->botId);
    }

    protected function matchesPoll(Update $update): ?PollData
    {
        if (is_null($this->pollOptions)) {
            return new PollData($update->message->poll, $update, $this->botId);
        }

        if ($this->pollOptions->pollType?->value && $this->pollOptions->pollType->value !== $update->message->poll->type) {
            return null;
        }

        if ($this->pollOptions->isAnonymous && $this->pollOptions->isAnonymous !== $update->message->poll->isAnonymous) {
            return null;
        }

        return new PollData($update->message->poll, $update, $this->botId);
    }

    protected function matchesPollClosed(Update $update): ?PollClosedData
    {
        if (is_null($this->pollOptions)) {
            return new PollClosedData($update, $update->poll, $this->botId);
        }

        if ($this->pollOptions->pollType?->value && $this->pollOptions->pollType?->value !== $update->poll->type) {
            return null;
        }

        if ($this->pollOptions->isAnonymous && $this->pollOptions->isAnonymous !== $update->poll->isAnonymous) {
            return null;
        }

        return new PollClosedData($update, $update->poll, $this->botId);
    }

    public function matchesCallbackQuery(Update $update): ?CallbackQueryData
    {
        if (is_null($update->callbackQuery)) {
            return null;
        }

        $data = $update->callbackQuery->data ?? '';

        try {
            $parsed = CallbackQueryDataString::parse($data);
        } catch (\Throwable) {
            return null;
        }

        if (!is_null($this->pattern) && $this->pattern !== '*' && $this->pattern !== $parsed->action) {
            return null;
        }

        if ($this->callbackQueryOptions !== null) {
            foreach ($this->callbackQueryOptions as $item) {
                if ($item->matches($parsed->params)) {
                    return new CallbackQueryData($update, $parsed->action, $parsed->params, $update->callbackQuery, $this->botId);
                }
            }
            return null;
        }

        if ($parsed->params !== []) {
            return null;
        }

        return new CallbackQueryData($update, $parsed->action, $parsed->params, $update->callbackQuery, $this->botId);
    }

    protected function extractCommandArguments(string $text): array
    {
        $parts = explode(' ', $text);
        array_shift($parts);

        return array_filter($parts);
    }

    protected function matchesAny(Update $update): ?AnyData
    {
        return new AnyData($update, $this->botId);
    }

    protected function matchesFallback(Update $update): ?FallbackData
    {
        return new FallbackData($update, $this->botId);
    }

    public function executeWithMiddleware(Update $update, callable $finalHandler): mixed
    {
        $manager = App::get(MiddlewareManager::class);
        $pipeline = new MiddlewarePipeline();

        $pipeline->addMany($manager->getGlobalMiddlewares());

        $routeMiddlewares = array_filter($this->middlewares, function($middleware) {
            return $middleware instanceof TelegramRouteMiddlewareInterface;
        });
        $pipeline->addMany($routeMiddlewares);
        
        return $pipeline->process($update, $finalHandler);
    }
}
