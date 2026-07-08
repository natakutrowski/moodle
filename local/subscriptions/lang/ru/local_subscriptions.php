<?php
$string['pluginname'] = 'Покупки';

// -- Subscription config
// Plans
$string['plan_1week'] = '1 неделя'; // do not delete
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

$string['your_subscriptions'] = 'Ваши покупки';
$string['no_active_subscriptions'] = 'У вас нет активных покупок.';

$string['pricepaid'] = 'Оплаченная сумма';

$string['courselist'] = 'Список курсов';

$string['close'] = 'Закрыть';

$string['subscribe'] = 'Купить';
$string['subscribe_to_campus'] = 'Купить на Campus<small><sup>FR</sup></small>';
$string['change_currency'] = 'Сменить валюту';

$string['payment_success_check_email'] = 'Проверьте вашу почту: там есть письмо для завершения входа и установки пароля.';
$string['payment_pending_msg'] = 'Ваш платёж обрабатывается. Обычно это занимает несколько секунд.';
$string['payment_success_title'] = 'Платёж успешен';
$string['payment_success_thanks'] = 'Спасибо! Ваш платёж успешно обработан.';
$string['payment_canceled_title'] = 'Платёж отменён';
$string['payment_canceled_msg']        = 'Ваш платёж был отменён. Попробуйте снова, чтобы получить доступ к курсу.';
$string['back_to_plans']               = 'Вернуться к доступным курсам';

$string['checkout_title'] = 'Оформление заказа';
$string['checkout_duration'] = 'Длительность:';
$string['checkout_go_to_payment'] = 'Перейти к оплате';

$string['welcome_subject'] = 'Ваш доступ к CampusFR активирован ✅';

$string['welcome_body_intro'] =
    '<p>Здравствуйте, {$a}!</p>' .
    '<p>Ваш доступ к Campus<small><sup>FR</sup></small> активирован.</p>' .
    '<p>Если вы уже пользовались Campus<small><sup>FR</sup></small> в пробный период, просто продолжайте с тем же аккаунтом — ваши выполненные упражнения и баллы-круассаны сохраняются. Если вы только что присоединились, этот аккаунт будет использоваться для всех входов.</p>' .
    '<p>Вот ваши данные для входа:</p>';

$string['welcome_username'] = 'E-mail:';
$string['welcome_plan_summary'] = 'Курс: {$a}';
$string['welcome_amount_summary'] = 'Сумма: {$a}';

$string['welcome_text_canal'] = 'Обязательно добавьтесь в канал Campus<small><sup>FR</sup></small>: там публикуются все важные новости и обновления, а также именно там можно задавать вопросы преподавателям.';
$string['welcome_button_canal'] = 'Канал Campus<small><sup>FR</sup></small>';
$string['welcome_text_group'] = 'Также вы можете добавиться в группу, где можно пообщаться друг с другом, спросить совета, поддержать других и просто чувствовать, что вы среди своих.';
$string['welcome_button_group'] = 'Группа Campus<small><sup>FR</sup></small>';
$string['welcome_footer'] = '<p>Это письмо отправлено автоматически.<br>
Если у вас появятся вопросы, напишите нам на <a href="mailto:{$a}">{$a}</a> — мы будем рады помочь.</p>

<p>Желаем вам радости от каждого маленького результата, интересных занятий и уверенного прогресса во французском ❤️</p>

<p>Ната и команда Campus<small><sup>FR</sup></small></p>';


$string['receipt_title'] = 'Покупка курса на CampusFR подтверждена ✅';
$string['receipt_plan'] = 'Курс: ';
$string['receipt_amount'] = 'Сумма: ';
$string['receipt_tx'] = 'ID транзакции: ';
$string['receipt_period'] = 'Период доступа: ';

$string['welcome_temp_password_label'] = 'Временный пароль:';
$string['welcome_security_hint'] = '<p>Пароль вы придумали при регистрации. Если вдруг вы его забудете — доступ всегда можно легко восстановить здесь:</p>' .
'👉 <a href="{$a->url}">Восстановить пароль</a></p>';
$string['welcome_mycourses'] =
    '<p>Перейти в ваш профиль на платформе можно по ссылке ниже:</p>' .
'👉 <a href="{$a->url}">Вход на кампус</a></p>' . 
'<p>Информация о вашей подписке:</p>';

$string['receipt_intro'] =
    '<p>Доступ к вашему курсу на Campus<small><sup>FR</sup></small> успешно активирован, и платёж подтверждён.</p>
<p>Вот основная информация о вашей покупке:</p>';

$string['receipt_button_open'] = 'Перейти в Campus<small><sup>FR</sup></small>';

$string['receipt_footer'] =
    '<p>До скорой встречи на Campus<small><sup>FR</sup></small> 🇫🇷🥐</p>
<p>Команда Campus<small><sup>FR</sup></small></p>';

// Emails – failure/abandoned/reminder
$string['email_failed_subject'] = 'Не удалось завершить оплату';
$string['email_failed_intro'] = 'К сожалению, ваша попытка оплаты не удалась.';
$string['email_failed_help'] = 'Вы можете повторить попытку через несколько секунд, используя кнопку ниже. Если проблема сохранится, попробуйте другую карту или свяжитесь с банком.';
$string['email_button_retry'] = 'Повторить оплату';

$string['email_abandoned_subject'] = 'Завершите покупку';
$string['email_abandoned_intro'] = 'Вы не завершили покупку. Продолжайте с того места, где остановились:';

$string['email_reminder_subject'] = 'Всё ещё интересно? Завершите покупку';
$string['email_reminder_intro']   = 'Вы можете завершить покупку в один клик:';

// Scheduled task
$string['task_followup'] = 'Подписки — последующие письма';

$string['payment_error_title'] = 'Ошибка оплаты';
$string['payment_error_intro'] = 'Возникла ошибка при подготовке платежа. Пожалуйста, попробуйте ещё раз позже.';
$string['email_reminder2_subject'] = 'Последнее напоминание: завершите покупку';
$string['email_reminder2_intro'] = 'Мягкое напоминание завершить покупку. Можно оформить в один клик:';

$string['mail_recurring_started_subject'] = 'Ваша периодическая подписка «{$a}» активна';
$string['mail_recurring_started_body'] = 'Спасибо! Ваша периодическая подписка «{$a->plan}» началась {$a->start}.';
$string['view_my_subscriptions'] = 'Мои покупки';

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
$string['have_account_login_to_see_options'] = 'У вас уже есть аккаунт? Войдите, чтобы продлить подписку.';

// Above the options
$string['advisor_help_upgrade']  = 'Вы можете продлить текущую подписку по цепочке или перейти на более длительный тариф. Цена апгрейда скорректирована с учётом прошедшего времени.';
$string['advisor_help_standard'] = 'Выберите, как активировать эту подписку.';
$string['advisor_help_guest']    = 'Войдите, чтобы увидеть варианты апгрейда. Иначе вы можете оформить новую подписку, указав свои данные.';

// Price summary
$string['summary_price_title'] = 'Итоговая цена';

$string['personal_info_title'] = 'Личная информация';
$string['personal_info_help']  = 'Эта информация необходима для создания аккаунта и активации доступа.';

$string['mail_hello']         = 'Здравствуйте, {$a},';
$string['mail_button_manage'] = 'Управлять покупками';

$string['subupdate_subject'] = 'Ваш доступ к «{$a}» активен';
$string['subupdate_body']    = 'Вот обновлённая информация о вашем доступе к «{$a}»:';
$string['renewal_subject']   = 'Продление подтверждено — {$a}';
$string['renewal_body']      = 'Ваш доступ к «{$a}» был продлён. Детали:';
$string['recurring_failed_subject'] = 'Неудачный платёж — {$a}';
$string['recurring_failed_body']    = 'Платёж по подписке «{$a}» не прошёл. Пожалуйста, обновите платёжные данные.';
$string['recurring_failed_button']  = 'Обновить способ оплаты';

$string['recurring_canceled_subject'] = 'Ваша подписка отменена — {$a}';
$string['recurring_canceled_body']    = 'Ваша подписка «{$a}» отменена. Доступ сохранится до конца текущего периода.';
$string['recurring_canceled_button']  = 'Оформить снова';

$string['details'] = 'Детали';
$string['subscription_details'] = 'Детали покупки';

$string['mysubs_title'] = 'Мои покупки';
$string['mysubs_empty'] = 'Вы пока не покупали курсы.';
$string['period'] = 'Период';

$string['btn_extend']    = 'Продлить';

$string['option_upgrade_now_replace'] = 'Апгрейд на выбранную длительность (заменить очередь)';

$string['task_send_expiry_reminders'] = 'Отправлять напоминания об окончании для непериодических подписок';
$string['expiry_reminder_subject'] = 'Ваш доступ заканчивается через {$a} дн.';
$string['expiry_reminder_body']    = 'Ваша подписка «{$a->plan}» закончится {$a->date}. Продлите сейчас, чтобы избежать перерыва.';

$string['subscription_activated_subject'] = 'Ваш доступ к {$a} теперь активен';
$string['subscription_activated_body']    = 'Хорошие новости! Ваш доступ к «{$a}» теперь активен.';

$string['subscription_expired_subject'] = 'Ваша подписка на {$a} закончилась';
$string['subscription_expired_body']    = 'Ваша подписка «{$a->plan}» завершилась {$a->date}. Оформите продление, чтобы вернуть доступ.';
$string['expired_button_renew']         = 'Продлить / Подписаться';
$string['task_expire_enrolments'] = 'Завершать подписки и обновлять зачисления';
$string['task_repair_paid_pr']        = 'Починка оплаченных PR: воссоздать отсутствующие подписки';

// Flags & statuses
$string['payment_failed'] = 'Платёж не прошёл';

