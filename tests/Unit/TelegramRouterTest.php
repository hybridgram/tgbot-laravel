<?php

declare(strict_types=1);

use HybridGram\Core\Routing\RouteData\CommandData;
use HybridGram\Core\Routing\TelegramRouteBuilder;
use HybridGram\Facades\TelegramRouter;

it('can use fluent API for routing', function () {
    TelegramRouter::forBot('main_bot')
        ->chatType(HybridGram\Core\Routing\ChatType::PRIVATE)
        ->fromChatState(['state'])
        ->cache(3600)
        ->sendAction(HybridGram\Core\Routing\ActionType::UPLOAD_DOCUMENT, 5)
        ->toChatState('new_state')
        ->onCommand(function (CommandData $commandData) {
            logger()->info("hello $commandData->command", $commandData->commandParams);
        }, 'start');

    expect(true)->toBeTrue();
});

it('can use group method for routing', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state', 'chat_type' => HybridGram\Core\Routing\ChatType::PRIVATE], function (TelegramRouteBuilder $builder) {
            $builder->onMessage(function (HybridGram\Core\Routing\RouteData\MessageData $messageData) {
                logger()->info("message: $messageData->message");
            }, 'hi');
            $builder->onMessage(function (HybridGram\Core\Routing\RouteData\MessageData $messageData) {
                logger()->info("message * $messageData->message");
            }, '*');
            $builder->onCommand(function (CommandData $commandData) {
                logger()->info("best command $commandData->command", $commandData->commandParams);
            }, 'best');
        });

    expect(true)->toBeTrue();
});

