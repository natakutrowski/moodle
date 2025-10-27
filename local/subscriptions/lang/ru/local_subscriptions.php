<?php
$string['pluginname'] = 'Подписки';

// -- Subscription config
// Plans
$string['plan_1month'] = '1 месяц'; // do not delete
$string['plan_3months'] = '3 месяца'; // do not delete
$string['plan_6months'] = '6 месяцев'; // do not delete
$string['plan_1year'] = '1 год'; // do not delete
$string['plan_3years'] = '3 года'; // do not delete
$string['plan_lifetime'] = 'Бессрочно'; // do not delete

// Buttons
$string['btn_import_csv'] = 'Импорт подписок из CSV';

// -- Manage subscriptions
$string['manage_subscriptions'] = 'Управление подписками';
$string['updated_subscriptions'] = 'Обновлено подписок: {$a}.';
$string['delete_subscriptions'] = 'Удалено подписок: {$a}.';
$string['edit_subscriptions'] = 'Редактировать подписки';
$string['user'] = 'Пользователь';
$string['plan'] = 'Тариф';
$string['start_date'] = 'Дата начала';
$string['end_date'] = 'Дата окончания';
$string['status'] = 'Статус';
$string['creation_date'] = 'Дата создания';
$string['save_modifications'] = 'Сохранить изменения';
$string['delete_selected'] = 'Удалить выбранные подписки';
$string['popover_duration'] = 'Длительность';
$string['popover_scope'] = 'Область доступа';
$string['popover_courses'] = 'Курсы';
$string['popover_no_courses'] = 'Курсы не определены';

// -- Add subscription
$string['add_subscription'] = 'Добавить подписку';
$string['unknown_user'] = 'Неизвестный пользователь';
$string['sub_created'] = '{$a->user} оформлена подписка на тариф <strong>{$a->plan}</strong>.';
$string['sub_exists'] = 'У {$a->user} уже есть подписка ({$a->plan}).';
$string['sub_test_done'] = '{$a} подписан(а) на тестовый курс.';
$string['select_user'] = 'Выберите пользователя';
$string['submit_sub'] = 'Оформить подписку на выбранную область';
$string['submit_sub_test'] = 'Подписать только на тест';

// -- Import CSV
$string['import_subscriptions'] = 'Импорт подписок';
$string['import_subscriptions_csv'] = 'Импорт подписок из CSV-файла';
$string['email'] = 'Email';
$string['already_exists'] = 'Уже существует';
$string['import_preview'] = 'Предпросмотр подписок для импорта';
$string['select_csv_file'] = 'Выберите CSV-файл';
$string['submit_csv_file'] = 'Загрузить CSV-файл';
$string['import_count_valid'] = 'строка(и) будут импортированы.';
$string['import_count_ignored'] = 'Пропущено строк: {$a} (подписка уже существует).';

// -- Process CSV
$string['missing_param'] = 'Отсутствует параметр';
$string['no_valid_rows'] = 'Нет подходящих строк для импорта';
$string['import_success_count'] = 'Успешно импортировано подписок: {$a}.';
$string['import_skipped'] = 'Пропущенные записи (отсутствуют или некорректны данные)';
$string['invalid_or_missing_fields'] = 'Некорректные или отсутствующие поля';

// -- Manage plans
$string['scopes'] = '🎓 Область доступа';
$string['plans'] = '📝 Тарифы';
$string['user_subscriptions'] = '👨‍🎓 / 👩‍🎓 Подписки пользователей';
$string['translatetooltip'] = 'Подсказка по переводу'; // to be checked
$string['pricestooltip'] = 'Подсказка по ценам'; // to be checked

// Scopes
$string['scopename'] = 'Название области';
$string['includedcourses'] = 'Включённые курсы';
$string['addscope'] = '➕ Добавить новую область';
$string['scopelist'] = 'Список областей';
$string['sortaz'] = 'Сортировать A → Z';
$string['sortza'] = 'Сортировать Z → A';
$string['name'] = 'Название';
$string['description'] = 'Описание';
$string['courses'] = '📖 Курсы';
$string['dates'] = '📅 Даты';
$string['actions'] = '🛠️ Действия';
$string['createdon'] = 'Создано:';
$string['modifiedon'] = 'Изменено:';
$string['editscope'] = '✏️ Редактировать область';
$string['deletescope'] = '🗑️ Удалить область';
$string['edit'] = 'Редактировать область';
$string['add'] = 'Добавить область';
$string['scopecreated'] = 'Область создана. Теперь добавьте перевод.';
$string['scopecreateerror'] = 'Ошибка при создании области.';
$string['scopedeleted'] = 'Область и её переводы удалены.';
$string['scopedeleteerror'] = 'Ошибка при удалении области.';
$string['error_scope_name_exists'] = 'Область с таким названием уже существует.';