$string['subscribe_now']  = 'Купить сейчас';

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
$string['email_footer_unexpected'] = 'Если это письмо адресовано не вам, просто проигнорируйте его.';
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
$string['provider_trial']  = 'Пробный';

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
$string['offer_url_ru'] = 'URL договор оферты (Россия)';
$string['offer_url_row'] = 'URL договор оферты (остальной мир)';
$string['privacy_policy'] = 'Политика конфиденциальности';
$string['terms_cgu'] = 'Условия и положения';
$string['terms_cgv'] = 'Договор оферты';
$string['i_accept_policy'] = 'Я согласен(на) с {$a}.';
$string['i_accept_terms']  = 'Я согласен(на) с {$a}.';
$string['i_accept_all_terms'] =
    'Я принимаю {$a->policy}, {$a->terms} и {$a->offer}.';

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
$string['payui_cta_my_subscriptions'] = 'Перейти к покупкам';
$string['payui_cta_signin'] = 'Войти';
$string['payui_session_display'] = 'Сессия оплаты: {$a}';
$string['payui_label_price'] = 'Цена';
$string['payui_label_plan'] = 'Тариф';
$string['payui_cta_mycourses'] = 'Перейти к моим курсам';

$string['settings_support_email'] = 'E-mail поддержки';
$string['settings_support_email_desc'] = 'Используется на страницах оплаты (успех/ошибка) для ссылки на поддержку.';
$string['stripe_price_mismatch'] = 'Stripe: обнаружено несоответствие цены. Повторите попытку или обратитесь в поддержку. ({$a})';

$string['settings_trial_section'] = 'Пробный период 7 дней';
$string['settings_trial_planid'] = 'План пробного периода (ID)';
$string['settings_trial_planid_desc'] = 'ID плана с флагом « is_trial ».';
$string['settings_trial_duration_days'] = 'Длительность пробного периода (дни)';
$string['settings_trial_duration_days_desc'] = 'Количество дней бесплатного доступа.';
$string['settings_trial_discount_percent'] = 'Скидка (%) в течение окна';
$string['settings_trial_discount_percent_desc'] = 'Для учёта и применения, не зависящего от платёжного провайдера.';
$string['settings_trial_discount_hours'] = 'Окно скидки (часы)';
$string['settings_trial_discount_hours_desc'] = 'Например, 72 часа после начала пробного периода.';
$string['missing_trial_plan'] = 'План пробного периода не настроен (trial_plan_id).';

$string['pricing_missing_price'] = 'Для этого плана и валюты ({$a}) не установлена цена.';
$string['cannot_purchase_trial_plan'] = 'Этот план является пробным и его нельзя купить.';
$string['settings_paylock_section'] = 'Фиксация цены (checkout)';
$string['settings_paylock_strict'] = 'Строгий режим при расхождении';
$string['settings_paylock_strict_desc'] = 'Если включено, создание подписки будет отклонено при несоответствии оплаченной суммы зафиксированной сумме.';
$string['settings_paylock_tolerance'] = 'Допустимое расхождение (в центах)';
$string['settings_paylock_tolerance_desc'] = 'Максимально допустимая разница между зафиксированной и оплаченной суммой (по умолчанию 2 цента).';

$string['pricing_missing_price'] = 'Для этого плана и валюты ({$a}) не указана цена.';
$string['cannot_purchase_trial_plan'] = 'Этот план является пробным и не может быть куплен.';
$string['payment_mismatch_too_large'] = 'Расхождение оплаты превышает допустимую толерантность.';
$string['paylock_missing_lockdata'] = 'Для этого запроса отсутствуют данные фиксированной цены (locked_*).';
$string['paylock_invalid_minor'] = 'Недопустимая зафиксированная сумма.';
$string['stripe_lock_requires_payment_mode'] = 'Для фиксации цены требуется режим Checkout « payment » (фиксированная сумма). Используйте разовый платёж или включите купоны.';
$string['alfa_nonpositive_amount'] = 'Неположительная сумма для Alfa после фиксации цены.';
$string['alfa:productname'] = 'Подписка';
$string['paylock_missing_context'] = 'Невозможно вычислить цену: не указан пользователь или план.';

$string['currency_selector_label'] = 'Валюта';
$string['currency_eur'] = '€ EUR';
$string['currency_rub'] = '₽ RUB';
$string['set_display_currency_symbols'] = 'Показывать символ валюты';
$string['set_display_currency_symbols_desc'] = 'Если включено, цены показываются с символом (например, 49 €). Иначе используется код (49 EUR).';
$string['badge_limited_offer'] = 'Скидка -{$a}%';
$string['price_unavailable_in'] = 'Недоступно в {$a->curr} — показана {$a->fallback}.';
$string['checkout_discount_note']        = 'Ваша акция ещё действует';
$string['checkout_discount_note_prefix'] = '🎁 −{$a}% на все покупки курсов. Скидка доступна только';
$string['days_short']                    = 'д';

$string['cancel_price_title']  = 'Ожидаемая цена';
$string['error_price_title']   = 'Ожидаемая цена';
$string['success_price_title'] = 'Оплаченная сумма';
$string['reason_trial72h']     = 'Скидка −{$a}% применена в период пробного доступа.';
$string['task_sub_expiry_reminders'] = 'Напоминания об окончании подписки';
$string['expiry_reminder_subject_today'] = 'Подписка истекает сегодня';

$string['email_copy_verbose'] = 'Техническое приложение в копиях (log@...)';
$string['email_copy_verbose_desc'] = 'Если включено, к каждой копии добавляется техническая сводка (PR/User/Plan/Sub, locked_*, и т.д.).';

$string['existing_account_login_first'] = 'Учётная запись с этим e-mail уже существует. Пожалуйста, войдите, чтобы продолжить и привязать покупку к своему аккаунту.';
$string['task_enrol_scope_fill'] = 'Подписки — дополнять зачисления по access scope тарифа';

$string['paymentsuccess_redirect_msg'] = 'Через {$a} секунд вы будете перенаправлены на страницу «Mes cours».';
$string['paymentsuccess_mascot_alt']   = 'Жираф Гюстав поздравляет вас с успешной оплатой.';
$string['paymentcancel_mascot_alt'] = 'Иллюстрация отменённого платежа.';
$string['paymenterror_mascot_alt'] = 'Иллюстрация ошибки при оплате.';
$string['plan_price_per_month'] = '(примерно {$a} в месяц)';

$string['upgrade_window_label']    = 'Период расчёта: {$a->start} → {$a->end}';
$string['upgrade_ref_prices']      = 'Базовые тарифы: текущий = {$a->current}, целевой = {$a->target}';
$string['upgrade_part_past']       = 'Часть уже использована по текущему тарифу: {$a}';
$string['upgrade_part_future']     = 'Оставшаяся часть по тарифу целевого плана: {$a}';
$string['upgrade_base_total']      = 'Теоретическая сумма за весь период: {$a}';
$string['upgrade_already_paid']    = 'Уже оплачено за этот период: {$a}';
$string['upgrade_base_minus_paid'] = 'Сумма доплаты до скидки: {$a->base} − {$a->paid} = {$a->diff}';
$string['upgrade_discount_line']   = 'Скидка −{$a->pct}% на {$a->before} ⇒ {$a->after}';
$string['upgrade_amount_proposed'] = 'Итого к доплате: {$a}';

$string['trial_subscribe_now'] = 'Купить подписку';
$string['plan_label'] = 'Тариф';
$string['checkout_go_to_payment_discount'] = 'Купить со скидкой';
$string['checkout_full_access_line'] = 'Безлимитный доступ ко всем урокам курса.';
$string['summary_price_title_single'] = 'Подписка на {$a}';

$string['digital_pdf_badge'] = 'PDF Campus<small><sup>FR</sup></small>';
$string['digital_pdf_intro'] = 'Практический PDF-гайд, который поможет понять, запомнить и повторить глаголы 3-й группы без бесконечных таблиц.';
$string['digital_pdf_item_1'] = 'Основные семьи глаголов 3-й группы.';
$string['digital_pdf_item_2'] = 'Самые полезные модели спряжения.';
$string['digital_pdf_item_3'] = 'Понятные объяснения, чтобы видеть закономерности.';
$string['digital_pdf_item_4'] = 'PDF-файл, который можно сохранить и повторять в своём темпе.';
$string['digital_pdf_price_eur'] = 'Цена в EUR';
$string['digital_pdf_price_rub'] = 'Цена в RUB';
$string['digital_pdf_buy_title'] = 'Купить карточки';
$string['digital_pdf_firstname'] = 'Имя';
$string['digital_pdf_lastname'] = 'Фамилия';
$string['digital_pdf_email'] = 'Email';
$string['digital_pdf_email_help'] = 'Ссылка для скачивания будет отправлена на этот адрес.';
$string['digital_pdf_buy_eur'] = 'Купить в EUR: {$a->price} €';
$string['digital_pdf_buy_rub'] = 'Купить в RUB: {$a->price} ₽';
$string['digital_payment_created'] = 'Запрос на оплату PDF создан. Подключение к платёжной системе будет добавлено на следующем этапе.';
$string['digital_success_title'] = 'Покупка на CampusFR';
$string['digital_success_preview'] = 'Тестовый режим: запрос на оплату создан, но оплата Stripe/Alfa пока не подключена.';
$string['digital_success_request_created'] = 'Запрос создан';
$string['digital_success_product'] = 'Продукт';
$string['digital_success_email'] = 'Email';
$string['digital_success_amount'] = 'Сумма';
$string['digital_success_provider'] = 'Платёжная система';
$string['digital_success_status'] = 'Статус';
$string['digital_cancel_title'] = 'Оплата отменена';
$string['digital_cancel_message'] = 'Оплата не была завершена. Вы можете попробовать ещё раз.';
$string['digital_cancel_retry'] = 'Попробовать оплатить ещё раз';
$string['digital_success_download'] = 'Скачать карточки';
$string['digital_success_payment_pending'] = 'Ваш платёж проверяется. Если вы только что оплатили, обновите страницу через несколько секунд.';
$string['digital_download_not_paid'] = 'Скачивание недоступно, так как оплата ещё не подтверждена.';
$string['digital_download_expired'] = 'Срок действия ссылки для скачивания истёк.';
$string['digital_download_file_missing'] = 'PDF-файл не найден.';
$string['digital_success_payment_confirmed'] = 'Ваш платёж подтверждён. Теперь вы можете скачать PDF.';


