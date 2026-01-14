### Типы апдейтов Telegram (`Update` / `allowed_updates`)

**Важно:** Telegram Bot API различает:
- **типы апдейтов** – что может прийти боту как объект `Update` (то, что указывается в `allowed_updates`);
- **типы сообщений/контента** – чем именно является `message` внутри апдейта (текст, фото, видео, опрос и т.д.).

Ниже сначала перечислены **все возможные типы апдейтов**, которые Telegram может прислать боту (по таблице `Update`), а затем – основные **типы содержимого `Message`**, которые на практике обычно хочется уметь маршрутизировать.

---

### 1. Полный список типов апдейтов (`Update` / `allowed_updates`) как методы `on*`

Каждый пункт соответствует:
- полю в объекте `Update` (кроме `update_id`);
- значению в массиве `allowed_updates` (`GetUpdates`, `setWebhook`);
- **методу** роутера/фасада в формате `onSomething`.

- [x] **`onMessage`** (`message`) – новое входящее сообщение любого типа (текст, фото, стикер и т.д.).
- [ ] **`onEditedMessage`** (`edited_message`) – новая версия уже известного боту сообщения.
- [ ] **`onChannelPost`** (`channel_post`) – новый пост в канале любого типа.
- [ ] **`onEditedChannelPost`** (`edited_channel_post`) – отредактированный пост в канале.

- [x] **`onBusinessConnection`** (`business_connection`) – бот был подключен/отключен к бизнес‑аккаунту, либо пользователь изменил настройки подключения. Обрабатывает объект `BusinessConnection` из `update.business_connection`. Метод принимает только `action` (без паттерна). В action передается `BusinessConnectionData` с полями: `businessConnection` (объект `BusinessConnection`), `update`, `botId`, а также доступны методы `getUser()` и `getChat()`.
- [ ] **`onBusinessMessage`** (`business_message`) – новое сообщение от подключённого бизнес‑аккаунта.
- [ ] **`onEditedBusinessMessage`** (`edited_business_message`) – отредактированное сообщение от бизнес‑аккаунта.
- [ ] **`onDeletedBusinessMessages`** (`deleted_business_messages`) – сообщения были удалены из подключённого бизнес‑аккаунта.

- [ ] **`onMessageReaction`** (`message_reaction`) – объект `MessageReactionUpdated`: пользователь изменил реакцию на сообщение.
- [ ] **`onMessageReactionCount`** (`message_reaction_count`) – объект `MessageReactionCountUpdated`: изменились суммарные анонимные реакции.

- [ ] **`onInlineQuery`** (`inline_query`) – новый входящий inline‑запрос (`InlineQuery`).
- [ ] **`onChosenInlineResult`** (`chosen_inline_result`) – выбранный пользователем результат inline‑запроса (`ChosenInlineResult`).

- [ ] **`onCallbackQuery`** (`callback_query`) – новый входящий callback от inline‑кнопки (`CallbackQuery`).

- [ ] **`onShippingQuery`** (`shipping_query`) – запрос на доставку (`ShippingQuery`) для инвойсов с гибкой ценой.
- [ ] **`onPreCheckoutQuery`** (`pre_checkout_query`) – pre‑checkout запрос перед подтверждением платежа (`PreCheckoutQuery`).
- [ ] **`onPurchasedPaidMedia`** (`purchased_paid_media`) – пользователь купил платный медиаконтент с непустым payload (`PaidMediaPurchased`).

- [x] **`onPoll`** (`poll`) – новое состояние опроса (`Poll`), включая остановленные опросы и опросы, отправленные ботом.
- [x] **`onPollAnswer`** (`poll_answer`) – пользователь изменил свой ответ в неанонимном опросе (`PollAnswer`).

- [ ] **`onMyChatMember`** (`my_chat_member`) – изменился статус **бота** в чате (`ChatMemberUpdated`).
- [ ] **`onChatMember`** (`chat_member`) – изменился статус **участника чата** (`ChatMemberUpdated`).
- [ ] **`onChatJoinRequest`** (`chat_join_request`) – пришёл запрос на вступление в чат (`ChatJoinRequest`).

- [ ] **`onChatBoost`** (`chat_boost`) – на чат добавили/изменили буст (`ChatBoostUpdated`).
- [ ] **`onRemovedChatBoost`** (`removed_chat_boost`) – у чата убрали буст (`ChatBoostRemoved`).

Это **все возможные значения `allowed_updates`** и все опциональные поля объекта `Update` на текущей версии Bot API, больше "типов апдейтов" в Telegram нет.

---

### 2. Основные типы содержимого `Message` как методы `on*Message`