// Translations scopes
$string['translationspagetitle'] = 'Переводы';
$string['scopedefaultname'] = 'Название области по умолчанию';
$string['translatedlanguages'] = 'Переведённые языки';
$string['addtranslation'] = 'Добавить перевод';
$string['backtoscopelist'] = 'Назад к списку областей';
$string['language'] = 'Язык';
$string['alreadyused'] = 'Уже используется';
$string['defaultscopename'] = 'Название области по умолчанию';
$string['translatedname'] = 'Переведённое название';
$string['save'] = 'Сохранить';
$string['deletetranslation'] = 'Удалить этот перевод';
$string['errorduplicatetranslation'] = 'Перевод на выбранном языке уже существует.';
$string['showalltranslations'] = 'Показать все переводы';
$string['cancel'] = 'Отмена';
$string['confirmdeletetranslation'] = 'Вы уверены, что хотите безвозвратно удалить этот перевод?';

// Plans
$string['delete'] = 'Удалить';
$string['deactivateplan'] = 'Деактивировать тариф';
$string['activateplan'] = 'Активировать тариф';
$string['planname'] = 'Название тарифа';
$string['planduration'] = '⌛ Длительность тарифа';
$string['saveplan'] = 'Сохранить тариф';
$string['plancreated'] = 'Тариф успешно создан.';
$string['plancreateerror'] = 'Ошибка при создании тарифа.';
$string['error_plan_name_exists'] = 'Тариф с таким названием уже существует.';
$string['planstatusupdated'] = 'Статус тарифа обновлён.';
$string['planlist'] = 'Список тарифов';
$string['deleteplan'] = 'Удалить этот тариф';
$string['editplan'] = 'Редактировать этот тариф';
$string['thisplan'] = 'этот тариф';
$string['plandefaultname'] = 'Название тарифа по умолчанию';
$string['plandeleted'] = 'Тариф и все его переводы и цены удалены.';
$string['plandeleteerror'] = 'Ошибка при удалении тарифа.';
$string['backtoplanlist'] = 'Назад к списку тарифов';
$string['addplan'] = 'Добавить новый тариф';
$string['duration'] = '⌛ Длительность';
$string['availabletranslations'] = 'Доступные переводы';
$string['notranslation'] = 'Переводы отсутствуют';
$string['availablecurrencies'] = 'Доступные валюты';
$string['nocurrency'] = 'Валюты отсутствуют';
$string['planincomplete'] = 'Нельзя активировать: для тарифа нужен минимум один перевод и одна цена.';
$string['cannotactivateplan'] = 'Перед активацией тарифа задайте хотя бы один перевод и одну цену.';
$string['is_recurring'] = 'Периодическая подписка (автопродление)';
$string['is_recurring_help'] = 'Если включено, тариф продаётся через Stripe Subscriptions. Убедитесь, что для каждой валюты задан stripe_price_id.'; // do not delete

// Prices
$string['currency'] = 'Валюта';
$string['price'] = 'Цена';
$string['saveprice'] = 'Сохранить цену';
$string['error_invalid_price'] = 'Введите корректную положительную цену.';
$string['planprices'] = 'Цены';
$string['planpricesfor'] = 'Цены для {$a}';
$string['addprice'] = 'Добавить цену';
$string['editprice'] = 'Редактировать цену';
$string['deleteprice'] = 'Удалить цену';
$string['priceadded'] = 'Цена успешно добавлена.';
$string['priceupdated'] = 'Цена обновлена.';
$string['pricedeleted'] = 'Цена удалена.';
$string['confirmdeleteprice'] = 'Вы уверены, что хотите удалить эту цену?';
$string['error_currency_already_exists'] = 'Эта валюта уже задана для данного тарифа.';
$string['noprices'] = 'Цены отсутствуют';

$string['stripe_price_id'] = 'Stripe Price ID';
$string['stripe_price_id_help'] = 'Идентификатор периодической цены в Stripe (например, price_123…). Требуется для периодических тарифов.'; // do not delete
$string['badge_recurring'] = 'Автопродление';

// JS delete...
$string['thisscope'] = 'эту область';
$string['confirmdeletetitle'] = 'Подтвердите удаление';
$string['confirmdeletemessage'] = '⚠️ Это действие необратимо.<br><br>Вы действительно хотите удалить <strong>{$a}</strong>?<br><br>Будут удалены все связанные переводы.';
$string['confirmdeleteplanmessage'] = '⚠️ Это действие необратимо.<br><br>Вы действительно хотите удалить <strong>{$a}</strong>?<br><br>Будут удалены все связанные переводы и цены.';

$string['scope_and_duration'] = 'Область и длительность';
$string['courses_included'] = 'Включённые курсы';
$string['select_price'] = 'Выберите цену и валюту';

$string['your_subscriptions'] = 'Ваши подписки';
$string['no_active_subscriptions'] = 'У вас нет активных подписок.';

$string['pricepaid'] = 'Оплаченная сумма';

$string['courselist'] = 'Список курсов';

$string['close'] = 'Закрыть';