$string['digital_mail_access_subject'] = 'Ваши карточки по глаголам 3 группы уже ждут вас 📘';
$string['digital_mail_access_intro'] = 'Спасибо за покупку ❤️ Ваш PDF-файл с карточками глаголов третьей группы уже доступен к скачиванию.';
$string['digital_mail_access_hint'] = 'Вы можете скачать PDF-файл с карточками по кнопке ниже. <br>Рекомендуем сохранить файл на устройстве, чтобы в любой момент легко к нему вернуться.';
$string['digital_mail_download_button'] = 'Скачать карточки';

$string['digital_mail_receipt_subject'] = 'Квитанция о покупке CampusFR';
$string['digital_mail_receipt_intro'] = 'Это письмо подтверждает вашу покупку на Campus<small><sup>FR</sup></small>. Ниже вы найдёте информацию о заказе.';

$string['digital_mail_product'] = 'Продукт';
$string['digital_mail_amount'] = 'Сумма';
$string['digital_mail_payment_date'] = 'Дата оплаты';

$string['digital_success_paid_heading'] = 'Спасибо за покупку!';
$string['digital_success_paid_intro'] = 'Ваш платёж подтверждён. PDF-файл готов к скачиванию.';
$string['digital_success_pending_heading'] = 'Платёж проверяется';
$string['digital_success_summary_title'] = 'Информация о покупке';
$string['digital_success_email_sent_hint'] = 'Мы также отправили ссылку для скачивания и квитанцию на ваш email.';
$string['digital_success_pending_hint'] = 'Если вы только что оплатили, обновите страницу через несколько секунд.';

$string['digital_sales_hero_intro'] = 'Практичный и понятный гайд, который поможет разобраться с глаголами 3-й группы, увидеть закономерности и перестать учить их “наугад”.';

$string['digital_sales_lifetime_access'] = '✔ Неограниченный доступ к карточкам после покупки';

$string['digital_sales_content_title'] = 'Эти глаголы все равно придется выучить 🤗<br>
Так пусть это будет наконец удобно и понятно!';
$string['digital_sales_content_item_1'] = 'Основные семьи глаголов 3-й группы.';
$string['digital_sales_content_item_2'] = 'Самые полезные модели спряжения.';
$string['digital_sales_content_item_3'] = 'Простые и наглядные объяснения.';
$string['digital_sales_content_item_4'] = 'Таблицы, удобные для быстрого повторения.';
$string['digital_sales_content_item_5'] = 'Логичные группы для лучшего запоминания.';
$string['digital_sales_content_item_6'] = 'Практичный материал, который удобно держать под рукой во время повторения.';

$string['digital_sales_forwho_title'] = 'Эти карточки идеально подойдут, если…';
$string['digital_sales_forwho_item_1'] = 'вы часто путаете глаголы 3-й группы.';
$string['digital_sales_forwho_item_2'] = 'вы хотите наконец увидеть закономерности.';
$string['digital_sales_forwho_item_3'] = 'вы изучаете французский самостоятельно.';
$string['digital_sales_forwho_item_4'] = 'вы готовитесь к экзамену или сертификату.';
$string['digital_sales_forwho_item_5'] = 'вы хотите иметь практичный материал под рукой.';

$string['digital_sales_secure_payment'] = '🔒 Безопасная оплата через Stripe или Alfa.';
$string['digital_sales_instant_access'] = '⚡ Мгновенный доступ после оплаты + ссылка для скачивания на email.';
$string['digital_cover_zoom_hint'] = 'Нажмите на изображение, чтобы увеличить его.';

$string['digital_purchases_title'] = 'Покупки цифровых продуктов';
$string['digital_purchases_export_xlsx'] = 'Экспорт в XLSX';
$string['digital_purchases_count'] = 'Найдено покупок: {$a}.';
$string['digital_purchases_payment_date'] = 'Дата оплаты';
$string['digital_purchases_emails'] = 'Письма';
$string['digital_purchases_access_email_short'] = 'PDF';
$string['digital_purchases_receipt_email_short'] = 'Квитанция';

$string['digital_download_mobile_missing'] = 'Мобильная версия этого файла недоступна.';
$string['digital_success_download_main'] = 'Скачать классическую версию';
$string['digital_success_download_mobile'] = 'Скачать мобильную версию';
$string['digital_mail_download_main_button'] = 'Скачать классическую версию';
$string['digital_mail_download_mobile_button'] = 'Скачать мобильную версию';

$string['task_reconcile_digital_payments'] = 'Сверка ожидающих платежей за цифровые продукты';
$string['digital_purchases_emails_status'] = 'PDF / Квитанция';
$string['digital_purchases_payment_or_creation_date'] = 'Дата оплаты / создания';
$string['digital_purchases_db_status'] = 'Статус БД';
$string['digital_purchases_provider_status'] = 'Статус провайдера';
$string['digital_purchases_provider_reason'] = 'Причина / детали провайдера';

$string['digital_sales_stats_button'] = 'Статистика продаж';

$string['digital_sales_stats_days'] = '{$a} день';
$string['digital_sales_stats_days_plural'] = '{$a} дней';

$string['digital_purchases_show_paid'] = 'Показать PAID';
$string['digital_purchases_show_pending'] = 'Показать pending';
$string['digital_purchases_show_pending_paid_provider'] = 'Pending / PAID provider';
$string['digital_purchases_show_all'] = 'Показать всё';
$string['digital_purchases_reconcile_pending'] = 'Сверить оплаченные pending';
$string['digital_purchases_hide_provider_status'] = 'Скрыть live-статусы провайдера';
$string['digital_purchases_check_provider_status'] = 'Проверить live-статусы провайдера';
$string['digital_purchases_provider_status_info'] = 'Статусы провайдера проверяются только для чтения. Изменения в базе и отправка писем не выполняются.';
$string['digital_purchases_reconcile_confirm'] = 'Подтвердить сверку pending-платежей, оплаченных у провайдера?';

$string['digital_download_classic'] = 'Классическая';
$string['digital_download_mobile'] = 'Мобильная';

$string['digital_sales_stats_title'] = 'Статистика цифровых продаж';
$string['digital_sales_stats_back_to_purchases'] = 'Назад к покупкам';
$string['digital_sales_stats_sales_found'] = 'Найдено оплаченных продаж: {$a}';
$string['digital_sales_stats_no_sales'] = 'За этот период оплаченных продаж нет.';
$string['digital_sales_stats_histogram'] = 'Количество продаж по времени';
$string['digital_sales_stats_cumulative'] = 'Накопительные продажи';
$string['digital_sales_stats_show_from'] = 'Показать начиная с';

$string['digital_catalog_title'] = 'Цифровой магазин Campus<small><sup>FR</sup></small>';
$string['digital_catalog_intro'] = 'Здесь вы найдёте наши PDF, гайды и практические материалы для изучения французского языка в удобном для вас темпе.';
$string['digital_catalog_empty'] = 'Сейчас цифровые продукты недоступны.';
$string['digital_catalog_view_product'] = 'Открыть продукт';

$string['digital_pdf_badge'] = 'Цифровой PDF';

$string['digital_product_not_found_redirect'] = 'Этот продукт недоступен. Мы перенаправили вас в магазин.';
$string['digital_product_not_found_catalog_notice'] = 'Запрошенный продукт недоступен или больше не существует. Ниже вы можете посмотреть доступные продукты.';

$string['digital_rub_confirm_title'] = 'Оплата в рублях';
$string['digital_rub_confirm_vpn'] = '💡 Перед оплатой лучше временно отключить VPN — иногда из-за него платежная страница может открываться некорректно или выдавать ошибку 😊';
$string['digital_rub_confirm_continue'] = 'Перейти к оплате';

$string['digital_products_admin_title'] = 'Цифровые продукты';
$string['digital_products_add'] = 'Добавить цифровой продукт';
$string['digital_products_view_purchases'] = 'Посмотреть покупки';
$string['digital_products_view_catalog'] = 'Открыть магазин';
$string['digital_products_count'] = 'Цифровых продуктов: {$a}';
$string['digital_products_cover'] = 'Обложка';
$string['digital_products_slug'] = 'Slug';
$string['digital_products_titles'] = 'Названия';
$string['digital_products_prices'] = 'Цены';
$string['digital_products_files'] = 'Файлы';
$string['digital_products_status'] = 'Статус';
$string['digital_products_purchases'] = 'Покупки';
$string['digital_products_sortorder'] = 'Порядок';
$string['digital_products_actions'] = 'Действия';
$string['digital_products_cover_missing'] = 'Изображение отсутствует';
$string['digital_products_file_main'] = 'Классическая';
$string['digital_products_file_mobile'] = 'Мобильная';
$string['digital_products_enabled'] = 'Активен';
$string['digital_products_disabled'] = 'Неактивен';
$string['digital_products_open_public'] = 'Открыть';
$string['digital_products_delete_confirm'] = 'Удалить этот цифровой продукт?';
$string['digital_product_edit_new_title'] = 'Новый цифровой продукт';
$string['digital_product_edit_edit_title'] = 'Редактирование цифрового продукта';

$string['digital_product_edit_main_info'] = 'Основная информация';
$string['digital_product_edit_internal_name'] = 'Внутреннее название';

$string['digital_product_edit_price_eur'] = 'Цена EUR';
$string['digital_product_edit_price_rub'] = 'Цена RUB';