Ниже – ключевые поля объекта `Message`, переписанные в формате методов `on*Message` или `on*`, по которым обычно имеет смысл строить роутинг (каждое поле является либо «типом сообщения», либо важным признаком).

- [ ] **`onTextMessage`** (`message.text`) – текстовое сообщение.
- [ ] **`onTextEntities`** (`message.entities` / `message.caption_entities`) – сущности в тексте/подписи (команды, ссылки, хэштеги и т.д.).
- [x] **`onAnimationMessage`** (`message.animation`) – сообщение‑анимация (`Animation`), обычно дублируется также в `document`.
- [x] **`onAudioMessage`** (`message.audio`) – сообщение с аудиофайлом (`Audio`).
- [x] **`onDocumentMessage`** (`message.document`) – сообщение с произвольным файлом (`Document`).
- [x] **`onPhotoMessage`** (`message.photo`) – сообщение‑фото (массив `PhotoSize`).
- [x] **`onStickerMessage`** (`message.sticker`) – сообщение‑стикер (`Sticker`).
- [x] **`onVideoMessage`** (`message.video`) – сообщение‑видео (`Video`).
- [x] **`onVideoNoteMessage`** (`message.video_note`) – круговое видео‑сообщение (`VideoNote`).
- [x] **`onVoiceMessage`** (`message.voice`) – голосовое сообщение (`Voice`).
- [x] **`onStoryMessage`** (`message.story`) – пересланная история (`Story`).
- [x] **`onPaidMediaMessage`** (`message.paid_media`) – сообщение с платным медиаконтентом (`PaidMediaInfo`).

- [x] **`onContactMessage`** (`message.contact`) – сообщение с контактом (`Contact`).
- [x] **`onLocationMessage`** (`message.location`) – сообщение с геопозицией (`Location`).
- [x] **`onVenueMessage`** (`message.venue`) – сообщение с местом (`Venue`).
- [x] **`onChecklistMessage`** (`message.checklist`) – сообщение‑чеклист (`Checklist`).
- [x] **`onDiceMessage`** (`message.dice`) – сообщение‑кубик (`Dice`).
- [x] **`onGameMessage`** (`message.game`) – сообщение‑игра (`Game`).
- [x] **`onPollMessage`** (`message.poll`) – встроенный опрос в сообщении (`Poll`).
- [x] **`onInvoiceMessage`** (`message.invoice`) – сообщение‑инвойс (`Invoice`).
- [x] **`onSuccessfulPaymentMessage`** (`message.successful_payment`) – сообщение об успешном платеже (`SuccessfulPayment`).
- [x] **`onPassportDataMessage`** (`message.passport_data`) – сообщение с данными Telegram Passport (`PassportData`).

- [x] **`onMediaGroupMessage`** (`message.media_group_id`) – сообщение, входящее в медиа‑группу (альбом).
- [x] **`onReplyMessage`** (`message.reply_to_message`) – обычный ответ на другое сообщение.
- [x] **`onExternalReplyMessage`** (`message.external_reply`) – ответ на сообщение из другого чата/топика.
- [x] **`onQuoteMessage`** (`message.quote`) – ответ с цитированием части исходного сообщения.
- [x] **`onReplyToStoryMessage`** (`message.reply_to_story`) – ответ на историю.

- [x] **`onNewChatTitle`** (`message.new_chat_title`) – изменён заголовок чата.
- [x] **`onNewChatPhoto`** (`message.new_chat_photo`) – установлено новое фото чата.
- [x] **`onDeleteChatPhoto`** (`message.delete_chat_photo`) – фото чата удалено.
- [x] **`onMessageAutoDeleteTimerChanged`** (`message.message_auto_delete_timer_changed`) – изменение авто‑удаления сообщений.
- [x] **`onPinnedMessage`** (`message.pinned_message`) – сообщение, содержащее закреплённое сообщение.
- [x] **`onForumTopicEvent`** (`message.forum_topic_*`) – события, связанные с форум‑топиками.
- [x] **`onGeneralForumTopicEvent`** (`message.general_forum_topic_*`) – события, связанные с общим форум‑топиком.
- [x] **`onBoostAdded`** (`message.boost_added` и связанные поля) – бусты, добавленные пользователем.

Полный список полей `Message` ещё шире (служебные флаги, бизнес‑поля, темы, истории и т.п.), но для маршрутизатора обычно достаточно перечисленных выше типов как базовых категорий.

---

### 3. Как этим пользоваться в либе

- **Уровень 1** – покрыть все типы из раздела 1 (апдейты) отдельными `RouteType`/методами роутера.
- **Уровень 2** – внутри `message`/`channel_post`/`business_message` разводить роуты по типам содержимого из раздела 2 (текст, фото, документ, опрос, платёж и т.д.).