$string['subscribe'] = 'Купить подписку';
$string['change_currency'] = 'Сменить валюту';

$string['payment_success_check_email'] = 'Проверьте вашу почту: там есть письмо для завершения входа и установки пароля.';
$string['payment_pending_msg'] = 'Ваш платёж обрабатывается. Обычно это занимает несколько секунд.';
$string['payment_success_title'] = 'Платёж успешен';
$string['payment_success_thanks'] = 'Спасибо! Ваш платёж успешно обработан.';
$string['payment_canceled_title'] = 'Платёж отменён';
$string['payment_canceled_msg'] = 'Ваш платёж был отменён. Подписка не создана.';
$string['back_to_plans'] = 'Назад к доступным тарифам';

$string['checkout_title'] = 'Оформление заказа';
$string['checkout_duration'] = 'Длительность:';
$string['checkout_go_to_payment'] = 'Перейти к оплате';

$string['welcome_subject'] = 'Добро пожаловать в {$a}';
$string['welcome_body_intro'] = 'Ваша учётная запись создана, а подписка активирована.';
$string['welcome_username'] = 'Ваш логин:';
$string['welcome_plan_summary'] = 'Тариф: {$a}';
$string['welcome_amount_summary'] = 'Сумма: {$a}';

$string['receipt_title'] = 'Квитанция об оплате';
$string['receipt_plan'] = 'Тариф: ';
$string['receipt_amount'] = 'Сумма: ';
$string['receipt_tx'] = 'ID транзакции: ';
$string['receipt_period'] = 'Период доступа: ';

$string['welcome_temp_password_label'] = 'Временный пароль:';
$string['welcome_security_hint'] = 'В целях безопасности при первом входе вам будет предложено задать новый пароль.';
$string['receipt_intro'] = 'Вот копия сведений о вашей покупке:';
$string['receipt_button_open'] = 'Открыть мои курсы';

// Emails – failure/abandoned/reminder
$string['email_failed_subject'] = 'Не удалось завершить оплату';
$string['email_failed_intro'] = 'К сожалению, ваша попытка оплаты не удалась.';
$string['email_failed_help'] = 'Вы можете повторить попытку через несколько секунд, используя кнопку ниже. Если проблема сохранится, попробуйте другую карту или свяжитесь с банком.';
$string['email_button_retry'] = 'Повторить оплату';

$string['email_abandoned_subject'] = 'Завершите покупку';
$string['email_abandoned_intro'] = 'Вы не завершили покупку. Продолжайте с того места, где остановились:';

$string['email_reminder_subject'] = 'Всё ещё интересно? Завершите оформление подписки';
$string['email_reminder_intro'] = 'Вы можете завершить оформление подписки в один клик:';

// Scheduled task
$string['task_followup'] = 'Подписки — последующие письма';

$string['payment_error_title'] = 'Ошибка оплаты';
$string['payment_error_intro'] = 'Возникла ошибка при подготовке платежа. Пожалуйста, попробуйте ещё раз позже.';
$string['email_reminder2_subject'] = 'Последнее напоминание: завершите подписку';
$string['email_reminder2_intro'] = 'Мягкое напоминание завершить покупку. Можно оформить в один клик:';

$string['mail_recurring_started_subject'] = 'Ваша периодическая подписка «{$a}» активна';
$string['mail_recurring_started_body'] = 'Спасибо! Ваша периодическая подписка «{$a->plan}» началась {$a->start}.';
$string['view_my_subscriptions'] = 'Посмотреть мои подписки';

$string['plan_highlight'] = 'Выделение';
$string['highlight_popular'] = 'Популярный';
$string['highlight_premium'] = 'Премиум';
$string['plan_highlight_help'] = 'Выберите, как выделять тариф на публичной странице:
<ul>
  <li><b>Нет</b>: стандартная карточка</li>
  <li><b>Популярный</b>: жёлтый бейдж и акцентное оформление</li>
  <li><b>Премиум</b>: премиум-оформление с ярким призывом к действию</li>
</ul>'; // do not delete

$string['task_cleanup_login_tokens'] = 'Очистка просроченных токенов входа';

$string['option_queue_future'] = 'Продлить (активация {$a})';
$string['option_purchase_new'] = 'Новая подписка';
$string['choose_option'] = 'Выберите вариант';
$string['have_account_login_to_see_options'] = 'У вас уже есть аккаунт? — Войдите, чтобы увидеть варианты обновления, нажав здесь.';

// Above the options
$string['advisor_help_upgrade']  = 'Вы можете продлить текущую подписку по цепочке или перейти на более длительный тариф. Цена апгрейда скорректирована с учётом прошедшего времени.';
$string['advisor_help_standard'] = 'Выберите, как активировать эту подписку.';
$string['advisor_help_guest']    = 'Войдите, чтобы увидеть варианты апгрейда. Иначе вы можете оформить новую подписку, указав свои данные.';