$string['digital_product_edit_files_hint'] = 'Файлы нужно размещать вручную в соответствующих папках: PDF — в moodledata/local_subscriptions/private_pdfs, обложки — в local/subscriptions/pix/cover.';

$string['digital_product_edit_translations'] = 'Переводы';

$string['digital_product_edit_title'] = 'Название';

$string['digital_product_edit_saved'] = 'Цифровой продукт сохранён.';
$string['digital_product_edit_slug_exists'] = 'Этот slug уже существует.';
$string['digital_product_edit_current_file'] = 'Текущий файл';
$string['digital_product_edit_no_file'] = 'Файл пока не выбран.';
$string['digital_product_edit_click_to_upload'] = 'Нажмите здесь, чтобы выбрать или заменить файл.';$string['digital_product_edit_access_note'] = 'Текст о доступе после покупки';
$string['digital_product_edit_content_title'] = 'Заголовок блока с содержанием';
$string['digital_product_edit_forwho_title'] = 'Заголовок блока “кому подойдёт”';
$string['digital_product_edit_buy_title'] = 'Заголовок блока покупки';
$string['digital_product_edit_access_note'] = 'Текст о доступе после покупки';
$string['digital_product_edit_content_title'] = 'Заголовок блока с содержанием';
$string['digital_product_edit_forwho_title'] = 'Заголовок блока “кому подойдёт”';
$string['digital_product_edit_buy_title'] = 'Заголовок блока покупки';
$string['digital_products_status_updated'] = 'Статус продукта обновлён.';
$string['digital_products_enable'] = 'Активировать';
$string['digital_products_disable'] = 'Отключить';
$string['digital_products_duplicate'] = 'Дублировать';
$string['digital_products_duplicated'] = 'Продукт дублирован. Теперь можно изменить копию.';
$string['digital_products_deleted'] = 'Цифровой продукт удалён.';
$string['digital_products_delete_has_purchases'] = 'Этот продукт нельзя удалить, потому что по нему уже есть покупки.';

$string['digital_reassurance_instant'] = 'Мгновенный доступ после оплаты';
$string['digital_reassurance_versions'] = 'Включены классическая и мобильная версии';
$string['digital_reassurance_email'] = 'Ссылки автоматически отправляются на email';
$string['digital_reassurance_nocampus'] = 'Аккаунт Campus<small><sup>FR</sup></small> не требуется';
$string['digital_reassurance_secure'] = 'Безопасная оплата через Stripe / Alfa Bank';

$string['digital_redirecting_payment'] = 'Переход на страницу оплаты…';

$string['digital_redirecting_payment_desc'] =
'Пожалуйста, подождите несколько секунд. Не закрывайте эту страницу.';

$string['digital_success_thank_you'] = 'Спасибо за покупку!';
$string['digital_success_confirmed_intro'] = 'Ваш платёж подтверждён. Файлы готовы к скачиванию.';
$string['digital_success_pending_title'] = 'Платёж проверяется';
$string['digital_success_payment_pending_support'] = 'Ваш платёж сейчас проверяется. Если вы только что оплатили, обновите страницу через несколько секунд. Если ничего не изменится, напишите нам на support@campusfr.fr.';
$string['digital_success_summary_title'] = 'Информация о покупке';
$string['digital_success_main_version_hint'] = 'Классическая версия: удобно для компьютера, планшета или печати.';
$string['digital_success_mobile_version_hint'] = 'Мобильная версия: оптимизирована для просмотра PDF на телефоне.';
$string['digital_success_email_sent_notice'] = 'Мы также отправили ссылки для скачивания и квитанцию на email.';
$string['digital_success_support_title'] = 'Проблема со скачиванием?';
$string['digital_success_support_text'] = 'Напишите нам на {$a}. Мы постараемся быстро помочь.';
$string['digital_success_back_to_shop'] = 'Посмотреть другие продукты';

$string['digital_cancel_heading'] = 'Платёж не завершён';
$string['digital_cancel_intro'] = 'Ваша покупка не была подтверждена. Если вы закрыли страницу оплаты до подтверждения, доступ пока не активирован.';
$string['digital_cancel_vpn_hint'] = '💡 Если вы оплачиваете в рублях, временно отключите VPN: платёжная страница иногда может открываться некорректно или показывать ошибку.';
$string['digital_cancel_support_text'] = 'Если вам кажется, что деньги списались, или вам нужна помощь, напишите нам на {$a}.';
$string['digital_cancel_gateway_timeout'] =
'Не удалось открыть страницу оплаты. Возможно, это временная проблема. Попробуйте ещё раз через несколько минут.';

$string['planentitlements'] = 'Права доступа тарифов';
$string['planentitlementsfor'] = 'Права доступа для тарифа: {$a}';
$string['addentitlement'] = 'Добавить право доступа';
$string['editentitlement'] = 'Изменить право доступа';
$string['deleteentitlement'] = 'Удалить право доступа';
$string['saveentitlement'] = 'Сохранить право доступа';
$string['noentitlements'] = 'Для этого тарифа пока не настроены права доступа.';
$string['entitlementcreated'] = 'Право доступа успешно создано.';
$string['entitlementupdated'] = 'Право доступа успешно обновлено.';
$string['entitlementdeleted'] = 'Право доступа успешно удалено.';
$string['confirmdeleteentitlement'] = 'Вы действительно хотите удалить это право доступа?';

$string['entitlement_course'] = 'Курс';
$string['entitlement_accesslevel'] = 'Уровень доступа';
$string['entitlement_role'] = 'Роль в курсе';
$string['entitlement_groupname'] = 'Название группы';
$string['entitlement_groupname_help'] = 'Необязательно. Если поле заполнено, пользователь будет добавлен в эту группу Moodle в выбранном курсе.';
$string['entitlement_priority'] = 'Приоритет';
$string['entitlement_priority_help'] = 'Доступ с более высоким приоритетом может заменить доступ с более низким приоритетом. Рекомендуемые значения: пробный доступ = 10, грамматика = 50, полный доступ = 100.';
$string['entitlement_already_exists'] = 'У этого тарифа уже есть такой уровень доступа для этого курса.';

$string['accesslevel_trial'] = 'Пробный доступ';
$string['accesslevel_grammar'] = 'Только грамматика';
$string['accesslevel_full'] = 'Полный доступ';
$string['invalidplanid'] = 'Тариф не найден или не указан.';

$string['planupgrades'] = 'Апгрейды тарифов';
$string['planupgradesintro'] = 'Здесь можно указать, с какого тарифа на какой можно перейти. Например: A2 Грамматика → A2 Полный доступ. При расчёте по разнице цена апгрейда считается так: цена целевого тарифа минус цена текущего тарифа, в той же валюте.';
$string['addupgrade'] = 'Добавить апгрейд';
$string['editupgrade'] = 'Изменить апгрейд';
$string['deleteupgrade'] = 'Удалить апгрейд';
$string['saveupgrade'] = 'Сохранить апгрейд';
$string['noupgrades'] = 'Правила апгрейда тарифов пока не настроены.';
$string['upgradecreated'] = 'Правило апгрейда успешно создано.';
$string['upgradeupdated'] = 'Правило апгрейда успешно обновлено.';
$string['upgradedeleted'] = 'Правило апгрейда успешно удалено.';
$string['confirmdeleteupgrade'] = 'Вы действительно хотите удалить это правило апгрейда?';

$string['upgrade_fromplan'] = 'Исходный тариф';
$string['upgrade_toplan'] = 'Целевой тариф';
$string['upgrade_pricingmode'] = 'Способ расчёта цены';
$string['upgrade_pricingmode_help'] = 'Пока поддерживается только расчёт по разнице: цена апгрейда = цена целевого тарифа - цена текущего тарифа, в той же валюте.';
$string['upgrade_pricing_difference'] = 'Разница между ценами двух тарифов';
$string['upgrade_isactive'] = 'Активен';
$string['upgrade_same_plan_error'] = 'Исходный и целевой тарифы должны быть разными.';
$string['upgrade_already_exists'] = 'Такое правило апгрейда уже существует.';
$string['active'] = 'Активен';
$string['inactive'] = 'Неактивен';
$string['status'] = 'Статус';

$string['planentitlements'] = 'Права доступа тарифов';
$string['planupgrades'] = 'Апгрейды тарифов';
$string['option_upgrade_difference'] = 'Перейти на полную версию';
$string['plan_already_owned'] = 'У вас уже есть доступ к этому тарифу.';
$string['upgrade_from_to_summary'] = 'Вы переходите с тарифа « {$a->from} » на тариф « {$a->to} ». Вы оплачиваете только разницу.';
$string['upgrade_badge'] = 'Апгрейд';
$string['upgrade_discount_applied'] = 'Ваша скидка после пробного доступа применена: {$a->discount} %.';
$string['upgrade_cta'] = 'Перейти на полную версию';

$string['unlock_grammar_title'] = 'Задание входит в модуль грамматики';
$string['unlock_grammar_text'] = 'Это задание входит в модуль грамматики. Вы можете купить только грамматику или полный курс.';
$string['unlock_grammar_button'] = 'Купить грамматику';

$string['unlock_full_title'] = 'Задание доступно только в полной версии';
$string['unlock_full_text'] = 'Это задание не входит в модуль грамматики. Перейдите на полную версию, чтобы получить доступ ко всему курсу.';
$string['unlock_full_button'] = 'Перейти на полную версию';

$string['restricted_access_title'] = 'Ограниченный доступ';
$string['restricted_access_text'] = 'Купите курс, чтобы открыть это задание.';
$string['buy'] = 'Купить';

$string['plan_already_covered'] = 'У вас уже есть такой же или более высокий доступ к этому содержимому.';
$string['all_courses_owned_title'] = 'У вас уже есть доступ ко всем доступным курсам';
$string['all_courses_owned_text'] = 'Сейчас ничего покупать не нужно. Вы можете продолжить обучение в своём разделе курсов.';