it('can use onVenue in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onVenue(function (HybridGram\Core\Routing\RouteData\VenueData $venueData) {
                logger()->info("venue received", ['videos' => $venueData->venue]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onLocation in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onLocation(function (HybridGram\Core\Routing\RouteData\VenueData $venueData) {
                logger()->info("venue received", ['videos' => $venueData->venue]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onAnimation in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onAnimation(function (HybridGram\Core\Routing\RouteData\AnimationData $animationData) {
                logger()->info("animation received", ['animation' => $animationData->animation]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onAudio in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onAudio(function (HybridGram\Core\Routing\RouteData\AudioData $audioData) {
                logger()->info("audio received", ['audio' => $audioData->audio]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onSticker in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onSticker(function (HybridGram\Core\Routing\RouteData\StickerData $stickerData) {
                logger()->info("sticker received", ['sticker' => $stickerData->sticker]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onVideoNote in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onVideoNote(function (HybridGram\Core\Routing\RouteData\VideoNoteData $videoNoteData) {
                logger()->info("video note received", ['videoNote' => $videoNoteData->videoNote]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onVoice in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onVoice(function (HybridGram\Core\Routing\RouteData\VoiceData $voiceData) {
                logger()->info("voice received", ['voice' => $voiceData->voice]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onPaidMedia in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onPaidMedia(function (HybridGram\Core\Routing\RouteData\PaidMediaData $paidMediaData) {
                logger()->info("paid media received", ['paidMedia' => $paidMediaData->paidMedia]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onContact in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onContact(function (HybridGram\Core\Routing\RouteData\ContactData $contactData) {
                logger()->info("contact received", ['contact' => $contactData->contact]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onChecklist in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onChecklist(function (HybridGram\Core\Routing\RouteData\ChecklistData $checklistData) {
                logger()->info("checklist received", ['checklist' => $checklistData->checklist]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onDice in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onDice(function (HybridGram\Core\Routing\RouteData\DiceData $diceData) {
                logger()->info("dice received", ['dice' => $diceData->dice]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onGame in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onGame(function (HybridGram\Core\Routing\RouteData\GameData $gameData) {
                logger()->info("game received", ['game' => $gameData->game]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onInvoice in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onInvoice(function (HybridGram\Core\Routing\RouteData\InvoiceData $invoiceData) {
                logger()->info("invoice received", ['invoice' => $invoiceData->invoice]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onSuccessfulPayment in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onSuccessfulPayment(function (HybridGram\Core\Routing\RouteData\SuccessfulPaymentData $successfulPaymentData) {
                logger()->info("successful payment received", ['successfulPayment' => $successfulPaymentData->successfulPayment]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onPassportData in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onPassportData(function (HybridGram\Core\Routing\RouteData\PassportData $passportData) {
                logger()->info("passport data received", ['passportData' => $passportData->passportData]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onReply in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
            $builder->onReply(function (HybridGram\Core\Routing\RouteData\ReplyData $replyData) {
                logger()->info("reply received", ['replyToMessage' => $replyData->replyToMessage]);
            });
        });

    expect(true)->toBeTrue();
});

it('can use onExternalReply in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->onExternalReply(function (HybridGram\Core\Routing\RouteData\ExternalReplyData $externalReplyData) {
            logger()->info("external reply received", ['externalReply' => $externalReplyData->externalReply]);
        });
    });

    expect(true)->toBeTrue();
});

it('can use onQuote in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->onQuote(function (HybridGram\Core\Routing\RouteData\QuoteData $quoteData) {
            logger()->info("quote received", ['quote' => $quoteData->quote]);
        });
    });

    expect(true)->toBeTrue();
});

it('can use onReplyToStory in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->onReplyToStory(function (HybridGram\Core\Routing\RouteData\ReplyToStoryData $replyToStoryData) {
            logger()->info("reply to story received", ['replyToStory' => $replyToStoryData->replyToStory]);
        });
    });

    expect(true)->toBeTrue();
});

it('can use onNewChatMembers in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onNewChatMembers(function (HybridGram\Core\Routing\RouteData\NewChatMembersData $newChatMembersData) {
                logger()->info("new chat members received", ['newChatMembers' => $newChatMembersData->newChatMembers]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onLeftChatMember in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onLeftChatMember(function (HybridGram\Core\Routing\RouteData\LeftChatMemberData $leftChatMemberData) {
                logger()->info("left chat member received", ['leftChatMember' => $leftChatMemberData->leftChatMember]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onNewChatTitle in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onNewChatTitle(function (HybridGram\Core\Routing\RouteData\NewChatTitleData $newChatTitleData) {
                logger()->info("new chat title received", ['newChatTitle' => $newChatTitleData->newChatTitle]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onNewChatPhoto in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onNewChatPhoto(function (HybridGram\Core\Routing\RouteData\NewChatPhotoData $newChatPhotoData) {
                logger()->info("new chat photo received", ['newChatPhoto' => $newChatPhotoData->newChatPhoto]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onDeleteChatPhoto in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onDeleteChatPhoto(function (HybridGram\Core\Routing\RouteData\DeleteChatPhotoData $deleteChatPhotoData) {
                logger()->info("chat photo deleted", ['deleteChatPhoto' => $deleteChatPhotoData->deleteChatPhoto]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onMessageAutoDeleteTimerChanged in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onMessageAutoDeleteTimerChanged(function (HybridGram\Core\Routing\RouteData\AutoDeleteTimerChangedData $data) {
                logger()->info('auto delete timer changed', [
                    'messageAutoDeleteTime' => $data->messageAutoDeleteTimerChanged->messageAutoDeleteTime,
                ]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onPinnedMessage in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onPinnedMessage(function (HybridGram\Core\Routing\RouteData\PinnedMessageData $data) {
                logger()->info('pinned message received', [
                    'pinnedMessage' => $data->pinnedMessage,
                ]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onForumTopicEvent in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onForumTopicEvent(function (HybridGram\Core\Routing\RouteData\ForumTopicEventData $data) {
                logger()->info('forum topic event received', [
                    'event' => $data->event,
                    'payload' => $data->payload,
                ]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onGeneralForumTopicEvent in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onGeneralForumTopicEvent(function (HybridGram\Core\Routing\RouteData\GeneralForumTopicEventData $data) {
                logger()->info('general forum topic event received', [
                    'event' => $data->event,
                    'payload' => $data->payload,
                ]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onBoostAdded in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->chatType(HybridGram\Core\Routing\ChatType::GROUP)
            ->onBoostAdded(function (HybridGram\Core\Routing\RouteData\BoostAddedData $data) {
                logger()->info('boost added received', [
                    'boostCount' => $data->boostAdded->boostCount,
                    'senderBoostCount' => $data->senderBoostCount,
                ]);
            });
    });

    expect(true)->toBeTrue();
});

it('can use onAny in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->onAny(function (HybridGram\Core\Routing\RouteData\AnyData $anyData) {
            logger()->info("any update received", ['update' => $anyData->update]);
        });
    });

    expect(true)->toBeTrue();
});

it('can use onInlineQuery in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->onInlineQuery(function (HybridGram\Core\Routing\RouteData\InlineQueryData $inlineQueryData) {
            logger()->info("inline query received", ['query' => $inlineQueryData->inlineQuery->query]);
        });
    });

    expect(true)->toBeTrue();
});

it('can use onFallback in group', function () {
    TelegramRouter::group(['for_bot' => 'main_bot', 'from_state' => 'state'], function (TelegramRouteBuilder $builder) {
        $builder->onFallback(function (HybridGram\Core\Routing\RouteData\FallbackData $fallbackData) {
            logger()->info("fallback route called", ['update' => $fallbackData->update]);
        });
    });

    expect(true)->toBeTrue();
});