// Price summary
$string['summary_price_title'] = 'Итоговая цена';

$string['personal_info_title'] = 'Личная информация';
$string['personal_info_help']  = 'Эти данные будут использованы для создания учётной записи и отправки подтверждения.';

$string['mail_hello'] = 'Здравствуйте, {$a},';
$string['mail_button_manage'] = 'Управлять моими подписками';

$string['subupdate_subject'] = 'Ваша подписка «{$a}» активна';
$string['subupdate_body']    = 'Вот обновлённые сведения о вашей подписке «{$a}»:';
$string['renewal_subject']   = 'Продление подтверждено — {$a}';
$string['renewal_body']      = 'Ваша подписка «{$a}» продлена. Подробности:';
$string['recurring_failed_subject'] = 'Неудачный платёж — {$a}';
$string['recurring_failed_body']    = 'Платёж по подписке «{$a}» не прошёл. Пожалуйста, обновите платёжные данные.';
$string['recurring_failed_button']  = 'Обновить способ оплаты';

$string['recurring_canceled_subject'] = 'Ваша подписка отменена — {$a}';
$string['recurring_canceled_body']    = 'Ваша подписка «{$a}» отменена. Доступ сохранится до конца текущего периода.';
$string['recurring_canceled_button']  = 'Оформить снова';

$string['details'] = 'Детали';
$string['subscription_details'] = 'Детали подписки';

$string['mysubs_title'] = 'Мои подписки';
$string['mysubs_empty'] = 'У вас ещё нет подписок.';
$string['period'] = 'Период';

$string['btn_extend']    = 'Продлить';

$string['option_upgrade_now_replace'] = 'Апгрейд на выбранную длительность (заменить очередь)';

$string['task_send_expiry_reminders'] = 'Отправлять напоминания об окончании для непериодических подписок';
$string['expiry_reminder_subject'] = 'Ваш доступ заканчивается через {$a} дн.';
$string['expiry_reminder_body']    = 'Ваша подписка «{$a->plan}» закончится {$a->date}. Продлите сейчас, чтобы избежать перерыва.';

$string['subscription_activated_subject'] = 'Ваша подписка на {$a} активирована';
$string['subscription_activated_body']    = 'Отличные новости! Ваша отложенная подписка «{$a}» теперь активна.';

$string['subscription_expired_subject'] = 'Ваша подписка на {$a} закончилась';
$string['subscription_expired_body']    = 'Ваша подписка «{$a->plan}» завершилась {$a->date}. Оформите продление, чтобы вернуть доступ.';
$string['expired_button_renew']         = 'Продлить / Подписаться';
$string['task_expire_enrolments'] = 'Завершать подписки и обновлять зачисления';
$string['task_repair_paid_pr']        = 'Починка оплаченных PR: воссоздать отсутствующие подписки';

// Flags & statuses
$string['payment_failed'] = 'Платёж не прошёл';

$string['subscribe_now']  = 'Подписаться сейчас';

$string['upgrade_window_label']  = 'Окно расчёта: {$a}';
$string['upgrade_tariffs']       = 'Базовые цены: текущая = {$a->p1}, целевая = {$a->p2}';
$string['upgrade_consumed_since_t0'] = 'Прошедшее время с начала окна: {$a}';
$string['upgrade_equation_past']  = 'Прошедшая часть (текущая ставка): {$a->p1} × t/{$a->d1} = {$a->val}';
$string['upgrade_equation_future']= 'Будущая часть (целевая ставка): {$a->p2} × (D2−t)/{$a->d2} = {$a->val}';
$string['upgrade_spent_window']   = 'Уже оплачено в этом окне: {$a}';
$string['upgrade_base_cap']       = 'База = {$a->base}; Дегрессивный предел = {$a->cap}';
$string['upgrade_final_amount']   = 'Предлагаемая сумма: <strong>{$a}</strong>';
$string['upgrade_details_summary'] = 'Как рассчитана цена?';

$string['upgrade_confirmed_subject']  = 'Ваш апгрейд до «{$a}» подтверждён';
$string['upgrade_confirmed_body']     = 'Хорошие новости! Ваша подписка обновлена. Краткая сводка:';

$string['unknown_plan']             = 'Неизвестный тариф';

$string['manage_billing'] = 'Управление оплатой';
$string['provider_portal_not_supported'] = 'Портал биллинга недоступен';
$string['provider_portal_not_supported_desc'] = 'Провайдер «{$a}» пока не предлагает клиентский портал. Управляйте подпиской в профиле.';