$string['unlock_subscriber_title'] = 'Задание доступно только подписчикам';
$string['unlock_subscriber_text'] = 'Это задание недоступно в пробном доступе. Выберите тариф, чтобы продолжить.';
$string['unlock_subscriber_button'] = 'Посмотреть тарифы';

$string['digital_purchases_profile_title'] = 'Ваши цифровые покупки';
$string['digital_purchase_date'] = 'Дата покупки';
$string['digital_purchases_filter_registered'] = 'Зарегистрированные покупатели';
$string['digital_purchases_filter_guests'] = 'Незарегистрированные покупатели';
$string['digital_purchases_campus_account'] = 'Аккаунт Campus';

$string['course_purchases_profile_title'] = 'Ваши покупки курсов';
$string['purchase_date'] = 'Дата покупки';
$string['available_courses'] = 'Доступные курсы';
$string['digital_product_view_page'] = 'Посмотреть страницу продукта';

$string['digital_purchases_empty'] = 'Вы пока не покупали цифровые продукты.';
$string['digital_purchase_details'] = 'Детали цифровой покупки';
$string['digital_purchase_downloads'] = 'Загрузки';
$string['digital_product'] = 'Цифровой продукт';
$string['user_purchases_title'] = 'Покупки пользователя {$a}';
$string['admin_details'] = 'Информация для администратора';
$string['subfield_id'] = 'ID';
$string['subfield_userid'] = 'ID пользователя';
$string['subfield_productid'] = 'ID продукта';
$string['subfield_slug'] = 'Slug';
$string['subfield_created_at'] = 'Создано';
$string['subfield_paid_at'] = 'Оплачено';
$string['subfield_expires_at'] = 'Истекает';
$string['subfield_paymentid'] = 'ID платежа';
$string['subfield_provider_paymentid'] = 'ID платежа у провайдера';
$string['subfield_checkout_url'] = 'URL оплаты';
$string['subfield_success_url'] = 'URL успешной оплаты';
$string['subfield_cancel_url'] = 'URL отмены';
$string['subfield_download_token'] = 'Токен скачивания';
$string['subfield_raw_response'] = 'Сырой ответ провайдера';
$string['admin_subscription_details'] = 'Информация об абонементе для администратора';
$string['admin_payment_request_details'] = 'Информация payment request';
$string['payment_request_not_found'] = 'Связанная payment request не найдена';

$string['subfield_planid'] = 'ID плана';
$string['subfield_status'] = 'Статус';
$string['subfield_payment_request_id'] = 'ID payment request';
$string['subfield_provider_subscription_id'] = 'ID подписки у провайдера';
$string['subfield_provider_customer_id'] = 'ID клиента у провайдера';
$string['subfield_renewal_date'] = 'Дата продления';
$string['subfield_updated_at'] = 'Обновлено';
$string['subfield_operation'] = 'Операция';
$string['subfield_sessionid'] = 'ID сессии';
$string['subfield_price'] = 'Цена';
$string['subfield_amount_minor'] = 'Сумма в минорных единицах';
$string['subfield_locked_list_price'] = 'Зафиксированная цена по каталогу';
$string['subfield_locked_discount_percent'] = 'Зафиксированная скидка (%)';
$string['subfield_locked_discount_amount'] = 'Зафиксированная сумма скидки';
$string['subfield_locked_discount_reason'] = 'Причина зафиксированной скидки';
$string['subfield_locked_final_price'] = 'Зафиксированная итоговая цена';
$string['subfield_locked_at'] = 'Цена зафиксирована';
$string['subfield_attempts'] = 'Попытки';
$string['subfield_last_attempt'] = 'Последняя попытка';
$string['subfield_last_error'] = 'Последняя ошибка';
$string['subfield_created_ip'] = 'IP при создании';
$string['subfield_accept_language'] = 'Accept-Language';
$string['subfield_http_referer'] = 'HTTP referer';
$string['subfield_payment_link'] = 'Ссылка на оплату';
$string['subfield_response_json'] = 'JSON-ответ провайдера';
$string['subfield_created_useragent'] = 'User-Agent при создании';

$string['manage_user_subscriptions'] = 'Управление подписками пользователей';
$string['all_plans'] = 'Все планы';
$string['filter_by_plan'] = 'Фильтр по плану';
$string['perpage'] = 'Результатов на странице';
$string['filter'] = 'Фильтровать';
$string['actions'] = 'Действия';
$string['no_subscriptions_found'] = 'Подписки не найдены.';
$string['confirm_delete_subscription'] = 'Подтвердить удаление';
$string['confirm_delete_subscription_body'] = 'Вы действительно хотите удалить эту подписку? Доступ к курсам, связанным с этим планом, тоже будет удалён.';
$string['subscription_deleted_successfully'] = 'Подписка успешно удалена.';
$string['close'] = 'Закрыть';
$string['delete'] = 'Удалить';

$string['edit_user_subscription'] = 'Редактировать подписку пользователя';
$string['subscription_summary'] = 'Сводка по подписке';
$string['no_end_date'] = 'Подписка без даты окончания';
$string['end_date_before_start_date'] = 'Дата окончания не может быть раньше даты начала.';
$string['subscription_updated_successfully'] = 'Подписка успешно обновлена.';
$string['invalid_subscription_status'] = 'Недопустимый статус подписки.';

$string['status_active'] = 'Активна';
$string['status_queued'] = 'В очереди';
$string['status_inactive'] = 'Неактивна';
$string['status_expired'] = 'Истекла';
$string['status_suspended'] = 'Приостановлена';
$string['status_canceled'] = 'Отменена';
$string['status_replaced'] = 'Заменена';
$string['status_pending'] = 'Ожидает оплаты';
$string['status_failed'] = 'Ошибка оплаты';
$string['status_error'] = 'Ошибка';
$string['status_paid'] = 'Оплачена';
$string['status_completed'] = 'Завершена';

$string['existing_user'] = 'Существующий пользователь';
$string['new_user'] = 'Новый пользователь';
$string['search_user_placeholder'] = 'Поиск по имени, фамилии или email';
$string['manual_subscription_user_section'] = 'Пользователь';
$string['manual_subscription_plan_section'] = 'Подписка';
$string['missing_user_for_manual_subscription'] = 'Не выбран или не создан корректный пользователь.';
$string['not_set'] = 'Не указано';

$string['admin_dashboard'] = 'Администрирование CampusFR';
$string['admin_dashboard_intro'] = 'Здесь собраны основные инструменты для управления подписками, планами и цифровыми продуктами.';

$string['admin_card_user_subscriptions_title'] = 'Подписки пользователей';
$string['admin_card_user_subscriptions_desc'] = 'Просмотр, фильтрация, редактирование и удаление активных и прошлых подписок.';

$string['admin_card_add_subscription_title'] = 'Добавить подписку';
$string['admin_card_add_subscription_desc'] = 'Создать подписку вручную для существующего или нового пользователя.';

$string['admin_card_import_csv_title'] = 'Импорт CSV';
$string['admin_card_import_csv_desc'] = 'Массовый импорт подписок из CSV-файла.';

$string['admin_card_plans_title'] = 'Планы и доступы';
$string['admin_card_plans_desc'] = 'Управление планами, ценами, переводами, scopes, entitlements и upgrades.';

$string['admin_card_digital_products_title'] = 'Цифровые продукты';
$string['admin_card_digital_products_desc'] = 'Создание и управление PDF-файлами, гайдами и отдельными цифровыми ресурсами.';

$string['admin_card_digital_purchases_title'] = 'Цифровые покупки';
$string['admin_card_digital_purchases_desc'] = 'Просмотр цифровых покупок и связанных платёжных данных.';

$string['admin_card_digital_stats_title'] = 'Цифровая статистика';
$string['admin_card_digital_stats_desc'] = 'Отслеживание продаж, доходов и эффективности цифровых продуктов.';
$string['date_format_placeholder'] = 'дд/мм/гггг';
$string['digital_invalid_email'] = 'Пожалуйста, введите корректный email.';
$string['subscription_period'] = 'Период';
$string['unlimited'] = 'Без ограничения';

$string['back_to_admin_dashboard'] = 'Назад в back-office Campus<small><sup>FR</sup></small>';

$string['crm_users'] = 'Пользователи CRM';
$string['crm_user_profile'] = 'Карточка пользователя';
$string['crm_search_user_placeholder'] = 'Поиск по имени, фамилии или email';
$string['crm_no_users_found'] = 'Пользователи не найдены.';
$string['crm_no_subscriptions'] = 'У этого пользователя нет подписок.';
$string['crm_no_digital_purchases'] = 'У этого пользователя нет цифровых покупок.';
$string['view_moodle_profile'] = 'Открыть профиль Moodle';

$string['admin_card_crm_users_title'] = 'Пользователи CRM';
$string['admin_card_crm_users_desc'] = 'Найти пользователя и открыть его полную карточку.';
$string['subscriptions'] = 'Подписки';
$string['digital_purchases'] = 'Цифровые покупки';
$string['product'] = 'Продукт';
$string['digital_purchases'] = 'Цифровые покупки';
$string['crm_quick_actions'] = 'Быстрые действия';
$string['crm_send_email'] = 'Отправить email';
$string['crm_reset_password'] = 'Сбросить пароль';
$string['subject'] = 'Тема';
$string['message'] = 'Сообщение';
$string['send'] = 'Отправить';
$string['crm_email_button_optional'] = 'Дополнительная кнопка';
$string['crm_email_button_label'] = 'Текст кнопки';
$string['crm_email_button_url'] = 'Ссылка кнопки';
$string['crm_email_button_url_required'] = 'Ссылка обязательна, если указан текст кнопки.';
$string['crm_email_button_label_required'] = 'Текст кнопки обязателен, если указана ссылка.';
$string['crm_email_subject_required'] = 'Тема письма обязательна.';
$string['crm_email_body_required'] = 'Текст письма обязателен.';
$string['crm_email_sent_successfully'] = 'Email успешно отправлен.';
$string['crm_notify_user_by_email'] = 'Отправить новый пароль по email';
$string['crm_password_too_short'] = 'Пароль должен содержать минимум 8 символов.';
$string['crm_password_updated_successfully'] = 'Пароль успешно обновлён.';
$string['crm_reset_password_warning'] = 'Это действие сразу заменит текущий пароль пользователя.';
$string['crm_password_email_subject'] = 'Ваш пароль Campus<small><sup>FR</sup></small> был обновлён';
$string['crm_password_email_intro'] = 'Здравствуйте, {$a}!';
$string['crm_password_email_password'] = 'Ваш новый пароль:';
$string['crm_password_email_security'] = 'Из соображений безопасности рекомендуем изменить его после следующего входа.';
$string['crm_login_button'] = 'Войти в CampusFR';
$string['crm_admin_history'] = 'История CRM';
$string['crm_no_admin_history'] = 'Для этого пользователя пока нет записанных действий.';
$string['admin_action'] = 'Действие';
$string['admin_actor'] = 'Выполнил';
$string['details'] = 'Детали';
$string['date'] = 'Дата';

$string['adminlog_email_custom_sent'] = 'Отправлен персональный email';
$string['adminlog_email_password_reset_notice_sent'] = 'Отправлен email о смене пароля';
$string['adminlog_user_password_updated'] = 'Пароль изменён';
$string['crm_internal_notes'] = 'Внутренние заметки';
$string['crm_note_placeholder'] = 'Добавить внутреннюю заметку, видимую только команде…';
$string['crm_add_note'] = 'Добавить заметку';
$string['crm_no_notes'] = 'Для этого пользователя нет внутренних заметок.';
$string['crm_note_required'] = 'Заметка не может быть пустой.';
$string['crm_note_added_successfully'] = 'Заметка успешно добавлена.';
$string['adminlog_user_note_added'] = 'Добавлена внутренняя заметка';

$string['crm_timeline'] = 'CRM-таймлайн';
$string['crm_timeline_empty'] = 'Для этого пользователя пока нет событий.';
$string['crm_timeline_note_added'] = 'Добавлена внутренняя заметка';
$string['adminlog_subscription_created'] = 'Подписка создана';
$string['adminlog_subscription_created_manual'] = 'Подписка создана вручную';
$string['adminlog_subscription_updated'] = 'Подписка изменена';
$string['adminlog_subscription_deleted'] = 'Подписка удалена';
$string['adminlog_subscription_status_updated'] = 'Статус подписки изменён';
$string['adminlog_subscription_dates_updated'] = 'Даты подписки изменены';

$string['adminlog_digital_purchase_created'] = 'Цифровая покупка создана';
$string['adminlog_digital_purchase_paid'] = 'Цифровая покупка оплачена';
$string['adminlog_digital_purchase_failed'] = 'Цифровая покупка не удалась';
$string['adminlog_digital_link_resent'] = 'Ссылка на цифровой продукт отправлена повторно';

$string['adminlog_payment_request_created'] = 'Платёжный запрос создан';
$string['adminlog_payment_request_paid'] = 'Платёжный запрос оплачен';
$string['adminlog_payment_request_failed'] = 'Платёжный запрос не удался';
$string['adminlog_payment_request_cancelled'] = 'Платёжный запрос отменён';

$string['adminlog_trial_started'] = 'Пробный доступ начат';
$string['adminlog_trial_expired'] = 'Пробный доступ истёк';

$string['change_user'] = 'Изменить пользователя';
$string['crm_accessible_courses'] = 'Доступные курсы';
$string['crm_no_accessible_courses'] = 'Сейчас нет доступных курсов.';
$string['access'] = 'Доступ';
$string['active'] = 'Активен';
$string['until'] = 'до';

$string['digital_purchases_more_actions'] = 'Другие действия';
$string['digital_purchases_reconcile_done'] = 'Сверка завершена: исправлено платежей — {$a->reconciled}, переведено в failed — {$a->failed}, пропущено — {$a->skipped}, ошибок — {$a->errors}.';

$string['digital_purchases_export_filename'] = 'pokupki_pdf_campusfr';
$string['digital_purchases_export_sheet'] = 'Покупки PDF';
$string['digital_purchases_export_slug'] = 'Slug';
$string['digital_purchases_export_file_classic'] = 'Обычный файл';
$string['digital_purchases_export_file_mobile'] = 'Мобильный файл';
$string['digital_purchases_export_transaction_id'] = 'ID транзакции';
$string['digital_purchases_export_session_id'] = 'ID сессии';
$string['digital_purchases_export_pdf_email_sent'] = 'Email с PDF отправлен';
$string['digital_purchases_export_receipt_sent'] = 'Чек отправлен';
$string['digital_purchases_export_payment_date'] = 'Дата оплаты';
$string['digital_purchases_export_last_update'] = 'Последнее обновление';
$string['digital_purchases_export_link_expiration'] = 'Срок действия ссылки';
$string['digital_purchases_export_download_classic'] = 'Ссылка на обычную версию';
$string['digital_purchases_export_download_mobile'] = 'Ссылка на мобильную версию';
$string['digital_purchases_export_last_error'] = 'Последняя ошибка DB';
$string['no_expiration'] = 'Без срока действия';

$string['crm_timeline_digital_purchase'] = 'Цифровая покупка';
$string['digital_purchase_details'] = 'Детали цифровой покупки';
$string['digital_purchase_resend_email'] = 'Отправить email повторно';
$string['digital_purchase_resend_email_confirm'] = 'Вы действительно хотите повторно отправить email по этой цифровой покупке?';
$string['digital_purchase_resend_email_logged_only'] = 'Действие записано. Реальная отправка будет подключена на следующем этапе.';
$string['digital_purchase_resend_email_success'] = 'Email с доступом успешно отправлен повторно.';
$string['adminlog_digital_link_resent'] = 'Email с доступом к цифровому продукту отправлен повторно';

$string['digital_purchase_regenerate_token'] = 'Сгенерировать ссылку заново';
$string['digital_purchase_extend_token'] = 'Продлить ссылку';
$string['digital_purchase_regenerate_token_confirm'] = 'Вы действительно хотите сгенерировать новую ссылку? Старая ссылка перестанет работать.';
$string['digital_purchase_extend_token_confirm'] = 'Продлить ссылку для скачивания на 30 дней?';
$string['digital_purchase_token_regenerated_success'] = 'Ссылка для скачивания успешно обновлена.';
$string['digital_purchase_token_extended_success'] = 'Ссылка для скачивания успешно продлена.';
$string['adminlog_digital_token_regenerated'] = 'Цифровая ссылка обновлена';
$string['adminlog_digital_token_extended'] = 'Цифровая ссылка продлена';
$string['digital_purchase_link_expires'] = 'Срок действия ссылки';
$string['digital_purchase_old_token'] = 'Старый токен';
$string['digital_purchase'] = 'Цифровая покупка';
$string['digital_payment_provider'] = 'Платёжный провайдер';
$string['digital_session_id'] = 'Session / Order ID';
$string['digital_transaction_id'] = 'ID транзакции';
$string['digital_payment_link'] = 'Ссылка на оплату';
$string['digital_attempts'] = 'Попытки';
$string['digital_last_attempt'] = 'Последняя попытка';
$string['digital_last_error'] = 'Последняя ошибка';
$string['digital_created_ip'] = 'IP создания';
$string['digital_accept_language'] = 'Язык браузера';
$string['digital_http_referer'] = 'HTTP referrer';
$string['digital_response_json'] = 'JSON-ответ провайдера';

$string['digital_check_provider_now'] = 'Проверить провайдера сейчас';
$string['digital_check_provider_now_confirm'] = 'Проверить статус оплаты у платёжного провайдера сейчас?';
$string['digital_provider_check_done'] = 'Проверка провайдера завершена: {$a->status}.';
$string['adminlog_digital_provider_checked'] = 'Провайдер проверен вручную';
$string['openlinkinnewwindow'] = 'Открыть ссылку в новом окне';
$string['last_update'] = 'Последнее обновление';
$string['digital_product_total_purchases'] = 'Всего покупок';
$string['digital_product_paid_purchases'] = 'Оплаченные покупки';
$string['digital_product_total_revenue'] = 'Выручка';
$string['digital_product_error_count'] = 'Ошибки';
$string['digital_product_recent_purchases'] = 'Последние покупки';
$string['digital_product_no_recent_purchases'] = 'У этого продукта пока нет недавних покупок.';

$string['dashboard_team_card_title'] = 'Сессия команды';
$string['dashboard_team_permissions'] = 'Активные права';
$string['dashboard_team_no_permissions'] = 'Права back-office не обнаружены.';
$string['dashboard_permission_users'] = 'CRM-пользователи';
$string['dashboard_permission_subscriptions'] = 'Подписки';
$string['dashboard_permission_digital'] = 'Цифровые продукты и покупки';
$string['dashboard_permission_payments'] = 'Платежи';
$string['dashboard_permission_configuration'] = 'Конфигурация';
$string['dashboard_today'] = 'Сегодня';
$string['dashboard_stats_new_users'] = 'Новые пользователи';
$string['dashboard_stats_new_subscriptions'] = 'Новые подписки';
$string['dashboard_stats_digital_purchases'] = 'Цифровые покупки';
$string['dashboard_stats_revenue'] = 'Выручка digital';
$string['dashboard_alerts'] = 'Требует внимания';
$string['dashboard_alert_pending_digital'] = 'Цифровые покупки в ожидании';
$string['dashboard_alert_failed_digital'] = 'Неудачные цифровые покупки';
$string['dashboard_alert_email_errors'] = 'Покупки с email/внутренней ошибкой';
$string['dashboard_alert_expired_tokens'] = 'Истёкшие цифровые ссылки';
$string['dashboard_recent_activity'] = 'Последняя активность';
$string['dashboard_no_recent_activity'] = 'Пока нет недавней активности.';