$string['subfield_id']                 = 'ID';
$string['subfield_userid']             = 'ID пользователя';
$string['subfield_planid']             = 'ID тарифа';
$string['subfield_start']              = 'Начало';
$string['subfield_end']                = 'Окончание';
$string['subfield_amount']             = 'Оплаченная сумма';
$string['subfield_txn']                = 'Транзакция';
$string['subfield_provider']           = 'Провайдер';
$string['subfield_provider_sub']       = 'Подписка провайдера';
$string['subfield_provider_customer']  = 'Клиент провайдера';
$string['subfield_last_invoice']       = 'Последний счёт';
$string['subfield_last_failed_at']     = 'Последний сбой';
$string['subfield_fail_reason']        = 'Причина сбоя';
$string['subfield_created']            = 'Создано';
$string['subfield_updated']            = 'Обновлено';
$string['subfield_unlimited']          = 'Неограниченно';
$string['subfield_payment_status']  = 'Статус платежа';
$string['subpayment_action']        = 'Требуется действие';

// (optionnel) labels traduits pour tes statuts
$string['substatus_active']            = 'Активна'; // do not delete
$string['substatus_queued']            = 'В очереди'; // do not delete
$string['substatus_replaced']          = 'Заменена'; // do not delete
$string['substatus_expired']           = 'Истекла'; // do not delete
$string['substatus_canceled']          = 'Отменена'; // do not delete
$string['substatus_pending']           = 'В ожидании'; // do not delete
$string['substatus_error']             = 'Ошибка'; // do not delete
$string['substatus_suspended']         = 'Приостановлена'; // do not delete
$string['substatus_paid']              = 'Оплачено'; // do not delete
$string['substatus_failed']            = 'Сбой оплаты'; // do not delete
$string['substatus_completed']         = 'Завершено'; // do not delete
$string['substatus_unknown']           = 'Неизвестно'; // do not delete

$string['optional_error_msg'] = 'Необязательное сообщение об ошибке';

$string['summary_price_wait'] = 'Выберите вариант, чтобы увидеть итоговую цену.';
$string['existing_account_hint_html'] = 'Учётная запись с этим email уже существует. <a class="link-primary fw-semibold" href="{$a->url}">Войдите</a>.';

$string['email_footer_copyright'] = '© {$a->year} {$a->brand}. Все права защищены.';
$string['email_footer_unexpected'] = 'Если вы не ожидали это письмо, просто проигнорируйте его.';
$string['receipt_total']  = 'Итого оплачено';
$string['receipt_invoice']= 'Счёт';

$string['email_show_pr_ref'] = 'Показывать ссылку на PR в письмах';
$string['email_show_pr_ref_desc'] = 'Добавлять небольшую техническую ссылку (PR # и дата) внизу писем. По умолчанию выключено.';
$string['unknown_payment_event'] = 'Неизвестное событие оплаты: {$a}';

$string['stripe:missingamount'] = 'В запросе оплаты отсутствует сумма.';
$string['stripe:productname'] = 'Тариф {$a}';
$string['stripe:missingpriceidforsubscription'] = 'Отсутствует stripe_price_id для подписки.';
$string['stripe:missingpriceid'] = 'Отсутствует price_id.';
$string['stripe:sdkautoloadnotfound'] = 'Не найден autoload Stripe SDK по пути {$a}.';
$string['sessiondisplay'] = 'Сессия: {$a}';

// Headings
$string['emails_links_heading'] = 'Письма и ссылки';
$string['emails_links_heading_desc'] = 'Настройки последующих писем и ссылок для продолжения.';
$string['followups_heading'] = 'Последующие письма и истечение';
$string['followups_heading_desc'] = 'Задержки (в минутах) для истечения и отправки напоминаний.';

// Brand logo (general/email)
$string['brand_logo_url_label'] = 'URL логотипа бренда';
$string['brand_logo_url_desc'] = 'Абсолютный URL на небольшой логотип (PNG/SVG, высота ~32px) для писем.';

// Email link secret
$string['email_link_secret_label'] = 'Секрет для ссылок-продолжений';
$string['email_link_secret_desc'] = 'Строка для подписи ссылок (по умолчанию: $CFG->passwordsaltmain).';

// Expiration & reminders
$string['expire_pending_after_minutes_label'] = 'Срок для ожидания платежей';
$string['expire_pending_after_minutes_desc'] = 'Переводить «ожидание» → «истекло» через N минут без оплаты.';
$string['reminder1_after_minutes_label'] = 'Напоминание №1';
$string['reminder1_after_minutes_desc'] = 'Отправить первое напоминание, если статус ∈ (pending, expired, failed) и возраст ≥ N минут.';
$string['reminder2_after_minutes_label'] = 'Напоминание №2';
$string['reminder2_after_minutes_desc'] = 'Отправить второе напоминание, если всё ещё не оплачено и возраст ≥ N минут (с момента создания).';

// Featured plan
$string['featured_planid_label'] = 'Избранный тариф';
$string['featured_planid_desc'] = 'ID тарифа, который выделяется на странице предложений.';

$string['alfa_missing_api_base'] = 'Не задан базовый URL API Alfa.';
$string['alfa_rub_only'] = 'Alfa (token) настроен только для валюты RUB.';
$string['alfa_register_error'] = 'Не удалось инициализировать платёж: {$a}';
$string['alfa_missing_formurl'] = 'Платёж инициализирован, но банк не вернул ссылку для оплаты.';
$string['alfa_not_paid'] = 'Платёж не завершён';