$string['crm_resend_welcome_email'] = 'Отправить приветствие';
$string['crm_resend_access_email'] = 'Отправить доступ';
$string['crm_resend_receipt'] = 'Отправить чек';

$string['crm_welcome_email_resent_success'] = 'Приветственное письмо отправлено повторно.';
$string['crm_access_email_resent_success'] = 'Письмо с доступом отправлено повторно.';
$string['crm_receipt_resent_success'] = 'Чек отправлен повторно.';
$string['crm_subscription_extended_success'] = 'Подписка продлена на {$a} дней.';

$string['crm_receipt_not_available'] = 'Для этой подписки нет доступного чека.';
$string['crm_timeline_course_purchase_paid'] = 'Покупка курса оплачена';
$string['crm_timeline_payment_request'] = 'Запрос на оплату';
$string['crm_timeline_subscription_created'] = 'Подписка создана';
$string['crm_timeline_trial_started'] = 'Пробный доступ начат';
$string['payment_provider'] = 'Платёжный провайдер';
$string['transactionid'] = 'ID транзакции';
$string['crm_timeline_email_receipt_sent'] = 'Чек отправлен';
$string['crm_timeline_email_access_sent'] = 'Письмо с доступом отправлено';
$string['crm_timeline_email_welcome_sent'] = 'Приветственное письмо отправлено';

$string['crm_email_type_receipt'] = 'Чек покупки';
$string['crm_email_type_access'] = 'Доступ к подписке';
$string['crm_email_type_welcome'] = 'Приветствие';

$string['crm_timeline_collapse_all'] = 'Свернуть / развернуть детали';
$string['crm_timeline_view_details'] = 'Показать детали';
$string['payment_request'] = 'Запрос на оплату';
$string['type'] = 'Тип';
$string['subscription_details'] = 'Детали подписки';
$string['crm_user_profile'] = 'CRM-карточка пользователя';
$string['crm_no_payment_request_for_subscription'] = 'К этой подписке не привязан запрос на оплату.';
$string['view_details'] = 'Подробнее';
$string['admin_section_discounts'] = 'Скидки';
$string['admin_section_provider'] = 'Информация о платёжном провайдере';
$string['admin_section_payment_failures'] = 'Ошибки оплаты';
$string['admin_section_dates'] = 'Даты';

$string['admin_section_payment_request_identity'] = 'Личные данные / контакты';
$string['admin_section_payment_status'] = 'Статус и операция';
$string['admin_section_amounts'] = 'Зафиксированные суммы';
$string['admin_section_links_tokens'] = 'Ссылки и токены';
$string['admin_section_reminders_attempts'] = 'Напоминания и попытки';
$string['admin_section_request_context'] = 'Контекст создания';

$string['discount_percent'] = 'Скидка (%)';
$string['discount_amount'] = 'Сумма скидки';
$string['discount_reason'] = 'Причина скидки';

$string['phone'] = 'Телефон';
$string['phone_country'] = 'Страна телефона';
$string['operation'] = 'Операция';
$string['reference_subscription_id'] = 'Исходная подписка';
$string['amount_minor'] = 'Сумма в минимальных единицах';

$string['locked_list_price'] = 'Зафиксированная цена по каталогу';
$string['locked_discount_percent'] = 'Зафиксированная скидка (%)';
$string['locked_discount_amount'] = 'Зафиксированная сумма скидки';
$string['locked_discount_reason'] = 'Причина зафиксированной скидки';
$string['locked_final_price'] = 'Зафиксированная итоговая цена';
$string['locked_at'] = 'Зафиксировано';

$string['retry_token'] = 'Токен повторной попытки';
$string['retry_expires'] = 'Срок действия токена повторной попытки';
$string['login_token'] = 'Токен входа';
$string['login_token_expires'] = 'Срок действия токена входа';

$string['emailsent'] = 'Письмо отправлено';
$string['reminder_stage'] = 'Этап напоминания';
$string['reminder1_at'] = 'Первое напоминание отправлено';
$string['reminder2_at'] = 'Второе напоминание отправлено';

$string['created_ip'] = 'IP-адрес при создании';
$string['created_useragent'] = 'User-Agent при создании';
$string['accept_language'] = 'Предпочитаемый язык';
$string['http_referer'] = 'HTTP Referer';
$string['expiration_date'] = 'Дата истечения';
$string['subscription'] = 'Подписка';

$string['crm_timeline_expand_all'] = 'Развернуть всё';
$string['crm_timeline_collapse_all'] = 'Свернуть всё';
$string['crm_timeline_view_details'] = 'Показать детали';

$string['crm_timeline_recent'] = 'Недавняя история (последние 30 дней)';
$string['crm_timeline_middle'] = 'История (31–90 дней)';
$string['crm_timeline_old'] = 'Старая история (более 90 дней)';

$string['crm_filter_purchases'] = 'Покупки';
$string['crm_filter_emails'] = 'Письма';
$string['crm_filter_other'] = 'Другое';
$string['crm_timeline_by_actor'] = 'администратор: {$a}';
$string['crm_timeline_by_admin'] = 'администратор';
$string['crm_email_preview'] = 'Предпросмотр письма';
$string['recipient'] = 'Получатель';

$string['crm_timeline_title'] = 'Таймлайн';
$string['crm_suspend_moodle_profile'] = 'Заблокировать профиль Moodle';
$string['crm_activate_moodle_profile'] = 'Активировать профиль Moodle';
$string['crm_moodle_profile_suspended'] = 'Профиль Moodle заблокирован.';
$string['crm_moodle_profile_activated'] = 'Профиль Moodle активирован.';
$string['adminlog_user_suspended'] = 'Профиль Moodle заблокирован';
$string['adminlog_user_reactivated'] = 'Профиль Moodle активирован';

$string['crm_stats_title'] = 'CRM-сводка';
$string['crm_accessible_courses'] = 'Доступные курсы';
$string['crm_total_spent'] = 'Всего потрачено';
$string['crm_last_activity'] = 'Последняя активность';

$string['crm_stats_subscriptions_hint'] = 'Подписки, связанные с профилем';
$string['crm_stats_digital_hint'] = 'Купленные цифровые продукты';
$string['crm_stats_courses_hint'] = 'Курсы, доступные сейчас';
$string['crm_stats_spent_hint'] = 'Всего оплачено по валютам';
$string['crm_stats_activity_hint'] = 'Последнее известное событие';

$string['crm_status'] = 'CRM-статус';
$string['crm_stats_status_hint'] = 'Текущее состояние профиля';
$string['crm_status_active_customer'] = 'Активный клиент';
$string['crm_status_trial'] = 'Пробный доступ';
$string['crm_status_former_customer'] = 'Бывший клиент';
$string['crm_status_suspended'] = 'Заблокирован';
$string['crm_status_lead'] = 'Лид';
$string['crm_status_unknown'] = 'Неизвестно';

$string['command_center_type_user'] = 'Пользователь';
$string['command_center_user_suspended'] = 'Аккаунт заблокирован';
$string['command_center_open'] = 'Открыть Command Center';
$string['command_center_placeholder'] = 'Найти пользователя, покупку, продукт…';
$string['command_center_input_placeholder'] = 'Поиск… или введите > для действий';
$string['command_center_hint'] = 'Enter — открыть · Esc — закрыть · ↑ ↓ — навигация';
$string['command_center_close'] = 'Закрыть Command Center';
$string['command_center_empty'] = 'Ничего не найдено';
$string['command_center_error'] = 'Ошибка поиска';
$string['command_center_loading'] = 'Поиск…';
$string['command_center_type_digital_product'] = 'Цифровой продукт';
$string['command_center_type_digital_purchase'] = 'Цифровая покупка';
$string['command_center_type_subscription'] = 'Подписка';
$string['command_center_disabled'] = 'Отключено';

$string['command_center_product_subtitle'] = '{$a->slug} · {$a->eur} EUR · {$a->rub} RUB';
$string['command_center_purchase_subtitle'] = '{$a->product} · {$a->status} · {$a->price} · {$a->date}';
$string['command_center_subscription_subtitle'] = '{$a->plan} · {$a->status} · {$a->period}';
$string['command_center_type_action'] = 'Действие';

$string['command_action_dashboard_title'] = 'Открыть dashboard';
$string['command_action_dashboard_subtitle'] = 'Вернуться на главную страницу CRM';

$string['command_action_users_title'] = 'Посмотреть пользователей CRM';
$string['command_action_users_subtitle'] = 'Открыть список пользователей и клиентов';

$string['command_action_products_title'] = 'Посмотреть цифровые продукты';
$string['command_action_products_subtitle'] = 'Управлять цифровыми продуктами CampusFR';

$string['command_action_product_create_title'] = 'Создать цифровой продукт';
$string['command_action_product_create_subtitle'] = 'Добавить новый цифровой продукт';

$string['command_action_purchases_title'] = 'Посмотреть цифровые покупки';
$string['command_action_purchases_subtitle'] = 'Открыть покупки и цифровые платежи';

$string['command_action_subscriptions_title'] = 'Посмотреть подписки';
$string['command_action_subscriptions_subtitle'] = 'Просматривать и управлять подписками пользователей';

$string['command_center_initial'] = 'Начните вводить, чтобы найти пользователя, продукт, покупку, подписку или действие.';

$string['command_center_group_actions'] = 'Действия';
$string['command_center_group_users'] = 'Пользователи';
$string['command_center_group_products'] = 'Продукты';
$string['command_center_group_purchases'] = 'Покупки';
$string['command_center_group_subscriptions'] = 'Подписки';

$string['command_center_action_open'] = 'Открыть';
$string['command_center_action_view'] = 'Посмотреть';
$string['command_center_action_edit'] = 'Изменить';
$string['command_center_hint_navigate'] = 'навигация';
$string['command_center_hint_open'] = 'открыть';
$string['command_center_hint_close'] = 'закрыть';
$string['command_center_best_match'] = 'Лучшее';
$string['command_center_recent'] = 'Недавние';
$string['command_center_key_enter'] = 'Enter';
$string['command_center_key_escape'] = 'Esc';
$string['command_center_favorites'] = 'Избранное';
$string['command_center_favorite_toggle'] = 'Добавить или удалить из избранного';
$string['command_center_clear_recent'] = 'Очистить';

$string['command_center_action_missing_url'] = 'URL отсутствует.';
$string['command_center_action_unknown'] = 'Неизвестная команда.';
$string['command_center_action_error'] = 'Не удалось выполнить команду.';
$string['command_center_action_failed'] = 'Не удалось выполнить действие.';
$string['command_center_action_missing_user'] = 'Пользователь отсутствует.';
$string['command_center_action_missing_product'] = 'Продукт отсутствует.';
$string['command_center_action_missing_purchase'] = 'Покупка отсутствует.';
$string['command_center_action_missing_subscription'] = 'Подписка отсутствует.';
$string['command_action_user_email_title'] = 'Отправить письмо пользователю';
$string['command_action_user_email_subtitle'] = 'Открыть список пользователей, чтобы выбрать контакт.';

$string['command_action_user_note_title'] = 'Добавить заметку пользователю';
$string['command_action_user_note_subtitle'] = 'Открыть список пользователей перед добавлением CRM-заметки.';

$string['command_action_purchase_resend_email_title'] = 'Повторно отправить письмо о покупке';
$string['command_action_purchase_resend_email_subtitle'] = 'Открыть цифровые покупки, чтобы выбрать нужную покупку.';
$string['command_menu_user_email'] = 'Отправить письмо';
$string['command_menu_user_note'] = 'Добавить заметку';
$string['command_menu_user_reset_password'] = 'Сбросить пароль';
$string['command_menu_purchase_resend_email'] = 'Повторно отправить письмо';
$string['command_menu_purchase_regenerate_token'] = 'Сгенерировать токен заново';
$string['command_menu_purchase_extend_token'] = 'Продлить токен';

$string['command_menu_product_edit'] = 'Изменить продукт';

$string['command_menu_subscription_open'] = 'Открыть подписку';
$string['command_center_purchase_email_resent'] = 'Письмо с доступом было отправлено повторно.';
$string['command_confirm_purchase_resend_email'] = 'Повторно отправить письмо с доступом для этой покупки?';
$string['command_menu_purchase_check_provider'] = 'Проверить оплату';
$string['command_confirm_purchase_regenerate_token'] = 'Сгенерировать новый токен доступа для этой покупки?';
$string['command_confirm_purchase_extend_token'] = 'Продлить токен доступа для этой покупки?';
$string['command_confirm_user_reset_password'] = 'Сбросить пароль этого пользователя?';
$string['command_action_users_title'] = 'Пользователи';
$string['command_action_users_subtitle'] = 'Открыть управление пользователями CRM.';
$string['command_action_digital_purchases_title'] = 'Цифровые покупки';
$string['command_action_digital_purchases_subtitle'] = 'Открыть цифровые покупки и продажи.';
$string['command_action_digital_products_title'] = 'Цифровые продукты';
$string['command_action_digital_products_subtitle'] = 'Открыть управление цифровыми продуктами.';
$string['command_action_subscriptions_title'] = 'Подписки';
$string['command_action_subscriptions_subtitle'] = 'Открыть управление подписками и доступами.';
$string['command_center_action_invalid_url'] = 'Недействительный URL.';
$string['command_center_confirm'] = 'Подтвердить';
$string['command_center_cancel'] = 'Отмена';
$string['command_center_danger_confirm'] = 'Важное действие';
$string['command_center_group_intents'] = 'Commandes';
$string['command_center_action_execute'] = 'Exécuter';

$string['command_intent_open_user'] = 'Ouvrir utilisateur';
$string['command_intent_open_purchase'] = 'Ouvrir achat';
$string['command_intent_open_product'] = 'Ouvrir produit';
$string['command_intent_open_subscription'] = 'Ouvrir abonnement';
$string['command_intent_direct_entity_subtitle'] = 'Commande directe depuis le Command Center.';
$string['command_center_group_intents'] = 'Команды';
$string['command_center_action_execute'] = 'Выполнить';

$string['command_intent_open_user'] = 'Открыть пользователя';
$string['command_intent_open_purchase'] = 'Открыть покупку';
$string['command_intent_open_product'] = 'Открыть продукт';
$string['command_intent_open_subscription'] = 'Открыть подписку';
$string['command_intent_direct_entity_subtitle'] = 'Прямая команда из Command Center.';
$string['command_intent_email_user'] = 'Отправить письмо пользователю';
$string['command_intent_note_user'] = 'Добавить заметку пользователю';
$string['command_intent_reset_user'] = 'Сбросить пароль пользователя';
$string['command_intent_user_quick_action_subtitle'] = 'Быстрое действие с пользователем из Command Center.';

$string['command_intent_resend_purchase_email'] = 'Повторно отправить письмо о покупке';
$string['command_intent_check_purchase'] = 'Проверить оплату';
$string['command_intent_purchase_quick_action_subtitle'] = 'Быстрое действие с покупкой из Command Center.';
$string['command_center_action_suggestion'] = 'Подсказка';

$string['command_suggestion_email_user_title'] = 'Отправить письмо пользователю';
$string['command_suggestion_email_user_subtitle'] = 'Пример: > email 12';

$string['command_suggestion_note_user_title'] = 'Добавить заметку пользователю';
$string['command_suggestion_note_user_subtitle'] = 'Пример: > note 12';

$string['command_suggestion_reset_user_title'] = 'Сбросить пароль';
$string['command_suggestion_reset_user_subtitle'] = 'Пример: > reset 12';

$string['command_suggestion_resend_purchase_title'] = 'Повторно отправить письмо о покупке';
$string['command_suggestion_resend_purchase_subtitle'] = 'Пример: > resend 7';

$string['command_suggestion_check_purchase_title'] = 'Проверить оплату';
$string['command_suggestion_check_purchase_subtitle'] = 'Пример: > check 7';

$string['command_action_user_email'] = 'Отправить email';
$string['command_action_user_note'] = 'Добавить заметку';
$string['command_action_user_reset_password'] = 'Сбросить пароль';
$string['crm_section_overview'] = 'Обзор';
$string['crm_section_quick_actions'] = 'Быстрые действия';
$string['crm_section_subscriptions'] = 'Активные и прошлые подписки';
$string['crm_section_digital_purchases'] = 'Цифровые покупки';
$string['crm_section_courses'] = 'Доступные курсы';
$string['crm_section_notes'] = 'Внутренние заметки';
$string['crm_note_empty'] = 'Заметка не может быть пустой.';
$string['crm_note_too_long'] = 'Заметка слишком длинная.';
$string['crm_note_type_general'] = 'Общее';
$string['crm_note_type_followup'] = 'Повторный контакт';
$string['crm_note_type_payment'] = 'Оплата';
$string['crm_note_type_access'] = 'Доступ';
$string['crm_note_type_sensitive'] = 'Важное / чувствительное';
$string['crm_invalid_tag'] = 'Недопустимый CRM-тег.';
$string['crm_tag_vip'] = 'VIP';
$string['crm_tag_followup'] = 'Связаться повторно';
$string['crm_tag_payment_issue'] = 'Проблема с оплатой';
$string['crm_tag_refund'] = 'Возврат';
$string['crm_tag_manual_access'] = 'Ручной доступ';
$string['crm_tag_sensitive'] = 'Чувствительный случай';
$string['crm_section_timeline'] = 'CRM-история';
$string['command_action_purchase_resend_email'] = 'Повторно отправить email покупки';
$string['task_run_crm_automations'] = 'Запуск CRM-автоматизаций';
$string['crm_timeline_automation_executed'] = 'CRM-автоматизация выполнена';
$string['crm_automations'] = 'CRM-автоматизации';
$string['crm_automation_history'] = 'История автоматизаций';
$string['crm_automation_trigger'] = 'Триггер';
$string['crm_automation_rule'] = 'Правило';
$string['enabled'] = 'Включено';
$string['disabled'] = 'Отключено';
$string['priority'] = 'Приоритет';

$string['command_action_automations_title'] = 'CRM-автоматизации';
$string['command_action_automations_subtitle'] = 'Управление правилами и workflow CRM';
$string['command_action_automation_history_title'] = 'История автоматизаций';
$string['command_action_automation_history_subtitle'] = 'Последние выполнения CRM-автоматизаций';

$string['crm_automation_no_rules'] = 'Нет правил автоматизации.';
$string['crm_automation_no_history'] = 'История автоматизаций отсутствует.';
$string['crm_automation_recent_history'] = 'Последние выполнения';
$string['crm_automation_trial_expired'] = 'Обнаружено окончание пробного периода';
$string['crm_automation_payment_failed'] = 'Обнаружен неуспешный платёж';
$string['crm_automation_digital_purchase_paid'] = 'Оплачен цифровой продукт';
$string['crm_automation_subscription_expired'] = 'Обнаружено окончание подписки';
$string['crm_automation_note_added'] = 'Добавлена CRM-заметка';
$string['crm_automation_tag_added'] = 'Добавлен CRM-тег';
$string['crm_automation_tag_removed'] = 'Удалён CRM-тег';
$string['crm_automation_rules_count'] = 'Настроено правил автоматизации: {$a}';
$string['crm_automation_status_success'] = 'Успешно';
$string['crm_automation_status_failed'] = 'Ошибка';
$string['crm_automation_status_skipped'] = 'Пропущено';