$string['subfield_pr_id'] = 'Заявка на оплату №';
$string['subfield_pr_status'] = 'Статус PR';
$string['subfield_pr_provider'] = 'Провайдер PR';
$string['subfield_pr_amount'] = 'Сумма PR';
$string['subfield_pr_orderid'] = 'PR orderId';
$string['subfield_pr_txnid'] = 'PR transactionId';
$string['subfield_pr_paidat'] = 'Оплачено (время)';
$string['subfield_pr_link'] = 'Ссылка на оплату PR';
$string['subfield_pr_lasterror'] = 'Последняя ошибка PR';
$string['notavailable'] = 'Н/Д';

$string['err_cannot_determine_price'] = 'Невозможно определить цену для создания платежного запроса.';
$string['err_no_redirect_url'] = 'Инициализация оформления не вернула ссылку для перехода.';

$string['btn_signin'] = 'Войти';

$string['provider_alfa'] = 'AlfaBank';
$string['provider_stripe'] = 'Stripe';
$string['provider_manual'] = 'Ручной';
$string['provider_csv'] = 'CSV';
$string['provider_dev'] = 'Разработка';

$string['configmissing'] = 'Отсутствует настройка: {$a}.';
$string['missing_customer_id'] = 'Отсутствует ID клиента Stripe.';
$string['invalidcsvupload'] = 'Загруженный CSV-файл некорректен.';
$string['csvwritefail'] = 'Не удалось сохранить CSV-файл.';
$string['invalidpricecurrency'] = 'Неверная комбинация цена/валюта.';
$string['plan_not_found'] = 'Тариф не найден.';
$string['scopenotfound'] = 'Область доступа не найдена.';
$string['scopedeleteinuse'] = 'Невозможно удалить эту область, так как она используется.';
$string['plannotfound'] = 'Тариф не найден.';
$string['paymentgatewayerror'] = 'Ошибка платёжного шлюза: {$a}';

$string['retry_invalid_status'] = 'Этот платёжный запрос нельзя повторить в текущем состоянии.';
$string['retry_link_expired'] = 'Ссылка для повтора недействительна или истекла. Начните новое оформление.';

// Sections
$string['providers_header'] = 'Платёжные провайдеры';
$string['provider_default'] = 'Провайдер по умолчанию';
$string['provider_default_desc'] = 'Какого провайдера использовать, если правило маршрутизации не применяется.';

// Common env
$string['env_mode'] = 'Среда';
$string['env_mode_desc'] = 'Выберите, какие учётные данные использовать.';
$string['env_test'] = 'Тест';
$string['env_live'] = 'Прод';


// Stripe
$string['stripe_secret_test'] = 'Секретный ключ (TEST)';
$string['stripe_publishable_test'] = 'Публичный ключ (TEST)';
$string['stripe_webhook_secret_test'] = 'Секрет вебхука Stripe (TEST)';
$string['stripe_portal_configuration_id_test'] = 'ID конфигурации портала Stripe (TEST)';
$string['stripe_portal_configuration_id_desc'] = 'Необязательно: ID конфигурации Customer Portal (например, pc_xxx). Если пусто — используется конфигурация по умолчанию Stripe.';

$string['stripe_secret_live'] = 'Секретный ключ (LIVE)';
$string['stripe_publishable_live'] = 'Публичный ключ (LIVE)';
$string['stripe_webhook_secret_live'] = 'Секрет вебхука Stripe (LIVE)';
$string['stripe_portal_configuration_id_live'] = 'ID конфигурации портала Stripe (LIVE)';


// Alfa
$string['alfa_settings_header'] = 'Альфа-Банк';
$string['alfa_api_base_test'] = 'Базовый URL API (TEST)';
$string['alfa_username_test'] = 'Логин (TEST)';
$string['alfa_password_test'] = 'Пароль (TEST)';
$string['alfa_token_test'] = 'API token (TEST)';
$string['alfa_webhook_secret_test'] = 'Секрет вебхука Alfa (TEST)';
$string['alfa_api_base_live'] = 'Базовый URL API (LIVE)';
$string['alfa_username_live'] = 'Логин (LIVE)';
$string['alfa_password_live'] = 'Пароль (LIVE)';
$string['alfa_token_live'] = 'API token (LIVE)';
$string['alfa_webhook_secret_live'] = 'Секрет вебхука Alfa (LIVE)';

$string['policy_url_ru'] = 'URL политики конфиденциальности (Россия)';
$string['policy_url_row'] = 'URL политики конфиденциальности (остальной мир)';
$string['terms_url_ru'] = 'URL условий (Россия)';
$string['terms_url_row'] = 'URL условий (остальной мир)';
$string['privacy_policy'] = 'Политика конфиденциальности';
$string['terms_cgu'] = 'Условия и положения';
$string['i_accept_policy'] = 'Я согласен(на) с {$a}.';
$string['i_accept_terms']  = 'Я согласен(на) с {$a}.';

$string['availability_mode'] = 'Доступность плагина';
$string['availability_mode_desc'] = 'Временно ограничить все публичные страницы плагина Subscriptions.';
$string['availability_enabled'] = 'Включено (публично)';
$string['availability_adminonly'] = 'Только для администраторов';
$string['availability_disabled'] = 'Выключено';

$string['subs_unavailable'] = 'Подписки временно недоступны.';
$string['subs_unavailable_adminonly'] = 'Страницы подписок в данный момент доступны только администраторам.';

$string['label_inactive'] = '(неактивно)';

$string['edittranslation'] = 'Редактировать перевод'; //  do not delete
$string['newtranslation'] = 'Новый перевод'; //  do not delete

$string['task_subscription_rollover'] = 'Активировать отложенные подписки и завершать истёкшие';
$string['renew_now'] = 'Продлить сейчас';
$string['renew_soon_msg'] = 'Ваш доступ заканчивается через {$a} дн. Продлите сейчас, чтобы избежать перерыва.';
$string['queued_starts_in'] = 'Начнётся через {$a} дн.';
$string['none'] = 'Нет';
$string['mycourses_profile_heading'] = 'Мои курсы';

$string['plan_inactive'] = 'Этот тариф больше недоступен. Пожалуйста, выберите активный тариф.';
$string['plan_inactive_redirect'] = 'Этот тариф больше недоступен. Пожалуйста, выберите новый тариф.';
$string['plan_description_show'] = 'Показать описание';

$string['email_copy_to'] = 'Адрес для копии администратору';
$string['email_copy_to_desc'] = 'Один или несколько адресов (через запятую) будут получать копии писем, отправляемых плагином Subscriptions.';

$string['settings:sitedefault'] = 'Язык сайта по умолчанию';
$string['settings:defaultuserlang'] = 'Язык по умолчанию для новых аккаунтов';
$string['settings:defaultuserlang_desc'] = 'Если не указано, новые пользователи наследуют язык сайта по умолчанию. Выберите язык, чтобы принудительно установить его при создании.';
$string['settings:defaultemaillang'] = 'Язык писем, отправляемых плагином';
$string['settings:defaultemaillang_desc'] = 'Если не указано, письма будут отправляться на предпочитаемом языке получателя (или на языке сайта по умолчанию). Выберите язык, чтобы принудительно установить его.';

$string['recurring_canceled_effect_now'] = 'Отмена вступает в силу немедленно. Ваш доступ приостановлен.';
$string['recurring_canceled_effect_on']  = 'Отмена вступит в силу {$a}. До этого момента доступ сохраняется.';
$string['payment_failcode'] = 'Причина';
$string['payment_nextretry'] = 'Следующая попытка';
$string['email_retry_expires'] = 'Ссылка действительна до';

$string['contact_admin_subject'] = 'Новое сообщение из формы контакта';
$string['contact_copy_subject']  = 'Мы получили ваше сообщение';
$string['contact_copy_intro']    = 'Спасибо за ваше сообщение. Мы свяжемся с вами в ближайшее время.';
$string['contact_label_name']    = 'Имя';
$string['contact_label_email']   = 'E-mail';
$string['contact_label_msg']     = 'Сообщение';
$string['view_site']             = 'Открыть сайт';
$string['contact_label_ip'] = 'IP';
$string['contact_label_ua'] = 'User-Agent';
$string['reply_now']              = 'Ответить сейчас';
$string['contact_reply_greeting'] = 'Здравствуйте, {$a}! Мы получили ваше сообщение.';
$string['contact_reply_reminder'] = 'Напоминание вашего сообщения:';
$string['contact_reply_marker']   = '— Напишите ответ ниже —';
$string['contact_reply_subject']  = 'Re: ваше сообщение в CampusFR';
$string['reply_in_admin'] = 'Ответить из админки (редактор HTML)';
$string['reply_text']             = 'Ваш ответ';
$string['contact_reply_sent_hint']  = 'Ответ отправлен получателю. Эту страницу можно закрыть.';

$string['trial_checkout_banner'] = 'Вы вошли с пробной учётной записью. Укажите свои данные, чтобы оформить подписку.';

// Общие / защита create_session
$string['invalid_operation'] = 'Недопустимая операция оплаты.';
$string['invalid_payment_request_status'] = 'Этот платёжный запрос больше нельзя использовать.';
$string['invalid_payment_request_owner'] = 'У вас нет доступа к этому платёжному запросу.';
$string['invalid_currency_for_alfa'] = 'Неверная валюта для Alfa: поддерживается только RUB.';
$string['err_no_redirect_url'] = 'Платёжный провайдер не вернул URL для переадресации.';
$string['err_cannot_determine_price'] = 'Не удалось определить цену плана для выбранной валюты.';

// Шлюз Alfa
$string['alfa_missing_api_base'] = 'Неполная конфигурация Alfa: не задан базовый адрес API.';
$string['alfa_rub_only'] = 'Alfa: поддерживается только валюта RUB.';
$string['alfa_register_error'] = 'Ошибка Alfa при инициализации платежа: {$a}';
$string['alfa_missing_formurl'] = 'Некорректный ответ Alfa: отсутствует formUrl или orderId.';
$string['paymentgatewayerror'] = 'Ошибка платёжного шлюза: {$a}';

// Доп.усиление (необязательные проверки)
$string['alfa_price_mismatch'] = 'Обнаружено несоответствие цены для Alfa. Повторите попытку или обратитесь в поддержку. ({$a})';
$string['alfa_amount_mismatch'] = 'Обнаружено несоответствие суммы для Alfa. Повторите попытку или обратитесь в поддержку. ({$a})';

// Шлюз Stripe
$string['stripe:missingamount'] = 'Stripe: отсутствует сумма для создания сессии оплаты.';
$string['stripe:productname'] = '{$a} — оплата';
$string['stripe:missingpriceidforsubscription'] = 'Stripe: отсутствует price_id для режима подписки.';
$string['stripe:missingpriceid'] = 'Stripe: отсутствует price_id.';
$string['stripe:sdkautoloadnotfound'] = 'Не найден файл автозагрузки Stripe SDK: {$a}';
$string['missing_customer_id'] = 'Отсутствует идентификатор клиента.';

// Усиление Stripe (необязательно)
$string['stripe_invalid_currency'] = 'Stripe: неверная или неподдерживаемая валюта: {$a}.';
$string['stripe_nonpositive_amount'] = 'Stripe: сумма должна быть больше 0.';

// UI — общее
$string['payui_error_title'] = 'Не удалось завершить оплату';
$string['payui_error_subtitle'] = 'Произошла ошибка на стороне платёжного сервиса или на нашей стороне.';
$string['payui_error_generic'] = 'Попробуйте ещё раз. Если проблема повторится, свяжитесь с нами — мы поможем оформить заказ.';

$string['payui_cta_retry'] = 'Повторить попытку';
$string['payui_cta_back'] = 'Назад к тарифам';
$string['payui_cta_contact'] = 'Связаться с поддержкой';
$string['payui_support_hint'] = 'Нужна помощь? Напишите нам: {$a}';

$string['payui_order_ref'] = 'Номер заказа: {$a}';

// UI — причины (кратко и понятно)
$string['payui_reason_security'] = 'Сессия истекла. Обновите страницу и попробуйте снова.';
$string['payui_reason_link'] = 'Ссылка на платёжную страницу недоступна. Попробуйте ещё раз.';
$string['payui_reason_currency'] = 'Не удалось подтвердить валюту платежа. Попробуйте ещё раз.';
$string['payui_reason_amount'] = 'Не удалось подтвердить сумму. Попробуйте ещё раз.';
$string['payui_reason_gateway'] = 'Платёжный сервис вернул ошибку. Попробуйте ещё раз.';
$string['payui_reason_canceled'] = 'Оплата была отменена.';
$string['payui_reason_declined'] = 'Банк отклонил платёж.';
$string['payui_reason_expired'] = 'Сессия оплаты истекла. Начните заново.';
$string['payui_reason_owner'] = 'Эта платёжная ссылка не принадлежит вашему аккаунту.';
$string['payui_reason_status'] = 'Эта платёжная ссылка больше не действительна.';

// Успех и ожидание
$string['payui_success_title'] = 'Платёж подтверждён';
$string['payui_success_subtitle'] = 'Спасибо! Ваш платёж прошёл успешно.';
$string['payui_success_thanks'] = 'Добро пожаловать! Доступ активирован.';
$string['payui_success_check_email'] = 'Мы отправили вам письмо с деталями доступа и следующими шагами. Войдите в аккаунт, чтобы начать обучение.';
$string['payui_pending_title'] = 'Почти готово…';
$string['payui_pending_msg'] = 'Платёж подтверждается. Это может занять до минуты. Можно закрыть страницу — мы напишем вам, когда всё будет готово.';

// Кнопки и подписи
$string['payui_cta_my_subscriptions'] = 'Мои подписки';
$string['payui_cta_signin'] = 'Войти';
$string['payui_session_display'] = 'Сессия оплаты: {$a}';
$string['payui_label_price'] = 'Цена';
$string['payui_label_plan'] = 'Тариф';
$string['payui_cta_mycourses'] = 'Перейти к моим курсам';

$string['settings_support_email'] = 'E-mail поддержки';
$string['settings_support_email_desc'] = 'Используется на страницах оплаты (успех/ошибка) для ссылки на поддержку.';
$string['stripe_price_mismatch'] = 'Stripe: обнаружено несоответствие цены. Повторите попытку или обратитесь в поддержку. ({$a})';
