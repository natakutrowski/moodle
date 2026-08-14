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


$string['btn_signin'] = 'Войти';

$string['provider_alfa'] = 'AlfaBank';
$string['provider_stripe'] = 'Stripe';
$string['provider_manual'] = 'Ручной';
$string['provider_csv'] = 'CSV';
$string['provider_dev'] = 'Разработка';
$string['provider_trial']  = 'Пробный';

$string['configmissing'] = 'Отсутствует настройка: {$a}.';

$string['invalidcsvupload'] = 'Загруженный CSV-файл некорректен.';
$string['csvwritefail'] = 'Не удалось сохранить CSV-файл.';
$string['invalidpricecurrency'] = 'Неверная комбинация цена/валюта.';
$string['plan_not_found'] = 'Тариф не найден.';
$string['scopenotfound'] = 'Область доступа не найдена.';
$string['scopedeleteinuse'] = 'Невозможно удалить эту область, так как она используется.';
$string['plannotfound'] = 'Тариф не найден.';


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
$string['stripe_profile_test'] = 'Тест';
$string['stripe_profile_live_ei'] = 'Live EI';
$string['stripe_profile_live_sas'] = 'Live SAS';
$string['stripe_secret_live_sas'] = 'Секретный ключ (LIVE SAS)';
$string['stripe_publishable_live_sas'] = 'Публичный ключ (LIVE SAS)';
$string['stripe_webhook_secret_live_sas'] = 'Секрет вебхука Stripe (LIVE SAS)';
$string['stripe_portal_configuration_id_live_sas'] = 'ID конфигурации портала Stripe (LIVE SAS)';


// Stripe
$string['stripe_secret_test'] = 'Секретный ключ (TEST)';
$string['stripe_publishable_test'] = 'Публичный ключ (TEST)';
$string['stripe_webhook_secret_test'] = 'Секрет вебхука Stripe (TEST)';
$string['stripe_portal_configuration_id_test'] = 'ID конфигурации портала Stripe (TEST)';
$string['stripe_portal_configuration_id_desc'] = 'Необязательно: ID конфигурации Customer Portal (например, pc_xxx). Если пусто — используется конфигурация по умолчанию Stripe.';

$string['stripe_secret_live'] = 'Секретный ключ (LIVE EI)';
$string['stripe_publishable_live'] = 'Публичный ключ (LIVE EI)';
$string['stripe_webhook_secret_live'] = 'Секрет вебхука Stripe (LIVE EI)';
$string['stripe_portal_configuration_id_live'] = 'ID конфигурации портала Stripe (LIVE EI)';


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
$string['digital_product_edit_click_to_upload'] = 'Нажмите здесь, чтобы выбрать или заменить файл.';


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
$string['unlock_full_button'] = 'Купить полную версию';

$string['restricted_access_title'] = 'Ограниченный доступ';
$string['restricted_access_text'] = 'Купите курс, чтобы открыть это задание.';
$string['buy'] = 'Купить';

$string['plan_already_covered'] = 'У вас уже есть такой же или более высокий доступ к этому содержимому.';
$string['all_courses_owned_title'] = 'У вас уже есть доступ ко всем доступным курсам';
$string['all_courses_owned_text'] = 'Сейчас ничего покупать не нужно. Вы можете продолжить обучение в своём разделе курсов.';

$string['unlock_subscriber_title'] = 'Задание доступно участникам';
$string['unlock_subscriber_text'] = 'Это задание недоступно в пробном режиме. Выберите подходящий вариант, чтобы продолжить.';
$string['unlock_subscriber_button'] = 'Посмотреть варианты';

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

$string['crm_search_user_placeholder'] = 'Поиск по имени, фамилии или email';
$string['crm_no_users_found'] = 'Пользователи не найдены.';
$string['crm_no_subscriptions'] = 'У этого пользователя нет подписок.';
$string['crm_no_digital_purchases'] = 'У этого пользователя нет цифровых покупок.';
$string['view_moodle_profile'] = 'Открыть профиль Moodle';

$string['admin_card_crm_users_title'] = 'Пользователи CRM';
$string['admin_card_crm_users_desc'] = 'Найти пользователя и открыть его полную карточку.';
$string['subscriptions'] = 'Подписки';

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


$string['adminlog_payment_request_created'] = 'Платёжный запрос создан';
$string['adminlog_payment_request_paid'] = 'Платёжный запрос оплачен';
$string['adminlog_payment_request_failed'] = 'Платёжный запрос не удался';
$string['adminlog_payment_request_cancelled'] = 'Платёжный запрос отменён';

$string['adminlog_trial_started'] = 'Пробный доступ начат';
$string['adminlog_trial_expired'] = 'Пробный доступ истёк';

$string['change_user'] = 'Изменить пользователя';

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
$string['dashboard_stats_digital_purchases'] = 'Цифровые покупки';
$string['dashboard_stats_revenue'] = 'Выручка';
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


$string['command_action_products_title'] = 'Посмотреть цифровые продукты';
$string['command_action_products_subtitle'] = 'Управлять цифровыми продуктами CampusFR';

$string['command_action_product_create_title'] = 'Создать цифровой продукт';
$string['command_action_product_create_subtitle'] = 'Добавить новый цифровой продукт';

$string['command_action_purchases_title'] = 'Посмотреть цифровые покупки';
$string['command_action_purchases_subtitle'] = 'Открыть покупки и цифровые платежи';


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

$string['crm_section_intelligence'] = 'CRM-аналитика';

$string['crm_intelligence_commercial_score'] = 'Коммерческий потенциал';
$string['crm_intelligence_engagement_score'] = 'Вовлечённость';
$string['crm_intelligence_risk_score'] = 'Риск';
$string['crm_intelligence_global_score'] = 'Общий рейтинг';

$string['crm_intelligence_reason_active_customer'] = 'Активный клиент';
$string['crm_intelligence_reason_trial_user'] = 'Пробный доступ';
$string['crm_intelligence_reason_paid_digital_purchase'] = 'Оплаченная цифровая покупка';
$string['crm_intelligence_reason_high_value'] = 'Высокая ценность клиента';
$string['crm_intelligence_reason_recent_activity'] = 'Недавняя активность';
$string['crm_intelligence_reason_inactive'] = 'Неактивный пользователь';
$string['crm_intelligence_reason_expired_subscription'] = 'Подписка истекла';
$string['crm_intelligence_reason_suspended'] = 'Учётная запись заблокирована';
$string['crm_intelligence_level_very_low'] = 'Очень низкий';
$string['crm_intelligence_level_low'] = 'Низкий';
$string['crm_intelligence_level_medium'] = 'Средний';
$string['crm_intelligence_level_high'] = 'Высокий';
$string['crm_intelligence_level_excellent'] = 'Отличный';

$string['crm_intelligence_summary_very_low'] = 'Пока недостаточно полезных сигналов.';
$string['crm_intelligence_summary_low'] = 'Профиль пока не приоритетный, но его стоит отслеживать.';
$string['crm_intelligence_summary_medium'] = 'Интересный профиль с несколькими полезными сигналами.';
$string['crm_intelligence_summary_high'] = 'Приоритетный профиль с хорошим коммерческим потенциалом.';
$string['crm_intelligence_summary_excellent'] = 'Очень приоритетный профиль с высокой CRM-ценностью.';
$string['crm_intelligence_segments'] = 'Сегменты';
$string['crm_intelligence_opportunities'] = 'Возможности';
$string['crm_intelligence_recommendations'] = 'Рекомендации';

$string['crm_intelligence_segment_customer'] = 'Клиент';
$string['crm_intelligence_segment_trial'] = 'Пробный доступ';
$string['crm_intelligence_segment_hot_lead'] = 'Горячий лид';
$string['crm_intelligence_segment_at_risk'] = 'В зоне риска';
$string['crm_intelligence_segment_vip'] = 'VIP';
$string['crm_intelligence_segment_cold_user'] = 'Холодный пользователь';

$string['crm_intelligence_opportunity_trial_to_purchase'] = 'Конверсия пробного доступа в покупку';
$string['crm_intelligence_opportunity_cross_sell_digital_product'] = 'Кросс-продажа цифрового продукта';
$string['crm_intelligence_opportunity_upgrade_subscription'] = 'Вероятный апгрейд';
$string['crm_intelligence_opportunity_winback_expired_customer'] = 'Возврат клиента с истёкшей подпиской';

$string['crm_intelligence_recommendation_send_trial_conversion_email'] = 'Отправить письмо для конверсии';
$string['crm_intelligence_recommendation_propose_upgrade'] = 'Предложить апгрейд';
$string['crm_intelligence_recommendation_send_winback_message'] = 'Отправить сообщение для возврата';
$string['crm_intelligence_recommendation_suggest_digital_product'] = 'Предложить цифровой продукт';
$string['crm_intelligence_recommendation_review_user_manually'] = 'Проверить профиль вручную';
$string['crm_intelligence_recommendation_create_first_crm_note'] = 'Создать первую CRM-заметку';

$string['crm_intelligence_dashboard_title'] = 'CRM-аналитика';
$string['crm_intelligence_dashboard_analysed_users'] = 'Проанализированные пользователи';
$string['crm_intelligence_dashboard_hot_leads'] = 'Горячие лиды';
$string['crm_intelligence_dashboard_at_risk'] = 'Профили в зоне риска';
$string['crm_intelligence_dashboard_vip'] = 'VIP';
$string['crm_intelligence_dashboard_trial_opportunities'] = 'Возможности пробного доступа';
$string['crm_intelligence_dashboard_upgrade_opportunities'] = 'Возможности апгрейда';
$string['crm_intelligence_dashboard_priority_profiles'] = 'Приоритетные профили';
$string['crm_intelligence_dashboard_no_priority_profiles'] = 'Приоритетные профили пока не обнаружены.';
$string['crm_intelligence_alerts_title'] = 'Умные CRM-уведомления';
$string['crm_intelligence_alerts_empty'] = 'Пока нет важных CRM-уведомлений.';
$string['crm_intelligence_alert_open_profile'] = 'Открыть профиль пользователя';

$string['crm_intelligence_alert_high_risk_user'] = 'Пользователь с высоким CRM-риском';
$string['crm_intelligence_alert_trial_without_purchase'] = 'Активный пробный доступ без покупки';
$string['crm_intelligence_alert_expired_without_reactivation'] = 'Подписка истекла без повторной активации';
$string['crm_intelligence_alert_inactive_user'] = 'Пользователь давно неактивен';
$string['crm_intelligence_alert_hot_opportunity'] = 'Горячая коммерческая возможность';
$string['command_crm_intelligence_dashboard'] = 'Панель CRM-аналитики';
$string['command_crm_intelligence_dashboard_desc'] = 'Посмотреть CRM-оценки, уведомления и рекомендации.';
$string['command_crm_alert_desc'] = 'Обнаружено умное CRM-уведомление.';

$string['crm_funnel_title'] = 'CRM-воронка';
$string['crm_funnel_users'] = 'Пользователи';
$string['crm_funnel_trials'] = 'Пробные доступы';
$string['crm_funnel_customers'] = 'Клиенты';
$string['crm_funnel_digital_customers'] = 'Цифровые клиенты';
$string['crm_funnel_expired_customers'] = 'Клиенты с истёкшей подпиской';
$string['crm_funnel_trial_conversion_rate'] = 'Конверсия пробного доступа в клиента';
$string['task_run_crm_intelligence_snapshot'] = 'Создание снимков CRM-аналитики';
$string['crm_trends_title'] = 'CRM-тенденции';
$string['crm_trends_empty'] = 'Пока недостаточно CRM-истории.';
$string['crm_trend_label'] = 'Тенденция';
$string['crm_trend_direction_up'] = 'Растёт';
$string['crm_trend_direction_down'] = 'Снижается';
$string['crm_trend_direction_stable'] = 'Стабильно';

$string['crm_explanation_active_customer'] = 'Активный клиент';
$string['crm_explanation_trial_user'] = 'Пробный доступ';
$string['crm_explanation_paid_digital_purchase'] = 'Оплаченная цифровая покупка';
$string['crm_explanation_high_value'] = 'Высокая ценность клиента';
$string['crm_explanation_recent_activity'] = 'Недавняя активность';
$string['crm_explanation_inactive'] = 'Обнаружена неактивность';
$string['crm_explanation_expired_subscription'] = 'Подписка истекла';
$string['crm_explanation_suspended'] = 'Учётная запись заблокирована';
$string['crm_explanation_no_crm_note'] = 'Нет CRM-заметки';
$string['crm_explanations_title'] = 'Почему такой рейтинг?';

$string['crm_daily_priorities_title'] = 'CRM-приоритеты на сегодня';
$string['crm_daily_priorities_empty'] = 'Пока нет важных CRM-приоритетов.';

$string['command_crm_priority_desc'] = 'Обнаружен CRM-приоритет на сегодня.';
$string['crm_recommendation_action_permission_denied'] = 'Недостаточно прав для выполнения этого действия.';
$string['crm_recommendation_action_unsupported'] = 'Это рекомендованное действие пока не поддерживается.';
$string['crm_recommendation_action_open_user_profile'] = 'Открыть профиль пользователя';

$string['dashboard_period_today'] = 'Сегодня';
$string['dashboard_period_week'] = 'Эта неделя';
$string['dashboard_period_month'] = 'Этот месяц';
$string['dashboard_command_center_title'] = 'Центр управления';
$string['crm_user_filter_all'] = 'Все пользователи';
$string['crm_user_filter_hot_lead'] = 'Горячие лиды';
$string['crm_user_filter_at_risk'] = 'Профили в зоне риска';
$string['crm_user_filter_vip'] = 'VIP-клиенты';
$string['crm_user_filter_cold_user'] = 'Неактивные пользователи';
$string['crm_user_filter_trial_to_purchase'] = 'Пробные доступы для конверсии';
$string['crm_user_filter_upgrade_subscription'] = 'Возможности апгрейда';
$string['crm_user_active_filter'] = 'Активный фильтр: {$a}';

$string['dashboard_issues_title'] = 'Требует внимания';
$string['dashboard_issues_subtitle'] = 'Пункты, которые требуют проверки или действия администратора.';

$string['dashboard_issue_pending_digital_title'] = 'Ожидающие цифровые платежи';
$string['dashboard_issue_pending_digital_desc'] = 'Платёжные заявки созданы, но ещё не подтверждены.';
$string['dashboard_issue_failed_digital_title'] = 'Неуспешные цифровые платежи';
$string['dashboard_issue_failed_digital_desc'] = 'Отклонённые или прерванные платежи для проверки.';


$string['dashboard_issue_open_queue'] = 'Открыть очередь';
$string['dashboard_issue_review_failures'] = 'Проверить';
$string['dashboard_issue_resend_emails'] = 'Отправить снова';
$string['dashboard_issue_regenerate_tokens'] = 'Обновить';
$string['digital_purchase_filter_no_issue'] = 'Без проблемы';
$string['digital_purchase_filter_issue_email_error'] = 'Ошибка email';
$string['digital_purchase_filter_issue_expired_token'] = 'Ссылка истекла';
$string['digital_purchase_filter_clear_issue'] = 'Убрать фильтр проблемы';
$string['digital_purchase_action_resend_email'] = 'Отправить email снова';
$string['digital_purchase_action_regenerate_token'] = 'Обновить ссылку';
$string['digital_purchase_action_extend_token'] = 'Продлить ссылку';
$string['digital_purchase_action_email_resent'] = 'Email успешно отправлен повторно.';
$string['digital_purchase_action_token_regenerated'] = 'Ссылка на скачивание обновлена.';
$string['digital_purchase_action_failed'] = 'Действие невозможно: {$a}';
$string['digital_purchases_actions'] = 'Действия';

$string['digital_purchase_action_resend_email_confirm'] = 'Повторно отправить письмо с доступом к этой покупке?';
$string['digital_purchase_action_regenerate_token_confirm'] = 'Создать новую ссылку для скачивания? Старая ссылка больше не будет работать.';
$string['digital_purchase_action_extend_token_confirm'] = 'Продлить эту ссылку для скачивания на 30 дней?';
$string['digital_purchase_access_action_requires_paid_status'] =
    'Это действие доступно только для подтверждённых платежей.';
$string['digital_payment_help_email_subject'] =
    'Возникли трудности с оплатой?';

$string['digital_payment_help_email_body'] =
    '<p>Здравствуйте, {$a->firstname}!</p>
    <p>Мы заметили, что попытка оплаты не была завершена.</p>
    <p>Возникли ли у вас трудности или нужна помощь, чтобы завершить покупку?</p>
    <p>Вы можете просто ответить на это письмо — наша команда с радостью поможет.</p>
    <p>С уважением,<br>Команда CampusFR</p>';
$string['digital_purchase_action_contact_buyer'] = 'Связаться с покупателем';
$string['digital_purchase_action_cancel'] = 'Отменить';
$string['digital_purchase_action_cancel_confirm'] =
    'Отменить эту попытку оплаты? Она больше не будет отображаться в списке требующих внимания.';
$string['digital_purchase_cancel_success'] =
    'Попытка оплаты отменена.';

$string['digital_purchase_cancel_invalid_status'] =
    'Эту покупку нельзя отменить, поскольку её текущий статус — {$a}.';
$string['digital_payment_help_email_context_title'] =
    'Связаться по поводу незавершённой оплаты';

$string['digital_payment_help_email_context_description'] =
    'Тема и текст письма уже заполнены. Вы можете изменить их перед отправкой.';

$string['digital_payment_help_purchase_user_mismatch'] =
    'Эта цифровая покупка не соответствует выбранному пользователю.';

$string['dashboard_issue_open_purchases'] = 'Открыть покупки';
$string['dashboard_issue_review_queue'] = 'Проверить очередь';

$string['dashboard_issues_empty_title'] = 'Всё под контролем';
$string['dashboard_issues_empty_description'] =
    'Нет платежей или цифровых доступов, требующих вашего внимания.';

$string['dashboard_issue_email_error_title'] =
    'Письма с доступом не отправлены';

$string['dashboard_issue_email_error_desc'] =
    'Оплаченные покупки, для которых не удалось отправить письмо с доступом.';

$string['dashboard_issue_expired_token_title'] =
    'Истёкшие ссылки для скачивания';

$string['dashboard_issue_expired_token_desc'] =
    'Оплаченные покупки, срок действия ссылки для скачивания которых истёк.';

$string['admin_event_unknown'] =
    'Административное событие';

$string['admin_event_email_custom_sent'] =
    'Персональное письмо отправлено';

$string['admin_event_digital_purchase_created'] =
    'Создана попытка цифровой оплаты';

$string['admin_event_digital_purchase_paid'] =
    'Цифровая оплата подтверждена';

$string['admin_event_digital_purchase_failed'] =
    'Ошибка цифровой оплаты';

$string['admin_event_digital_purchase_cancelled'] =
    'Попытка оплаты отменена';

$string['admin_event_digital_link_resent'] =
    'Письмо с цифровым доступом отправлено повторно';

$string['admin_event_digital_token_regenerated'] =
    'Ссылка для скачивания обновлена';

$string['admin_event_digital_token_extended'] =
    'Срок действия ссылки продлён';

$string['admin_event_user_suspended'] =
    'Профиль Moodle приостановлен';

$string['admin_event_user_reactivated'] =
    'Профиль Moodle восстановлен';

$string['crm_help_title'] =
    'Справочный центр CRM';

$string['crm_help_subtitle'] =
    'Познакомьтесь с возможностями CRM CampusFR и быстро находите ответы на свои вопросы.';

$string['crm_help_search_placeholder'] =
    'Поиск по документации…';

$string['crm_help_search_results'] =
    'Результаты поиска по запросу «{$a}»';

$string['crm_help_no_results'] =
    'По вашему запросу ничего не найдено.';

$string['crm_help_article_count'] =
    '{$a} статей';

$string['crm_help_category_getting_started'] =
    'Начало работы';

$string['crm_help_category_getting_started_desc'] =
    'Познакомьтесь с CRM и быстро начните эффективно работать.';

$string['crm_help_category_daily_work'] =
    'Ежедневная работа';

$string['crm_help_category_daily_work_desc'] =
    'Основные инструменты для ежедневной работы с CRM.';

$string['crm_help_category_users'] =
    'Пользователи CRM';

$string['crm_help_category_users_desc'] =
    'Поиск пользователей, профили, фильтры, сегменты, теги и действия.';

$string['crm_help_category_digital'] =
    'Цифровые покупки';

$string['crm_help_category_digital_desc'] =
    'Платежи, доступы, электронные письма, ссылки для скачивания и обработка проблем.';

$string['crm_help_category_automation'] =
    'Автоматизации';

$string['crm_help_category_automation_desc'] =
    'Создание и понимание правил автоматизации CRM.';

$string['crm_help_category_intelligence'] =
    'CRM Intelligence';

$string['crm_help_category_intelligence_desc'] =
    'Оценки, риски, возможности, рекомендации и приоритеты.';

$string['crm_help_category_shortcuts'] =
    'Горячие клавиши';

$string['crm_help_category_shortcuts_desc'] =
    'Экономьте время с помощью Command Center и сочетаний клавиш.';

$string['crm_help_category_developer'] =
    'Документация разработчика';

$string['crm_help_category_developer_desc'] =
    'Внутренняя архитектура, соглашения и расширение CRM.';

$string['crm_help_article_overview_title'] =
    'Знакомство с CRM CampusFR';

$string['crm_help_article_overview_summary'] =
    'Обзор Dashboard, Command Center и основных модулей CRM.';

$string['crm_help_article_overview_content'] =
    '<p>CRM CampusFR объединяет пользователей, подписки, цифровые покупки, автоматизации и инструменты интеллектуального анализа.</p>';

$string['crm_help_article_dashboard_periods_title'] =
    'Использование периодов Dashboard';

$string['crm_help_article_dashboard_periods_summary'] =
    'Переключайтесь между «Сегодня», «Эта неделя» и «Этот месяц».';

$string['crm_help_article_dashboard_periods_content'] =
    '<p>При смене периода все показатели Dashboard автоматически пересчитываются для выбранного интервала.</p>';

$string['crm_help_article_user_filters_title'] =
    'Фильтрация пользователей с помощью CRM Intelligence';

$string['crm_help_article_user_filters_summary'] =
    'Просматривайте горячих лидов, пользователей группы риска, VIP и возможности.';

$string['crm_help_article_user_filters_content'] =
    '<p>Интеллектуальные фильтры позволяют сразу открыть сегменты, обнаруженные движком CRM Intelligence.</p>';

$string['crm_help_article_digital_issues_title'] =
    'Обработка проблем с цифровыми платежами';

$string['crm_help_article_digital_issues_summary'] =
    'Разберитесь с платежами Pending, Failed, Cancelled и проблемами доступа.';

$string['crm_help_article_digital_issues_content'] =
    '<p>Неподтверждённый платёж никогда не должен предоставлять доступ. Администратор может связаться с клиентом или отменить попытку оплаты.</p>';

$string['crm_help_article_shortcuts_title'] =
    'Использование Command Center';

$string['crm_help_article_shortcuts_summary'] =
    'Быстро находите и запускайте основные действия CRM.';

$string['crm_help_article_shortcuts_content'] =
    '<p>Нажмите Ctrl или Cmd + K, чтобы мгновенно открыть Command Center.</p>';

$string['crm_help_article_developer_architecture_title'] =
    'Архитектура CRM';

$string['crm_help_article_developer_architecture_summary'] =
    'Понять устройство repositories, services, renderers и правила безопасности.';

$string['crm_help_article_developer_architecture_content'] =
    '<p>SQL находится только в repositories, бизнес-логика — в services, а отображение — в renderers.</p>';

$string['crm_help_all_categories'] = 'Все категории';
$string['crm_help_category_empty'] =
    'В этой категории пока нет статей.';
$string['crm_help_read_article'] = 'Читать статью';
$string['crm_help_home'] = 'Справочный центр';
$string['crm_help_article_navigation'] =
    'Навигация по документации';

$string['crm_help_article_not_found'] =
    'Запрошенная статья не найдена.';
$string['crm_help_article_read_error'] =
    'Не удалось прочитать содержимое статьи.';
$string['crm_help_article_content_missing'] =
    'Не найдено содержимое статьи «{$a}».';
$string['crm_help_content_directory_missing'] =
    'Каталог документации CRM не найден.';

$string['crm_context_help_trigger'] =
    'Помощь по этой странице';

$string['crm_context_help_title'] =
    'Нужна помощь?';

$string['crm_context_help_description'] =
    'Эти статьи относятся к странице, которую вы сейчас просматриваете.';

$string['crm_context_help_empty'] =
    'Для этой страницы пока нет справочных статей.';

$string['crm_context_help_open_center'] =
    'Открыть справочный центр';

$string['admin_dashboard_description'] =
    'Управляйте CRM, отслеживайте показатели и обрабатывайте приоритетные задачи.';

$string['crm_users_explorer_description'] =
    'Ищите, фильтруйте и анализируйте пользователей CRM.';

$string['digital_purchases_help_description'] =
    'Просматривайте цифровые платежи, обрабатывайте проблемы и управляйте доступами.';

$string['crm_user_profile_help_description'] =
    'Просматривайте историю, подписки, покупки и рекомендации для этого пользователя.';

$string['crm_onboarding_title'] =
    'Начало работы с CRM';

$string['crm_onboarding_description'] =
    'Выполните эти шаги, чтобы познакомиться с основными инструментами и быстро освоить CRM.';

$string['crm_onboarding_progress_label'] =
    '{$a->completed} из {$a->total} этапов';

$string['crm_onboarding_mark_complete'] =
    'Отметить как выполненное';

$string['crm_onboarding_mark_incomplete'] =
    'Открыть снова';

$string['crm_onboarding_complete_title'] =
    'Знакомство с CRM завершено';

$string['crm_onboarding_complete_desc'] =
    'Вы познакомились с основными возможностями CRM CampusFR.';

$string['crm_onboarding_restart'] =
    'Начать checklist заново';

$string['crm_onboarding_restart_confirm'] =
    'Сбросить весь прогресс знакомства с CRM?';

$string['crm_onboarding_reset_success'] =
    'Прогресс знакомства с CRM сброшен.';

$string['crm_onboarding_invalid_step'] =
    'Неизвестный этап знакомства с CRM.';

$string['crm_onboarding_invalid_action'] =
    'Недопустимое действие onboarding.';

$string['crm_onboarding_step_dashboard_title'] =
    'Познакомиться с Dashboard';

$string['crm_onboarding_step_dashboard_desc'] =
    'Просмотреть показатели, приоритеты и задачи, требующие внимания.';

$string['crm_onboarding_step_command_center_title'] =
    'Попробовать Command Center';

$string['crm_onboarding_step_command_center_desc'] =
    'Быстро найти пользователя или административное действие.';

$string['crm_onboarding_step_users_title'] =
    'Изучить пользователей CRM';

$string['crm_onboarding_step_users_desc'] =
    'Использовать поиск и открыть полный профиль CRM.';

$string['crm_onboarding_step_intelligence_title'] =
    'Изучить фильтры Intelligence';

$string['crm_onboarding_step_intelligence_desc'] =
    'Просмотреть горячих лидов и профили группы риска.';

$string['crm_onboarding_step_digital_title'] =
    'Просмотреть цифровые покупки';

$string['crm_onboarding_step_digital_desc'] =
    'Разобраться со статусами платежей и доступными действиями.';

$string['crm_onboarding_step_automations_title'] =
    'Познакомиться с автоматизациями';

$string['crm_onboarding_step_automations_desc'] =
    'Просмотреть правила автоматизации и их историю.';

$string['crm_onboarding_step_help_title'] =
    'Открыть справочный центр';

$string['crm_onboarding_step_help_desc'] =
    'Найти функциональную документацию CRM.';

$string['crm_onboarding_step_architecture_title'] =
    'Изучить правила архитектуры';

$string['crm_onboarding_step_architecture_desc'] =
    'Понять технические соглашения плагина.';

$string['crm_help_guides_title'] =
    'Практические руководства';

$string['crm_help_guides_description'] =
    'Следуйте простым сценариям для выполнения основных задач CRM.';

$string['crm_help_guide_step_count'] =
    '{$a} этапов';

$string['crm_help_guide_progress'] =
    '{$a->completed} из {$a->total} этапов';

$string['crm_help_guide_complete_step'] =
    'Отметить этап как выполненный';

$string['crm_help_guide_reopen_step'] =
    'Открыть снова';

$string['crm_help_guide_complete'] =
    'Вы завершили это руководство.';

$string['crm_help_guide_reset'] =
    'Сбросить руководство';

$string['crm_help_guide_reset_confirm'] =
    'Сбросить прогресс этого руководства?';

$string['crm_help_guide_reset_success'] =
    'Прогресс руководства был сброшен.';

$string['crm_help_guide_not_found'] =
    'Запрошенное руководство не найдено.';

$string['crm_help_guide_step_not_found'] =
    'Этот этап руководства не найден.';

$string['crm_help_guide_invalid_action'] =
    'Недопустимое действие для руководства.';

$string['crm_help_guide_dashboard_title'] =
    'Начало работы с Dashboard';

$string['crm_help_guide_dashboard_desc'] =
    'Разберитесь с показателями, приоритетами и задачами, требующими внимания.';

$string['crm_help_guide_dashboard_period_title'] =
    'Выбрать период';

$string['crm_help_guide_dashboard_period_desc'] =
    'Переключайтесь между «Сегодня», «Эта неделя» и «Этот месяц».';

$string['crm_help_guide_dashboard_kpis_title'] =
    'Изучить показатели';

$string['crm_help_guide_dashboard_kpis_desc'] =
    'Проанализируйте новых пользователей, подписки, покупки и выручку.';

$string['crm_help_guide_dashboard_issues_title'] =
    'Проверить задачи, требующие внимания';

$string['crm_help_guide_dashboard_issues_desc'] =
    'Откройте очереди платежей или доступов, требующих действий администратора.';

$string['crm_help_guide_dashboard_priority_title'] =
    'Открыть приоритетный профиль';

$string['crm_help_guide_dashboard_priority_desc'] =
    'Изучите объяснение оценки и выберите подходящее действие.';

$string['crm_help_guide_open_dashboard'] =
    'Открыть Dashboard';

$string['crm_help_guide_digital_title'] =
    'Обработать цифровой платёж';

$string['crm_help_guide_digital_desc'] =
    'Проверить, связаться с покупателем или отменить попытку оплаты.';

$string['crm_help_guide_digital_open_title'] =
    'Открыть очередь платежей';

$string['crm_help_guide_digital_open_desc'] =
    'Начните с платежей со статусом pending или failed.';

$string['crm_help_guide_digital_verify_title'] =
    'Проверить фактический статус';

$string['crm_help_guide_digital_verify_desc'] =
    'Перед любым действием убедитесь, что платёж не был подтверждён.';

$string['crm_help_guide_digital_contact_title'] =
    'Связаться с покупателем';

$string['crm_help_guide_digital_contact_desc'] =
    'Используйте заранее заполненное сообщение, чтобы предложить помощь.';

$string['crm_help_guide_digital_cancel_title'] =
    'Отменить попытку оплаты';

$string['crm_help_guide_digital_cancel_desc'] =
    'Отменяйте только ненужные попытки со статусом pending или failed.';

$string['crm_help_guide_open_pending'] =
    'Показать платежи pending';

$string['crm_help_guide_hot_lead_title'] =
    'Проанализировать горячего лида';

$string['crm_help_guide_hot_lead_desc'] =
    'Разберитесь с оценкой и выберите лучшее следующее действие.';

$string['crm_help_guide_hot_lead_open_title'] =
    'Открыть сегмент';

$string['crm_help_guide_hot_lead_open_desc'] =
    'Показать список горячих лидов.';

$string['crm_help_guide_hot_lead_score_title'] =
    'Изучить оценку';

$string['crm_help_guide_hot_lead_score_desc'] =
    'Просмотрите факторы, которые повысили потенциал лида.';

$string['crm_help_guide_hot_lead_history_title'] =
    'Изучить timeline';

$string['crm_help_guide_hot_lead_history_desc'] =
    'Проверьте недавние покупки, подписки и взаимодействия.';

$string['crm_help_guide_hot_lead_action_title'] =
    'Выбрать действие';

$string['crm_help_guide_hot_lead_action_desc'] =
    'Связывайтесь с потенциальным клиентом только тогда, когда это оправдано контекстом.';

$string['crm_help_guide_open_hot_leads'] =
    'Показать горячих лидов';

$string['crm_help_guide_command_title'] =
    'Освоить Command Center';

$string['crm_help_guide_command_desc'] =
    'Быстро выполняйте поиск и навигацию по CRM.';

$string['crm_help_guide_command_open_title'] =
    'Открыть Command Center';

$string['crm_help_guide_command_open_desc'] =
    'Используйте Ctrl или Cmd + K.';

$string['crm_help_guide_command_search_title'] =
    'Найти объект';

$string['crm_help_guide_command_search_desc'] =
    'Найдите пользователя, покупку, подписку или продукт.';

$string['crm_help_guide_command_keyboard_title'] =
    'Навигация с клавиатуры';

$string['crm_help_guide_command_keyboard_desc'] =
    'Используйте стрелки, Enter и Escape.';

$string['crm_help_guide_command_favorites_title'] =
    'Использовать избранное и недавние команды';

$string['crm_help_guide_command_favorites_desc'] =
    'Быстро находите часто используемые команды.';

$string['crm_help_guide_profile_title'] =
    'Разобраться с профилем пользователя';

$string['crm_help_guide_profile_desc'] =
    'Изучите всю доступную информацию перед принятием решения.';

$string['crm_help_guide_profile_identity_title'] =
    'Проверить данные пользователя';

$string['crm_help_guide_profile_identity_desc'] =
    'Проверьте контактные данные и статус аккаунта.';

$string['crm_help_guide_profile_timeline_title'] =
    'Изучить timeline';

$string['crm_help_guide_profile_timeline_desc'] =
    'Восстановите важные события в хронологическом порядке.';

$string['crm_help_guide_profile_intelligence_title'] =
    'Разобраться с CRM Intelligence';

$string['crm_help_guide_profile_intelligence_desc'] =
    'Изучите оценку, сегмент и рекомендации.';

$string['crm_help_guide_profile_action_title'] =
    'Выполнить действие';

$string['crm_help_guide_profile_action_desc'] =
    'Выберите быстрое действие, подходящее текущему контексту.';
$string['crm_context_help_articles_title'] =
    'Рекомендуемые статьи';

$string['crm_context_help_guides_title'] =
    'Практические руководства';
$string['command_help_center_title'] =
    'Открыть справочный центр CRM';

$string['command_help_center_subtitle'] =
    'Документация, руководства и контекстная помощь';
$string['crm_help_diagnostics_title'] =
    'Диагностика справочного центра';

$string['crm_help_diagnostics_description'] =
    'Проверьте согласованность статей, переводов, Markdown-файлов, категорий и руководств CRM.';

$string['crm_help_diagnostics_successes'] =
    'Успешные проверки';

$string['crm_help_diagnostics_warnings'] =
    'Предупреждения';

$string['crm_help_diagnostics_errors'] =
    'Ошибки';

$string['crm_help_diagnostics_valid'] =
    'Справочный центр корректен и готов к использованию.';

$string['crm_help_diagnostics_invalid'] =
    'Справочный центр содержит ошибки, которые необходимо исправить.';

$string['crm_help_open_diagnostics'] =
    'Проверить документацию';

$string['crm_user_sort_name_asc'] = 'Имя: от А до Я';
$string['crm_user_sort_name_desc'] = 'Имя: от Я до А';
$string['crm_user_sort_score_desc'] = 'Наивысшая оценка CRM';
$string['crm_user_sort_risk_desc'] = 'Наивысший риск';
$string['crm_user_sort_last_access_desc'] = 'Недавняя активность';
$string['crm_user_sort_created_desc'] = 'Недавняя регистрация';

$string['crm_user_account_status_all'] = 'Все аккаунты';
$string['crm_user_account_status_active'] = 'Активные аккаунты';
$string['crm_user_account_status_suspended'] = 'Приостановленные аккаунты';

$string['crm_user_account_active'] = 'Активен';
$string['crm_user_account_suspended'] = 'Приостановлен';
$string['crm_user_account_status'] = 'Статус аккаунта';

$string['crm_user_explorer_result_count'] = 'пользователей';
$string['crm_user_explorer_active_filters'] = 'Активных фильтров: {$a}';
$string['crm_user_explorer_clear_filters'] = 'Сбросить фильтры';
$string['crm_user_explorer_search_label'] = 'Поиск';
$string['crm_user_country_all'] = 'Все страны';
$string['crm_user_tag_all'] = 'Все теги';
$string['crm_user_sort_label'] = 'Сортировка';
$string['crm_user_per_page'] = 'На странице';
$string['crm_user_apply_filters'] = 'Применить фильтры';


$string['crm_user_explorer_empty_title'] = 'Пользователи не найдены';
$string['crm_user_explorer_empty_description'] =
    'Измените фильтры или поисковый запрос, чтобы увидеть другие профили.';

$string['crm_user_score_level_unknown'] = 'Не проанализирован';
$string['crm_user_score_level_very_low'] = 'Очень низкий';
$string['crm_user_score_level_low'] = 'Низкий';
$string['crm_user_score_level_medium'] = 'Средний';
$string['crm_user_score_level_high'] = 'Высокий';
$string['crm_user_score_level_excellent'] = 'Отличный';
$string['country'] = 'Страна';
$string['crm_user_tags'] = 'Теги';

$string['crm_user_column_user'] = 'Пользователь';
$string['crm_user_column_tags'] = 'Теги';
$string['crm_user_column_score'] = 'Оценка CRM';
$string['crm_user_column_risk'] = 'Риск';
$string['crm_user_column_intelligence'] = 'Intelligence';
$string['crm_user_column_subscriptions'] = 'Подписки';
$string['crm_user_column_purchases'] = 'Цифровые покупки';
$string['crm_user_column_country'] = 'Страна';
$string['crm_user_column_registered'] = 'Регистрация';
$string['crm_user_column_last_access'] = 'Последняя активность';

$string['crm_user_configure_columns'] = 'Настроить столбцы';
$string['crm_user_columns_saved'] = 'Настройки столбцов сохранены.';
$string['crm_user_columns_reset'] = 'Столбцы по умолчанию восстановлены.';

$string['crm_user_save_view'] = 'Сохранить представление';
$string['crm_user_view_name_placeholder'] = 'Название представления';
$string['crm_user_view_name_required'] =
    'Необходимо указать название представления.';
$string['crm_user_view_limit_reached'] =
    'Нельзя сохранить более {$a} представлений.';
$string['crm_user_view_saved'] = 'Представление сохранено.';
$string['crm_user_view_deleted'] = 'Представление удалено.';
$string['crm_user_view_delete'] = 'Удалить представление';
$string['crm_user_view_delete_confirm'] =
    'Навсегда удалить это сохранённое представление?';

$string['crm_user_explorer_invalid_action'] =
    'Недопустимое действие User Explorer.';

$string['crm_user_advanced_filters'] = 'Расширенные фильтры';
$string['crm_user_score_min'] = 'Минимальная оценка';
$string['crm_user_score_max'] = 'Максимальная оценка';
$string['crm_user_risk_min'] = 'Минимальный риск';
$string['crm_user_risk_max'] = 'Максимальный риск';

$string['crm_user_presence_all'] = 'Все';
$string['crm_user_presence_yes'] = 'Да';
$string['crm_user_presence_no'] = 'Нет';

$string['crm_user_has_subscription'] = 'Есть подписка';
$string['crm_user_has_purchase'] = 'Есть цифровая покупка';

$string['crm_user_activity_filter'] = 'Последняя активность';
$string['crm_user_activity_all'] = 'Любая активность';
$string['crm_user_activity_7days'] = 'За последние 7 дней';
$string['crm_user_activity_30days'] = 'За последние 30 дней';
$string['crm_user_activity_90days'] = 'За последние 90 дней';
$string['crm_user_activity_never'] = 'Никогда не входил';

$string['crm_user_export_csv'] = 'Экспорт в CSV';

$string['crm_inbox_navigation'] = 'CRM Inbox';
$string['crm_inbox_title'] = 'Входящие CRM';
$string['crm_inbox_foundation_ready'] =
    'Основа CRM Inbox установлена. Почтовый аккаунт пока не подключён.';
$string['crm_inbox_no_account_configured'] =
    'Настройка OVH и синхронизация IMAP будут добавлены на следующих этапах.';

$string['privacy:metadata:inbox'] =
    'CRM Inbox хранит сообщения службы поддержки и их возможные связи с пользователями CampusFR.';
$string['privacy:metadata:inbox:email'] =
    'Адрес электронной почты участника сообщения.';
$string['privacy:metadata:inbox:name'] =
    'Отображаемое имя участника сообщения.';
$string['privacy:metadata:inbox:message'] =
    'Содержимое полученного или отправленного сообщения.';
$string['privacy:metadata:inbox:userid'] =
    'Пользователь Moodle, при необходимости связанный с контактом Inbox.';

$string['crm_inbox_credential_missing'] =
    'Учётные данные Inbox «{$a}» отсутствуют в конфигурации Moodle.';
$string['crm_inbox_credential_invalid'] =
    'Конфигурация учётных данных Inbox «{$a}» недействительна.';
$string['crm_inbox_credential_field_missing'] =
    'Поле «{$a->field}» отсутствует в учётных данных Inbox «{$a->key}».';

$string['crm_inbox_account_disabled'] =
    'Аккаунт CRM Inbox отключён.';
$string['crm_inbox_account_no_credential'] =
    'Для этого аккаунта Inbox не указана ссылка на учётные данные.';
$string['crm_inbox_account_not_found'] =
    'Запрошенный аккаунт CRM Inbox не найден.';

$string['crm_inbox_imap_configuration_missing'] =
    'Конфигурация IMAP для аккаунта Inbox отсутствует.';
$string['crm_inbox_imap_field_missing'] =
    'Обязательное поле IMAP «{$a}» отсутствует.';
$string['crm_inbox_imap_extension_missing'] =
    'Расширение PHP IMAP не установлено или не активировано на сервере.';

$string['task_sync_crm_inbox'] =
    'Синхронизация CRM Inbox';
$string['task_reconcile_crm_inbox_contacts'] =
    'Связывание контактов CRM Inbox с пользователями';

$string['crm_inbox_empty'] =
    'Нет разговоров, соответствующих этим критериям.';
$string['crm_inbox_search'] = 'Поиск';
$string['crm_inbox_status'] = 'Статус';
$string['crm_inbox_priority'] = 'Приоритет';
$string['crm_inbox_assignment'] = 'Назначение';
$string['crm_inbox_assignment_mine'] = 'Мои разговоры';
$string['crm_inbox_assignment_unassigned'] = 'Не назначено';
$string['crm_inbox_assignment_team'] = 'Назначено команде';

$string['crm_inbox_status_open'] = 'Открыто';
$string['crm_inbox_status_pending'] = 'В ожидании';
$string['crm_inbox_status_resolved'] = 'Решено';
$string['crm_inbox_status_closed'] = 'Закрыто';
$string['crm_inbox_status_spam'] = 'Спам';

$string['crm_inbox_priority_low'] = 'Низкий';
$string['crm_inbox_priority_normal'] = 'Обычный';
$string['crm_inbox_priority_high'] = 'Высокий';
$string['crm_inbox_priority_urgent'] = 'Срочный';

$string['crm_inbox_unknown_contact'] =
    'Неизвестный контакт';
$string['crm_inbox_no_subject'] = 'Без темы';
$string['crm_inbox_unread_count'] =
    'Непрочитанных сообщений: {$a}';
$string['crm_inbox_back'] = 'Вернуться во входящие';
$string['crm_inbox_matched_user'] =
    'Пользователь CampusFR: {$a}';
$string['crm_inbox_external_contact'] =
    'Внешний незарегистрированный контакт';

$string['crm_inbox_reply'] = 'Ответить';
$string['crm_inbox_save_draft'] =
    'Сохранить черновик';
$string['crm_inbox_send'] = 'Отправить';
$string['crm_inbox_draft_saved'] =
    'Черновик сохранён.';
$string['crm_inbox_reply_sent'] =
    'Ответ отправлен.';
$string['crm_inbox_send_failed'] =
    'Не удалось отправить ответ: {$a}';
$string['crm_inbox_invalid_recipient'] =
    'Получатель этого разговора недействителен.';

$string['crm_inbox_direction_inbound'] = 'Получено';
$string['crm_inbox_direction_outbound'] = 'Отправлено';
$string['crm_inbox_message_status_draft'] = 'Черновик';

$string['crm_inbox_thread_not_found'] =
    'Этот разговор Inbox не найден.';
$string['crm_inbox_archive'] = 'Архивировать';
$string['crm_inbox_move_to_trash'] =
    'Переместить в корзину';
$string['crm_inbox_trash_confirm'] =
    'Переместить этот разговор в корзину почтового провайдера?';
$string['crm_inbox_moved_to_trash'] =
    'Разговор перемещён в корзину.';
$string['crm_inbox_deleted_locally'] =
    'Разговор удалён из CRM.';
$string['crm_inbox_folder_not_configured'] =
    'Папка провайдера «{$a}» не настроена.';

$string['crm_timeline_inbox_received'] =
    'Письмо получено в CRM Inbox';
$string['crm_timeline_inbox_sent'] =
    'Ответ отправлен из CRM Inbox';

$string['command_action_inbox_title'] =
    'Открыть CRM Inbox';
$string['command_action_inbox_subtitle'] =
    'Просмотр и обработка обращений службы поддержки CampusFR.';

$string['task_download_crm_inbox_attachments'] =
    'Загрузка вложений CRM Inbox';

$string['crm_inbox_diagnostics'] =
    'Диагностика CRM Inbox';
$string['crm_inbox_diagnostics_metrics'] =
    'Показатели Inbox';

$string['crm_help_category_inbox'] =
    'CRM Inbox';
$string['crm_help_category_inbox_desc'] =
    'Письма поддержки, разговоры, контакты и назначения.';

$string['crm_help_article_inbox_title'] =
    'Работа с CRM Inbox';
$string['crm_help_article_inbox_summary'] =
    'Получение, связывание, назначение и обработка писем поддержки CampusFR.';

$string['crm_help_guide_inbox_title'] =
    'Обработка разговора Inbox';
$string['crm_help_guide_inbox_desc'] =
    'Полный процесс обработки обращения в поддержку.';
$string['crm_help_guide_inbox_open_title'] =
    'Открыть входящие';
$string['crm_help_guide_inbox_open_desc'] =
    'Просмотрите новые и неназначенные разговоры.';
$string['crm_help_guide_open_inbox'] =
    'Открыть CRM Inbox';
$string['crm_help_guide_inbox_contact_title'] =
    'Определить контакт';
$string['crm_help_guide_inbox_contact_desc'] =
    'Проверьте, является ли контакт внешним или связанным с пользователем CampusFR.';
$string['crm_help_guide_inbox_assign_title'] =
    'Назначить разговор';
$string['crm_help_guide_inbox_assign_desc'] =
    'Назначьте обращение администратору или команде.';
$string['crm_help_guide_inbox_reply_title'] =
    'Подготовить и отправить ответ';
$string['crm_help_guide_inbox_reply_desc'] =
    'Сохраните черновик или отправьте ответ непосредственно из CRM.';
$string['crm_help_guide_inbox_close_title'] =
    'Решить и архивировать';
$string['crm_help_guide_inbox_close_desc'] =
    'Отметьте разговор как решённый или архивируйте его.';

$string['crm_inbox_account_validation_failed'] =
    'Конфигурация аккаунта Inbox недействительна: {$a}';

$string['crm_inbox_validation_invalid_email'] =
    'Адрес электронной почты аккаунта Inbox отсутствует или недействителен.';
$string['crm_inbox_validation_provider_missing'] =
    'Провайдер аккаунта Inbox не указан.';
$string['crm_inbox_validation_smtp_missing'] =
    'Конфигурация SMTP аккаунта Inbox отсутствует.';
$string['crm_inbox_validation_sync_missing'] =
    'Конфигурация синхронизации Inbox отсутствует.';
$string['crm_inbox_validation_host_missing'] =
    'Хост {$a} не указан.';
$string['crm_inbox_validation_port_invalid'] =
    'Порт {$a} недействителен.';
$string['crm_inbox_validation_encryption_invalid'] =
    'Настройка шифрования {$a} недействительна.';
$string['crm_inbox_validation_unencrypted'] =
    'Соединение {$a} не использует шифрование.';
$string['crm_inbox_validation_batchsize'] =
    'Размер пакета Inbox должен быть от 1 до 200.';
$string['crm_inbox_validation_interval'] =
    'Интервал синхронизации должен быть от 5 до 1440 минут.';
$string['crm_inbox_validation_inbox_folder_missing'] =
    'Основная папка IMAP не настроена; по умолчанию будет использована INBOX.';
$string['crm_inbox_validation_folders_missing'] =
    'Конфигурация папок Inbox отсутствует.';
$string['crm_inbox_validation_folder_missing'] =
    'Папка Inbox «{$a}» ещё не определена.';

$string['crm_inbox_folder_discovery_success'] =
    'Обнаружено папок: {$a->count}. Входящие: {$a->inbox}; отправленные: {$a->sent}; корзина: {$a->trash}; архив: {$a->archive}; черновики: {$a->drafts}.';
$string['crm_inbox_folder_discovery_missing'] =
    'Некоторые обязательные папки не найдены: {$a}.';

$string['crm_inbox_remote_image_blocked'] =
    'Удалённое изображение заблокировано для защиты конфиденциальности.';
$string['privacy:metadata:inbox_contact'] =
    'Внешние или связанные контакты, используемые в CRM Inbox.';
$string['privacy:metadata:inbox_contact:displayname'] =
    'Отображаемое имя контакта.';
$string['privacy:metadata:inbox_contact:primaryemail'] =
    'Основной адрес электронной почты контакта.';
$string['privacy:metadata:inbox_contact:normalizedemail'] =
    'Нормализованный адрес электронной почты для сопоставления контакта.';
$string['privacy:metadata:inbox_contact:matcheduserid'] =
    'Идентификатор связанного пользователя Moodle.';
$string['privacy:metadata:inbox_contact:matchstatus'] =
    'Текущий статус связи с пользователем.';
$string['privacy:metadata:inbox_contact:matchsource'] =
    'Источник, использованный для сопоставления контакта.';
$string['privacy:metadata:inbox_contact:matchconfidence'] =
    'Уровень достоверности сопоставления контакта.';
$string['privacy:metadata:inbox_contact:lastmatchedat'] =
    'Дата последней операции сопоставления.';

$string['privacy:metadata:inbox_thread'] =
    'Разговоры, обрабатываемые в CRM Inbox.';
$string['privacy:metadata:inbox_thread:contactid'] =
    'Основной контакт разговора.';
$string['privacy:metadata:inbox_thread:subject'] =
    'Тема разговора.';
$string['privacy:metadata:inbox_thread:assigneduserid'] =
    'Администратор, которому назначен разговор.';
$string['privacy:metadata:inbox_thread:status'] =
    'Статус разговора.';
$string['privacy:metadata:inbox_thread:priority'] =
    'Приоритет разговора.';
$string['privacy:metadata:inbox_thread:lastmessageat'] =
    'Дата последнего сообщения.';

$string['privacy:metadata:inbox_message'] =
    'Сообщения, полученные или отправленные через CRM Inbox.';
$string['privacy:metadata:inbox_message:threadid'] =
    'Разговор, к которому относится сообщение.';
$string['privacy:metadata:inbox_message:direction'] =
    'Направление сообщения.';
$string['privacy:metadata:inbox_message:subject'] =
    'Тема сообщения.';
$string['privacy:metadata:inbox_message:bodytext'] =
    'Текстовая версия сообщения.';
$string['privacy:metadata:inbox_message:bodyhtml'] =
    'HTML-версия сообщения.';
$string['privacy:metadata:inbox_message:receivedat'] =
    'Дата получения сообщения.';
$string['privacy:metadata:inbox_message:sentat'] =
    'Дата отправки сообщения.';
$string['privacy:metadata:inbox_message:createdby'] =
    'Администратор, создавший исходящее сообщение.';

$string['privacy:metadata:inbox_participant'] =
    'Участники сообщений CRM Inbox.';
$string['privacy:metadata:inbox_participant:messageid'] =
    'Связанное сообщение.';
$string['privacy:metadata:inbox_participant:contactid'] =
    'Контакт, связанный с участником.';
$string['privacy:metadata:inbox_participant:participanttype'] =
    'Тип участника: отправитель, получатель, копия или адрес для ответа.';
$string['privacy:metadata:inbox_participant:email'] =
    'Адрес электронной почты участника.';
$string['privacy:metadata:inbox_participant:displayname'] =
    'Отображаемое имя участника.';

$string['privacy:metadata:inbox_attachment'] =
    'Вложения сообщений CRM Inbox.';
$string['privacy:metadata:inbox_attachment:messageid'] =
    'Сообщение, содержащее вложение.';
$string['privacy:metadata:inbox_attachment:filename'] =
    'Имя файла вложения.';
$string['privacy:metadata:inbox_attachment:mimetype'] =
    'MIME-тип вложения.';
$string['privacy:metadata:inbox_attachment:filesize'] =
    'Размер файла вложения.';

$string['privacy:path:inbox'] =
    'CRM Inbox';
$string['crm_inbox_match_status'] =
    'Связь с пользователем';
$string['crm_inbox_match_matched'] =
    'Пользователь связан';
$string['crm_inbox_match_unmatched'] =
    'Внешний контакт';
$string['crm_inbox_match_ambiguous'] =
    'Неоднозначная связь';
$string['crm_inbox_team'] =
    'Команда';
$string['crm_inbox_unread_only'] =
    'Только непрочитанные';
$string['crm_inbox_per_page'] =
    'На странице';

$string['task_cleanup_crm_inbox'] =
    'Очистка устаревших данных CRM Inbox';

$string['task_cleanup_crm_inbox_ai_results'] =
    'Очистка устаревших результатов ИИ CRM Inbox';

$string['crm_inbox_ai_empty_content'] =
    'Содержимое для анализа отсутствует.';

$string['crm_inbox_ai_empty_conversation'] =
    'В этом разговоре нет сообщений, доступных для анализа.';

$string['crm_inbox_ai_language_unknown'] =
    'Язык не определён';

$string['crm_inbox_ai_urgency_low'] =
    'Низкая срочность';
$string['crm_inbox_ai_urgency_normal'] =
    'Обычная срочность';
$string['crm_inbox_ai_urgency_high'] =
    'Высокая срочность';
$string['crm_inbox_ai_urgency_critical'] =
    'Критическая срочность';

$string['crm_inbox_ai_category_payment'] =
    'Оплата';
$string['crm_inbox_ai_category_access'] =
    'Доступ';
$string['crm_inbox_ai_category_subscription'] =
    'Подписка';
$string['crm_inbox_ai_category_technical'] =
    'Техническая проблема';
$string['crm_inbox_ai_category_course_content'] =
    'Учебный контент';
$string['crm_inbox_ai_category_account'] =
    'Аккаунт пользователя';
$string['crm_inbox_ai_category_refund'] =
    'Возврат средств';
$string['crm_inbox_ai_category_billing'] =
    'Выставление счетов';
$string['crm_inbox_ai_category_commercial'] =
    'Коммерческий запрос';
$string['crm_inbox_ai_category_feedback'] =
    'Отзыв пользователя';
$string['crm_inbox_ai_category_spam'] =
    'Спам';
$string['crm_inbox_ai_category_other'] =
    'Другое';

$string['crm_inbox_ai_reply_requires_review'] =
    'Это предложение необходимо проверить и подтвердить перед отправкой.';

$string['crm_inbox_ai_tone_professional'] =
    'Профессиональный';
$string['crm_inbox_ai_tone_friendly'] =
    'Дружелюбный';
$string['crm_inbox_ai_tone_empathetic'] =
    'Эмпатичный';
$string['crm_inbox_ai_tone_concise'] =
    'Краткий';

$string['crm_inbox_ai_translation_failed'] =
    'Не удалось создать перевод.';

$string['crm_inbox_ai_reply_unavailable'] =
    'Предложение ответа недоступно у текущего провайдера.';

$string['crm_inbox_ai_context_partial'] =
    'Некоторые данные CRM не удалось добавить в контекст.';

$string['crm_inbox_ai_panel_title'] =
    'ИИ-помощник';
$string['crm_inbox_ai_panel_description'] =
    'Анализ и предложения для помощи администратору.';
$string['crm_inbox_ai_human_review_badge'] =
    'Обязательная проверка человеком';
$string['crm_inbox_ai_permission_required'] =
    'У вас нет права использовать ИИ-помощника.';
$string['crm_inbox_ai_no_analysis'] =
    'Для этого разговора ещё не выполнялся ИИ-анализ.';
$string['crm_inbox_ai_analyse'] =
    'Проанализировать разговор';
$string['crm_inbox_ai_suggest_reply'] =
    'Предложить ответ';
$string['crm_inbox_ai_reply_language'] =
    'Язык ответа';
$string['crm_inbox_ai_reply_tone'] =
    'Тон ответа';
$string['crm_inbox_ai_analysis_completed'] =
    'ИИ-анализ завершён.';
$string['crm_inbox_ai_detected_language'] =
    'Определённый язык';
$string['crm_inbox_ai_urgency'] =
    'Срочность';
$string['crm_inbox_ai_category'] =
    'Категория';
$string['crm_inbox_ai_summary'] =
    'Краткое содержание';
$string['crm_inbox_ai_key_points'] =
    'Ключевые моменты';
$string['crm_inbox_ai_pending_questions'] =
    'Нерешённые вопросы';
$string['crm_inbox_ai_customer_requests'] =
    'Запросы клиента';
$string['crm_inbox_ai_suggested_reply'] =
    'Предлагаемый ответ';
$string['crm_inbox_ai_confidence'] =
    'Уверенность: {$a}%';

$string['crm_inbox_ai_quota_exceeded'] =
    'Дневной лимит ИИ-помощника исчерпан.';
$string['task_analyse_crm_inbox'] =
    'Анализ разговоров CRM Inbox';
$string['crm_inbox_ai_diagnostics'] =
    'Диагностика ИИ CRM Inbox';
$string['crm_inbox_ai_diagnostic_table_ok'] =
    'Таблица результатов ИИ доступна.';
$string['crm_inbox_ai_diagnostic_table_missing'] =
    'Таблица результатов ИИ отсутствует.';
$string['crm_inbox_ai_diagnostic_fallback'] =
    'Локальный fallback-провайдер доступен.';
$string['crm_inbox_ai_diagnostic_orchestrator_ok'] =
    'Оркестратор ИИ успешно создаётся.';
$string['crm_inbox_ai_usage_today'] =
    'Использование сегодня';
$string['crm_inbox_ai_usage_global'] =
    'Общее использование: {$a->used} / {$a->limit}';
$string['crm_inbox_ai_usage_user'] =
    'Ваше использование: {$a->used} / {$a->limit}';
$string['crm_inbox_ai_failures_today'] =
    'Ошибок анализа сегодня: {$a}';

$string['crm_help_article_inbox_ai_title'] =
    'Работа с ИИ-помощником CRM Inbox';
$string['crm_help_article_inbox_ai_summary'] =
    'Анализ, резюме, перевод и подготовка ответов с обязательной проверкой человеком.';

$string['settings:inbox_ai_header'] =
    'ИИ-помощник CRM Inbox';

$string['settings:inbox_ai_header_desc'] =
    'Настройка ИИ-провайдера для анализа обращений службы поддержки.';

$string['settings:inbox_ai_openai_enabled'] =
    'Включить OpenAI';

$string['settings:inbox_ai_openai_enabled_desc'] =
    'При запросе анализа содержимое писем может передаваться в OpenAI.';

$string['settings:inbox_ai_openai_model'] =
    'Модель OpenAI';

$string['settings:inbox_ai_openai_model_desc'] =
    'Точный идентификатор модели OpenAI, разрешённой для CRM Inbox.';

$string['settings:inbox_ai_openai_endpoint'] =
    'Endpoint OpenAI';

$string['settings:inbox_ai_openai_endpoint_desc'] =
    'Endpoint, используемый для OpenAI Responses API.';

$string['settings:inbox_ai_openai_timeout'] =
    'Тайм-аут OpenAI';

$string['settings:inbox_ai_openai_max_output_tokens'] =
    'Максимальное количество выходных токенов';

$string['settings:inbox_ai_openai_store'] =
    'Разрешить удалённое хранение ответов';

$string['settings:inbox_ai_openai_store_desc'] =
    'Оставьте выключенным, если удалённое хранение не было отдельно проверено и одобрено.';

$string['settings:inbox_ai_include_crm_context'] =
    'Передавать проверенный контекст CRM';

$string['settings:inbox_ai_include_contact_email'] =
    'Передавать адрес электронной почты контакта';

$string['settings:inbox_ai_include_contact_email_desc'] =
    'По умолчанию отключено для минимизации персональных данных, передаваемых ИИ-провайдеру.';

$string['settings:inbox_ai_global_daily_limit'] =
    'Общий дневной лимит';

$string['settings:inbox_ai_user_daily_limit'] =
    'Дневной лимит на администратора';

$string['settings:inbox_ai_automatic_analysis'] =
    'Включить автоматический анализ';

$string['settings:inbox_ai_automatic_analysis_desc'] =
    'Включайте только после проверки стоимости, конфиденциальности и лимитов провайдера.';

$string['crm_inbox_ai_openai_enabled'] =
    'OpenAI включён.';

$string['crm_inbox_ai_openai_disabled'] =
    'OpenAI выключен.';

$string['crm_inbox_ai_openai_key_available'] =
    'Ключ OpenAI API доступен на сервере.';

$string['crm_inbox_ai_openai_key_missing'] =
    'Ключ OpenAI API отсутствует.';

$string['crm_inbox_ai_openai_model_configured'] =
    'Настроенная модель OpenAI: {$a}.';

$string['crm_inbox_ai_openai_model_missing'] =
    'Модель OpenAI не настроена.';

$string['crm_inbox_ai_data_transmission_notice'] =
    'Содержимое писем может передаваться настроенному ИИ-провайдеру для создания анализа или предложений.';

$string['crm_inbox_ai_provider_label'] =
    'Провайдер';

$string['crm_inbox_ai_model_label'] =
    'Модель';

$string['crm_inbox_ai_cache_hit'] =
    'Результат из кэша';

$string['crm_inbox_ai_cache_miss'] =
    'Новый анализ провайдера';

$string['crm_inbox_ai_force_refresh'] =
    'Обновить анализ';

$string['crm_inbox_ai_request_tokens'] =
    'Входные токены: {$a}';

$string['crm_inbox_ai_response_tokens'] =
    'Выходные токены: {$a}';

$string['crm_inbox_ai_total_tokens'] =
    'Всего токенов: {$a}';

$string['crm_inbox_ai_latency'] =
    'Время обработки: {$a} мс';

$string['crm_inbox_ai_validation_failed'] =
    'Результат ИИ не прошёл локальную проверку и не был показан как успешный анализ.';

$string['crm_inbox_ai_provider_unavailable'] =
    'Выбранный ИИ-провайдер недоступен.';

$string['crm_inbox_ai_provider_error'] =
    'ИИ-провайдер не смог выполнить анализ.';

$string['crm_inbox_ai_rate_limit'] =
    'Лимит запросов ИИ-провайдера исчерпан. Повторите попытку позже.';

$string['crm_inbox_ai_authentication_error'] =
    'ИИ-провайдер отклонил настроенные учётные данные.';

$string['crm_inbox_ai_privacy_notice'] =
    'Результаты ИИ являются только предложениями. Проверяйте весь текст перед использованием или отправкой.';

// Phase 6.5F — интеграция CRM Inbox.
$string['admin_event_inbox_message_received'] =
    'Письмо получено во входящих CRM';

$string['admin_event_inbox_reply_sent'] =
    'Ответ отправлен из входящих CRM';

$string['admin_event_inbox_thread_assigned'] =
    'Диалог во входящих CRM назначен';

$string['admin_event_inbox_thread_unassigned'] =
    'Назначение диалога во входящих CRM снято';

$string['admin_event_inbox_thread_status_changed'] =
    'Статус диалога во входящих CRM изменён';

$string['admin_event_inbox_thread_priority_changed'] =
    'Приоритет диалога во входящих CRM изменён';

$string['admin_event_inbox_ai_analysis_executed'] =
    'Выполнен ИИ-анализ диалога';

$string['admin_event_inbox_ai_reply_suggested'] =
    'Создана ИИ-подсказка для ответа';

// Phase 6.5F — Inbox в карточке пользователя.
$string['crm_user_inbox_section'] =
    'Входящие CRM';

$string['crm_user_inbox_badge'] =
    'Входящие';

$string['crm_user_inbox_badge_empty'] =
    'Нет диалогов';

$string['crm_user_inbox_badge_unread'] =
    'Непрочитанных писем: {$a}';

$string['crm_user_inbox_conversations'] =
    'Диалоги';

$string['crm_user_inbox_open_conversations'] =
    'Открытые';

$string['crm_user_inbox_unread'] =
    'Непрочитанные письма';

$string['crm_user_inbox_ai_suggestions'] =
    'ИИ-подсказки';

$string['crm_user_inbox_last_email'] =
    'Последнее письмо';

$string['crm_user_inbox_last_received'] =
    'Последнее полученное письмо';

$string['crm_user_inbox_last_sent'] =
    'Последний отправленный ответ';

$string['crm_user_inbox_recent_conversations'] =
    'Недавние диалоги';

$string['crm_user_inbox_no_conversations'] =
    'К этому пользователю пока не привязан ни один диалог Inbox.';

$string['crm_user_inbox_open_all'] =
    'Открыть диалоги во входящих';

$string['crm_user_inbox_unread_badge'] =
    'Непрочитанных: {$a}';

// Phase 6.5F — Inbox в центре команд.
$string['command_center_type_inbox_thread'] =
    'Диалог Inbox';

$string['command_center_type_inbox_contact'] =
    'Контакт Inbox';

$string['command_center_group_inbox_threads'] =
    'Диалоги Inbox';

$string['command_center_group_inbox_contacts'] =
    'Контакты Inbox';

$string['command_inbox_thread_status'] =
    'Статус: {$a}';

$string['command_inbox_thread_priority'] =
    'Приоритет: {$a}';

$string['command_inbox_thread_unread'] =
    'Непрочитанных: {$a}';

$string['command_inbox_contact_conversations'] =
    'Диалогов: {$a}';

$string['command_inbox_contact_unread'] =
    'Непрочитанных: {$a}';

$string['command_inbox_unknown_contact'] =
    'Неизвестный контакт Inbox';

$string['command_action_inbox_unassigned_title'] =
    'Открыть диалоги без ответственного';

$string['command_action_inbox_unassigned_subtitle'] =
    'Показать диалоги Inbox без назначенного администратора или команды.';

$string['command_action_inbox_urgent_title'] =
    'Открыть срочные диалоги';

$string['command_action_inbox_urgent_subtitle'] =
    'Показать диалоги Inbox со срочным приоритетом.';

$string['command_action_inbox_diagnostics_title'] =
    'Открыть диагностику Inbox';

$string['command_action_inbox_diagnostics_subtitle'] =
    'Проверить аккаунты, подключения, синхронизацию и ошибки Inbox.';

$string['command_action_inbox_ai_diagnostics_title'] =
    'Открыть диагностику ИИ Inbox';

$string['command_action_inbox_ai_diagnostics_subtitle'] =
    'Проверить ИИ-провайдер, модели, промпты, кэш и конфигурацию.';

$string['command_action_inbox_sync_title'] =
    'Синхронизировать Inbox';

$string['command_action_inbox_sync_subtitle'] =
    'Получить одну страницу новых писем для каждой настроенной папки.';

$string['command_confirm_inbox_sync'] =
    'Запустить ручную синхронизацию всех активных аккаунтов Inbox?';

$string['command_center_action_run'] =
    'Выполнить';

$string['command_inbox_sync_no_accounts'] =
    'Нет настроенных активных аккаунтов Inbox.';

$string['command_inbox_sync_success'] =
    'Синхронизация завершена: получено {$a->fetched}, создано {$a->created}, пропущено {$a->skipped}, ошибок {$a->errors}.';

$string['command_inbox_sync_has_more'] =
    'Доступны дополнительные письма. Они будут получены при следующем запуске.';

$string['command_inbox_sync_failed'] =
    'Ручная синхронизация Inbox завершилась ошибкой. Проверьте диагностику Inbox.';

// Phase 6.5F — Inbox в User Explorer и Intelligence.
$string['crm_user_column_inbox'] =
    'Входящие';

$string['crm_user_has_inbox'] =
    'Есть диалог Inbox';

$string['crm_user_has_inbox_unread'] =
    'Есть непрочитанные письма Inbox';

$string['crm_user_inbox_none'] =
    'Нет диалогов';

$string['crm_user_inbox_conversation_count'] =
    'Диалогов: {$a}';

$string['crm_user_inbox_open_count'] =
    'Открытых: {$a}';

$string['crm_user_inbox_unread_count'] =
    'Непрочитанных: {$a}';

$string['crm_user_inbox_urgent_count'] =
    'Срочных: {$a}';

$string['crm_intelligence_inbox_conversations'] =
    'Диалогов Inbox: {$a}';

$string['crm_intelligence_inbox_open'] =
    'Открытых: {$a}';

$string['crm_intelligence_inbox_unread'] =
    'Непрочитанных: {$a}';

$string['crm_intelligence_inbox_urgent'] =
    'Срочных: {$a}';

$string['crm_intelligence_inbox_open_link'] =
    'Открыть диалоги';

// Phase 6.5F — Help Center для Inbox.
$string['crm_help_article_inbox_diagnostics_title'] =
    'Диагностика CRM Inbox';

$string['crm_help_article_inbox_diagnostics_summary'] =
    'Проверка IMAP, SMTP, синхронизации, вложений, сопоставления пользователей и ИИ.';

$string['crm_help_guide_inbox_ai_title'] =
    'Использовать ИИ-помощник';

$string['crm_help_guide_inbox_ai_desc'] =
    'Проанализируйте диалог, проверьте язык и срочность и обязательно перечитайте ИИ-подсказку.';

$string['crm_help_guide_inbox_ai_action'] =
    'Открыть справку по ИИ';

$string['crm_help_guide_inbox_diagnostics_title'] =
    'Проверить диагностику';

$string['crm_help_guide_inbox_diagnostics_desc'] =
    'Проверьте IMAP, SMTP, синхронизацию, вложения, ИИ-провайдера и квоты.';

$string['crm_help_guide_inbox_diagnostics_action'] =
    'Открыть руководство по диагностике';

$string['crm_onboarding_step_inbox_title'] =
    'Познакомиться с CRM Inbox';

$string['crm_onboarding_step_inbox_desc'] =
    'Просмотрите обращения поддержки, контакты, приоритеты и инструменты ответа.';

$string['crm_help_open_inbox_help'] =
    'Руководство по диагностике Inbox';

$string['crm_help_open_inbox_diagnostics'] =
    'Диагностика Inbox';

$string['crm_help_open_inbox_ai_diagnostics'] =
    'Диагностика ИИ Inbox';

$string['crm_inbox_help_subtitle'] =
    'Обрабатывайте, назначайте и контролируйте обращения поддержки CampusFR.';

$string['crm_inbox_thread_help_subtitle'] =
    'Просмотрите полную историю, данные CRM и доступные ИИ-подсказки.';

$string['crm_inbox_diagnostics_help_subtitle'] =
    'Проверьте аккаунты, подключения, синхронизацию и технические ошибки Inbox.';

$string['crm_inbox_ai_diagnostics_help_subtitle'] =
    'Проверьте ИИ-провайдера, модели, квоты, кэш и последние ошибки.';

// Phase 6.5F — UX и доступность Inbox.
$string['crm_inbox_region_label'] =
    'Входящие CRM';

$string['crm_inbox_result_count'] =
    'Найдено диалогов: {$a}';

$string['crm_inbox_empty_title'] =
    'Диалоги не найдены';

$string['crm_inbox_thread_list_label'] =
    'Список диалогов Inbox';

$string['crm_inbox_filters_label'] =
    'Фильтры CRM Inbox';

$string['crm_inbox_unread_count_accessible'] =
    'Непрочитанных сообщений в этом диалоге: {$a}';

$string['crm_inbox_thread_region_label'] =
    'Диалог Inbox';

$string['crm_inbox_thread_actions_label'] =
    'Действия с диалогом';

$string['crm_inbox_action_processing'] =
    'Действие выполняется.';

$string['crm_inbox_processing'] =
    'Обработка…';

$string['crm_inbox_message_content_label'] =
    'Содержимое сообщения';

$string['crm_inbox_attachments_label'] =
    'Вложения сообщения';

$string['crm_inbox_download_attachment'] =
    'Скачать вложение {$a}';

$string['crm_inbox_reply_form_label'] =
    'Форма ответа Inbox';

$string['crm_inbox_reply_processing'] =
    'Ответ обрабатывается.';

$string['crm_inbox_reply_help'] =
    'Проверьте тему и текст перед отправкой. ИИ-подсказки можно редактировать, и они всегда требуют проверки человеком.';

$string['crm_inbox_reply_actions_label'] =
    'Действия формы ответа';

$string['crm_inbox_saving'] =
    'Сохранение…';

$string['crm_inbox_sending'] =
    'Отправка…';

$string['dashboard_inbox_title'] =
    'CRM Inbox';

$string['dashboard_inbox_subtitle'] =
    'Оперативный обзор диалогов и обращений, требующих внимания.';

$string['dashboard_inbox_open'] =
    'Открыть Inbox';

$string['dashboard_inbox_open_conversations'] =
    'Открытые диалоги';

$string['dashboard_inbox_unassigned'] =
    'Не назначено';

$string['dashboard_inbox_urgent'] =
    'Срочные';

$string['dashboard_inbox_pending'] =
    'Ожидают ответа';

$string['dashboard_inbox_recent_activity'] =
    'Недавняя активность';

$string['dashboard_inbox_empty'] =
    'Недавних диалогов Inbox нет.';

$string['dashboard_inbox_metric_aria'] =
    '{$a->label}: {$a->count}';

$string['crm_inbox_status_unknown'] =
    'Неизвестный статус';

$string['crm_inbox_priority_unknown'] =
    'Неизвестный приоритет';

$string['crm_user_view_delete_processing'] =
    'Сохранённый вид удаляется.';

$string['crm_user_view_delete_processing_short'] =
    'Удаление…';

$string['crm_inbox_ai_analysis_processing'] =
    'Выполняется анализ диалога.';

$string['crm_inbox_ai_analysis_processing_short'] =
    'Анализ…';

$string['crm_inbox_ai_reply_processing'] =
    'Создаётся предложение ответа.';

$string['crm_inbox_ai_reply_processing_short'] =
    'Создание…';

$string['crm_inbox_ai_actions_label'] =
    'Действия ИИ-помощника';

$string['crm_inbox_messages_heading'] =
    'Сообщения диалога';

$string['crm_inbox_attachment_unavailable'] =
    'недоступно';

$string['crm_inbox_attachment_unavailable_aria'] =
    'Вложение {$a} сейчас недоступно';

$string['crm_user_inbox_statistics_label'] =
    'Статистика Inbox пользователя';

$string['crm_user_inbox_stat_aria'] =
    '{$a->label}: {$a->value}';

$string['command_center_menu_actions'] =
    'Действия с результатом';

$string['command_center_confirmation_dialog'] =
    'Подтверждение действия';

$string['subscriptions:view_work_items'] =
    'Просматривать рабочие элементы CRM';

$string['subscriptions:manage_work_items'] =
    'Создавать и управлять рабочими элементами CRM';

$string['subscriptions:manage_work_configuration'] =
    'Настраивать команды и рабочие процессы CRM';

$string['crm_work_title'] = 'Рабочие элементы';

$string['crm_work_subtitle'] =
    'Управляйте задачами, тикетами, инцидентами и внутренними запросами CampusFR.';

$string['crm_work_region_label'] =
    'Список рабочих элементов';

$string['crm_work_result_count'] =
    'Рабочих элементов: {$a}';

$string['crm_work_empty'] =
    'Нет рабочих элементов, соответствующих выбранным критериям.';

$string['crm_work_create'] =
    'Создать рабочий элемент';

$string['crm_work_created'] =
    'Рабочий элемент создан.';

$string['crm_work_back'] =
    'Вернуться к рабочим элементам';

$string['crm_work_status'] =
    'Статус';

$string['crm_work_priority'] =
    'Приоритет';

$string['crm_work_type'] =
    'Тип';

$string['crm_work_team'] =
    'Команда';

$string['crm_work_due'] =
    'Срок выполнения';

$string['crm_work_assigned_user'] =
    'Назначено участнику команды';

$string['crm_work_filter_mine'] =
    'Мои задачи';

$string['crm_work_filter_unassigned'] =
    'Без ответственного';

$string['crm_work_filter_overdue'] =
    'Просроченные';

$string['crm_work_comments'] =
    'Внутренние комментарии';

$string['crm_work_add_comment'] =
    'Добавить комментарий';

$string['crm_work_subtasks'] =
    'Подзадачи';

$string['crm_work_links'] =
    'Связанные объекты';

$string['crm_work_history'] =
    'История';

$string['crm_work_teams'] =
    'Команды управления работой';

$string['crm_work_team_name'] =
    'Название команды';

$string['crm_work_team_create'] =
    'Создать команду';

$string['crm_work_field_title'] =
    'Название';

$string['crm_work_field_description'] =
    'Описание';

$string['crm_work_create_from_thread'] =
    'Создать рабочий элемент';

$string['crm_work_user_section'] =
    'Рабочие элементы';

$string['crm_work_total'] =
    'Всего';

$string['crm_work_active'] =
    'Активные';

$string['crm_work_urgent'] =
    'Срочные';

$string['crm_work_overdue'] =
    'Просроченные';

$string['crm_work_unassigned'] =
    'Без ответственного';

$string['crm_work_my_items'] =
    'Мои задачи';

$string['crm_work_open_user_items'] =
    'Посмотреть все рабочие элементы';

$string['crm_work_create_for_user'] =
    'Создать задачу';

$string['crm_work_dashboard_title'] =
    'Управление работой';

$string['crm_work_status_open'] =
    'Открыт';

$string['crm_work_status_in_progress'] =
    'В работе';

$string['crm_work_status_blocked'] =
    'Заблокирован';

$string['crm_work_status_waiting'] =
    'В ожидании';

$string['crm_work_status_resolved'] =
    'Решён';

$string['crm_work_status_closed'] =
    'Закрыт';

$string['crm_work_status_cancelled'] =
    'Отменён';

$string['crm_work_priority_low'] =
    'Низкий';

$string['crm_work_priority_normal'] =
    'Обычный';

$string['crm_work_priority_high'] =
    'Высокий';

$string['crm_work_priority_urgent'] =
    'Срочный';

$string['crm_work_priority_critical'] =
    'Критический';

$string['crm_work_type_task'] =
    'Задача';

$string['crm_work_type_support'] =
    'Поддержка';

$string['crm_work_type_bug'] =
    'Ошибка';

$string['crm_work_type_incident'] =
    'Инцидент';

$string['crm_work_type_feature'] =
    'Функциональность';

$string['crm_work_type_content'] =
    'Контент';

$string['crm_work_type_marketing'] =
    'Маркетинг';

$string['crm_work_type_finance'] =
    'Финансы';

$string['crm_work_type_administration'] =
    'Администрирование';

$string['crm_work_type_follow_up'] =
    'Последующее действие';

$string['command_action_work_items_title'] =
    'Открыть рабочие элементы';

$string['command_action_work_items_subtitle'] =
    'Посмотреть все задачи, тикеты и внутренние запросы.';

$string['command_action_work_items_mine_title'] =
    'Мои задачи';

$string['command_action_work_items_mine_subtitle'] =
    'Посмотреть рабочие элементы, назначенные вам.';

$string['command_action_work_items_urgent_title'] =
    'Срочные рабочие элементы';

$string['command_action_work_items_urgent_subtitle'] =
    'Посмотреть рабочие элементы со срочным приоритетом.';

$string['command_action_work_items_overdue_title'] =
    'Просроченные рабочие элементы';

$string['command_action_work_items_overdue_subtitle'] =
    'Посмотреть рабочие элементы с истёкшим сроком.';

$string['command_action_work_items_unassigned_title'] =
    'Рабочие элементы без ответственного';

$string['command_action_work_items_unassigned_subtitle'] =
    'Посмотреть активные рабочие элементы без назначенного ответственного.';

$string['crm_work_team_role_member'] =
    'Участник';

$string['crm_work_team_role_lead'] =
    'Руководитель';

$string['crm_work_remove_member_confirm'] =
    'Удалить этого участника из команды?';

$string['crm_help_category_work_management'] =
    'Управление работой';

$string['crm_help_category_work_management_desc'] =
    'Организуйте задачи, тикеты, инциденты и внутренние запросы CampusFR.';

$string['crm_help_article_work_management_title'] =
    'Управление рабочими элементами';

$string['crm_help_article_work_management_summary'] =
    'Статусы, приоритеты, команды, назначения, подзадачи и связи рабочих элементов с CRM.';

$string['crm_assistant_title'] = 'CRM-помощник';
$string['crm_assistant_navigation'] = 'CRM-помощник';
$string['crm_assistant_description'] = 'Приоритетные ситуации, выявленные межмодульной аналитикой CRM. Помощник объясняет ситуацию и предлагает действия, но никогда не принимает автоматических решений.';
$string['crm_assistant_open'] = 'Открыть CRM-помощник';
$string['crm_assistant_empty'] = 'Сейчас нет рекомендаций, требующих вашего внимания.';
$string['crm_assistant_user_section'] = 'CRM-помощник';

$string['crm_assistant_metric_active'] = 'Активные';
$string['crm_assistant_metric_critical'] = 'Критические';
$string['crm_assistant_metric_urgent'] = 'Срочные';
$string['crm_assistant_metric_accepted'] = 'Принятые';
$string['crm_assistant_metric_crossdomain'] = 'Межмодульные';
$string['crm_assistant_metric_users'] = 'Пользователи';

$string['crm_assistant_filter_scope'] = 'Область';
$string['crm_assistant_filter_priority'] = 'Приоритет';
$string['crm_assistant_filter_status'] = 'Статус';
$string['crm_assistant_filter_any'] = 'Все';
$string['crm_assistant_scope_active'] = 'Активные рекомендации';
$string['crm_assistant_scope_all'] = 'Вся история';

$string['crm_assistant_priority_critical'] = 'Критический';
$string['crm_assistant_priority_urgent'] = 'Срочный';
$string['crm_assistant_priority_high'] = 'Высокий';
$string['crm_assistant_priority_normal'] = 'Обычный';
$string['crm_assistant_priority_low'] = 'Низкий';

$string['crm_assistant_status_proposed'] = 'Предложена';
$string['crm_assistant_status_accepted'] = 'Принята';
$string['crm_assistant_status_dismissed'] = 'Отклонена';
$string['crm_assistant_status_completed'] = 'Завершена';
$string['crm_assistant_status_expired'] = 'Истекла';

$string['crm_assistant_target'] = 'Пользователь';
$string['crm_assistant_why'] = 'Почему появилась эта рекомендация?';
$string['crm_assistant_priority_score'] = 'Оценка приоритета: {$a}';
$string['crm_assistant_evidence_count'] = 'Фактов: {$a}';
$string['crm_assistant_source_count'] = 'Источников: {$a}';
$string['crm_assistant_last_detected'] = 'Последнее обнаружение: {$a}';

$string['crm_assistant_action_accept'] = 'Принять';
$string['crm_assistant_action_complete'] = 'Отметить выполненной';
$string['crm_assistant_action_dismiss'] = 'Отклонить';
$string['crm_assistant_accepted'] = 'Рекомендация принята.';
$string['crm_assistant_completed'] = 'Рекомендация отмечена как выполненная.';
$string['crm_assistant_dismissed'] = 'Рекомендация отклонена.';
$string['crm_assistant_action_failed'] = 'Не удалось выполнить действие с рекомендацией.';

$string['command_crm_assistant'] = 'Открыть CRM-помощник';
$string['command_crm_assistant_desc'] = 'Показать рекомендации и приоритетные ситуации.';
$string['command_crm_recommendation_desc'] = 'Активная рекомендация CRM.';

$string['crm_assistant_recommendation_intervene_disengagement_spiral'] = 'Отреагировать на постепенную потерю вовлечённости';
$string['crm_assistant_recommendation_intervene_disengagement_spiral_desc'] = 'Несколько сигналов указывают на устойчивое снижение активности и прогресса.';

$string['crm_assistant_recommendation_coordinate_learning_support_response'] = 'Скоординировать учебную и техническую поддержку';
$string['crm_assistant_recommendation_coordinate_learning_support_response_desc'] = 'Учебная трудность сопровождается активным обращением в поддержку.';

$string['crm_assistant_recommendation_coordinate_payment_support_resolution'] = 'Совместно решить вопрос оплаты и поддержки';
$string['crm_assistant_recommendation_coordinate_payment_support_resolution_desc'] = 'Проблема оплаты, вероятно, связана с обращением в поддержку.';

$string['crm_assistant_recommendation_intervene_cross_domain_churn_risk'] = 'Отреагировать на высокий риск ухода';
$string['crm_assistant_recommendation_intervene_cross_domain_churn_risk_desc'] = 'Доступ, активность и несколько проблем указывают на риск ухода пользователя.';

$string['crm_assistant_recommendation_coordinate_operational_overload'] = 'Скоординировать нерешённые рабочие запросы';
$string['crm_assistant_recommendation_coordinate_operational_overload_desc'] = 'Нагрузка Inbox и нерешённые Work Items требуют совместного вмешательства.';

$string['crm_assistant_recommendation_review_customer_success_risk'] = 'Проверить риск Customer Success';
$string['crm_assistant_recommendation_review_learning_difficulty'] = 'Проверить учебную трудность';
$string['crm_assistant_recommendation_review_support_situation'] = 'Проверить ситуацию поддержки';
$string['crm_assistant_recommendation_review_blocked_payment'] = 'Проверить заблокированный платёж';
$string['crm_assistant_recommendation_review_active_work_items'] = 'Проверить активные Work Items';
$string['crm_assistant_recommendation_recognise_positive_progress'] = 'Отметить прогресс учащегося';

$string['crm_assistant_action_propose_work_item'] = 'Подготовить Work Item';

$string['crm_work_suggestion_title'] = 'Предложение Work Item';
$string['crm_work_suggestion_summary'] = 'Предложение CRM-помощника';
$string['crm_work_suggestion_confidence'] = 'Уверенность предложения: {$a}%';
$string['crm_work_suggestion_suggested_type'] = 'Предложенный тип: {$a}';
$string['crm_work_suggestion_suggested_priority'] = 'Предложенный приоритет: {$a}';
$string['crm_work_suggestion_suggested_due'] = 'Предложенный срок: {$a}';
$string['crm_work_suggestion_duplicates'] = 'Похожие Work Items';
$string['crm_work_suggestion_probable_duplicate_warning'] = 'Вероятно, уже существует аналогичный Work Item. Проверьте его перед созданием нового.';
$string['crm_work_suggestion_similarity'] = 'Оценка сходства: {$a}%';
$string['crm_work_suggestion_teams'] = 'Предложенные команды';
$string['crm_work_suggestion_team_score'] = 'Соответствие: {$a->score}% · Активная нагрузка: {$a->workload}';
$string['crm_work_suggestion_allow_duplicate'] = 'Всё равно создать Work Item, несмотря на вероятный дубль';
$string['crm_work_suggestion_create'] = 'Создать Work Item';
$string['crm_work_suggestion_created'] = 'Work Item создан на основе рекомендации.';
$string['crm_work_suggestion_duplicate_blocked'] = 'Создание заблокировано, поскольку вероятный дубль уже существует.';

$string['crm_work_suggestion_description_intro'] = 'Этот Work Item был подготовлен CRM-помощником. Администратор должен проверить и подтвердить его содержание.';
$string['crm_work_suggestion_source_recommendation'] = 'Исходная рекомендация: {$a}';
$string['crm_work_suggestion_priority_score'] = 'Оценка приоритета рекомендации: {$a}';
$string['crm_work_suggestion_evidence_heading'] = 'Факты, послужившие основанием для рекомендации:';

$string['crm_work_suggestion_title_intervene_disengagement_spiral'] = 'Связаться с учащимся из-за устойчивого снижения вовлечённости';
$string['crm_work_suggestion_title_coordinate_learning_support_response'] = 'Скоординировать учебное сопровождение и поддержку';
$string['crm_work_suggestion_title_coordinate_payment_support_resolution'] = 'Решить проблему оплаты и обращения в поддержку';
$string['crm_work_suggestion_title_intervene_cross_domain_churn_risk'] = 'Организовать меры по предотвращению ухода пользователя';
$string['crm_work_suggestion_title_coordinate_operational_overload'] = 'Скоординировать обращения и незавершённые Work Items';
$string['crm_work_suggestion_title_review_customer_success_risk'] = 'Проверить ситуацию Customer Success';
$string['crm_work_suggestion_title_review_learning_difficulty'] = 'Организовать учебное сопровождение';
$string['crm_work_suggestion_title_review_support_situation'] = 'Обработать ситуацию поддержки';
$string['crm_work_suggestion_title_review_blocked_payment'] = 'Обработать заблокированный платёж';
$string['crm_work_suggestion_title_review_active_work_items'] = 'Проверить активные Work Items';

$string['local/subscriptions:use_crm_assistant_ai'] = 'Использовать диалоговый CRM-помощник';

$string['crm_assistant_ai_title'] = 'Задать вопрос CRM-помощнику';
$string['crm_assistant_ai_description'] = 'Задайте вопрос о рекомендациях, пользователях и активных Work Items. Ответ основан только на данных, уже рассчитанных CRM.';
$string['crm_assistant_ai_question'] = 'Ваш вопрос';
$string['crm_assistant_ai_placeholder'] = 'Например: каким учащимся сегодня требуется вмешательство?';
$string['crm_assistant_ai_ask'] = 'Спросить помощника';
$string['crm_assistant_ai_thinking'] = 'Помощник анализирует доступную информацию…';
$string['crm_assistant_ai_request_failed'] = 'CRM-помощник не смог ответить на вопрос.';
$string['crm_assistant_ai_human_review'] = 'Ответы помощника являются предложениями и всегда должны проверяться администратором.';
$string['crm_assistant_ai_keypoints'] = 'Основные моменты';
$string['crm_assistant_ai_suggested_actions'] = 'Предложенные действия';
$string['crm_assistant_ai_warnings'] = 'Предупреждения';
$string['crm_assistant_ai_references'] = 'Связанные объекты CRM';
$string['crm_assistant_ai_confidence'] = 'Оценка уверенности';

$string['crm_assistant_ai_example_priorities'] = 'Какими пользователями нужно заняться в первую очередь сегодня?';
$string['crm_assistant_ai_example_risks'] = 'Какие ситуации представляют наибольший риск?';
$string['crm_assistant_ai_example_work'] = 'Какие Work Items выглядят срочными или заблокированными?';

$string['crm_assistant_question_rejected'] = 'CRM-помощник не может обработать этот вопрос.';

$string['task_run_crm_recommendations'] = 'Обновление рекомендаций CRM';

$string['crm_recommendation_health_healthy'] = 'Механизм рекомендаций работает нормально.';
$string['crm_recommendation_health_degraded'] = 'Механизм рекомендаций работает с предупреждениями.';
$string['crm_recommendation_health_unhealthy'] = 'Механизм рекомендаций требует внимания.';
$string['crm_recommendation_run_completed'] = 'Расчёт рекомендаций завершён';
$string['crm_recommendation_run_partial'] = 'Расчёт рекомендаций завершён частично';
$string['crm_recommendation_run_failed'] = 'Ошибка расчёта рекомендаций';
$string['crm_recommendation_run_skipped'] = 'Расчёт рекомендаций пропущен';

$string['csplanpage'] = 'План Customer Success';
$string['csplanusersection'] = 'План Customer Success';
$string['csplannoneforuser'] = 'Нет открытых планов Customer Success.';
$string['csplanblocked'] = 'Заблокирован';

$string['csplanstatus_draft'] = 'Черновик';
$string['csplanstatus_active'] = 'Активен';
$string['csplanstatus_paused'] = 'Приостановлен';
$string['csplanstatus_completed'] = 'Завершён';
$string['csplanstatus_cancelled'] = 'Отменён';

$string['csplanstepstatus_pending'] = 'Ожидает';
$string['csplanstepstatus_ready'] = 'Готов';
$string['csplanstepstatus_blocked'] = 'Заблокирован';
$string['csplanstepstatus_in_progress'] = 'В работе';
$string['csplanstepstatus_completed'] = 'Завершён';
$string['csplanstepstatus_skipped'] = 'Пропущен';

$string['csplanpriority_low'] = 'Низкий';
$string['csplanpriority_normal'] = 'Обычный';
$string['csplanpriority_high'] = 'Высокий';
$string['csplanpriority_urgent'] = 'Срочный';
$string['csplanpriority_critical'] = 'Критический';

$string['csplanprogressvalue'] = '{$a->completed}/{$a->total} этапов — {$a->percentage}%';
$string['csplanprogresspercentage'] = 'Прогресс: {$a}%';
$string['csplanstepdependency'] = 'Зависит от этапа №{$a}';

$string['csplanaction_activate'] = 'Активировать';
$string['csplanaction_pause'] = 'Приостановить';
$string['csplanaction_cancel'] = 'Отменить';
$string['csplanaction_startstep'] = 'Начать';
$string['csplanaction_completestep'] = 'Завершить';
$string['csplanaction_skipstep'] = 'Пропустить';
$string['csplanaction_unblockstep'] = 'Разблокировать';
$string['csplanactioncompleted'] = 'План Customer Success обновлён.';

$string['csplantimelinecreated'] = 'План Customer Success создан';
$string['csplantimelineactivated'] = 'План Customer Success активирован';
$string['csplantimelinestepcompleted'] = 'Этап плана обработан';
$string['csplantimelinecompleted'] = 'План Customer Success завершён';

$string['csplandashboard_title'] = 'Планы Customer Success';
$string['csplandashboard_open'] = 'Открытые планы';
$string['csplandashboard_active'] = 'Активные планы';
$string['csplandashboard_blocked'] = 'Заблокированные этапы';
$string['csplandashboard_critical'] = 'Критические планы';
$string['csplandashboard_completedtoday'] = 'Завершено сегодня';
$string['csplandashboard_averageprogress'] = 'Средний прогресс: {$a}%';

$string['csplancommand_open'] = 'Открыть план №{$a}';
$string['csplancommand_open_desc'] = 'Открыть план Customer Success';

$string['crm_user_has_customer_success_plan'] =
    'Есть открытый план Customer Success';

$string['crm_user_customer_success_plan_blocked'] =
    'Есть заблокированный этап Customer Success';

$string['crm_user_customer_success_plan_status'] =
    'Статус плана Customer Success';

$string['crm_user_customer_success_plan_status_all'] =
    'Все статусы';

$string['crm_user_column_customer_success_plans'] =
    'Customer Success';

$string['crm_user_customer_success_none'] =
    'Нет открытого плана';

$string['crm_user_customer_success_open_count'] =
    'Открытых планов: {$a}';

$string['crm_user_customer_success_blocked_count'] =
    'Заблокировано: {$a}';

$string['csplanobjective_reduce_churn_risk'] =
    'Снизить риск оттока';

$string['csplanobjective_resolve_payment_friction'] =
    'Устранить трудности с оплатой';

$string['csplanobjective_resolve_support_pressure'] =
    'Решить обращения в службу поддержки';

$string['csplanobjective_restore_learning_access'] =
    'Восстановить доступ к обучению';

$string['csplanobjective_restore_learning_engagement'] =
    'Возобновить учебную активность';

$string['csplanobjective_develop_customer_opportunity'] =
    'Развить возможности работы с клиентом';

$string['csplanobjective_coordinate_customer_success'] =
    'Скоординировать сопровождение Customer Success';

$string['csplandescription_recommendations'] =
    'План Customer Success подготовлен на основе рекомендаций CRM: {$a}.';

$string['csplanblockedreason_dependency_cycle'] =
    'Этот этап заблокирован из-за циклической зависимости.';

$string['csplanblockedreason_manual'] =
    'Этот этап был заблокирован вручную.';

$string['csplanblockedreason_unknown'] =
    'Этот этап заблокирован. Техническая причина: {$a}';

$string['csplansource_manual'] =
    'Создан вручную';

$string['csplansource_recommendation_engine'] =
    'Механизм рекомендаций';

$string['csplansource_correlation_engine'] =
    'Механизм корреляций';

$string['csplansource_crm_assistant'] =
    'CRM-помощник';

$string['csplansource_user_360'] =
    'Профиль пользователя 360°';

$string['csplanprogresslabel'] =
    'Выполнение плана Customer Success';

$string['csplanactionfailed'] =
    'Не удалось выполнить действие с планом Customer Success.';

$string['admin_event_customer_success_plan_created'] =
    'План Customer Success создан';

$string['admin_event_customer_success_plan_activated'] =
    'План Customer Success активирован';

$string['admin_event_customer_success_plan_paused'] =
    'План Customer Success приостановлен';

$string['admin_event_customer_success_plan_cancelled'] =
    'План Customer Success отменён';

$string['admin_event_customer_success_plan_completed'] =
    'План Customer Success завершён';

$string['admin_event_customer_success_plan_auto_completed'] =
    'План Customer Success завершён автоматически';

$string['admin_event_customer_success_step_started'] =
    'Этап Customer Success начат';

$string['admin_event_customer_success_step_completed'] =
    'Этап Customer Success завершён';

$string['admin_event_customer_success_step_skipped'] =
    'Этап Customer Success пропущен';

$string['admin_event_customer_success_step_blocked'] =
    'Этап Customer Success заблокирован';

$string['admin_event_customer_success_step_unblocked'] =
    'Этап Customer Success разблокирован';

$string['csplanconfirmtitle'] =
    'Подтверждение действия';

$string['csplanconfirmcancel'] =
    'Вы действительно хотите отменить план «{$a}»? История плана не будет удалена.';

$string['csplanconfirmskipstep'] =
    'Вы действительно хотите пропустить этап «{$a}»? После этого зависимые этапы могут стать доступными.';

$string['csplanblockreasonlabel'] =
    'Причина блокировки';

$string['csplanblockreasonplaceholder'] =
    'Укажите, почему этот этап заблокирован';

$string['csplanblockreasonhelp'] =
    'Причина будет показана в плане и записана в административную историю.';

$string['csplanblockreasonrequired'] =
    'Необходимо указать причину блокировки.';

$string['csplanblockreasontoolong'] =
    'Причина блокировки не может превышать 500 символов.';

$string['csplanaction_blockstep'] =
    'Заблокировать этап';

$string['crm_filter_customer_success'] =
    'Customer Success';

$string['crm_assistant_evidence_activity_inactive_30d'] =
    'Нет активности уже не менее 30 дней';

$string['crm_assistant_evidence_value_activity_inactive_30d'] =
    '{$a} дн. с момента последней активности';

$string['crm_assistant_evidence_loyalty_no_current_access'] =
    'В настоящее время нет активного доступа';

$string['crm_assistant_evidence_value_loyalty_no_current_access'] =
    '{$a} завершённых или отменённых доступов';

$string['crm_assistant_recommendation_send_trial_conversion_email'] =
    'Помочь пользователю перейти от пробного доступа к покупке';

$string['crm_assistant_recommendation_send_trial_conversion_email_desc'] =
    'Пользователь попробовал платформу, но ещё не приобрёл платный доступ.';

$string['crm_assistant_recommendation_propose_upgrade'] =
    'Предложить более полную подписку';

$string['crm_assistant_recommendation_propose_upgrade_desc'] =
    'Текущий доступ клиента можно расширить с помощью более полной подписки.';

$string['crm_assistant_recommendation_send_winback_message'] =
    'Вернуть бывшего клиента';

$string['crm_assistant_recommendation_send_winback_message_desc'] =
    'У бывшего клиента больше нет активного доступа, и с ним стоит связаться повторно.';

$string['crm_assistant_recommendation_suggest_digital_product'] =
    'Предложить цифровой продукт';

$string['crm_assistant_recommendation_suggest_digital_product_desc'] =
    'Клиента может заинтересовать дополнительный цифровой продукт.';

$string['crm_assistant_recommendation_create_first_crm_note'] =
    'Создать первую CRM-заметку';

$string['crm_assistant_recommendation_create_first_crm_note_desc'] =
    'В CRM пока не добавлена качественная информация об этом клиенте.';

$string['crm_assistant_evidence_crm_customer_without_notes'] =
    'Для этого клиента ещё не добавлено ни одной CRM-заметки';

$string['crm_assistant_evidence_opportunity_trial_to_purchase'] =
    'Пробный доступ пока не привёл к покупке';

$string['crm_assistant_evidence_opportunity_upgrade_subscription'] =
    'Клиенту может подойти более полная подписка';

$string['crm_assistant_evidence_opportunity_winback_expired_customer'] =
    'У клиента больше нет активного доступа';

$string['crm_assistant_evidence_opportunity_cross_sell_digital_product'] =
    'Клиенту можно предложить дополнительный цифровой продукт';

$string['crm_work_source_manual'] =
    'Создано вручную';

$string['crm_work_source_inbox'] =
    'CRM Inbox';

$string['crm_work_source_user_360'] =
    'Карточка пользователя 360°';

$string['crm_work_source_dashboard'] =
    'Панель CRM';

$string['crm_work_source_automation'] =
    'Автоматизация CRM';

$string['crm_work_source_intelligence'] =
    'CRM Intelligence';

$string['crm_work_source_assistant'] =
    'CRM Assistant';

$string['crm_work_source_command_center'] =
    'Command Center';

$string['crm_work_source_system'] =
    'Система';

$string['crm_work_suggestion_reason_generated_from_recommendation'] =
    'Предложение создано на основе рекомендации CRM';

$string['crm_work_suggestion_reason_priority_derived_from_recommendation'] =
    'Приоритет рассчитан по срочности рекомендации';

$string['crm_work_suggestion_reason_type_derived_from_scenario'] =
    'Тип задачи определён по выявленной ситуации';

$string['crm_work_suggestion_reason_team_suggested_from_domain_and_workload'] =
    'Команда предложена с учётом её специализации и текущей нагрузки';

$string['crm_work_suggestion_reason_duplicate_candidates_detected'] =
    'Обнаружены похожие рабочие задачи';

$string['crm_assistant_unknown_label'] =
    'Информация недоступна';

$string['crm_assistant_evidence_learning_low_progress'] =
    'Недостаточный прогресс в обучении';

$string['crm_assistant_evidence_recommendation_review_customer_success_risk'] =
    'Необходимо проверить риск Customer Success';

$string['crm_assistant_evidence_recommendation_review_learning_difficulty'] =
    'Обнаружена возможная трудность в обучении';

$string['crm_assistant_evidence_activity_inactive_14d'] =
    'Отсутствие активности более 14 дней';

$string['crm_assistant_evidence_learning_not_started'] =
    'Обучение ещё не начато';

$string['crm_assistant_evidence_activity_never_accessed'] =
    'Учебные активности ещё не просматривались';

$string['crm_assistant_evidence_value_learning_low_progress'] =
    'Текущий прогресс: {$a}%';

$string['crm_assistant_evidence_value_activity_inactive_14d'] =
    '{$a} дн. с момента последней активности';

$string['crm_daily_priorities_item_fallback'] =
    'Рекомендуемое действие CRM';

$string['crm_intelligence_alert_fallback'] =
    'Предупреждение CRM для проверки';

$string['dashboard_revenue_currency_select'] =
    'Выбрать валюту выручки';

$string['dashboard_revenue_subscriptions'] =
    'Подписки';

$string['dashboard_revenue_digital'] =
    'Цифровые продукты';

$string['dashboard_revenue_no_data'] =
    'За этот период выручки нет';

$string['dashboard_new_trials'] = 'Новые пробные подписки';
$string['dashboard_new_customers'] = 'Новые клиенты';

$string['dashboard_trial_customer_ratio'] =
    'Соотношение клиентов и пробных подписок за период';

$string['dashboard_trial_customer_ratio_help'] =
    'Сравнивает новых клиентов с пользователями, начавшими пробную подписку за выбранный период. Это не когортная конверсия: клиент мог начать пробную подписку в более ранний период.';

$string['dashboard_trial_customer_ratio_unavailable'] = '—';

$string['dashboard_trial_customer_ratio_value'] = '{$a} %';

$string['dashboard_funnel_title'] =
    'Воронка привлечения';

$string['dashboard_funnel_subtitle'] =
    'Когорты и проверяемая конверсия за {$a} дней';

$string['dashboard_funnel_new_users'] =
    'Новые пользователи';

$string['dashboard_funnel_trial_users'] =
    'Первые пробные подписки';

$string['dashboard_funnel_new_customers'] =
    'Новые платящие клиенты';

$string['dashboard_funnel_digital_buyers'] =
    'Покупатели цифровых продуктов';

$string['dashboard_funnel_conversion'] =
    'Конверсия пробных подписок';

$string['dashboard_funnel_conversion_details'] =
    '{$a->converted} конверсий среди {$a->mature} пробных подписок с завершённым периодом наблюдения {$a->days} дней';

$string['dashboard_funnel_pending_observation'] =
    '{$a} недавних пробных подписок всё ещё находятся в периоде наблюдения.';

$string['dashboard_funnel_rate_unavailable'] =
    'Недоступно';

$string['dashboard_funnel_rate_value'] =
    '{$a} %';

$string['dashboard_funnel_trend_stable'] =
    'Без изменений по сравнению с предыдущим периодом';

$string['dashboard_funnel_trend_not_comparable'] =
    'Сравнение недоступно';

$string['dashboard_funnel_trend_absolute'] =
    '{$a} по сравнению с предыдущим периодом';

$string['dashboard_funnel_trend_percent'] =
    '{$a} % по сравнению с предыдущим периодом';

$string['dashboard_funnel_trend_points'] =
    '{$a} п.п. по сравнению с предыдущим периодом';

$string['dashboard_funnel_explorer_active'] =
    'Активен фильтр воронки';

$string['dashboard_funnel_explorer_new_users'] =
    'Пользователи, созданные за выбранный период';

$string['dashboard_funnel_explorer_trial_users'] =
    'Пользователи, чья первая пробная подписка началась за выбранный период';

$string['dashboard_funnel_explorer_new_customers'] =
    'Пользователи, чей первый успешный платёж выполнен за выбранный период';

$string['dashboard_funnel_explorer_digital_buyers'] =
    'Уникальные покупатели цифровых продуктов за период';

$string['dashboard_funnel_explorer_converted_trials'] =
    'Пробные подписки когорты, конвертированные в течение {$a} дней';

// Phase 7.75E - Dashboard CRM trends.
$string['crm_trends_subtitle'] = '{$a->analysed} пользователей доступны для сравнения из {$a->available} обновлённых профилей.';
$string['crm_trends_users'] = 'пользователь(ей)';
$string['crm_trends_previous_value'] = 'Предыдущий период: {$a}';
$string['crm_trends_difference_only'] = '{$a} пользователь(ей)';
$string['crm_trends_difference_with_percent'] = '{$a->difference} пользователь(ей) · {$a->variation} %';
$string['crm_trends_stable'] = 'Без изменений';
$string['crm_trends_open_explorer'] = 'Открыть Explorer';
$string['crm_trends_freshness'] = 'Последний снимок: {$a}';
$string['crm_trends_freshness_unknown'] = 'Дата последнего снимка недоступна.';
$string['crm_trends_no_current_data'] = 'Для этого периода нет доступных снимков Intelligence.';
$string['crm_trends_insufficient_data'] = 'Снимки существуют, но истории пока недостаточно для расчёта изменений.';
$string['crm_trends_no_movements'] = 'За этот период значительных изменений не обнаружено.';
$string['crm_trends_error'] = 'В настоящее время не удалось загрузить тенденции CRM.';

$string['crm_trends_metric_risk_up'] = 'Рост риска';
$string['crm_trends_metric_risk_up_desc'] = 'Профили, у которых показатель риска значительно вырос.';
$string['crm_trends_metric_risk_down'] = 'Снижение риска';
$string['crm_trends_metric_risk_down_desc'] = 'Профили, у которых показатель риска значительно улучшился.';

$string['crm_trends_metric_engagement_up'] = 'Рост вовлечённости';
$string['crm_trends_metric_engagement_up_desc'] = 'Профили, у которых вырос показатель вовлечённости.';
$string['crm_trends_metric_engagement_down'] = 'Снижение вовлечённости';
$string['crm_trends_metric_engagement_down_desc'] = 'Профили, у которых снизился показатель вовлечённости.';

$string['crm_trends_metric_global_up'] = 'Рост общего показателя';
$string['crm_trends_metric_global_up_desc'] = 'Профили, у которых улучшилось общее состояние CRM.';
$string['crm_trends_metric_global_down'] = 'Снижение общего показателя';
$string['crm_trends_metric_global_down_desc'] = 'Профили, у которых ухудшилось общее состояние CRM.';

$string['crm_trends_metric_unknown'] = 'Изменение CRM';
$string['crm_trends_metric_unknown_desc'] = 'В данных CRM было обнаружено изменение.';

// Phase 7.75E - User Explorer trend drill-down.
$string['crm_trends_metric_open'] = 'Показать пользователей по тенденции: {$a}';
$string['crm_user_explorer_trend_active'] = 'Активный фильтр тенденции';
$string['crm_user_explorer_trend_period'] = 'С {$a->start} по {$a->end}';
$string['crm_user_explorer_trend_threshold'] = 'Минимальное изменение: {$a} баллов';
$string['crm_user_explorer_trend_clear'] = 'Выйти из просмотра тенденции';

$string['crm_intelligence_alert_priority_critical'] =
    'Критический';

$string['crm_intelligence_alert_priority_high'] =
    'Высокий';

$string['crm_intelligence_alert_priority_normal'] =
    'Обычный';

$string['crm_intelligence_alert_priority_label'] =
    'Приоритет: {$a}';

$string['crm_intelligence_alert_signal_date'] =
    'CRM-сигнал оценён {$a}';

$string['crm_intelligence_alert_signal_age'] =
    'Возраст сигнала: {$a}';

$string['crm_intelligence_alert_next_action_label'] =
    'Рекомендуемое следующее действие';

$string['crm_intelligence_alert_next_action_high_risk_user'] =
    'Проверить ситуацию клиента и организовать приоритетное сопровождение.';

$string['crm_intelligence_alert_next_action_trial_without_purchase'] =
    'Связаться с пользователем и выяснить, что мешает совершить покупку.';

$string['crm_intelligence_alert_next_action_expired_without_reactivation'] =
    'Предложить повторную активацию или подходящее предложение с учётом истории клиента.';

$string['crm_intelligence_alert_next_action_inactive_user'] =
    'Проверить последнюю активность и подготовить персонализированное сообщение.';

$string['crm_intelligence_alert_next_action_hot_opportunity'] =
    'Быстро связаться с пользователем и предложить подходящее коммерческое решение.';

$string['crm_intelligence_alert_next_action_default'] =
    'Открыть профиль пользователя и определить следующее действие.';

$string['crm_intelligence_alert_work_item'] =
    'Активная рабочая задача';

$string['crm_intelligence_alert_cs_plan'] =
    'План Customer Success';

$string['crm_intelligence_alert_responsible'] =
    'Ответственный: {$a}';

$string['crm_intelligence_alert_due_date'] =
    'Срок выполнения: {$a}';

$string['crm_intelligence_alert_target_date'] =
    'Целевая дата: {$a}';

$string['crm_intelligence_alert_open_work_item'] =
    'Открыть рабочую задачу';

$string['crm_intelligence_alert_create_work_item'] =
    'Создать рабочую задачу';

$string['crm_intelligence_alert_open_cs_plan'] =
    'Открыть план CS';

$string['dashboard_state_loading_title'] =
    'Загрузка';

$string['dashboard_state_loading_description'] =
    'Информация для этой карточки подготавливается.';

$string['dashboard_state_error_title'] =
    'Не удалось загрузить карточку';

$string['dashboard_state_error_description'] =
    'При загрузке информации произошла ошибка.';

$string['dashboard_state_empty_title'] =
    'Нет доступной информации';

$string['dashboard_state_empty_description'] =
    'Сейчас здесь нечего отображать.';

$string['dashboard_state_retry'] =
    'Повторить';

$string['dashboard_open_all'] =
    'Показать всё';

$string['admin_event_email_password_reset_notice_sent'] =
    'Отправлено уведомление о сбросе пароля';

$string['admin_event_email_welcome_sent'] =
    'Отправлено приветственное письмо';

$string['admin_event_email_receipt_sent'] =
    'Отправлена квитанция об оплате';

$string['admin_event_email_subscription_access_sent'] =
    'Отправлена информация о доступе к подписке';

$string['admin_event_user_password_updated'] =
    'Пароль пользователя обновлён';

$string['admin_event_user_note_added'] =
    'Добавлена заметка CRM';

$string['admin_event_subscription_created'] =
    'Подписка создана';

$string['admin_event_subscription_created_manual'] =
    'Подписка создана вручную';

$string['admin_event_subscription_updated'] =
    'Подписка обновлена';

$string['admin_event_subscription_deleted'] =
    'Подписка удалена';

$string['admin_event_subscription_status_updated'] =
    'Статус подписки обновлён';

$string['admin_event_subscription_dates_updated'] =
    'Даты подписки обновлены';

$string['admin_event_subscription_created_auto'] =
    'Подписка создана автоматически';

$string['admin_event_subscription_extended'] =
    'Подписка продлена';

$string['admin_event_digital_provider_checked'] =
    'Статус цифрового платежа проверен';

$string['admin_event_payment_request_created'] =
    'Запрос на оплату создан';

$string['admin_event_payment_request_paid'] =
    'Запрос на оплату оплачен';

$string['admin_event_payment_request_failed'] =
    'Ошибка запроса на оплату';

$string['admin_event_payment_request_cancelled'] =
    'Запрос на оплату отменён';

$string['admin_event_trial_started'] =
    'Пробный период начат';

$string['admin_event_trial_expired'] =
    'Пробный период завершён';

$string['admin_event_work_item_created'] =
    'Рабочая задача создана';

$string['admin_event_work_item_status_changed'] =
    'Статус рабочей задачи изменён';

$string['admin_event_work_item_priority_changed'] =
    'Приоритет рабочей задачи изменён';

$string['admin_event_work_item_assigned'] =
    'Рабочая задача назначена';

$string['admin_event_work_item_comment_added'] =
    'К рабочей задаче добавлен комментарий';

$string['admin_event_work_item_linked'] =
    'К рабочей задаче добавлена связь';

$string['admin_event_work_item_suggestion_opened'] =
    'Открыто предложение рабочей задачи';

$string['admin_event_work_item_created_from_recommendation'] =
    'Рабочая задача создана из рекомендации';

$string['admin_event_work_item_duplicate_override'] =
    'Задача создана несмотря на возможный дубликат';

$string['admin_event_recommendation_created'] =
    'Рекомендация создана';

$string['admin_event_recommendation_refreshed'] =
    'Рекомендация обновлена';

$string['admin_event_recommendation_accepted'] =
    'Рекомендация принята';

$string['admin_event_recommendation_dismissed'] =
    'Рекомендация отклонена';

$string['admin_event_recommendation_completed'] =
    'Рекомендация выполнена';

$string['admin_event_recommendation_expired'] =
    'Срок рекомендации истёк';

$string['admin_event_recommendation_run_completed'] =
    'Формирование рекомендаций завершено';

$string['admin_event_recommendation_run_partial'] =
    'Формирование рекомендаций завершено частично';

$string['admin_event_recommendation_run_failed'] =
    'Ошибка формирования рекомендаций';

$string['admin_event_recommendation_run_skipped'] =
    'Формирование рекомендаций пропущено';

$string['admin_event_description_reference'] =
    'Номер: {$a}';

$string['admin_event_description_transition'] =
    '{$a->from} → {$a->to}';

$string['admin_event_description_status'] =
    'Статус: {$a}';

$string['admin_event_description_priority'] =
    'Приоритет: {$a}';

$string['admin_event_description_plan'] =
    'Тариф: {$a}';

$string['admin_event_description_contact'] =
    'Контакт: {$a}';

$string['admin_event_description_recommendation'] =
    'Рекомендация: {$a}';

$string['admin_event_description_cs_plan'] =
    '{$a->reference} — {$a->title}';

$string['admin_event_description_cs_step'] =
    '{$a->plan} — {$a->step}';

$string['dashboard_activity_actor'] =
    'Выполнил: {$a}';

$string['dashboard_activity_system_actor'] =
    'Автоматическое действие';

$string['dashboard_activity_open'] =
    'Открыть';

$string['dashboard_activity_target'] =
    'Клиент: {$a}';

$string['dashboard_activity_exact_date'] =
    'Зарегистрировано: {$a}';

$string['crm_app_navigation'] =
    'Основная навигация CRM';

$string['crm_admin_tools_title'] =
    'Инструменты администратора';

$string['crm_admin_tools_description'] =
    'Безопасный запуск и контроль технических операций CRM.';

$string['crm_admin_tool_busy'] =
    'Эта операция уже выполняется.';

$string['crm_admin_tool_failed'] =
    'Не удалось выполнить операцию. Подробности доступны в истории.';

$string['crm_admin_tool_status_running'] =
    'Выполняется';

$string['crm_admin_tool_status_success'] =
    'Завершено';

$string['crm_admin_tool_status_failed'] =
    'Ошибка';

$string['crm_admin_tool_status_busy'] =
    'Уже выполняется';

$string['crm_admin_tool_status_cancelled'] =
    'Отменено';

$string['crm_admin_tool_risk_low'] =
    'Низкий риск';

$string['crm_admin_tool_risk_normal'] =
    'Средний риск';

$string['crm_admin_tool_risk_high'] =
    'Высокий риск';

$string['crm_admin_tools_nav'] =
    'Инструменты';

$string['crm_admin_tool_unknown'] =
    'Запрошенный инструмент администратора не найден.';

$string['crm_admin_tools_empty'] =
    'Для вашей роли нет доступных инструментов администратора.';

$string['crm_admin_tool_open'] =
    'Открыть';

$string['crm_admin_tool_execute'] =
    'Запустить';

$string['crm_admin_tool_confirmation_warning'] =
    'Эта операция может изменить данные CRM. Проверьте параметры перед запуском.';

$string['crm_admin_tool_limit'] =
    'Максимальное количество элементов';

$string['crm_admin_tool_reset_cursor'] =
    'Начать обработку рекомендаций с начала';

$string['crm_admin_tool_never_run'] =
    'Никогда не запускалось';

$string['crm_admin_tool_last_run'] =
    'Последний запуск: {$a->date} — {$a->status}';

$string['crm_admin_tool_history'] =
    'История операций';

$string['crm_admin_tool_history_empty'] =
    'Операции администратора ещё не запускались.';

$string['crm_admin_tool_history_date'] =
    'Дата';

$string['crm_admin_tool_history_tool'] =
    'Инструмент';

$string['crm_admin_tool_history_actor'] =
    'Пользователь';

$string['crm_admin_tool_history_status'] =
    'Статус';

$string['crm_admin_tool_history_duration'] =
    'Длительность';

$string['crm_admin_tool_inbox_sync'] =
    'Синхронизировать CRM Inbox';

$string['crm_admin_tool_inbox_sync_desc'] =
    'Получает новые сообщения из активных почтовых ящиков.';

$string['crm_admin_tool_inbox_sync_success'] =
    'Синхронизация Inbox завершена.';

$string['crm_admin_tool_inbox_sync_partial'] =
    'Синхронизация Inbox завершена с ошибками.';

$string['crm_admin_tool_inbox_diagnostics'] =
    'Диагностика CRM Inbox';

$string['crm_admin_tool_inbox_diagnostics_desc'] =
    'Проверяет настройки, таблицы, учетные данные и подключения IMAP/SMTP.';

$string['crm_admin_tool_inbox_diagnostics_success'] =
    'Все проверки Inbox пройдены.';

$string['crm_admin_tool_inbox_diagnostics_failed'] =
    'Некоторые проверки Inbox завершились ошибкой.';

$string['crm_admin_tool_automations'] =
    'Запустить автоматизации';

$string['crm_admin_tool_automations_desc'] =
    'Немедленно запускает правила и сканеры автоматизации CRM.';

$string['crm_admin_tool_automations_success'] =
    'Автоматизации CRM выполнены.';

$string['crm_admin_tool_intelligence'] =
    'Пересчитать оценки Intelligence';

$string['crm_admin_tool_intelligence_desc'] =
    'Пересчитывает и сохраняет снимки оценок CRM.';

$string['crm_admin_tool_intelligence_success'] =
    'Оценки Intelligence пересчитаны.';

$string['crm_admin_tool_recommendations'] =
    'Пересчитать рекомендации';

$string['crm_admin_tool_recommendations_desc'] =
    'Запускает новый пакет движка рекомендаций CRM.';

$string['crm_admin_tool_recommendations_success'] =
    'Пакет рекомендаций обработан.';

$string['crm_admin_tool_recommendations_partial'] =
    'Пакет рекомендаций обработан частично или с ошибками.';

$string['crm_admin_tool_digital_reconciliation'] =
    'Сверить цифровые платежи';

$string['crm_admin_tool_digital_reconciliation_desc'] =
    'Проверяет ожидающие цифровые платежи у платежных провайдеров.';

$string['crm_admin_tool_digital_reconciliation_success'] =
    'Сверка цифровых платежей завершена.';

$string['crm_admin_tool_digital_reconciliation_partial'] =
    'Сверка цифровых платежей завершена с ошибками.';

$string['crm_admin_tool_help_validation'] =
    'Проверить Help Center';

$string['crm_admin_tool_help_validation_desc'] =
    'Проверяет статьи, руководства, онбординг и переводы Help Center.';

$string['crm_admin_tool_help_validation_success'] =
    'Help Center успешно прошёл проверку.';

$string['crm_admin_tool_help_validation_failed'] =
    'В Help Center обнаружены ошибки.';

$string['csplancommandsubtitle'] =
    'Открыть и управлять планом Customer Success этого пользователя';

$string['crm_admin_tool_confirmation_required'] =
    'Перед запуском необходимо явно подтвердить эту операцию.';

$string['crm_admin_tool_confirmation_checkbox'] =
    'Я понимаю последствия этой операции и подтверждаю её запуск.';

$string['crm_admin_tool_limit_help'] =
    'Значение по умолчанию: {$a->default}. Максимум: {$a->maximum}.';

$string['crm_admin_tool_unknown_actor'] =
    'Недоступный пользователь (#{$a})';

$string['err_invalid_redirect_url'] =
    'Платёжный шлюз вернул недопустимый адрес перенаправления.';

$string['payment_error_session_create'] =
    'Не удалось открыть страницу оплаты. Платёж не был выполнен. Повторите попытку через несколько минут.';

$string['payment_error_digital_session_create'] =
    'Не удалось открыть страницу оплаты покупки. Платёж не был выполнен.';

$string['payment_error_retry'] =
    'Не удалось запустить повторную попытку оплаты. Новый платёж не был выполнен.';

$string['payment_error_invalid_redirect'] =
    'Платёжный шлюз вернул недопустимый адрес. Платёж не был выполнен.';

$string['payment_error_provider_unavailable'] =
    'Платёжный шлюз временно недоступен. Платёж не был выполнен.';

$string['payment_error_reference'] =
    'Номер обращения: {$a}';

$string['crm_topbar_brand_suffix'] = 'CRM';
$string['crm_topbar_dashboard_link'] = 'Открыть панель CampusFR CRM';
$string['crm_topbar_moodle_admin'] = 'Администрирование Moodle';

$string['crm_topbar_user_menu'] = 'Открыть меню пользователя';
$string['crm_topbar_user_navigation'] = 'Навигация по учётной записи';
$string['crm_topbar_view_profile'] = 'Открыть профиль';
$string['crm_topbar_my_courses'] = 'Мои курсы';
$string['crm_topbar_my_campus'] = 'Мой Campus';
$string['crm_topbar_my_resources'] = 'Мои ресурсы';
$string['crm_topbar_my_purchases'] = 'Мои покупки';
$string['crm_topbar_shop'] = 'Магазин';
$string['crm_topbar_grades'] = 'Оценки';
$string['crm_topbar_calendar'] = 'Календарь';
$string['crm_topbar_preferences'] = 'Настройки';
$string['crm_topbar_switch_role'] = 'Сменить роль…';
$string['crm_topbar_logout'] = 'Выйти';

$string['crm_topbar_language'] = 'Язык';
$string['crm_topbar_language_menu'] = 'Выбрать язык';
$string['crm_topbar_language_navigation'] = 'Доступные языки';

$string['dashboard_personalization_open'] = 'Настроить дашборд';
$string['dashboard_personalization_title'] = 'Настроить дашборд';
$string['dashboard_personalization_description'] = 'Выберите отображаемые карточки и измените их порядок перетаскиванием или кнопками Вверх и Вниз.';
$string['dashboard_personalization_close'] = 'Закрыть настройки дашборда';
$string['dashboard_personalization_save'] = 'Сохранить расположение';
$string['dashboard_personalization_reset'] = 'Восстановить расположение по умолчанию';
$string['dashboard_personalization_reset_confirm'] = 'Восстановить расположение дашборда по умолчанию?';
$string['dashboard_personalization_save_error'] = 'Не удалось сохранить расположение дашборда.';
$string['dashboard_personalization_drag'] = 'Перетащите для перемещения';
$string['dashboard_personalization_move_up'] = 'Переместить карточку «{$a}» вверх';
$string['dashboard_personalization_move_down'] = 'Переместить карточку «{$a}» вниз';
$string['dashboard_personalization_visibility'] = 'Показывать карточку «{$a}»';
$string['dashboard_personalization_zone_hero'] = 'Основные показатели';
$string['dashboard_personalization_zone_main'] = 'Основной дашборд';
$string['dashboard_personalization_zone_side'] = 'Боковая колонка';
$string['dashboard_personalization_main_empty'] = 'Все карточки основного дашборда скрыты. Используйте кнопку настройки, чтобы снова их отобразить.';

$string['dashboard_personalization_card_stats'] = 'Основные показатели';
$string['dashboard_personalization_card_stats_description'] = 'Пользователи, подписки, пробные периоды, покупки и доход.';
$string['dashboard_personalization_card_intelligence'] = 'CRM Intelligence';
$string['dashboard_personalization_card_intelligence_description'] = 'Оценки, сегменты, возможности и приоритетные профили.';
$string['dashboard_personalization_card_assistant'] = 'CRM-ассистент';
$string['dashboard_personalization_card_assistant_description'] = 'Рекомендации и действия, предложенные ассистентом.';
$string['dashboard_personalization_card_inbox'] = 'CRM Inbox';
$string['dashboard_personalization_card_inbox_description'] = 'Сообщения, непрочитанные диалоги и недавняя активность.';
$string['dashboard_personalization_card_work'] = 'Рабочие задачи';
$string['dashboard_personalization_card_work_description'] = 'Назначенные, срочные, просроченные и свободные задачи.';
$string['dashboard_personalization_card_customer_success'] = 'Customer Success';
$string['dashboard_personalization_card_customer_success_description'] = 'Активные планы, прогресс, блокировки и критические ситуации.';
$string['dashboard_personalization_card_issues'] = 'Требует внимания';
$string['dashboard_personalization_card_issues_description'] = 'Проблемы и аномалии, требующие вмешательства.';
$string['dashboard_personalization_card_priorities'] = 'Ежедневные приоритеты';
$string['dashboard_personalization_card_priorities_description'] = 'Приоритетные профили и действия на сегодня.';
$string['dashboard_personalization_card_funnel'] = 'Воронка';
$string['dashboard_personalization_card_funnel_description'] = 'Привлечение, пробные периоды, конверсии и новые клиенты.';
$string['dashboard_personalization_card_trends'] = 'Тенденции';
$string['dashboard_personalization_card_trends_description'] = 'Изменение риска, вовлечённости и прогресса.';
$string['dashboard_personalization_card_intelligence_alerts'] = 'Интеллектуальные уведомления';
$string['dashboard_personalization_card_intelligence_alerts_description'] = 'Расширенные CRM-уведомления и контекст Customer Success.';
$string['dashboard_personalization_card_navigation'] = 'Административные ссылки';
$string['dashboard_personalization_card_navigation_description'] = 'Доступ к пользователям, планам, покупкам и инструментам.';
$string['dashboard_personalization_card_activity'] = 'Недавняя активность';
$string['dashboard_personalization_card_activity_description'] = 'Последние события, зарегистрированные в CRM.';
$string['dashboard_personalization_card_team'] = 'Команда';
$string['dashboard_personalization_card_team_description'] = 'Сводка элементов, назначенных текущему пользователю.';
$string['dashboard_personalization_zone_onboarding'] = 'Знакомство с CRM';

$string['workspace_toolbar_title'] = 'Режим редактирования';
$string['workspace_toolbar_description'] = 'Настройте рабочее пространство. Изменения расположения будут применены после сохранения.';
$string['workspace_toolbar_status_clean'] = 'Нет несохранённых изменений';
$string['workspace_toolbar_status_dirty'] = 'Есть несохранённые изменения';
$string['workspace_toolbar_status_saving'] = 'Сохранение…';
$string['workspace_toolbar_hidden_singular'] = 'скрытый элемент';
$string['workspace_toolbar_hidden_plural'] = 'скрытых элементов';
$string['workspace_toolbar_reset'] = 'Восстановить настройки';
$string['workspace_toolbar_cancel'] = 'Отменить';
$string['workspace_toolbar_save'] = 'Сохранить';
$string['workspace_item_type_card'] = 'Карточка';
$string['workspace_item_type_widget'] = 'Виджет';
$string['workspace_item_type_system'] = 'Системный элемент';
$string['workspace_item_drag_handle'] = 'Переместить этот элемент';
$string['workspace_item_drag_handle_named'] = 'Переместить элемент «{$a}»';
$string['workspace_item_menu_open_named'] = 'Открыть действия для элемента «{$a}»';
$string['workspace_item_menu_label_named'] = 'Доступные действия для элемента «{$a}»';
$string['workspace_item_move_before'] = 'Переместить выше';
$string['workspace_item_move_after'] = 'Переместить ниже';
$string['workspace_item_hide'] = 'Скрыть';
$string['workspace_item_reset'] = 'Сбросить изменения элемента';
$string['workspace_action_configure'] = 'Настроить';
$string['workspace_action_duplicate'] = 'Дублировать';

$string['dashboard_category_overview'] = 'Обзор';
$string['dashboard_category_intelligence'] = 'Интеллект';
$string['dashboard_category_operations'] = 'Операции';
$string['dashboard_category_customer_success'] = 'Customer Success';
$string['dashboard_category_navigation_activity'] = 'Навигация и активность';
$string['dashboard_category_team'] = 'Команда';
$string['dashboard_category_system'] = 'Система';
$string['dashboard_category_other'] = 'Другое';

$string['dashboard_personalization_width_compact'] = 'Компактная';
$string['dashboard_personalization_width_medium'] = 'Средняя';
$string['dashboard_personalization_width_full'] = 'На всю ширину';

$string['dashboard_personalization_type_card'] = 'Карточка';
$string['dashboard_personalization_type_widget'] = 'Виджет';
$string['dashboard_personalization_type_system'] = 'Системный элемент';

$string['dashboard_personalization_period_aware'] = 'Зависит от периода';
$string['dashboard_personalization_order_hint'] = 'Изменяйте порядок элементов прямо на дашборде в режиме редактирования.';
$string['dashboard_workspace_action_open_details'] = 'Открыть подробный обзор';
$string['dashboard_workspace_empty_hero'] = 'Основные показатели сейчас не отображаются.';
$string['dashboard_workspace_empty_main'] = 'В основной области сейчас нет отображаемых карточек.';
$string['dashboard_workspace_empty_side'] = 'В боковой колонке сейчас нет отображаемых элементов.';
$string['dashboard_period_year'] = 'Этот год';
$string['dashboard_period_all'] = 'За всё время';

$string['dashboard_trends_all_time_title'] = 'Сводные данные';
$string['dashboard_trends_all_time_subtitle'] = 'Данные с момента создания CRM';
$string['dashboard_trends_all_time_message'] = 'Для отображения тенденций необходим сопоставимый предыдущий период. Выберите Сегодня, Эту неделю, Этот месяц или Этот год.';

$string['inbox_workspace_name'] = 'Рабочее пространство Inbox';

$string['inbox_workspace_navigation'] = 'Навигация';

$string['inbox_workspace_list'] = 'Диалоги';

$string['inbox_workspace_reading'] = 'Просмотр';

$string['inbox_workspace_context'] = 'Контекст клиента';
$string['inbox_workspace_filters_label'] =
    'Фильтры Inbox';

$string['inbox_workspace_filters_description'] =
    'Поиск и фильтрация диалогов Inbox.';

$string['inbox_workspace_thread_list_label'] =
    'Список диалогов';

$string['inbox_workspace_thread_list_description'] =
    'Просмотр диалогов, соответствующих активным фильтрам.';

$string['inbox_thread_workspace_messages'] = 'Сообщения';
$string['inbox_thread_workspace_messages_description'] =
    'Просмотр полной истории диалога.';
$string['inbox_thread_workspace_reply'] = 'Ответить';
$string['inbox_thread_workspace_reply_description'] =
    'Подготовить ответ на этот диалог.';
$string['inbox_thread_workspace_context'] =
    'Диалог и контакт';
$string['inbox_thread_workspace_context_description'] =
    'Просмотр контакта, статуса и доступных действий.';
$string['inbox_thread_workspace_ai'] = 'ИИ-помощник';
$string['inbox_thread_workspace_ai_description'] =
    'Проанализировать диалог и подготовить ответ.';
$string['inbox_thread_workspace_context_zone'] =
    'Контекст диалога';
$string['inbox_workspace_personalization_open'] =
    'Настроить';
$string['inbox_workspace_personalization_title'] =
    'Настроить диалог';
$string['inbox_workspace_personalization_description'] =
    'Выберите видимые панели и измените порядок контекста диалога.';
$string['inbox_workspace_personalization_close'] =
    'Закрыть настройки';
$string['inbox_workspace_personalization_save_error'] =
    'Не удалось сохранить расположение диалога.';
$string['inbox_workspace_personalization_reset_confirm'] =
    'Сбросить расположение диалога?';

$string['inbox_workspace_zone_reading'] = 'Переписка';
$string['inbox_workspace_zone_context'] = 'Контекст';

$string['inbox_workspace_reading_placeholder_label'] =
    'Предпросмотр диалога';

$string['inbox_workspace_reading_placeholder_item_description'] =
    'Область для просмотра выбранного диалога.';

$string['inbox_workspace_reading_placeholder_title'] =
    'Выберите диалог';

$string['inbox_workspace_reading_placeholder_description'] =
    'Здесь появится предварительный просмотр переписки.';

$string['inbox_workspace_context_placeholder_label'] =
    'Контекст диалога';

$string['inbox_workspace_context_placeholder_item_description'] =
    'Область для информации о контакте и диалоге.';

$string['inbox_workspace_context_placeholder_title'] =
    'Контекстная информация';

$string['inbox_workspace_context_placeholder_description'] =
    'Выберите диалог, чтобы увидеть контакт, статус и полезную информацию.';

$string['inbox_thread_workspace_overview'] =
    'Обзор';

$string['inbox_thread_workspace_overview_description'] =
    'Статус, приоритет, почтовый ящик и основные сведения о переписке.';

$string['inbox_thread_workspace_contact'] =
    'Контакт';

$string['inbox_thread_workspace_contact_description'] =
    'Контактные данные и ссылка на соответствующий профиль CRM.';

$string['inbox_thread_workspace_actions'] =
    'Действия';

$string['inbox_thread_workspace_actions_description'] =
    'Доступные действия для управления этой перепиской.';

$string['inbox_thread_overview_account'] =
    'Почтовый ящик';

$string['inbox_thread_overview_folder'] =
    'Папка';

$string['inbox_thread_overview_messages'] =
    'Сообщения';

$string['inbox_thread_overview_unread'] =
    'Непрочитанные';

$string['inbox_thread_overview_assignment'] =
    'Назначение';

$string['inbox_thread_overview_last_message'] =
    'Последнее сообщение';

$string['inbox_thread_assignment_team'] =
    'Команда: {$a}';

$string['inbox_thread_assignment_user'] =
    'Пользователь: {$a}';

$string['inbox_thread_assignment_unassigned'] =
    'Не назначено';

$string['inbox_thread_contact_title'] =
    'Контакт';

$string['inbox_thread_contact_unavailable'] =
    'Контактные данные отсутствуют.';

$string['inbox_thread_contact_open_profile'] =
    'Открыть профиль CRM';

$string['inbox_thread_contact_external_description'] =
    'Этот контакт пока не связан с пользователем Moodle.';

$string['inbox_thread_actions_title'] =
    'Действия';

$string['inbox_thread_actions_description'] =
    'Измените статус, архивируйте переписку или создайте задачу для дальнейшей работы.';

$string['user360_workspace_region_label'] =
    'Рабочее пространство профиля пользователя';

$string['user360_workspace_hero'] =
    'Данные пользователя';

$string['user360_workspace_hero_description'] =
    'Основные данные пользователя, статус CRM, теги и сведения об аккаунте.';

$string['user360_workspace_zone_hero'] =
    'Профиль';

$string['user360_workspace_zone_main'] =
    'Основная информация';

$string['user360_workspace_zone_sidebar'] =
    'Дополнительная информация';

$string['user360_workspace_zone_timeline'] =
    'История';

$string['user360_workspace_personalization_open'] =
    'Настроить профиль';

$string['user360_workspace_personalization_title'] =
    'Настройка профиля пользователя';

$string['user360_workspace_personalization_description'] =
    'Выберите отображаемые панели и расположите их в удобном для работы порядке.';

$string['user360_workspace_personalization_close'] =
    'Закрыть настройку';

$string['user360_workspace_personalization_save_error'] =
    'Не удалось сохранить настройки профиля пользователя.';

$string['user360_workspace_personalization_reset_confirm'] =
    'Сбросить расположение блоков профиля пользователя?';

$string['user360_workspace_intelligence'] =
    'CRM-аналитика';

$string['user360_workspace_intelligence_description'] =
    'Оценки, тенденции, сегменты, возможности и рекомендации для этого пользователя.';

$string['user360_workspace_customer_success'] =
    'Customer Success';

$string['user360_workspace_customer_success_description'] =
    'Планы сопровождения, последующие действия и поддержка пользователя.';

$string['user360_workspace_inbox'] =
    'Входящие';

$string['user360_workspace_inbox_description'] =
    'Переписка, непрочитанные сообщения и последние обращения пользователя.';

$string['user360_workspace_notes'] =
    'Заметки';

$string['user360_workspace_notes_description'] =
    'Внутренние CRM-заметки, связанные с этим пользователем.';

$string['user360_workspace_work_items'] =
    'Задачи';

$string['user360_workspace_work_items_description'] =
    'Задачи и рабочие элементы, связанные с этим пользователем.';

$string['user360_workspace_timeline'] =
    'Хронология';

$string['user360_workspace_timeline_description'] =
    'Полная хронологическая история событий, связанных с пользователем.';

$string['user360_workspace_zone_summary'] =
    'Сводка';

$string['user360_workspace_stats'] =
    'Обзор';

$string['user360_workspace_stats_description'] =
    'CRM-статус, подписки, покупки, доступные курсы, доход и последняя активность.';

$string['user360_workspace_quick_actions'] =
    'Быстрые действия';

$string['user360_workspace_quick_actions_description'] =
    'Административные действия и быстрое добавление заметки для пользователя.';

$string['user360_workspace_assistant'] =
    'CRM-ассистент';

$string['user360_workspace_assistant_description'] =
    'Анализ, рекомендации и действия, предложенные CRM-ассистентом.';

$string['user360_workspace_commercial'] =
    'Коммерческая активность';

$string['user360_workspace_commercial_description'] =
    'Подписки и цифровые покупки, связанные с пользователем.';

$string['user360_workspace_courses'] =
    'Доступные курсы';

$string['user360_workspace_courses_description'] =
    'Курсы, доступные этому пользователю в настоящее время.';

$string['crm_user_not_found'] = 'Пользователь не найден';
$string['crm_user_not_found_description'] = 'Запрошенный профиль CRM невозможно отобразить.';
$string['crm_user_not_found_message'] = 'Активный пользователь Moodle с идентификатором {$a} не найден. Возможно, пользователь был удалён или ссылка устарела.';
$string['crm_user_not_found_back'] = 'Вернуться к пользователям';
$string['crm_user_deleted'] = 'Удалённая учётная запись Moodle';
$string['crm_user_deleted_description'] = 'Этот пользователь больше не активен в Moodle.';
$string['crm_user_deleted_message'] = 'Учётная запись Moodle с идентификатором {$a} была удалена. Некоторые исторические данные CRM всё ещё могут быть доступны.';
$string['crm_user_history_title'] = 'Исторический профиль CRM · пользователь {$a}';
$string['crm_user_history_description'] = 'Данные CRM, сохранённые для удалённой учётной записи Moodle.';
$string['crm_user_history_readonly'] = 'Исторический профиль только для чтения';
$string['crm_user_history_readonly_description'] = 'Учётная запись Moodle с идентификатором {$a} была удалена. Отображаемые здесь данные нельзя использовать для действий с учётной записью.';
$string['crm_user_history_summary'] = 'Сводка исторического профиля CRM';
$string['crm_user_history_userid'] = 'Идентификатор Moodle';
$string['crm_user_history_subscriptions'] = 'История подписок';
$string['crm_user_history_digital_purchases'] = 'Цифровые покупки';
$string['crm_user_history_courses'] = 'История курсов';
$string['crm_user_history_last_activity'] = 'Последняя активность CRM';
$string['crm_user_history_revenue'] = 'Историческая выручка';
$string['crm_user_history_open_users'] = 'Вернуться к пользователям';
$string['crm_user_history_open_inbox'] = 'Открыть во входящих';
$string['crm_user_history_open_work'] = 'Открыть рабочие задачи';
$string['crm_user_history_no_subscriptions'] = 'Исторические подписки не найдены.';
$string['crm_user_history_no_digital_purchases'] = 'Исторические цифровые покупки не найдены.';
$string['crm_user_history_no_notes'] = 'Исторические заметки CRM не найдены.';
$string['crm_user_history_no_tags'] = 'Исторические теги CRM не найдены.';
$string['crm_user_history_unknown_plan'] = 'Недоступный тариф';
$string['crm_user_history_unknown_product'] = 'Недоступный продукт';
$string['crm_user_history_plan'] = 'Тариф';
$string['crm_user_history_amount'] = 'Сумма';
$string['crm_notes'] = 'Заметки CRM';
$string['crm_tags'] = 'Теги CRM';

$string['crm_inbox_invalid_form_action'] = 'Действие формы входящих сообщений отсутствует или является недопустимым.';

$string['crm_timeline_category_commercial'] = 'Продажи';
$string['crm_timeline_category_learning'] = 'Обучение';
$string['crm_timeline_category_inbox'] = 'Входящие';
$string['crm_timeline_category_notes'] = 'Заметки и теги';
$string['crm_timeline_category_work'] = 'Рабочие задачи';
$string['crm_timeline_category_customer_success'] = 'Customer Success';
$string['crm_timeline_category_automation'] = 'Автоматизации';
$string['crm_timeline_category_administration'] = 'Администрирование';

$string['crm_timeline_search'] = 'Поиск по хронологии';
$string['crm_timeline_period'] = 'Период хронологии';
$string['crm_timeline_period_all'] = 'За всё время';
$string['crm_timeline_period_7_days'] = 'Последние 7 дней';
$string['crm_timeline_period_30_days'] = 'Последние 30 дней';
$string['crm_timeline_period_90_days'] = 'Последние 90 дней';
$string['crm_timeline_period_year'] = 'Последние 12 месяцев';
$string['crm_timeline_important_only'] = 'Только важные события';
$string['crm_timeline_filter_categories'] = 'Фильтр хронологии по категориям';
$string['crm_timeline_results_count'] = 'Отображено событий: {$a}';
$string['crm_timeline_no_filtered_results'] = 'Нет событий, соответствующих выбранным фильтрам.';
$string['crm_timeline_open_event'] = 'Открыть';
$string['crm_timeline_event'] = 'Событие CRM';
$string['crm_timeline_yesterday'] = 'Вчера';
$string['crm_timeline_load_more'] = 'Показать больше событий';
$string['crm_timeline_loading'] = 'Загрузка…';
$string['crm_timeline_loading_error'] = 'Повторить загрузку';
$string['crm_timeline_loaded_events'] = 'загружено событий';
$string['crm_timeline_important_events'] = 'важных событий';
$string['crm_timeline_latest_event'] = 'Последнее событие';
$string['crm_timeline_view_full'] = 'Открыть полную хронологию';

$string['user360_workspace_timeline_summary'] = 'Сводка хронологии';
$string['user360_workspace_timeline_summary_description'] = 'Показывает последние события и количество важных элементов.';

$string['crm_navigation_toggle'] = 'Навигация';
$string['crm_navigation_open'] = 'Открыть навигацию CRM';
$string['crm_navigation_close'] = 'Закрыть навигацию CRM';
$string['crm_command_center_short_label'] = 'Поиск';

$string['crm_inbox_back_to_thread'] =
    'Вернуться к переписке';

$string['crm_inbox_reply_help_subtitle'] =
    'Напишите, сохраните или отправьте ответ в этой переписке.';
$string['crm_work_create_subtitle'] =
    'Создайте задачу, контрольное действие или другое действие CRM и назначьте его сотруднику или команде.';
$string['crm_work_teams_subtitle'] =
    'Создавайте команды CRM и управляйте их участниками, руководителями и доступностью.';
$string['crm_customer_success_plan_subtitle'] =
    'Просмотрите цели, действия, сроки и сигналы, связанные с этим планом Customer Success.';
$string['crm_work_suggestion_subtitle'] =
    'Проверьте предложение Ассистента перед созданием Work Item.';
$string['crm_admin_tool_history_subtitle'] =
    'Просмотрите последние запуски административных инструментов и их результаты.';

$string['crm_breadcrumb_navigation'] =
    'Навигационная цепочка CRM';
$string['crm_help_home_subtitle'] =
    'Откройте документацию, практические руководства и инструменты диагностики CRM CampusFR.';
$string['crm_skip_to_content'] =
    'Перейти непосредственно к содержимому';

$string['crm_inbox_preview_loading'] =
    'Загрузка переписки…';

$string['crm_inbox_preview_error'] =
    'Не удалось загрузить предварительный просмотр переписки.';

$string['crm_inbox_preview_loaded'] =
    'Переписка «{$a}» загружена.';

$string['crm_inbox_preview_open_full'] =
    'Открыть всю переписку';

$string['crm_inbox_preview_manage'] =
    'Ответить и управлять перепиской';

$string['crm_inbox_preview_reading_region'] =
    'Предварительный просмотр переписки';

$string['crm_inbox_preview_context_region'] =
    'Контекст контакта';

$string['crm_commerce_nav'] = 'Коммерция';
$string['crm_commerce_title'] = 'Коммерция';
$string['crm_commerce_description'] = 'Управляйте подписками, цифровыми покупками и продуктами в едином коммерческом пространстве.';

$string['crm_commerce_no_access'] = 'У вас пока нет доступа к коммерческим модулям.';

$string['crm_commerce_subscriptions_title'] = 'Подписки';
$string['crm_commerce_subscriptions_description'] = 'Просматривайте и управляйте подписками, платными зачислениями и их историей.';

$string['crm_commerce_imports_title'] = 'Импорт';
$string['crm_commerce_imports_description'] = 'Импортируйте подписки и используйте связанные инструменты импорта.';

$string['crm_commerce_configuration_title'] = 'Настройки коммерции';
$string['crm_commerce_configuration_description'] = 'Управляйте тарифами, ценами, правами доступа, переводами и переходами между тарифами.';

$string['crm_commerce_digital_products_title'] = 'Цифровые продукты';
$string['crm_commerce_digital_products_description'] = 'Создавайте и управляйте цифровыми продуктами, доступными в магазине.';

$string['crm_commerce_digital_purchases_title'] = 'Цифровые покупки';
$string['crm_commerce_digital_purchases_description'] = 'Просматривайте цифровые покупки, платежи и предоставленный клиентам доступ.';

$string['crm_commerce_statistics_title'] = 'Коммерческая статистика';
$string['crm_commerce_statistics_description'] = 'Анализируйте цифровые продажи, выручку и основные коммерческие показатели.';

$string['admin_card_commerce_title'] = 'Коммерция';
$string['admin_card_commerce_description'] = 'Откройте подписки, цифровые продукты, покупки, импорт, статистику и коммерческие инструменты.';

$string['crm_subscriptions_title'] = 'Подписки';
$string['crm_subscriptions_description'] = 'Просматривайте и управляйте платными зачислениями, периодами доступа и назначенными пользователям тарифами.';
$string['crm_subscriptions_breadcrumb'] = 'Подписки';
$string['crm_subscription_view_description'] = 'Просматривайте коммерческие данные, сроки доступа, связанный платёж и идентификаторы платёжного провайдера.';
$string['crm_subscription_edit_description'] = 'Измените сроки доступа и статус этой подписки.';
$string['crm_subscription_add_description'] = 'Назначьте тариф существующему пользователю вручную или создайте новый аккаунт перед зачислением.';
$string['crm_subscriptions_export_title'] = 'Экспорт подписок';
$string['crm_subscriptions_export_description'] = 'Скачайте подписки и основные коммерческие данные в формате Excel.';
$string['crm_subscriptions_export_help'] = 'Книга содержит отдельные листы для долгосрочных тарифов, курса A1 и пробных подписок.';
$string['crm_subscriptions_export_download'] = 'Скачать файл Excel';
$string['crm_subscriptions_export_sheet_long'] = '1 год - 3 года - бессрочно';
$string['crm_subscriptions_export_sheet_a1'] = 'Курс A1';
$string['crm_subscriptions_export_sheet_trial'] = 'Пробный период';
$string['crm_subscriptions_import_description'] = 'Импортируйте несколько подписок из CSV-файла и проверьте данные перед созданием.';
$string['crm_subscriptions_import_result_title'] = 'Результат импорта';
$string['crm_subscriptions_import_result_description'] = 'Просмотрите импортированные подписки и строки, пропущенные во время обработки.';
$string['crm_subscriptions_view_list'] = 'Открыть подписки';
$string['crm_subscriptions_import_another'] = 'Импортировать другой файл';
$string['crm_subscription_configuration_title'] = 'Настройки подписок';
$string['crm_subscription_configuration_description'] = 'Управляйте тарифами, сроками действия и наборами курсов, к которым они предоставляют доступ.';
$string['crm_plan_prices_description'] = 'Управляйте ценами в разных валютах и идентификаторами цен платёжного провайдера для этого тарифа.';
$string['crm_plan_translations_title'] = 'Переводы тарифов';
$string['crm_plan_translations_description'] = 'Управляйте переведёнными названиями и содержанием коммерческих тарифов на доступных языках.';
$string['crm_plan_entitlements_description'] = 'Настройте курсы, роли, группы и уровни доступа, автоматически предоставляемые этим тарифом.';
$string['crm_plan_upgrades_description'] = 'Настройте разрешённые переходы между тарифами и способ расчёта стоимости.';
$string['crm_scope_translations_title'] = 'Переводы областей доступа';
$string['crm_scope_translations_description'] = 'Управляйте переведёнными названиями областей доступа к курсам, используемых тарифами.';
$string['crm_digital_products_description'] = 'Управляйте цифровыми продуктами, файлами, переводами, ценами и доступностью в магазине.';
$string['crm_digital_product_add_description'] = 'Создайте цифровой продукт, загрузите файлы и подготовьте коммерческие тексты на французском, английском и русском языках.';
$string['crm_digital_product_edit_description'] = 'Измените файлы, цены, доступность и переведённые материалы цифрового продукта.';
$string['crm_digital_purchase_view_description'] = 'Просматривайте коммерческие данные, платёж, доступ к файлу и техническую информацию о цифровой покупке.';
$string['crm_digital_sales_stats_description'] = 'Анализируйте объём и накопительную динамику продаж цифровых продуктов за выбранный период.';

$string['crm_commerce_section_navigation'] = 'Дополнительная навигация раздела «Коммерция»';
$string['crm_commerce_nav_overview'] = 'Обзор';
$string['crm_commerce_nav_subscriptions'] = 'Подписки';
$string['crm_commerce_nav_digital_purchases'] = 'Цифровые покупки';
$string['crm_commerce_nav_digital_products'] = 'Цифровые продукты';
$string['crm_commerce_nav_statistics'] = 'Статистика';
$string['crm_commerce_nav_configuration'] = 'Настройки';

$string['settings:commerce_migration_heading'] = 'Commerce — миграция и безопасность';
$string['settings:commerce_migration_heading_desc'] = 'Расширенные настройки платежных потоков Commerce. Изменения могут повлиять на выручку: проверяйте каждый сценарий и сохраняйте возможность отката.';
$string['settings:commerce_fulfillment_enabled'] = 'Включить fulfillment Commerce';
$string['settings:commerce_fulfillment_enabled_desc'] =
    'Использует сертифицированный Commerce fulfillment после подтверждения платежа. Отключите этот параметр для немедленного возврата к Legacy-обработке платежа.';
$string['settings:commerce_checkout_enabled'] =
    'Включить оформление платежей Commerce';

$string['settings:commerce_checkout_enabled_desc'] =
    'Использует сертифицированную архитектуру Commerce для создания платежей Stripe в EUR и Alfa в RUB. Отключите этот параметр для немедленного возврата к Legacy checkout.';

$string['crm_help_category_commerce'] =
    'Commerce и платежи';

$string['crm_help_category_commerce_desc'] =
    'Архитектура Commerce, оформление платежей, провайдеры, fulfillment, эксплуатация и диагностика.';

$string['crm_help_article_commerce_overview_title'] =
    'Архитектура Commerce';

$string['crm_help_article_commerce_overview_summary'] =
    'Обзор покупок Commerce, платежей, checkout и fulfillment.';

$string['crm_help_article_commerce_operations_title'] =
    'Эксплуатация Commerce в production';

$string['crm_help_article_commerce_operations_summary'] =
    'Конфигурация, аварийные выключатели, провайдеры и безопасный откат.';

$string['crm_help_article_commerce_diagnostics_title'] =
    'Аудит и диагностика Commerce';

$string['crm_help_article_commerce_diagnostics_summary'] =
    'Команды проверки, сертификации, целостности и диагностики fulfillment.';

$string['crm_help_article_commerce_extension_title'] =
    'Расширение архитектуры Commerce';

$string['crm_help_article_commerce_extension_summary'] =
    'Добавление провайдера, типа покупки или fulfillment-handler без обхода контрактов Commerce.';

$string['settings:commerce_dual_write_enabled'] = 'Включить двойную запись Commerce';
$string['settings:commerce_dual_write_enabled_desc'] = 'После изменения Legacy-покупки синхронизирует и проверяет нативный снимок Commerce. По умолчанию отключено.';
$string['settings:commerce_dual_write_strict'] = 'Строгая двойная запись Commerce';
$string['settings:commerce_dual_write_strict_desc'] = 'Прерывает исходную операцию при ошибке нативной синхронизации. На начальном этапе наблюдения оставьте выключенным.';
$string['settings:commerce_native_read_shadow_enabled'] = 'Включить теневое чтение Commerce';
$string['settings:commerce_native_read_shadow_enabled_desc'] = 'Дополнительно читает и сравнивает нативный снимок, но всегда возвращает Legacy. По умолчанию отключено.';
$string['settings:commerce_native_read_shadow_strict'] = 'Строгое теневое чтение Commerce';
$string['settings:commerce_native_read_shadow_strict_desc'] = 'Вызывает исключение при расхождении. Использовать только для тестов и аудитов DEV.';

$string['settings:commerce_runtime_read_mode'] = 'Режим чтения Commerce runtime';
$string['settings:commerce_runtime_read_mode_desc'] = 'Выбирает источник данных для runtime I7. Экраны CRM и студента будут переведены в I8 и I9.';
$string['settings:commerce_runtime_read_mode_legacy'] = 'Только Legacy';
$string['settings:commerce_runtime_read_mode_shadow'] = 'Shadow: вернуть Legacy и сравнить Native';
$string['settings:commerce_runtime_read_mode_native'] = 'Только Native';
$string['settings:commerce_runtime_read_mode_auto'] = 'Auto: Native с автоматическим fallback на Legacy';
$string['settings:commerce_runtime_read_strict'] = 'Строгий режим чтения Commerce runtime';
$string['settings:commerce_runtime_read_strict_desc'] = 'Выбрасывает исключение при fallback, расхождении или отсутствии данных. Только для DEV.';
$string['settings:commerce_native_crm_reads_enabled'] = 'Native-чтение Commerce для CRM';
$string['settings:commerce_native_crm_reads_enabled_desc'] = 'Использует слой Native-чтения I10C для CRM.';
$string['settings:commerce_native_admin_reads_enabled'] = 'Native-чтение Commerce для администрирования';
$string['settings:commerce_native_admin_reads_enabled_desc'] = 'Использует слой Native-чтения I10C для административных страниц.';
$string['settings:commerce_native_user_reads_enabled'] = 'Native-чтение Commerce для страниц пользователя';
$string['settings:commerce_native_user_reads_enabled_desc'] = 'Использует слой Native-чтения I10C для пользовательских страниц.';
$string['settings:commerce_native_email_reads_enabled'] = 'Native-чтение Commerce для писем';
$string['settings:commerce_native_email_reads_enabled_desc'] = 'Использует слой Native-чтения I10C для контекстов писем.';
$string['settings:commerce_native_task_reads_enabled'] = 'Native-чтение Commerce для задач';
$string['settings:commerce_native_task_reads_enabled_desc'] = 'Использует слой Native-чтения I10C для плановых задач Commerce.';
$string['settings:commerce_native_shadow_compare_enabled'] = 'Сравнивать Native и Legacy чтение';
$string['settings:commerce_native_shadow_compare_enabled_desc'] = 'Выполняет неблокирующее Shadow-сравнение источников Native и Legacy.';
$string['settings:commerce_native_legacy_fallback_enabled'] = 'Разрешить fallback на Legacy';
$string['settings:commerce_native_legacy_fallback_enabled_desc'] = 'Использует Legacy-данные, если Native-чтение недоступно.';

// I10D Native-aware commands.
$string['settings:commerce_native_dual_write_enabled'] = 'Включить Native dual-write I10D';
$string['settings:commerce_native_dual_write_enabled_desc'] = 'Разрешает сервисам команд Commerce синхронизировать Legacy-записи с Native-хранилищем. По умолчанию отключено.';
$string['settings:commerce_native_task_dual_write_enabled'] = 'Включить dual-write I10D для задач';
$string['settings:commerce_native_task_dual_write_enabled_desc'] = 'Разрешает плановым задачам Commerce синхронизировать Legacy-изменения с Native-хранилищем. По умолчанию отключено.';
$string['settings:commerce_native_shadow_write_compare_enabled'] = 'Включить Shadow-сравнение записей I10D';
$string['settings:commerce_native_shadow_write_compare_enabled_desc'] = 'Сравнивает состояния Legacy и Native после команды, не меняя результат для пользователя.';

$string['commerce_native_reconciliation_enabled'] = 'Сверка Native Commerce';
$string['commerce_native_reconciliation_enabled_desc'] = 'Включает сверку Native Commerce.';
$string['commerce_native_repair_enabled'] = 'Исправление Native Commerce';
$string['commerce_native_repair_enabled_desc'] = 'Разрешает явные исправления во время сверки.';

// Phase 7.94E4 - Unified Commerce Product Editor.
$string['crm_commerce_nav_products'] = 'Товары';
$string['commerce_products_title'] = 'Товары Commerce';
$string['commerce_products_description'] = 'Управление единым каталогом Native Commerce.';
$string['commerce_product_add'] = 'Добавить товар';
$string['commerce_product_sku'] = 'SKU';
$string['commerce_product_name'] = 'Название';
$string['commerce_product_type'] = 'Тип';
$string['commerce_product_status'] = 'Статус';
$string['commerce_product_description'] = 'Описание';
$string['commerce_product_definition'] = 'Состав';
$string['commerce_product_definition_counts'] = 'Цены: {$a->prices}; переводы: {$a->translations}; компоненты: {$a->components}; права: {$a->entitlements}';
$string['commerce_bundle_edit_components'] = 'Изменить компоненты набора';

// Phase 7.94E5 - Bundle visual component editor.
$string['commerce_bundle_components_title'] = 'Компоненты — {$a}';
$string['commerce_bundle_components_help'] = 'Выберите товары, количество и порядок отображения. Пустые строки игнорируются. При сохранении проверяется всё рекурсивное раскрытие набора.';
$string['commerce_bundle_component_number'] = 'Компонент {$a}';
$string['commerce_bundle_component_product'] = 'Товар';
$string['commerce_bundle_component_quantity'] = 'Количество';
$string['commerce_bundle_component_order'] = 'Порядок';
$string['commerce_bundle_add_rows'] = 'Добавить строки';
$string['commerce_bundle_preview_title'] = 'Предпросмотр раскрытого набора';

// Phase 7.94E6 - Bundle preview and guided CRM workflow.
$string['commerce_product_workflow'] = 'Этапы настройки продукта';
$string['commerce_product_step_information'] = 'Информация';
$string['commerce_product_step_components'] = 'Состав';
$string['commerce_product_step_preview'] = 'Предпросмотр';
$string['commerce_product_step_pricing'] = 'Цена';
$string['commerce_bundle_open_preview'] = 'Открыть полный предпросмотр';
$string['commerce_bundle_preview_eyebrow'] = 'Проверка перед публикацией';
$string['commerce_bundle_preview_intro'] = 'Проверьте включённые продукты, их количество, доступные цены и предоставляемые права.';
$string['commerce_bundle_preview_unavailable'] = 'Предпросмотр пока недоступен';
$string['commerce_bundle_fix_components'] = 'Исправить состав';
$string['commerce_bundle_preview_products'] = 'Конечные продукты';
$string['commerce_bundle_preview_quantity'] = 'Общее количество';
$string['commerce_bundle_preview_entitlements'] = 'Заявленные права';
$string['commerce_bundle_preview_depth'] = 'Максимальная глубина';
$string['commerce_bundle_preview_empty'] = 'В этом наборе пока нет конечных продуктов.';
$string['commerce_bundle_preview_prices'] = 'Активные цены продукта';
$string['commerce_bundle_preview_rights'] = 'Предоставляемые права';
$string['commerce_bundle_preview_paths'] = 'Пути состава';
$string['commerce_no_active_price'] = 'Нет активной цены';
$string['commerce_no_entitlement'] = 'Права не определены';
$string['commerce_entitlement_lifetime'] = 'Бессрочно';
$string['commerce_back_to_products'] = 'Назад к продуктам';

// Phase 7.94E7 - Bundle pricing.
$string['commerce_bundle_pricing_title'] = 'Цена — {$a}';
$string['commerce_bundle_pricing_eyebrow'] = 'Коммерческая стратегия';
$string['commerce_bundle_pricing_intro'] = 'Выберите способ расчёта цены набора и сразу проверьте результат для каждой валюты.';
$string['commerce_bundle_pricing_method'] = 'Метод расчёта';
$string['commerce_bundle_pricing_method_help'] = 'Фиксированная цена использует цену самого набора. Сумма складывает цены продуктов. Скидка применяется к этой сумме.';
$string['commerce_bundle_pricing_fixed'] = 'Фиксированная цена набора';
$string['commerce_bundle_pricing_sum'] = 'Сумма цен компонентов';
$string['commerce_bundle_pricing_discount'] = 'Сумма компонентов со скидкой';
$string['commerce_bundle_discount_percent'] = 'Скидка (%)';
$string['commerce_bundle_fixed_prices'] = 'Фиксированные цены набора';
$string['commerce_bundle_fixed_prices_help'] = 'Используются только для фиксированной цены. Оставьте пустым, чтобы не менять существующую цену.';
$string['commerce_bundle_price_simulation'] = 'Текущий расчёт';
$string['commerce_bundle_final_price'] = 'Итоговая цена набора';
$string['commerce_bundle_component_total'] = 'Стоимость отдельно';
$string['commerce_bundle_savings'] = 'Экономия клиента';

// 7.94E8 - Единый менеджер товаров Commerce.
$string['commerce_product_type_course_access'] = 'Доступ к курсу';
$string['commerce_product_type_digital_download'] = 'Цифровой продукт';
$string['commerce_product_type_bundle'] = 'Пакет / Bundle';
$string['commerce_product_type_service'] = 'Услуга';
$string['commerce_product_status_draft'] = 'Черновик';
$string['commerce_product_status_active'] = 'Активен';
$string['commerce_product_status_inactive'] = 'Неактивен';
$string['commerce_product_status_archived'] = 'В архиве';
$string['commerce_product_edit_steps'] = 'Этапы настройки продукта';
$string['commerce_product_type_help'] = 'Тип можно менять, пока продукт остаётся черновиком. SKU — стабильный технический идентификатор, который нельзя изменить после создания.';
$string['commerce_product_description_help'] = 'Описание по умолчанию используется как резервное. Тексты для клиента следует заполнить в переводах ниже.';
$string['commerce_product_translations_title'] = 'Многоязычный контент';
$string['commerce_product_translations_help'] = 'Укажите название и коммерческие описания для клиента на каждом языке.';
$string['commerce_product_short_description'] = 'Краткое описание';
$string['commerce_product_summary'] = 'Обзор продукта';
$string['commerce_prices'] = 'Цена';
$string['commerce_translations'] = 'переводов';
$string['commerce_components'] = 'компонентов';
$string['commerce_entitlements'] = 'прав';
$string['commerce_products_empty'] = 'Нет продуктов, соответствующих выбранным фильтрам.';
$string['commerce_product_archived'] = 'Продукт помещён в архив. Он остаётся в истории и больше не предлагается к продаже.';
$string['commerce_products_card_description'] = 'Управление единым каталогом, пакетами, переводами, ценами и связанными правами.';
$string['commerce_entitlement_course_access'] = 'Доступ к курсу {$a->courseid} — {$a->level}';
$string['commerce_entitlement_course_generic'] = 'Доступ к курсу: {$a}';
$string['commerce_entitlement_digital_product'] = 'Скачивание цифрового продукта №{$a}';
$string['commerce_entitlement_digital_generic'] = 'Цифровой продукт: {$a}';
$string['commerce_entitlement_generic'] = '{$a->type}: {$a->resource}';
$string['commerce_entitlement_access_full'] = 'полный доступ';
$string['commerce_entitlement_access_grammar'] = 'доступ к грамматике';
$string['commerce_entitlement_access_trial'] = 'пробный доступ';
$string['commerce_bundle_preview_pricing'] = 'Тарификация пакета';
$string['commerce_bundle_pricing_incomplete'] = 'Тарификация для этой валюты ещё не завершена.';

// 7.94E9 - Итоговая сертификация.
$string['commerce_bundle_phase_certification'] = 'Сертификация товаров и пакетов Commerce';

$string['commerce_product_type_unknown'] = 'Другой продукт';
$string['commerce_product_status_unknown'] = 'Неизвестный статус';
$string['commerce_entitlement_course_named'] = 'Доступ к курсу «{$a->course}» — {$a->level}';
$string['commerce_entitlement_digital_named'] = 'Доступ к цифровому продукту «{$a}»';
$string['commerce_entitlement_generic_readable'] = '{$a->type}: {$a->resource}';
$string['commerce_entitlement_type_course'] = 'Доступ к курсу';
$string['commerce_entitlement_type_digital_product'] = 'Цифровой доступ';
$string['commerce_entitlement_type_other'] = 'Другое право';
$string['commerce_course_fallback'] = 'Курс №{$a}';
$string['commerce_digital_product_fallback'] = 'Цифровой продукт №{$a}';
$string['commerce_entitlement_access_generic'] = 'стандартный доступ';
$string['commerce_product_archive'] = 'Архивировать';
$string['commerce_bundle_add_currency'] = 'Добавить другую валюту';
$string['commerce_bundle_add_currency_help'] = 'Введите любой код валюты ISO 4217, например USD, GBP, CAD или AUD.';

$string['commerce_price'] = 'Цена';

$string['commerce_bundle_component_comparison_unavailable'] = 'Цена пакета активна. Общая стоимость компонентов и экономия клиента появятся, когда для всех компонентов будет задана активная цена в этой валюте.';

$string['commerce_fulfillment_shadow_enabled'] = 'Включить Shadow нативного fulfillment';
$string['commerce_fulfillment_shadow_enabled_desc'] = 'Запускает нативный fulfillment в dry-run после Legacy и сохраняет сравнения без изменения прав.';
$string['commerce_runtime_mode'] = 'Режим выполнения Commerce fulfillment';
$string['commerce_runtime_mode_desc'] = 'Выберите основной механизм fulfillment. Legacy используется по умолчанию; Shadow сохраняет Legacy основным; Native делает Native-механизм основным.';
$string['commerce_runtime_mode_legacy'] = 'Legacy';
$string['commerce_runtime_mode_shadow'] = 'Shadow';
$string['commerce_runtime_mode_native'] = 'Native';
$string['commerce_runtime_native_fallback_enabled'] = 'Включить автоматический возврат к Legacy';
$string['commerce_runtime_native_fallback_enabled_desc'] = 'Если Native fulfillment завершится исключением, немедленно выполнить Legacy-путь. Оставьте включённым во время DEV-переключения.';


// Commerce 7.95B1 — shared UX vocabulary.
$string['commerce_vocabulary_product_type_client_course_access'] = 'Курс';
$string['commerce_vocabulary_product_type_client_digital_download'] = 'Цифровой материал';
$string['commerce_vocabulary_product_type_client_bundle'] = 'Набор';
$string['commerce_vocabulary_product_type_client_service'] = 'Услуга';
$string['commerce_vocabulary_product_type_crm_course_access'] = 'Доступ к курсу';
$string['commerce_vocabulary_product_type_crm_digital_download'] = 'Цифровой продукт';
$string['commerce_vocabulary_product_type_crm_bundle'] = 'Пакет';
$string['commerce_vocabulary_product_type_crm_service'] = 'Услуга';
$string['commerce_vocabulary_product_type_unknown'] = 'Другой продукт';
$string['commerce_vocabulary_product_status_client_active'] = 'Доступно';
$string['commerce_vocabulary_product_status_client_draft'] = 'Скоро';
$string['commerce_vocabulary_product_status_client_inactive'] = 'Недоступно';
$string['commerce_vocabulary_product_status_client_archived'] = 'Недоступно';
$string['commerce_vocabulary_product_status_crm_active'] = 'Активен';
$string['commerce_vocabulary_product_status_crm_draft'] = 'Черновик';
$string['commerce_vocabulary_product_status_crm_inactive'] = 'Неактивен';
$string['commerce_vocabulary_product_status_crm_archived'] = 'В архиве';
$string['commerce_vocabulary_product_status_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_purchase_status_client_draft'] = 'Черновик';
$string['commerce_vocabulary_purchase_status_client_created'] = 'Создано';
$string['commerce_vocabulary_purchase_status_client_prepared'] = 'Подготовлено';
$string['commerce_vocabulary_purchase_status_client_payment_pending'] = 'Ожидает оплаты';
$string['commerce_vocabulary_purchase_status_client_authorized'] = 'Авторизовано';
$string['commerce_vocabulary_purchase_status_client_captured'] = 'Оплата получена';
$string['commerce_vocabulary_purchase_status_client_paid'] = 'Оплачено';
$string['commerce_vocabulary_purchase_status_client_fulfillment_pending'] = 'Ожидает предоставления доступа';
$string['commerce_vocabulary_purchase_status_client_fulfilled'] = 'Выполнено';
$string['commerce_vocabulary_purchase_status_client_completed'] = 'Завершено';
$string['commerce_vocabulary_purchase_status_client_active'] = 'Активно';
$string['commerce_vocabulary_purchase_status_client_expired'] = 'Истекло';
$string['commerce_vocabulary_purchase_status_client_replaced'] = 'Заменено';
$string['commerce_vocabulary_purchase_status_client_cancelled'] = 'Отменено';
$string['commerce_vocabulary_purchase_status_client_failed'] = 'Ошибка';
$string['commerce_vocabulary_purchase_status_client_refunded'] = 'Возвращено';
$string['commerce_vocabulary_purchase_status_client_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_purchase_status_crm_draft'] = 'Черновик';
$string['commerce_vocabulary_purchase_status_crm_created'] = 'Создано';
$string['commerce_vocabulary_purchase_status_crm_prepared'] = 'Подготовлено';
$string['commerce_vocabulary_purchase_status_crm_payment_pending'] = 'Ожидает оплаты';
$string['commerce_vocabulary_purchase_status_crm_authorized'] = 'Авторизовано';
$string['commerce_vocabulary_purchase_status_crm_captured'] = 'Оплата получена';
$string['commerce_vocabulary_purchase_status_crm_paid'] = 'Оплачено';
$string['commerce_vocabulary_purchase_status_crm_fulfillment_pending'] = 'Ожидает предоставления доступа';
$string['commerce_vocabulary_purchase_status_crm_fulfilled'] = 'Выполнено';
$string['commerce_vocabulary_purchase_status_crm_completed'] = 'Завершено';
$string['commerce_vocabulary_purchase_status_crm_active'] = 'Активно';
$string['commerce_vocabulary_purchase_status_crm_expired'] = 'Истекло';
$string['commerce_vocabulary_purchase_status_crm_replaced'] = 'Заменено';
$string['commerce_vocabulary_purchase_status_crm_cancelled'] = 'Отменено';
$string['commerce_vocabulary_purchase_status_crm_failed'] = 'Ошибка';
$string['commerce_vocabulary_purchase_status_crm_refunded'] = 'Возвращено';
$string['commerce_vocabulary_purchase_status_crm_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_purchase_status_unknown'] = 'Статус покупки не указан';
$string['commerce_vocabulary_payment_status_client_created'] = 'Создано';
$string['commerce_vocabulary_payment_status_client_requires_action'] = 'Требуется действие';
$string['commerce_vocabulary_payment_status_client_pending'] = 'В ожидании';
$string['commerce_vocabulary_payment_status_client_authorized'] = 'Авторизовано';
$string['commerce_vocabulary_payment_status_client_captured'] = 'Оплата получена';
$string['commerce_vocabulary_payment_status_client_paid'] = 'Оплачено';
$string['commerce_vocabulary_payment_status_client_succeeded'] = 'Успешно';
$string['commerce_vocabulary_payment_status_client_failed'] = 'Ошибка';
$string['commerce_vocabulary_payment_status_client_cancelled'] = 'Отменено';
$string['commerce_vocabulary_payment_status_client_expired'] = 'Истекло';
$string['commerce_vocabulary_payment_status_client_refunded'] = 'Возвращено';
$string['commerce_vocabulary_payment_status_client_partially_refunded'] = 'Частичный возврат';
$string['commerce_vocabulary_payment_status_client_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_payment_status_crm_created'] = 'Создано';
$string['commerce_vocabulary_payment_status_crm_requires_action'] = 'Требуется действие';
$string['commerce_vocabulary_payment_status_crm_pending'] = 'В ожидании';
$string['commerce_vocabulary_payment_status_crm_authorized'] = 'Авторизовано';
$string['commerce_vocabulary_payment_status_crm_captured'] = 'Оплата получена';
$string['commerce_vocabulary_payment_status_crm_paid'] = 'Оплачено';
$string['commerce_vocabulary_payment_status_crm_succeeded'] = 'Успешно';
$string['commerce_vocabulary_payment_status_crm_failed'] = 'Ошибка';
$string['commerce_vocabulary_payment_status_crm_cancelled'] = 'Отменено';
$string['commerce_vocabulary_payment_status_crm_expired'] = 'Истекло';
$string['commerce_vocabulary_payment_status_crm_refunded'] = 'Возвращено';
$string['commerce_vocabulary_payment_status_crm_partially_refunded'] = 'Частичный возврат';
$string['commerce_vocabulary_payment_status_crm_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_payment_status_unknown'] = 'Статус оплаты не указан';
$string['commerce_vocabulary_fulfillment_status_client_pending'] = 'В ожидании';
$string['commerce_vocabulary_fulfillment_status_client_processing'] = 'В процессе';
$string['commerce_vocabulary_fulfillment_status_client_fulfilled'] = 'Выполнено';
$string['commerce_vocabulary_fulfillment_status_client_completed'] = 'Завершено';
$string['commerce_vocabulary_fulfillment_status_client_failed'] = 'Ошибка';
$string['commerce_vocabulary_fulfillment_status_client_cancelled'] = 'Отменено';
$string['commerce_vocabulary_fulfillment_status_client_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_fulfillment_status_crm_pending'] = 'В ожидании';
$string['commerce_vocabulary_fulfillment_status_crm_processing'] = 'В процессе';
$string['commerce_vocabulary_fulfillment_status_crm_fulfilled'] = 'Выполнено';
$string['commerce_vocabulary_fulfillment_status_crm_completed'] = 'Завершено';
$string['commerce_vocabulary_fulfillment_status_crm_failed'] = 'Ошибка';
$string['commerce_vocabulary_fulfillment_status_crm_cancelled'] = 'Отменено';
$string['commerce_vocabulary_fulfillment_status_crm_unknown'] = 'Статус не указан';
$string['commerce_vocabulary_fulfillment_status_unknown'] = 'Статус предоставления доступа не указан';
$string['commerce_vocabulary_access_type_client_course'] = 'Доступ к курсу';
$string['commerce_vocabulary_access_type_client_digital_product'] = 'Доступ к материалу';
$string['commerce_vocabulary_access_type_client_subscription'] = 'Подписка';
$string['commerce_vocabulary_access_type_client_bundle'] = 'Доступ к набору';
$string['commerce_vocabulary_access_type_crm_course'] = 'Право доступа к курсу';
$string['commerce_vocabulary_access_type_crm_digital_product'] = 'Право цифрового доступа';
$string['commerce_vocabulary_access_type_crm_subscription'] = 'Право подписки';
$string['commerce_vocabulary_access_type_crm_bundle'] = 'Право доступа к пакету';
$string['commerce_vocabulary_access_type_unknown'] = 'Другой тип доступа';

// Commerce 7.95B2-B4 UX foundation.
$string['commerce_products_empty_title'] = 'Пока нет продуктов';
$string['commerce_products_table_label'] = 'Список коммерческих продуктов';
$string['commerce_product_eyebrow'] = 'Коммерческий продукт';

// Commerce 7.95C4-C6 — Панель Native-статистики.
$string['commerce_statistics_title'] = 'Статистика Commerce';
$string['commerce_statistics_description'] = 'Отслеживайте продажи, платежи и операции Commerce на основе Native-данных.';
$string['commerce_statistics_period'] = 'Период';
$string['commerce_statistics_currency'] = 'Валюта';
$string['commerce_statistics_provider'] = 'Платёжный провайдер';
$string['commerce_statistics_period_today'] = 'Сегодня';
$string['commerce_statistics_period_7_days'] = 'Последние 7 дней';
$string['commerce_statistics_period_30_days'] = 'Последние 30 дней';
$string['commerce_statistics_period_90_days'] = 'Последние 90 дней';
$string['commerce_statistics_period_year'] = 'Последние 12 месяцев';
$string['commerce_statistics_all_currencies'] = 'Все валюты';
$string['commerce_statistics_all_providers'] = 'Все провайдеры';
$string['commerce_statistics_period_summary'] = 'Анализируемый период: с {$a->from} по {$a->to}. Сравнение с предыдущим периодом той же длительности.';
$string['commerce_statistics_empty_title'] = 'Нет данных Commerce';
$string['commerce_statistics_empty_description'] = 'Для выбранных фильтров и периода Native-активность не найдена.';
$string['commerce_statistics_payment_health'] = 'Платежи и выдача доступа';
$string['commerce_statistics_metric_net_paid_minor'] = 'Чистая выручка';
$string['commerce_statistics_metric_orders'] = 'Заказы';
$string['commerce_statistics_metric_average_order_minor'] = 'Средний чек';
$string['commerce_statistics_metric_customers'] = 'Платящие клиенты';
$string['commerce_statistics_metric_successful_payments'] = 'Успешные платежи';
$string['commerce_statistics_metric_failed_payments'] = 'Неуспешные платежи';
$string['commerce_statistics_metric_refunded_minor'] = 'Сумма возвратов';
$string['commerce_statistics_metric_pending_fulfillments'] = 'Выдачи доступа, требующие обработки';
$string['commerce_statistics_open_details'] = 'Открыть связанные записи';
$string['commerce_statistics_metric_link'] = 'Посмотреть детали показателя «{$a->metric}» в {$a->currency}';
$string['commerce_statistics_no_comparison'] = 'Сравнение недоступно';
$string['commerce_statistics_comparison_unavailable'] = 'Изменение невозможно рассчитать';
$string['commerce_statistics_vs_previous'] = '{$a} по сравнению с предыдущим периодом';
$string['commerce_statistics_operational_shortcuts'] = 'Оперативные действия';
$string['commerce_statistics_open_subscriptions'] = 'Управлять подписками';
$string['commerce_statistics_open_digital_purchases'] = 'Управлять цифровыми покупками';
$string['commerce_statistics_open_products'] = 'Управлять продуктами';

$string['commerce_statistics_charts_title'] = 'Динамика и распределение';
$string['commerce_statistics_chart_revenue'] = 'Динамика выручки';
$string['commerce_statistics_chart_orders'] = 'Динамика заказов';
$string['commerce_statistics_chart_top_products'] = 'Самые продаваемые продукты';
$string['commerce_statistics_chart_payment_health'] = 'Состояние платежей';
$string['commerce_statistics_chart_product_revenue'] = 'Динамика продаж продукта';
$string['commerce_statistics_payment_successful'] = 'Успешные';
$string['commerce_statistics_payment_failed'] = 'Неудачные';
$string['commerce_statistics_payment_refunded'] = 'Возвращённые';
$string['commerce_statistics_accessible_table'] = 'Показать данные в таблице';
$string['commerce_product_statistics_title'] = 'Коммерческие показатели';
$string['commerce_product_statistics_period'] = 'Данные за последние 90 дней отдельно по каждой валюте.';
$string['commerce_statistics_table_period'] = 'Период';
$string['commerce_statistics_table_value'] = 'Значение';


// 7.95D4-D6 — Unified Commerce sales.
$string['crm_commerce_nav_purchases'] = 'Продажи';
$string['commerce_purchases_title'] = 'Продажи';
$string['commerce_purchases_description'] = 'Все продажи Native Commerce в едином рабочем пространстве.';
$string['commerce_purchases_search'] = 'Поиск';
$string['commerce_purchases_results'] = 'Найденные продажи';
$string['commerce_purchases_empty_title'] = 'Продажи не найдены';
$string['commerce_purchases_empty'] = 'Нет продаж Native Commerce, соответствующих выбранным фильтрам.';
$string['commerce_purchases_table_label'] = 'Единый список продаж Commerce';
$string['commerce_purchase_reference'] = 'Номер';
$string['commerce_purchase_customer'] = 'Клиент';
$string['commerce_purchase_products'] = 'Продукты';
$string['commerce_purchase_amount'] = 'Сумма';
$string['commerce_purchase_status'] = 'Статус';
$string['commerce_purchase_type'] = 'Тип продажи';
$string['commerce_purchase_commercial_status'] = 'Коммерческий статус';
$string['commerce_purchase_payment_status'] = 'Оплата';
$string['commerce_purchase_fulfillment_status'] = 'Предоставление';
$string['commerce_purchase_provider'] = 'Провайдер';
$string['commerce_purchase_view_title'] = 'Продажа {$a}';
$string['commerce_purchase_view_description'] = 'Единое Native-представление продажи, оплаты и предоставления.';
$string['commerce_purchase_items_count'] = 'Позиций';
$string['commerce_purchase_summary_section'] = 'Сводка';
$string['commerce_purchase_customer_section'] = 'Клиент';
$string['commerce_purchase_products_section'] = 'Продукты';
$string['commerce_purchase_payments_section'] = 'Платежи';
$string['commerce_purchase_fulfillments_section'] = 'Предоставление';
$string['commerce_purchase_diagnostics_section'] = 'Техническая диагностика';
$string['commerce_purchase_product'] = 'Продукт';
$string['commerce_purchase_quantity'] = 'Количество';
$string['commerce_purchase_provider_reference'] = 'Номер провайдера';
$string['commerce_purchase_fulfillment'] = 'Предоставление';
$string['commerce_purchase_source'] = 'Источник';
$string['commerce_purchase_legacy_family'] = 'Legacy-семейство';
$string['commerce_purchase_legacy_id'] = 'Legacy ID';
$string['commerce_purchase_open_customer'] = 'Открыть клиента';
$string['commerce_purchase_open_product'] = 'Открыть продукт';
$string['commerce_purchase_no_payments'] = 'Попытки оплаты не зарегистрированы.';
$string['commerce_purchase_no_fulfillments'] = 'Операции предоставления не зарегистрированы.';
$string['commerce_purchase_not_found'] = 'Запрошенная продажа Commerce не найдена.';
$string['commerce_purchase_commercial_status_pending'] = 'Ожидает';
$string['commerce_purchase_commercial_status_paid'] = 'Оплачена';
$string['commerce_purchase_commercial_status_to_fulfill'] = 'Оплачена, ожидает предоставления';
$string['commerce_purchase_commercial_status_partially_fulfilled'] = 'Предоставлена частично';
$string['commerce_purchase_commercial_status_fulfilled'] = 'Предоставлена';
$string['commerce_purchase_commercial_status_payment_failed'] = 'Ошибка оплаты';
$string['commerce_purchase_commercial_status_refunded'] = 'Возврат';
$string['commerce_purchase_commercial_status_cancelled'] = 'Отменена';
$string['commerce_purchase_commercial_status_replaced'] = 'Заменена';
$string['commerce_purchase_commercial_status_unknown'] = 'Неизвестно';
$string['commerce_purchase_type_subscription'] = 'Подписка';
$string['commerce_purchase_type_digital'] = 'Цифровой продукт';
$string['commerce_purchase_type_bundle'] = 'Пакет';

// 7.95D7-D10 unified purchase actions and compatibility.
$string['commerce_purchase_actions_section'] = 'Действия';
$string['commerce_purchase_retry_fulfillment'] = 'Повторить выдачу доступа';
$string['commerce_purchase_retry_confirm'] = 'Повторить Native-выдачу для этой покупки? Операция идемпотентна.';
$string['commerce_purchase_retry_success'] = 'Выдача успешно выполнена.';
$string['commerce_purchase_retry_failed'] = 'Не удалось завершить выдачу. Проверьте сведения о выдаче.';
$string['commerce_purchase_internal_note'] = 'Внутренняя заметка';
$string['commerce_purchase_add_note'] = 'Добавить заметку';
$string['commerce_purchase_note_added'] = 'Внутренняя заметка добавлена.';
$string['commerce_purchase_destructive_actions_deferred'] = 'Отмена, замена и возврат пока недоступны до сертификации Native-команд с учётом платёжного провайдера.';
$string['commerce_purchase_action_not_allowed'] = 'Это действие недоступно для текущего состояния покупки.';
$string['commerce_purchase_note_required'] = 'Введите внутреннюю заметку.';
$string['commerce_purchase_note_too_long'] = 'Внутренняя заметка слишком длинная.';

// 7.95D11-D12 — Unified sales polish and certification.
$string['commerce_purchase_identifier'] = 'Идентификатор пользователя Moodle';
$string['commerce_purchase_open_user360'] = 'Открыть User360';


// 7.95D13 — Unified sales visual and operational completion.
$string['commerce_purchase_open_moodle_profile'] = 'Открыть профиль Moodle';
$string['commerce_purchase_retry_short'] = 'Повторить';
$string['commerce_purchase_payment_request'] = 'Платёжный запрос';
$string['commerce_purchase_payment_request_attempts'] = 'Попытки: {$a}';
$string['commerce_purchase_payment_request_expires'] = 'Истекает: {$a}';
$string['commerce_purchase_fulfillment_type_subscription_enrolment'] = 'Зачисление на курс';
$string['commerce_purchase_fulfillment_type_course_access'] = 'Доступ к курсу';
$string['commerce_purchase_fulfillment_type_digital_download'] = 'Скачивание цифрового продукта';
$string['commerce_purchase_fulfillment_type_digital_product'] = 'Доступ к цифровому продукту';
$string['commerce_purchase_payment_status_none'] = 'Нет платежа';
$string['commerce_purchase_payment_status_created'] = 'Создан';
$string['commerce_purchase_payment_status_pending'] = 'Ожидает';
$string['commerce_purchase_payment_status_processing'] = 'Обрабатывается';
$string['commerce_purchase_payment_status_paid'] = 'Оплачен';
$string['commerce_purchase_payment_status_succeeded'] = 'Успешно';
$string['commerce_purchase_payment_status_completed'] = 'Завершён';
$string['commerce_purchase_payment_status_failed'] = 'Ошибка';
$string['commerce_purchase_payment_status_error'] = 'Ошибка';
$string['commerce_purchase_payment_status_refunded'] = 'Возвращён';
$string['commerce_purchase_payment_status_cancelled'] = 'Отменён';
$string['commerce_purchase_payment_status_canceled'] = 'Отменён';
$string['commerce_purchase_payment_status_expired'] = 'Истёк';
$string['commerce_purchase_fulfillment_status_none'] = 'Не начато';
$string['commerce_purchase_fulfillment_status_pending'] = 'Ожидает';
$string['commerce_purchase_fulfillment_status_processing'] = 'Выполняется';
$string['commerce_purchase_fulfillment_status_queued'] = 'В очереди';
$string['commerce_purchase_fulfillment_status_fulfilled'] = 'Выполнено';
$string['commerce_purchase_fulfillment_status_completed'] = 'Выполнено';
$string['commerce_purchase_fulfillment_status_failed'] = 'Ошибка';
$string['commerce_purchase_fulfillment_status_error'] = 'Ошибка';
$string['commerce_purchase_fulfillment_status_active'] = 'Активно';
$string['commerce_purchase_type_course_access'] = 'Доступ к курсу';
$string['commerce_purchase_type_digital_download'] = 'Цифровой продукт';

// 7.95D14 purchase polish.
$string['commerce_purchase_payment_request_open'] = 'Открыть запрос #{$a}';
$string['commerce_purchase_payment_request_family'] = 'Тип';
$string['commerce_purchase_payment_requests_section'] = 'Связанные платёжные запросы';
$string['commerce_purchase_payment_request_summary'] = '{$a->family} — запрос #{$a->id}';
$string['commerce_purchase_payment_request_field_userid'] = 'Userid';
$string['commerce_purchase_payment_request_field_email'] = 'Email';
$string['commerce_purchase_payment_request_field_firstname'] = 'Firstname';
$string['commerce_purchase_payment_request_field_lastname'] = 'Lastname';
$string['commerce_purchase_payment_request_field_price'] = 'Price';
$string['commerce_purchase_payment_request_field_sessionid'] = 'Sessionid';
$string['commerce_purchase_payment_request_field_transactionid'] = 'Transactionid';
$string['commerce_purchase_payment_request_field_payment_link'] = 'Payment Link';
$string['commerce_purchase_payment_request_field_creation_date'] = 'Creation Date';
$string['commerce_purchase_payment_request_field_last_update'] = 'Last Update';
$string['commerce_purchase_payment_request_field_payment_date'] = 'Payment Date';
$string['commerce_purchase_payment_request_field_expiration_date'] = 'Expiration Date';
$string['commerce_purchase_payment_request_field_attempts'] = 'Attempts';
$string['commerce_purchase_payment_request_field_last_attempt'] = 'Last Attempt';
$string['commerce_purchase_payment_request_field_last_error'] = 'Last Error';
$string['commerce_purchase_payment_request_field_locked_list_price'] = 'Locked List Price';
$string['commerce_purchase_payment_request_field_locked_discount_percent'] = 'Locked Discount Percent';
$string['commerce_purchase_payment_request_field_locked_discount_amount'] = 'Locked Discount Amount';
$string['commerce_purchase_payment_request_field_locked_discount_reason'] = 'Locked Discount Reason';
$string['commerce_purchase_payment_request_field_locked_final_price'] = 'Locked Final Price';
$string['commerce_purchase_payment_request_field_locked_at'] = 'Locked At';
$string['commerce_purchase_payment_request_field_created_ip'] = 'Created Ip';
$string['commerce_purchase_payment_request_field_created_useragent'] = 'Created Useragent';
$string['commerce_purchase_payment_request_field_accept_language'] = 'Accept Language';
$string['commerce_purchase_payment_request_field_http_referer'] = 'Http Referer';
$string['commerce_purchase_payment_request_field_response_json'] = 'Response Json';
$string['commerce_purchase_payment_request_field_emailsent'] = 'Emailsent';
$string['commerce_purchase_payment_request_field_planid'] = 'Planid';
$string['commerce_purchase_payment_request_field_phone'] = 'Phone';
$string['commerce_purchase_payment_request_field_phone_country'] = 'Phone Country';
$string['commerce_purchase_payment_request_field_subscriptionid'] = 'Subscriptionid';
$string['commerce_purchase_payment_request_field_retry_expires'] = 'Retry Expires';
$string['commerce_purchase_payment_request_field_reminder_stage'] = 'Reminder Stage';
$string['commerce_purchase_payment_request_field_reminder1_at'] = 'Reminder1 At';
$string['commerce_purchase_payment_request_field_reminder2_at'] = 'Reminder2 At';
$string['commerce_purchase_payment_request_field_login_token_expires'] = 'Login Token Expires';
$string['commerce_purchase_payment_request_field_operation'] = 'Operation';
$string['commerce_purchase_payment_request_field_reference_subscription_id'] = 'Reference Subscription Id';
$string['commerce_purchase_payment_request_field_productid'] = 'Productid';
$string['commerce_purchase_payment_request_field_download_token_expires'] = 'Download Token Expires';
$string['commerce_purchase_payment_request_field_receipt_sent'] = 'Receipt Sent';
$string['commerce_purchase_payment_request_field_buyer_lang'] = 'Buyer Lang';

// 7.95E — Единый коммерческий каталог.
$string['commerce_catalog_title'] = 'Коммерческий каталог';
$string['commerce_catalog_description'] = 'Единое пространство для Native-продуктов, исторических тарифов и цифровых продуктов.';
$string['commerce_catalog_product_eyebrow'] = 'Продукт каталога';
$string['commerce_catalog_table_label'] = 'Единый список коммерческих продуктов';
$string['commerce_catalog_origin'] = 'Источник';
$string['commerce_catalog_editorial'] = 'Редакционный статус';
$string['commerce_catalog_visibility'] = 'Видимость';
$string['commerce_catalog_availability'] = 'Доступность';
$string['commerce_catalog_technical'] = 'Техническое состояние';
$string['commerce_catalog_content'] = 'Предоставляемый контент';
$string['commerce_catalog_compatibility'] = 'Совместимость с Legacy';
$string['commerce_catalog_available_from'] = 'Доступно с';
$string['commerce_catalog_available_until'] = 'Доступно до';
$string['commerce_catalog_fulfillments_count'] = 'Выдач доступа: {$a}';
$string['commerce_catalog_product_not_found'] = 'Запрошенный продукт не найден в едином каталоге.';
$string['commerce_catalog_editorial_draft'] = 'Черновик';
$string['commerce_catalog_editorial_published'] = 'Опубликован';
$string['commerce_catalog_editorial_archived'] = 'Архивирован';
$string['commerce_catalog_visibility_visible'] = 'Виден';
$string['commerce_catalog_visibility_hidden'] = 'Скрыт';
$string['commerce_catalog_visibility_direct_link'] = 'Только по ссылке';
$string['commerce_catalog_availability_on_sale'] = 'В продаже';
$string['commerce_catalog_availability_upcoming'] = 'Скоро';
$string['commerce_catalog_availability_unavailable'] = 'Недоступен';
$string['commerce_catalog_availability_ended'] = 'Продажа завершена';
$string['commerce_catalog_technical_valid'] = 'Корректен';
$string['commerce_catalog_technical_incomplete'] = 'Неполная настройка';
$string['commerce_catalog_technical_error'] = 'Ошибка настройки';
$string['commerce_catalog_origin_native'] = 'Native';
$string['commerce_catalog_origin_legacy_plan'] = 'Исторический тариф';
$string['commerce_catalog_origin_legacy_digital'] = 'Исторический цифровой продукт';
$string['commerce_catalog_type_course_access'] = 'Доступ к курсу';
$string['commerce_catalog_type_digital_download'] = 'Цифровой продукт';
$string['commerce_catalog_type_bundle'] = 'Комплект';
$string['commerce_catalog_type_service'] = 'Услуга';
$string['commerce_catalog_fulfillment_course'] = 'Доступ к курсу «{$a}»';
$string['commerce_catalog_fulfillment_download'] = 'Цифровая загрузка';
$string['commerce_catalog_fulfillment_course_enrolment'] = 'Запись на курс';
$string['commerce_catalog_fulfillment_digital_download'] = 'Цифровая загрузка';

// Commerce 7.95E7-E10 unified product editor.
$string['commerce_product_step_prices'] = 'Prices';
$string['commerce_product_step_fulfillments'] = 'Fulfillments';
$string['commerce_product_step_access_scope'] = 'Access scope';
$string['commerce_product_prices_title'] = 'Product prices';
$string['commerce_product_prices_help'] = 'Manage Native catalogue prices by currency and provider. Existing purchases keep their locked price snapshot.';
$string['commerce_price_amount'] = 'Amount';
$string['commerce_provider'] = 'Provider';
$string['commerce_provider_price_id'] = 'Provider price identifier';
$string['commerce_product_fulfillments_title'] = 'Delivered access and content';
$string['commerce_product_fulfillments_help'] = 'Define the concrete rights granted by this product. A Plan and an Access Scope remain distinct objects: the Plan is sold, while the Scope describes reusable course coverage.';
$string['commerce_fulfillment_type'] = 'Delivery type';
$string['commerce_fulfillment_resource'] = 'Resource';
$string['commerce_fulfillment_duration'] = 'Duration (seconds)';
$string['commerce_fulfillment_quantity'] = 'Quantity';
$string['commerce_product_fulfillments_lifetime_help'] = 'Use 0 seconds for lifetime access.';
$string['commerce_access_scope_relation_title'] = 'Plan and Access Scope';
$string['commerce_access_scope_relation_help'] = 'The commercial product represents the sellable Plan. Its Access Scope remains a separate reusable object defining the covered courses.';
$string['commerce_access_scope_unmapped'] = 'This Native course-access product is not mapped to a Legacy Plan, so no Access Scope relation can be displayed.';
$string['commerce_access_scope_plan'] = 'Sellable plan';
$string['commerce_access_scope_scope'] = 'Linked access scope';
$string['commerce_access_scope_edit_plan'] = 'Edit the plan';
$string['commerce_access_scope_edit_scope'] = 'Edit the access scope';
$string['commerce_access_scope_courses'] = 'Courses covered by the scope';
$string['commerce_product_step_prices'] = 'Цены';
$string['commerce_product_step_fulfillments'] = 'Выдача доступа';
$string['commerce_product_step_access_scope'] = 'Область доступа';
$string['commerce_product_prices_title'] = 'Цены продукта';
$string['commerce_product_prices_help'] = 'Управление ценами Native-каталога по валюте и поставщику. В существующих покупках сохраняется зафиксированный снимок цены.';
$string['commerce_price_amount'] = 'Сумма';
$string['commerce_provider'] = 'Поставщик';
$string['commerce_provider_price_id'] = 'Идентификатор цены поставщика';
$string['commerce_product_fulfillments_title'] = 'Выдаваемый доступ и контент';
$string['commerce_product_fulfillments_help'] = 'Определите конкретные права, выдаваемые продуктом. План и Access Scope остаются разными объектами: продаётся План, а Scope описывает повторно используемый набор курсов.';
$string['commerce_fulfillment_type'] = 'Тип выдачи';
$string['commerce_fulfillment_resource'] = 'Ресурс';
$string['commerce_fulfillment_duration'] = 'Срок (секунды)';
$string['commerce_fulfillment_quantity'] = 'Количество';
$string['commerce_product_fulfillments_lifetime_help'] = 'Укажите 0 секунд для бессрочного доступа.';
$string['commerce_access_scope_relation_title'] = 'План и Access Scope';
$string['commerce_access_scope_relation_help'] = 'Коммерческий продукт представляет продаваемый План. Его Access Scope остаётся отдельным повторно используемым объектом, определяющим охваченные курсы.';
$string['commerce_access_scope_unmapped'] = 'Этот Native-продукт доступа к курсам не связан с историческим Планом, поэтому связь с Access Scope недоступна.';
$string['commerce_access_scope_plan'] = 'Продаваемый план';
$string['commerce_access_scope_scope'] = 'Связанный Access Scope';
$string['commerce_access_scope_edit_plan'] = 'Изменить план';
$string['commerce_access_scope_edit_scope'] = 'Изменить Access Scope';
$string['commerce_access_scope_courses'] = 'Курсы в Scope';
$string['commerce_edit_in_source'] = 'Edit in source';
$string['commerce_edit_in_source'] = 'Изменить в исходном разделе';
$string['commerce_product_step_bundle_pricing'] = 'Правила цены комплекта';
$string['commerce_product_step_assets'] = 'Медиа и файлы';
$string['commerce_product_assets_title'] = 'Медиа и файлы';
$string['commerce_product_assets_help'] = 'Управляйте обложкой товара, а для цифровых товаров — версиями файла для компьютера и мобильного устройства.';
$string['commerce_cover_image'] = 'Обложка';
$string['commerce_digital_files'] = 'Цифровые файлы';
$string['commerce_desktop_file'] = 'Версия для компьютера';
$string['commerce_mobile_file'] = 'Мобильная версия';
$string['commerce_digital_files_need_mapping'] = 'Для управления файлами Native-товар должен быть связан с историческим цифровым товаром.';
$string['commerce_invalid_asset_type'] = 'Этот тип файла не разрешён.';
$string['commerce_product_prices_guided_help'] = 'Выберите валюту и провайдера из списков. Можно добавить нужное количество тарифов.';
$string['commerce_add_price_row'] = 'Добавить тариф';
$string['commerce_invalid_price'] = 'Указана неверная сумма.';
$string['commerce_product_fulfillments_guided_help'] = 'Определите, что получает клиент, используя готовые списки. По умолчанию показаны две строки.';
$string['commerce_add_fulfillment_row'] = 'Добавить выдачу';
$string['commerce_incomplete_fulfillment_row'] = 'Строка выдачи {$a} заполнена не полностью.';
$string['commerce_unknown_fulfillment_type'] = 'Неизвестный тип выдачи.';
$string['commerce_invalid_fulfillment_resource'] = 'Выбранный ресурс не найден.';
$string['commerce_fulfillment_course_access'] = 'Доступ к курсу Moodle';
$string['commerce_fulfillment_course_enrolment'] = 'Зачисление на курс Moodle';
$string['commerce_fulfillment_digital_download'] = 'Скачивание цифрового файла';
$string['commerce_fulfillment_digital_product'] = 'Доступ к цифровому товару';
$string['commerce_fulfillment_custom'] = 'Пользовательская выдача';
$string['commerce_resource_course'] = 'Курс: {$a}';
$string['commerce_resource_digital'] = 'Цифровой товар: {$a}';
$string['commerce_duration_lifetime'] = 'Бессрочный доступ';
$string['commerce_duration_30_days'] = '30 дней';
$string['commerce_duration_90_days'] = '90 дней';
$string['commerce_duration_365_days'] = '365 дней';
$string['commerce_missing_course'] = 'Курс не найден (#{$a})';
$string['commerce_missing_digital_product'] = 'Цифровой товар не найден (#{$a})';
$string['commerce_product_diagnostic'] = 'Диагностика товара';
$string['commerce_validation_no_active_price'] = 'Нет активного тарифа.';
$string['commerce_validation_no_fulfillment'] = 'Не настроена выдача.';
$string['commerce_validation_hidden'] = 'Товар скрыт в магазине.';
$string['commerce_validation_not_on_sale'] = 'Товар сейчас не продаётся.';
$string['commerce_technical_reference'] = 'Техническая ссылка';
$string['commerce_status_publication'] = 'Публикация';
$string['commerce_status_sale'] = 'Продажа';
$string['commerce_status_visibility'] = 'Видимость';
$string['commerce_status_configuration'] = 'Конфигурация';
$string['settings:commerce_catalog_heading'] = 'Каталог Commerce';
$string['settings:commerce_catalog_heading_desc'] = 'Общие настройки каталога.';
$string['settings:commerce_enabled_currencies'] = 'Доступные валюты';
$string['settings:commerce_enabled_currencies_desc'] = 'ISO-коды через запятую. Поддерживаются EUR, RUB, USD, GBP, CHF, CAD и JPY.';

$string['commerce_price_deleted'] = 'Тариф удалён.';
$string['commerce_price_currency_duplicate'] = 'Для этой валюты уже существует тариф. Для одного товара допускается только один коммерческий тариф в каждой валюте.';
$string['commerce_prices_unique_currency_help'] = 'Для каждой валюты допускается только один коммерческий тариф. Измените существующую строку вместо создания новой.';
$string['commerce_add_price'] = 'Добавить тариф';
$string['commerce_plans_title'] = 'Планы доступа';
$string['commerce_plan_add'] = 'Добавить план';
$string['commerce_plan_edit'] = 'Изменить план';
$string['commerce_scopes_title'] = 'Области доступа';
$string['commerce_scope_add'] = 'Добавить область доступа';
$string['commerce_scope_edit'] = 'Изменить область доступа';
$string['commerce_scope_plans_count'] = 'Связанные планы';
$string['commerce_scope_used_by_plans'] = 'Планы, использующие эту область';

$string['commerce_catalog_lifecycle_active'] = 'Активен';
$string['commerce_catalog_lifecycle_inactive'] = 'Неактивен';
$string['commerce_catalog_lifecycle_archived'] = 'В архиве';
$string['commerce_product_activate'] = 'Активировать';
$string['commerce_product_deactivate'] = 'Деактивировать';
$string['commerce_product_activated'] = 'Товар активирован.';
$string['commerce_product_deactivated'] = 'Товар деактивирован.';
$string['commerce_product_archived'] = 'Товар перемещён в архив.';
$string['commerce_product_status_managed_help'] = 'Статус меняется в списке товаров. Неполная конфигурация блокирует активацию.';
$string['commerce_validation_missing_plan'] = 'К товару не привязан тарифный план.';
$string['commerce_validation_missing_scope'] = 'У тарифного плана нет области доступа.';
$string['commerce_validation_empty_scope'] = 'Область доступа не содержит курсов.';
$string['commerce_validation_missing_digital'] = 'К товару не привязан цифровой продукт.';
$string['commerce_validation_missing_digital_file'] = 'У цифрового продукта нет ни компьютерной, ни мобильной версии файла.';
$string['commerce_validation_bundle_too_small'] = 'Набор должен содержать не менее двух компонентов.';
$string['commerce_validation_inactive_bundle_component'] = 'Все компоненты набора должны быть активны.';
$string['commerce_edit_digital_source'] = 'Изменить цифровые файлы';
$string['commerce_prices_catalogue_help'] = 'Для каждой валюты можно задать только один коммерческий тариф. Платёжный провайдер выбирается на этапе checkout; существующие технические данные провайдера сохраняются в фоне.';
$string['commerce_scope_plans_count'] = 'Количество связанных планов';
$string['commerce_scope_delete_blocked'] = 'Невозможно удалить область доступа: её используют планы ({$a}).';
$string['commerce_scope_deleted'] = 'Область доступа удалена.';
$string['commerce_plan_current_subscriptions'] = 'Активные или ожидающие подписки';
$string['commerce_plan_delete_blocked'] = 'Невозможно удалить план: с ним связаны активные или ожидающие подписки ({$a}).';
$string['commerce_plan_deleted'] = 'План удалён.';

// 7.95E19B.
$string['commerce_plan_toggle_help'] = 'Включить или отключить этот план';
$string['commerce_plan_business_information'] = 'Информация о плане';
$string['commerce_technical_information'] = 'Техническая информация';
$string['commerce_date_created'] = 'Дата создания';
$string['commerce_date_modified'] = 'Последнее изменение';
$string['commerce_cover_error_maxbytes'] = 'Обложка не сохранена: размер файла превышает максимально допустимый ({$a}).';
$string['commerce_cover_error_upload'] = 'Обложка не сохранена. Проверьте формат файла и повторите попытку.';
$string['commerce_product_back_to_view'] = 'Карточка продукта';
$string['commerce_internal_id'] = 'Внутренний идентификатор';
$string['commerce_plan_entitlements_explanation'] = 'Эти права доступа являются историческими правилами исполнения плана (курс, роль, группа и уровень доступа). Они дополняют бизнес-связь «План → Область доступа», не создавая повторно редактируемые Fulfillments.';
$string['commerce_plan_upgrades_explanation'] = 'Эти правила определяют разрешённые переходы между данным планом и другими планами, а также применяемый способ расчёта.';
$string['commerce_manage_entitlements'] = 'Управлять правами доступа';
$string['commerce_manage_upgrades'] = 'Управлять правилами перехода';
$string['commerce_back_to_plan'] = 'Вернуться к карточке плана';
$string['commerce_plan_upgrades_for'] = 'Правила перехода для плана: {$a}';

$string['commerce_purchase_status_overview'] = 'Статус покупки';
$string['commerce_purchase_dimension_payment'] = 'Оплата';
$string['commerce_purchase_dimension_order'] = 'Заказ';
$string['commerce_purchase_dimension_delivery'] = 'Выдача';
$string['commerce_purchase_dimension_access'] = 'Доступ';
$string['commerce_purchase_payment_not_required'] = 'Оплата не требуется';
$string['commerce_purchase_fulfillment_status_not_started'] = 'Не начато';
$string['commerce_purchase_access_status_active'] = 'Активен';
$string['commerce_purchase_access_status_pending'] = 'Ожидает';
$string['commerce_purchase_access_status_blocked'] = 'Заблокирован';

$string['commerce_purchase_order_status_completed'] = 'Завершён';

// 7.95E19D final fulfillment distinction.
$string['commerce_purchase_start_fulfillment'] = 'Запустить выдачу доступа';
$string['commerce_purchase_start_fulfillment_confirm'] = 'Запустить первичную выдачу доступа для этой покупки?';
$string['commerce_purchase_fulfillment_process_success'] = 'Выдача доступа успешно выполнена.';
$string['commerce_purchase_fulfillment_missing_grants'] = 'Выдачу доступа невозможно запустить: для этой покупки не зарегистрированы права Native. Вероятно, это историческая или неполная запись; операция не создана, чтобы избежать ошибочного доступа.';

// Commerce 7.95E19E fix.
$string['admin_event_commerce_purchase_fulfillment_retried'] = 'Повторная попытка выдачи покупки';
$string['admin_event_commerce_purchase_note_added'] = 'К покупке добавлена заметка';
$string['admin_event_commerce_purchase_fulfillment_closed_without_delivery'] = 'Покупка закрыта без выдачи доступа';
$string['commerce_purchase_close_without_fulfillment'] = 'Закрыть без выдачи доступа';
$string['commerce_purchase_close_without_fulfillment_confirm'] = 'Подтвердить закрытие без выдачи доступа';
$string['commerce_purchase_close_without_fulfillment_confirm_text'] = 'Покупка будет отмечена как закрытая без выдачи. Доступ не будет создан или удалён.';
$string['commerce_purchase_closed_without_fulfillment_success'] = 'Покупка закрыта без выдачи. Доступ не был создан.';
$string['commerce_purchase_closed_without_fulfillment_notice'] = 'Выдача закрыта без предоставления доступа.';

$string['commerce_statistics_products_title'] = 'Статистика по продуктам';
$string['commerce_statistics_products_description'] = 'Выручка учитывает только заказы с успешной оплатой. Бесплатные заказы считаются отдельно, а валюты никогда не конвертируются и не суммируются.';
$string['commerce_statistics_products_empty'] = 'За выбранный период и по выбранным фильтрам продаж продуктов нет.';
$string['commerce_statistics_products_table_label'] = 'Коммерческая статистика продуктов в валюте {$a}';
$string['commerce_statistics_product'] = 'Продукт';
$string['commerce_statistics_product_orders'] = 'Заказы';
$string['commerce_statistics_product_paid_orders'] = 'Платные';
$string['commerce_statistics_product_free_orders'] = 'Бесплатные';
$string['commerce_statistics_product_quantity'] = 'Количество';
$string['commerce_statistics_product_revenue'] = 'Полученная выручка';

$string['commerce_product_statistics_empty'] = 'За этот период продаж данного продукта не зарегистрировано.';

$string['commerce_digital_file_unavailable'] = 'Запрошенный цифровой файл недоступен.';

// Commerce 7.95E21 fix: product performance filters.
$string['commerce_statistics_period_label'] = 'Период';
$string['commerce_statistics_period_180_days'] = 'Последние 6 месяцев';
$string['commerce_statistics_period_365_days'] = 'Последние 12 месяцев';
$string['commerce_statistics_period_all_time'] = 'За всё время';

// 7.95E21 statistics refinements.
$string['commerce_statistics_chart_mode'] = 'Отображение выручки';
$string['commerce_statistics_chart_mode_instant'] = 'Выручка по периодам';
$string['commerce_statistics_chart_mode_cumulative'] = 'Накопительная выручка';
$string['commerce_statistics_chart_revenue_cumulative'] = 'Накопительная выручка';
$string['commerce_statistics_chart_product_revenue_cumulative'] = 'Накопительная выручка по продукту';
$string['commerce_statistics_chart_product_orders'] = 'Продажи продукта';
$string['commerce_statistics_product_failed_payments'] = 'Неуспешные платежи';

// Commerce 7.95F2 — единая витрина.
$string['commerce_storefront_title'] = 'Магазин CampusFR';
$string['commerce_storefront_intro'] = 'Курсы, цифровые материалы и наборы — всё необходимое для успешного изучения французского.';
$string['commerce_storefront_search_placeholder'] = 'Найти курс, материал или набор…';
$string['commerce_storefront_filter_type'] = 'Тип продукта';
$string['commerce_storefront_buy_now'] = 'Купить';
$string['commerce_storefront_discover'] = 'Подробнее';
$string['commerce_storefront_empty_title'] = 'Продукты не найдены';
$string['commerce_storefront_empty'] = 'Измените фильтры или валюту, чтобы увидеть другие продукты.';
$string['commerce_storefront_result_count'] = 'Продуктов: {$a}';
$string['commerce_storefront_product_not_found'] = 'Этот продукт недоступен в магазине.';
$string['commerce_storefront_back'] = 'Вернуться в магазин';
$string['commerce_storefront_detail_scaffold_notice'] = 'Эта первая страница использует общий каркас магазина. На этапе F3 можно будет создавать полностью индивидуальные страницы продуктов.';

// Права доступа.
$string['subscriptions:view_dashboard'] = 'Просматривать панель подписок';
$string['subscriptions:view_users'] = 'Просматривать пользователей';
$string['subscriptions:manage_users'] = 'Управлять пользователями';
$string['subscriptions:manage_subscriptions'] = 'Управлять подписками';
$string['subscriptions:view_digital'] = 'Просматривать цифровые продукты';
$string['subscriptions:manage_digital'] = 'Управлять цифровыми продуктами';
$string['subscriptions:view_payments'] = 'Просматривать платежи';
$string['subscriptions:view_statistics'] = 'Просматривать статистику Commerce';
$string['subscriptions:view_inbox'] = 'Просматривать входящие CRM';
$string['subscriptions:manage_inbox'] = 'Управлять входящими CRM';
$string['subscriptions:manage_configuration'] = 'Управлять настройками Commerce';
$string['subscriptions:use_inbox_ai'] = 'Использовать функции ИИ во входящих CRM';
$string['subscriptions:use_crm_assistant_ai'] = 'Использовать ИИ-помощника CRM';
$string['subscriptions:manage_crm_admin_tools'] = 'Управлять административными инструментами CRM';

// Commerce 7.95F3 — настраиваемые страницы продуктов.
$string['commerce_storefront_components_title'] = 'Что входит в продукт';
$string['commerce_storefront_faq_title'] = 'Часто задаваемые вопросы';

// Commerce 7.95F4 — Редактор страницы магазина.
$string['commerce_product_step_storefront'] = 'Страница магазина';
$string['commerce_storefront_editor_title'] = 'Страница презентации продукта';
$string['commerce_storefront_editor_intro'] = 'Создайте редакционную страницу продукта. Цены и действия покупки по-прежнему контролируются Commerce.';
$string['commerce_storefront_preview'] = 'Предпросмотр страницы';
$string['commerce_storefront_layout_title'] = 'Оформление страницы';
$string['commerce_storefront_template'] = 'Шаблон страницы';
$string['commerce_storefront_template_default'] = 'Стандартный';
$string['commerce_storefront_template_editorial'] = 'Редакционный';
$string['commerce_storefront_template_immersive'] = 'Иммерсивный';
$string['commerce_storefront_theme'] = 'Ключ темы';
$string['commerce_storefront_theme_help'] = 'Необязательный технический ключ для индивидуального стиля продукта, например a1-premium.';
$string['commerce_storefront_section_empty'] = 'Неиспользуемый раздел';
$string['commerce_storefront_section_rich_text'] = 'Расширенный текст';
$string['commerce_storefront_section_features'] = 'Карточки преимуществ';
$string['commerce_storefront_section_media'] = 'Изображение или медиа';
$string['commerce_storefront_section_testimonial'] = 'Отзыв';
$string['commerce_storefront_section_faq'] = 'Частые вопросы';
$string['commerce_storefront_section_cta'] = 'Призыв к действию';
$string['commerce_storefront_section_components'] = 'Состав бандла';
$string['commerce_storefront_section_number'] = 'Раздел {$a}';
$string['commerce_storefront_section_type'] = 'Тип раздела';
$string['commerce_storefront_section_title'] = 'Заголовок';
$string['commerce_storefront_section_subtitle'] = 'Подзаголовок';
$string['commerce_storefront_section_content'] = 'Основное содержимое';
$string['commerce_storefront_section_content_help'] = 'Используется для расширенного текста, подписи к медиа, отзыва и призыва к действию. HTML поддерживается.';
$string['commerce_storefront_section_auxiliary'] = 'URL или автор';
$string['commerce_storefront_section_auxiliary_help'] = 'Для медиа укажите URL изображения. Для отзыва укажите автора.';
$string['commerce_storefront_section_alt'] = 'Альтернативный текст';
$string['commerce_storefront_section_items'] = 'Карточки или вопросы';
$string['commerce_storefront_section_items_help'] = 'Один элемент на строку в формате: заголовок ||| содержимое. Для FAQ: вопрос ||| ответ.';

$string['settings:storefront_header'] = 'Единый магазин';
$string['settings:storefront_header_desc'] = 'Позволяет постепенно перенаправлять старые публичные страницы каталога в единый магазин Commerce.';
$string['settings:storefront_enabled'] = 'Включить перенаправление в единый магазин';
$string['settings:storefront_enabled_desc'] = 'Перенаправляет старый каталог курсов в единый магазин. Встроенные представления и прямые ссылки на старые тарифы остаются без изменений.';

$string['commerce_storefront_merchandising_title'] = 'Коммерческое оформление';
$string['commerce_storefront_merchandising_intro'] = 'Управляйте порядком товаров, их выделением и маркетинговыми отметками в магазине.';
$string['commerce_storefront_featured_product'] = 'Сделать товар рекомендуемым';
$string['commerce_storefront_display_order'] = 'Порядок отображения';
$string['commerce_storefront_display_order_help'] = 'Сначала показываются рекомендуемые товары, затем остальные по возрастанию. Значение по умолчанию — 1000.';
$string['commerce_storefront_badges'] = 'Маркетинговые бейджи';
$string['commerce_storefront_badge_new'] = 'Новинка';
$string['commerce_storefront_badge_bestseller'] = 'Бестселлер';
$string['commerce_storefront_badge_popular'] = 'Самый популярный';
$string['commerce_storefront_badge_limited_offer'] = 'Ограниченное предложение';
$string['commerce_storefront_badge_gustave_choice'] = 'Выбор Гюстава';
$string['commerce_storefront_badge_premium'] = 'Премиум';
$string['commerce_storefront_badge_lifetime_access'] = 'Доступ навсегда';
$string['commerce_storefront_badge_complete_course'] = 'Полный курс';
$string['commerce_storefront_badge_promotion'] = 'Скидка';
$string['commerce_storefront_featured'] = 'Рекомендуемый товар';
$string['commerce_storefront_promotions_title'] = 'Сравнительные цены и акции';
$string['commerce_storefront_promotions_help'] = 'Активная Native-цена остаётся фактической суммой оплаты. Укажите более высокую сравнительную цену, чтобы показать зачёркнутую цену. Даты необязательны.';
$string['commerce_storefront_compare_price'] = 'Сравнительная цена';
$string['commerce_storefront_promotion_start'] = 'Начало';
$string['commerce_storefront_promotion_end'] = 'Окончание';
$string['commerce_storefront_discount_percentage'] = '-{$a}%';
$string['commerce_storefront_promotion_until'] = 'Предложение действует до {$a}';


// 7.95F6B — Storefront customer experience.
$string['commerce_storefront_group_auto'] = 'Автоматически (по типу продукта)';
$string['commerce_storefront_group_courses'] = 'Курсы';
$string['commerce_storefront_group_resources'] = 'Материалы';
$string['commerce_storefront_group_bundles'] = 'Наборы';
$string['commerce_storefront_group_courses_intro'] = 'Структурированные программы для прогресса во французском.';
$string['commerce_storefront_group_resources_intro'] = 'Практические материалы для тренировки и повторения.';
$string['commerce_storefront_group_bundles_intro'] = 'Выгодные комплексные предложения.';
$string['commerce_storefront_owned'] = 'Уже приобретено';
$string['commerce_storefront_access_course'] = 'Перейти к курсу';
$string['commerce_storefront_access_purchase'] = 'Посмотреть покупку';
$string['commerce_storefront_trust_secure_payment'] = 'Безопасная оплата';
$string['commerce_storefront_trust_immediate_access'] = 'Мгновенный доступ';
$string['commerce_storefront_trust_support'] = 'Поддержка CampusFR';
$string['commerce_storefront_trust_lifetime_access'] = 'Пожизненный доступ';
$string['commerce_storefront_experience_title'] = 'Клиентский опыт';
$string['commerce_storefront_experience_intro'] = 'Выберите блок каталога, элементы доверия и ключевые характеристики продукта.';
$string['commerce_storefront_group'] = 'Блок каталога';
$string['commerce_storefront_trust_title'] = 'Элементы доверия';
$string['commerce_storefront_quickfacts'] = 'Ключевые факты';
$string['commerce_storefront_quickfacts_help'] = 'Один факт на строку: значение ||| подпись. Например: 82 ||| видео. Отображается не более шести фактов.';

$string['commerce_product_technical_name'] = 'Техническое название на английском';
$string['commerce_product_sku_generated_help'] = 'SKU будет создан автоматически из типа и технического названия на английском. Публичные названия затем задаются переводами.';
$string['commerce_product_sku_immutable_help'] = 'Неизменяемая техническая ссылка, созданная при создании продукта.';
$string['commerce_access_scope_no_plan'] = 'Исторический тариф не привязан';
$string['commerce_access_scope_plan_without_scope'] = 'без области доступа';
$string['commerce_access_scope_link_plan'] = 'Связанный исторический тариф';
$string['commerce_access_scope_link_plan_help'] = 'Связь позволяет Native-продукту использовать существующую область доступа. Один тариф можно связать только с одним Native-продуктом.';
$string['commerce_storefront_recommendations_title'] = 'Дополните свой маршрут';
$string['commerce_storefront_recommendations_help'] = 'Один Native SKU на строку, максимум четыре рекомендации. Уже приобретённые продукты скрываются.';

$string['commerce_access_scope_mapping_conflict'] = 'Этот план уже связан с другим Native-продуктом. Проверьте связь перед переносом.';
$string['commerce_access_scope_already_linked_to'] = 'уже связан с {$a}';
$string['commerce_access_scope_transfer_warning'] = 'План уже связан с другим продуктом. Повторное сохранение явно перенесёт связь на этот продукт.';
$string['commerce_storefront_edit_language'] = 'Язык редакционного контента';
$string['commerce_storefront_edit_language_help'] = 'Разделы и ключевая информация сохраняются отдельно для выбранного языка. Коммерческая структура остаётся общей.';
$string['commerce_product_lifecycle_title'] = 'Жизненный цикл продукта';
$string['commerce_product_lifecycle_intro'] = 'Архивируйте продукт, чтобы убрать его из продажи. Полное удаление предназначено для очистки тестовых продуктов.';
$string['commerce_product_archive_title'] = 'Архивировать продукт';
$string['commerce_product_archive_action'] = 'Архивировать';
$string['commerce_product_delete_title'] = 'Удалить навсегда';
$string['commerce_product_delete_safe_help'] = 'Продаж и прав нет: удаление разрешено.';
$string['commerce_product_delete_blocked_help'] = 'Есть продажи или права. Принудительное удаление по умолчанию отключено и должно быть явно разрешено в config.php.';
$string['commerce_product_delete_action'] = 'Удалить продукт';
$string['commerce_product_force_delete_action'] = 'Удалить продукт и продажи';
$string['commerce_product_admin_password'] = 'Пароль администратора';
$string['commerce_product_delete_confirmation'] = 'Введите SUPPRIMER для подтверждения';
$string['commerce_product_force_delete_disabled'] = 'Принудительное удаление продукта отключено. Включайте настройку только в контролируемой среде.';
$string['commerce_product_force_delete_confirmation_failed'] = 'Неверный пароль или текст подтверждения.';
$string['commerce_product_deleted'] = 'Продукт удалён.';

$string['commerce_access_scope_f6e_help'] = 'Коммерческий периметр доступа и каноническая связь миграции теперь настраиваются отдельно.';
$string['commerce_access_scope_shared_title'] = 'Общий периметр доступа';
$string['commerce_access_scope_shared_help'] = 'Несколько Native-продуктов могут использовать один и тот же периметр доступа. Эта настройка не изменяет Legacy-связь для миграции.';
$string['commerce_access_scope_source_plan'] = 'План-источник периметра';
$string['commerce_access_scope_no_scope'] = 'Без периметра доступа';
$string['commerce_access_scope_canonical_title'] = 'Каноническая связь Legacy → Native';
$string['commerce_access_scope_canonical_help'] = 'Используется для совместимости и переноса PROD: у одного Legacy-плана может быть только один канонический Native-продукт.';
$string['commerce_access_scope_canonical_plan'] = 'Канонический Legacy-план';
$string['commerce_access_scope_no_canonical_plan'] = 'Без канонической связи';
$string['commerce_access_scope_canonical_conflict'] = 'У этого Legacy-плана уже есть канонический Native-продукт. Используйте его общий периметр без переноса связи.';

// 7.95F6F — финальная UX-доработка и жизненный цикл продукта.
$string['settings:commerce_security_header'] = 'Безопасность Commerce';
$string['settings:commerce_security_header_desc'] = 'Настройки безопасности для чувствительных операций Native-каталога.';
$string['settings:commerce_allow_destructive_product_delete'] = 'Разрешить разрушительное удаление продуктов';
$string['settings:commerce_allow_destructive_product_delete_desc'] = 'Разрешает администратору удалить продукт вместе со связанными продажами или правами после усиленного подтверждения. В продакшене оставляйте настройку выключенной.';
$string['commerce_product_restored'] = 'Продукт восстановлен как черновик.';
$string['commerce_product_restore_title'] = 'Восстановить продукт';
$string['commerce_product_restore_help'] = 'Архивный продукт будет восстановлен как черновик, чтобы его можно было проверить перед повторной публикацией.';
$string['commerce_product_restore_action'] = 'Восстановить как черновик';
$string['commerce_product_archive_help'] = 'Продукт исчезнет из магазина и больше не будет доступен для покупки. История сохранится.';
$string['commerce_product_back_to_editor'] = 'Вернуться к карточке продукта';
$string['commerce_product_dependencies_title'] = 'Связанные данные и история';
$string['commerce_product_dependency_prices'] = 'Цены';
$string['commerce_product_dependency_translations'] = 'Переводы';
$string['commerce_product_dependency_components'] = 'Компоненты набора';
$string['commerce_product_dependency_entitlements'] = 'Настроенные права';
$string['commerce_product_dependency_mappings'] = 'Legacy-связи';
$string['commerce_product_dependency_native_purchase_items'] = 'Позиции Native-покупок';
$string['commerce_product_dependency_native_purchases'] = 'Native-покупки';
$string['commerce_product_dependency_legacy_plan_sales'] = 'Продажи Legacy-планов';
$string['commerce_product_dependency_legacy_digital_sales'] = 'Продажи цифровых Legacy-продуктов';
$string['commerce_product_dependency_grants'] = 'Выданные права';
$string['commerce_product_delete_checkbox'] = 'Я подтверждаю окончательное удаление этого продукта.';
$string['commerce_product_delete_confirmation_failed'] = 'Подтверждение или пароль администратора неверны.';
$string['commerce_product_force_delete_confirmation_failed'] = 'Для разрушительного удаления введите точно SUPPRIMER.';
$string['commerce_product_force_delete_disabled_help'] = 'Разрушительное удаление отключено в настройках Commerce. Продукт нельзя удалить, пока существует его история.';

$string['commerce_catalog_origin_legacy_only'] = 'Только Legacy';
$string['commerce_catalog_origin_native_short'] = 'NATIVE';
$string['commerce_catalog_origin_legacy_short'] = 'LEGACY';
$string['commerce_catalog_open_legacy_plan'] = 'Открыть исторический план';
$string['commerce_catalog_open_legacy_digital'] = 'Открыть исторический цифровой продукт';

// 7.95G4-G5 — Интерфейс корзины магазина и страница корзины.
$string['commerce_cart_title'] = 'Ваша корзина';
$string['commerce_cart_add'] = 'Добавить в корзину';
$string['commerce_cart_view'] = 'Открыть корзину';
$string['commerce_cart_already_owned'] = 'Уже куплено';
$string['commerce_cart_empty_title'] = 'Ваша корзина пуста';
$string['commerce_cart_empty_text'] = 'Откройте магазин CampusFR и добавьте подходящие вам курсы или материалы.';
$string['commerce_cart_continue_shopping'] = 'Продолжить покупки';
$string['commerce_cart_checkout'] = 'Подтвердить корзину';
$string['commerce_cart_quantity'] = 'Количество';
$string['commerce_cart_unit_price'] = 'Цена за единицу';
$string['commerce_cart_subtotal'] = 'Промежуточный итог';
$string['commerce_cart_discount'] = 'Скидки';
$string['commerce_cart_tax'] = 'Налоги';
$string['commerce_cart_total'] = 'Итого';
$string['commerce_cart_remove'] = 'Удалить';
$string['commerce_cart_update'] = 'Обновить';
$string['commerce_cart_message_already_owned'] = 'Вы уже приобрели этот продукт.';
$string['commerce_cart_message_already_in_cart'] = 'Этот продукт уже находится в вашей корзине.';
$string['commerce_cart_message_item_not_found'] = 'Не удалось найти эту позицию в корзине.';
$string['commerce_cart_message_add_success'] = 'Продукт добавлен в корзину.';
$string['commerce_cart_message_remove_success'] = 'Продукт удалён из корзины.';
$string['commerce_cart_message_update_success'] = 'Количество обновлено.';
$string['commerce_cart_message_clear_success'] = 'Корзина очищена.';
$string['commerce_cart_message_unchanged'] = 'Корзина не была изменена.';
$string['commerce_cart_message_error'] = 'Не удалось обновить корзину.';

// 7.95G6 — UX и финальная доработка корзины.
$string['commerce_cart_total_ttc'] = 'Итого с НДС';
$string['commerce_cart_view_product'] = 'Открыть страницу товара';
$string['commerce_cart_payment_secure'] = 'Безопасная оплата';
$string['commerce_cart_instant_access'] = 'Мгновенный доступ после оплаты';

// 7.95G7C-G7D — Промокоды корзины.
$string['commerce_cart_promo_code'] = 'Промокод';
$string['commerce_cart_promo_placeholder'] = 'Введите промокод';
$string['commerce_cart_promo_apply'] = 'Применить';
$string['commerce_cart_promo_remove'] = 'Удалить';
$string['commerce_cart_message_promotion_code_saved'] = 'Промокод сохранён.';
$string['commerce_cart_message_promotion_removed'] = 'Промокод удалён.';
$string['commerce_cart_message_promotion_already_applied'] = 'Этот промокод уже применён.';
$string['commerce_cart_message_promotion_code_required'] = 'Введите промокод.';
$string['commerce_cart_message_promotion_not_found'] = 'Такого промокода не существует.';
$string['commerce_cart_message_promotion_inactive'] = 'Этот промокод неактивен.';
$string['commerce_cart_message_promotion_not_started'] = 'Этот промокод ещё не действует.';
$string['commerce_cart_message_promotion_expired'] = 'Срок действия промокода истёк.';
$string['commerce_cart_message_promotion_currency_mismatch'] = 'Этот промокод не действует для выбранной валюты.';
$string['commerce_cart_message_promotion_minimum_cart_not_reached'] = 'Не достигнута минимальная сумма заказа для этого промокода.';
$string['commerce_cart_message_promotion_no_eligible_product'] = 'Этот промокод не применяется к товарам в корзине.';
$string['commerce_cart_message_promotion_global_usage_limit_reached'] = 'Лимит использования этого промокода исчерпан.';
$string['commerce_cart_message_promotion_user_usage_limit_reached'] = 'Вы уже использовали этот промокод максимальное количество раз.';

// 7.95G7E — Управление и стабилизация промоакций.
$string['commerce_promotions_title'] = 'Промоакции';
$string['commerce_promotions_description'] = 'Создание и управление промокодами и автоматическими скидками Commerce.';
$string['commerce_promotions_empty'] = 'Промоакции пока не созданы.';
$string['commerce_promotion_add'] = 'Добавить промоакцию';
$string['commerce_promotion_edit'] = 'Изменить промоакцию';
$string['commerce_promotion_name'] = 'Название';
$string['commerce_promotion_code'] = 'Код';
$string['commerce_promotion_type'] = 'Тип';
$string['commerce_promotion_value'] = 'Значение';
$string['commerce_promotion_value_minor'] = 'Значение (базисные пункты или минимальные единицы)';
$string['commerce_promotion_percentage'] = 'Процент';
$string['commerce_promotion_fixed'] = 'Фиксированная сумма';
$string['commerce_promotion_minimum'] = 'Минимальная сумма корзины';
$string['commerce_promotion_priority'] = 'Приоритет';
$string['commerce_promotion_uses'] = 'Использования';
$string['commerce_promotion_global_limit'] = 'Общий лимит использований';
$string['commerce_promotion_user_limit'] = 'Лимит на пользователя';
$string['commerce_promotion_active'] = 'Активна';
$string['commerce_promotion_automatic'] = 'Автоматическая';
$string['commerce_promotion_stackable'] = 'Суммируется';
$string['commerce_promotion_productskus'] = 'Допустимые SKU (по одному в строке)';
$string['commerce_promotion_producttypes'] = 'Допустимые типы продуктов (по одному в строке)';


// 7.95G7F — Promotion polish and Commerce configuration hub.
$string['commerce_configuration_title'] = 'Настройки Commerce';
$string['commerce_configuration_description'] = 'Настраивайте правила доступа, тарифные планы и промокампании в одном разделе.';
$string['commerce_configuration_scopes_title'] = 'Области доступа';
$string['commerce_configuration_scopes_description'] = 'Определяйте курсы и ресурсы, к которым получают доступ клиенты.';
$string['commerce_configuration_plans_title'] = 'Планы';
$string['commerce_configuration_plans_description'] = 'Управляйте планами, сроками, доступами и коммерческой доступностью.';
$string['commerce_configuration_promotions_title'] = 'Промоакции';
$string['commerce_configuration_promotions_description'] = 'Создавайте промокоды и автоматические рекламные кампании.';
$string['commerce_configuration_open'] = 'Открыть';
$string['commerce_promotion_back_to_list'] = 'Вернуться к списку промокодов';
$string['commerce_promotion_all_currencies'] = 'Все валюты';
$string['commerce_promotion_select_all'] = 'Все — без ограничений';
$string['commerce_promotion_name_help'] = 'Внутреннее название для удобной идентификации кампании в CRM.';
$string['commerce_promotion_code_help'] = 'Код, который вводит клиент. Для автоматической акции он не требуется.';
$string['commerce_promotion_type_help'] = 'Выберите скидку в процентах или фиксированную сумму.';
$string['commerce_promotion_value_display'] = 'Размер скидки';
$string['commerce_promotion_value_display_help'] = 'Введите понятное значение: 20 означает 20% для процентной скидки или 20 денежных единиц для фиксированной.';
$string['commerce_promotion_currency_help'] = 'Ограничьте акцию одной из настроенных валют или выберите все валюты.';
$string['commerce_promotion_minimum_display'] = 'Минимальная сумма корзины';
$string['commerce_promotion_minimum_help'] = 'Минимальная сумма до скидки в отображаемых денежных единицах. Введите 0, чтобы убрать ограничение.';
$string['commerce_promotion_priority_help'] = 'Акции с более высоким приоритетом проверяются первыми.';
$string['commerce_promotion_global_limit_help'] = 'Максимальное число применений для всех клиентов. Оставьте пустым без ограничения.';
$string['commerce_promotion_user_limit_help'] = 'Максимальное число применений на одного клиента. Оставьте пустым без ограничения.';
$string['commerce_promotion_active_help'] = 'Акция может проверяться и применяться.';
$string['commerce_promotion_automatic_help'] = 'Акция применяется без ввода кода клиентом.';
$string['commerce_promotion_stackable_help'] = 'Акцию можно сочетать с другими совместимыми акциями.';
$string['commerce_promotion_productskus_help'] = 'Выберите подходящие товары. «Все» означает отсутствие ограничений по товарам.';
$string['commerce_promotion_producttypes_help'] = 'Выберите подходящие типы товаров. «Все» означает отсутствие ограничений по типам.';
$string['commerce_promotion_validation_required'] = 'Это поле обязательно.';
$string['commerce_promotion_validation_duplicate'] = 'Этот код уже используется.';
$string['commerce_promotion_validation_invalid'] = 'Введено недопустимое значение.';

// Commerce 7.95H2 — Unified Checkout UI.
$string['commerce_checkout_title'] = 'Оформление заказа';
$string['commerce_checkout_eyebrow'] = 'CampusFR Commerce';
$string['commerce_checkout_subtitle'] = 'Проверьте заказ и выберите способ оплаты.';
$string['commerce_checkout_order_summary'] = 'Состав заказа';
$string['commerce_checkout_payment_title'] = 'Оплата';
$string['commerce_checkout_payment_description'] = 'Выберите платёжную систему, доступную для вашей валюты.';
$string['commerce_checkout_provider_label'] = 'Способ оплаты';
$string['commerce_checkout_provider_stripe'] = 'Stripe';
$string['commerce_checkout_provider_stripe_desc'] = 'Безопасная оплата банковской картой.';
$string['commerce_checkout_provider_alfa'] = 'Альфа-Банк';
$string['commerce_checkout_provider_alfa_desc'] = 'Безопасная оплата в рублях.';
$string['commerce_checkout_continue_payment'] = 'Оплатить сейчас';
$string['commerce_checkout_back_cart'] = 'Вернуться в корзину';
$string['commerce_checkout_prepare_error'] = 'Не удалось подготовить заказ. Проверьте корзину.';
$string['commerce_checkout_launch_h2_hint'] = 'Переход к платёжной системе будет включён в H4.';
$string['commerce_checkout_issue_empty_cart'] = 'Ваша корзина пуста.';
$string['commerce_checkout_issue_customer_mismatch'] = 'Эта корзина принадлежит другой учётной записи.';
$string['commerce_checkout_issue_currency_mismatch'] = 'Валюта корзины не совпадает с валютой заказа.';
$string['commerce_checkout_issue_generic'] = 'Перед продолжением необходимо проверить заказ.';

$string['commerce_checkout_launch_error'] = 'Не удалось начать оплату. Списание не производилось.';

$string['commerce_checkout_steps_label'] = 'Этапы оплаты';
$string['commerce_checkout_step_cart'] = 'Корзина';
$string['commerce_checkout_step_review'] = 'Проверка';
$string['commerce_checkout_step_payment'] = 'Оплата';
$string['commerce_checkout_step_confirmation'] = 'Подтверждение';
$string['commerce_checkout_prepare_error_reference'] = 'Не удалось подготовить оформление заказа. Проверьте корзину. Код: {$a}';
$string['commerce_checkout_launch_error_reference'] = 'Не удалось начать оплату. Списание не производилось. Код: {$a}';
$string['commerce_purchase_grants_section'] = 'Предоставленные права';
$string['commerce_purchase_no_grants'] = 'Для этой покупки не зарегистрировано прав Native.';
$string['commerce_purchase_grant_type'] = 'Тип права';
$string['commerce_purchase_resource'] = 'Предоставленный ресурс';
$string['commerce_purchase_beneficiary'] = 'Получатель';
$string['commerce_purchase_handler'] = 'Выполненный обработчик';
$string['commerce_purchase_attempts'] = 'Попытки';
$string['commerce_purchase_duration'] = 'Длительность';
$string['commerce_purchase_duration_seconds'] = '{$a} с';
$string['commerce_purchase_execution_reference'] = 'Идентификатор выполнения';
$string['commerce_purchase_message'] = 'Сообщение';
$string['commerce_purchase_error'] = 'Ошибка';
$string['commerce_purchase_fulfillment_attempts_section'] = 'История попыток предоставления';
$string['commerce_purchase_no_fulfillment_attempts'] = 'Попытки предоставления Native не зарегистрированы.';

// Commerce 7.95 H4.8.5 — Native-файлы цифровых товаров.
$string['commerce_digital_files_native_help'] = 'Файлы хранятся непосредственно в Native-товаре через закрытый Moodle File API. Для выдачи в первую очередь используется версия для компьютера.';
$string['commerce_digital_files_legacy_fallback'] = 'Native-файл пока не добавлен. Временно продолжает использоваться связанный исторический файл для обратной совместимости.';
$string['commerce_invalid_digital_file_type'] = 'Цифровой файл должен быть документом PDF.';
$string['commerce_digital_file_error_upload'] = 'Не удалось сохранить цифровой файл. Убедитесь, что это корректный PDF.';
$string['commerce_digital_file_error_maxbytes'] = 'Размер цифрового файла превышает допустимый максимум ({$a}).';

$string['commerce_guest_checkout_title'] = 'Ваши данные';
$string['commerce_guest_checkout_description'] = 'Укажите свои данные, чтобы создать защищённую временную учётную запись и перейти к оплате.';
$string['commerce_guest_checkout_continue'] = 'Перейти к оформлению заказа';
$string['commerce_guest_checkout_existing_account'] = 'Учётная запись с этим адресом уже существует. Войдите, чтобы продолжить без создания дубликата.';
$string['commerce_guest_checkout_provisional_ready'] = 'Временная учётная запись и корзина готовы. Теперь можно перейти к безопасной оплате.';
$string['commerce_guest_checkout_identity_required'] = 'Укажите свои данные перед переходом к оплате.';
$string['commerce_guest_checkout_login_required'] = 'Этот адрес уже связан с существующей учётной записью. Войдите, чтобы продолжить.';
$string['commerce_guest_checkout_account_activated'] = 'Покупка подтверждена. Ваша учётная запись CampusFR активирована; перед первым входом задайте новый пароль.';
$string['commerce_guest_checkout_activation_subject'] = 'Ваша учётная запись CampusFR готова';
$string['commerce_guest_checkout_activation_message'] = 'Здравствуйте, {$a->firstname}!\n\nОплата подтверждена, и ваша учётная запись CampusFR активирована. Перед первым входом задайте пароль по ссылке:\n{$a->reseturl}';
$string['commerce_guest_checkout_invalid_email'] = 'Введите корректный адрес электронной почты.';
$string['commerce_guest_checkout_invalid_firstname'] = 'Введите имя (не более 100 символов).';
$string['commerce_guest_checkout_invalid_lastname'] = 'Введите фамилию (не более 100 символов).';
$string['commerce_guest_checkout_duplicate_email_accounts'] = 'Этот адрес электронной почты используется в нескольких аккаунтах. Сначала войдите в свой аккаунт.';
$string['commerce_guest_checkout_session_expired'] = 'Сессия оформления заказа истекла. Корзина сохранена: начните оформление ещё раз.';
$string['commerce_guest_checkout_account_mismatch'] = 'Выполнен вход в аккаунт, который не соответствует адресу из оформления заказа. Войдите в нужный аккаунт.';
$string['commerce_i2_order_not_found'] = 'Заказ не найден.';
$string['commerce_i2_title_success'] = 'Покупка подтверждена!';
$string['commerce_i2_message_success'] = 'Оплата подтверждена, и доступ к материалам уже открыт.';
$string['commerce_i2_title_processing'] = 'Оплата подтверждена, доступ готовится';
$string['commerce_i2_message_processing'] = 'Мы получили оплату. CampusFR завершает активацию ваших материалов.';
$string['commerce_i2_title_pending'] = 'Оплата проверяется';
$string['commerce_i2_message_pending'] = 'Платёжный сервис ещё подтверждает операцию. После проверки здесь появятся ваши материалы.';
$string['commerce_i2_title_failed'] = 'Оплата не прошла';
$string['commerce_i2_message_failed'] = 'Подтверждённого списания нет. Можно повторить оплату или выбрать другой способ.';
$string['commerce_i2_title_cancelled'] = 'Оплата отменена';
$string['commerce_i2_message_cancelled'] = 'Заказ сохранён, но оплата не была подтверждена.';
$string['commerce_i2_title_unknown'] = 'Мы проверяем ваш заказ';
$string['commerce_i2_message_unknown'] = 'Полученный статус требует дополнительной проверки. Данные заказа сохранены.';
$string['commerce_i2_order_label'] = 'Заказ CampusFR';
$string['commerce_i2_quantity'] = 'Количество: {$a}';
$string['commerce_i2_open_course'] = 'Перейти к курсу';
$string['commerce_i2_download_file'] = 'Скачать файл';
$string['commerce_i2_retry'] = 'Повторить оплату';
$string['commerce_i2_back_cart'] = 'Вернуться в корзину';
$string['commerce_i2_my_orders'] = 'Мои покупки';
$string['commerce_i2_my_courses'] = 'Мои курсы';
$string['commerce_i2_my_resources'] = 'Мои материалы';
$string['commerce_i2_support'] = 'Обратиться в поддержку';
$string['commerce_i2_support_subject'] = 'Помощь по заказу {$a}';
$string['commerce_i2_next_title'] = 'Мои материалы';
$string['commerce_i3_access_unavailable'] = 'Этот доступ ещё не готов или больше недоступен.';
$string['commerce_i3_access_missing'] = 'Запрошенный материал не найден.';
$string['commerce_i3_access_unsupported'] = 'Этот тип доступа не поддерживается.';
$string['commerce_i3_access_pending'] = 'Доступ готовится.';
$string['commerce_i3_access_expired'] = 'Срок действия доступа истёк.';
$string['commerce_i3_access_download_limit_reached'] = 'Лимит скачиваний исчерпан.';

$string['commerce_i43_page_title'] = 'Заказ {$a}';
$string['commerce_i43_back'] = 'Вернуться к покупкам';
$string['commerce_i43_order'] = 'Заказ CampusFR';
$string['commerce_i43_total'] = 'Итого';
$string['commerce_i43_statuses'] = 'Статусы заказа';
$string['commerce_i43_order_status'] = 'Заказ';
$string['commerce_i43_payment_status'] = 'Оплата';
$string['commerce_i43_access_status'] = 'Доступ';
$string['commerce_i43_items'] = 'Состав заказа';
$string['commerce_i43_unit_price'] = 'Цена за единицу';
$string['commerce_i43_gross'] = 'Промежуточный итог';
$string['commerce_i43_discount'] = 'Скидка';
$string['commerce_i43_summary'] = 'Сводка';
$string['commerce_i43_provider'] = 'Способ оплаты';
$string['commerce_i43_paid_at'] = 'Оплата подтверждена';
$string['commerce_i43_technical'] = 'Техническая информация';


// Commerce 7.95 I4.4-I4.5 — Order timeline and payment information.
$string['commerce_i44_timeline'] = 'Ход выполнения заказа';
$string['commerce_i44_event_order_created'] = 'Заказ создан';
$string['commerce_i44_event_payment_confirmed'] = 'Оплата подтверждена';
$string['commerce_i44_event_payment_pending'] = 'Ожидание оплаты';
$string['commerce_i44_event_payment_processing'] = 'Оплата обрабатывается';
$string['commerce_i44_event_payment_failed'] = 'Ошибка оплаты';
$string['commerce_i44_event_payment_cancelled'] = 'Оплата отменена';
$string['commerce_i44_event_access_available'] = 'Доступ открыт';
$string['commerce_i44_event_access_planned'] = 'Подготовка доступа';
$string['commerce_i44_event_access_processing'] = 'Доступ активируется';
$string['commerce_i44_event_access_failed'] = 'Не удалось активировать доступ';
$string['commerce_i45_payment_information'] = 'Информация об оплате';
$string['commerce_i45_payment_status'] = 'Статус';
$string['commerce_i45_provider'] = 'Платёжный сервис';
$string['commerce_i45_amount'] = 'Сумма платежа';
$string['commerce_i45_paid_at'] = 'Оплата подтверждена';
$string['commerce_i45_request_status'] = 'Статус запроса';
$string['commerce_i45_requested_at'] = 'Запрос создан';
$string['commerce_i45_expires_at'] = 'Срок действия запроса';

// 7.95 I4.6-I4.8.
$string['commerce_i46_support_body'] = 'Здравствуйте!\\n\\nМне нужна помощь с заказом {$a->reference}.\\nАккаунт: {$a->email}\\n\\nОписание проблемы:\\n';
$string['commerce_i46_contact_support'] = 'Связаться с поддержкой';
$string['commerce_i46_support_title'] = 'Нужна помощь?';
$string['commerce_i46_support_description'] = 'Наша команда поможет с доступом, скачиванием или вопросом по оплате. Номер заказа будет автоматически добавлен в сообщение.';
$string['commerce_i46_reference_to_share'] = 'Номер заказа';
$string['commerce_i47_bundle_eyebrow'] = 'Комплект';
$string['commerce_i47_bundle_title'] = 'Состав вашего комплекта';
$string['commerce_i47_bundle_description'] = 'Все включённые материалы показаны ниже отдельно вместе с доступными действиями.';
$string['commerce_i47_bundle_items'] = 'элементов';
$string['commerce_i47_bundle_courses'] = 'курсов';
$string['commerce_i47_bundle_digitals'] = 'цифровых материалов';
$string['commerce_i47_bundle_accesses'] = 'доступов';

// Commerce 7.95 I4.9 — клиентское представление покупок.
$string['commerce_i49_course_purchase'] = 'Курс CampusFR';
$string['commerce_i49_view_course_page'] = 'Открыть описание';
$string['commerce_i49_open_course'] = 'Перейти к курсу';
$string['commerce_i49_bundle_purchases'] = 'Купленные наборы';
$string['commerce_i49_bundle_default_name'] = 'Предложение CampusFR';
$string['commerce_i49_bundle_badge'] = 'Набор';
$string['commerce_i49_bundle_contains'] = 'Содержимое';

// 7.95 I4.10 — Customer Experience Certification.
$string['commerce_i410_order_confirmed'] = 'Заказ подтверждён';
$string['commerce_i410_order_processing'] = 'Заказ обрабатывается';
$string['commerce_i410_order_cancelled'] = 'Заказ отменён';
$string['commerce_i410_order_failed'] = 'Заказ не завершён';
$string['commerce_i410_payment_received'] = 'Оплата получена';
$string['commerce_i410_payment_pending'] = 'Ожидание оплаты';
$string['commerce_i410_payment_cancelled'] = 'Оплата отменена';
$string['commerce_i410_payment_failed'] = 'Оплата отклонена';
$string['commerce_i410_access_available'] = 'Доступы открыты';
$string['commerce_i410_access_preparing'] = 'Доступы подготавливаются';
$string['commerce_i410_access_failed'] = 'Активация доступов прервана';
$string['commerce_i410_step_completed'] = 'Готово';
$string['commerce_i410_step_pending'] = 'В процессе';
$string['commerce_i410_step_failed'] = 'Требуется действие';
$string['commerce_i410_order_update'] = 'Обновление заказа';
$string['commerce_i410_product_access'] = 'Доступ к продуктам';
$string['commerce_i410_payment_method_unknown'] = 'Онлайн-оплата';
$string['commerce_i410_not_available'] = 'Нет данных';
$string['commerce_i410_type_bundle'] = 'Набор';
$string['commerce_i410_type_course'] = 'Курс';
$string['commerce_i410_type_digital'] = 'Цифровой материал';
$string['commerce_i410_type_product'] = 'Продукт';
$string['commerce_i410_bundle_includes'] = 'В набор входят';
$string['commerce_i410_open_course'] = 'Перейти к курсу';
$string['commerce_i410_reference'] = 'Номер';
$string['commerce_i410_order_date'] = 'Дата';
$string['commerce_i410_article_count'] = 'Количество позиций';
$string['commerce_i410_payment_method'] = 'Способ оплаты';
$string['commerce_i410_amount_paid'] = 'Оплаченная сумма';
$string['commerce_i410_invoice'] = 'Счёт';
$string['commerce_i410_invoice_description'] = 'Скачайте документ для этого заказа.';
$string['commerce_i410_download_invoice'] = 'Скачать PDF';
$string['commerce_i410_invoice_title'] = 'Счёт {$a}';
$string['commerce_i410_invoice_reference'] = 'Номер счёта';
$string['commerce_i410_invoice_date'] = 'Дата';
$string['commerce_i410_invoice_customer'] = 'Клиент';
$string['commerce_i410_invoice_item'] = 'Позиция';
$string['commerce_i410_invoice_quantity'] = 'Количество';
$string['commerce_i410_invoice_total'] = 'Итого';
$string['commerce_i410_invoice_generated_notice'] = 'Документ автоматически создан в личном кабинете CampusFR.';

$string['commerce_i411_original_amount'] = 'Original amount';
$string['commerce_i411_discount'] = 'Discount';
$string['commerce_i411_promo_code'] = 'Promotion code';
$string['commerce_i411_paid_badge'] = 'Paid';
$string['commerce_i411_product_page'] = 'View product';
$string['commerce_i411_invoice_settings'] = 'Commerce invoicing';
$string['commerce_i411_invoice_settings_desc'] = 'Configure separate invoice issuers for EUR and RUB.';
$string['commerce_i411_invoice_profile_eur'] = 'EUR / Stripe invoice entity';
$string['commerce_i411_invoice_profile_rub'] = 'RUB / Alfa invoice entity';
$string['commerce_i411_invoice_name'] = 'Nom ou raison sociale';
$string['commerce_i411_invoice_address'] = 'Adresse complète';
$string['commerce_i411_invoice_legal'] = 'Informations légales';
$string['commerce_i411_invoice_email'] = 'E-mail';
$string['commerce_i411_invoice_phone'] = 'Téléphone';
$string['commerce_i411_invoice_website'] = 'Site web';
$string['commerce_i411_invoice_tax_notice'] = 'Mention fiscale';
$string['commerce_i411_invoice_footer'] = 'Pied de facture';

$string['commerce_order_access_preparing'] = 'Доступ подготавливается';

$string['commerce_multi_item_order_title'] = 'Заказ {$a}';
$string['commerce_purchase_origin'] = 'Источник покупки';
$string['commerce_purchase_origin_legacy'] = 'Legacy';
$string['commerce_purchase_origin_native'] = 'Native';
$string['commerce_invoice_purchase_date'] = 'Дата покупки';
$string['commerce_invoice_bundle_includes'] = 'Включает:';
$string['commerce_invoice_subtotal'] = 'Промежуточный итог';
$string['commerce_invoice_discount'] = 'Скидка';
$string['commerce_invoice_promotion_code'] = 'Промокод';
$string['commerce_invoice_total_paid'] = 'Оплачено';
$string['commerce_invoice_payment_information'] = 'Информация об оплате';
$string['commerce_invoice_payment_provider'] = 'Способ оплаты';
$string['commerce_invoice_transaction_id'] = 'Идентификатор транзакции';
$string['commerce_invoice_generated_at'] = 'Счёт сформирован {$a}';

$string['digital_library_title'] = 'Мои цифровые материалы';
$string['digital_library_user_title'] = 'Цифровые материалы пользователя {$a}';
$string['digital_library_subtitle'] = 'Здесь собраны ваши цифровые продукты и доступные для скачивания файлы.';
$string['digital_library_empty_title'] = 'Цифровых материалов пока нет';
$string['digital_library_empty_description'] = 'Цифровые продукты появятся здесь после покупки, открывающей доступ к файлу.';
$string['digital_library_open_catalog'] = 'Открыть магазин';
$string['digital_library_download'] = 'Скачать';
$string['digital_library_source_legacy'] = 'Историческая система';
$string['digital_library_source_native'] = 'Commerce';
$string['digital_library_bundle_badge'] = 'В составе набора';
$string['digital_library_resource_fallback'] = 'Цифровой материал';
$string['digital_library_resource_number'] = 'Цифровой материал №{$a}';

$string['digital_library_view_product'] = 'Открыть страницу продукта';
$string['digital_library_file'] = 'Доступный файл';
$string['digital_library_files'] = 'Доступные файлы';
$string['digital_library_file_type'] = 'Тип';
$string['digital_library_file_size'] = 'Размер';
$string['digital_library_already_downloaded'] = 'Уже скачан';
$string['digital_library_not_downloaded_yet'] = 'Ещё не скачан';
$string['digital_library_last_download'] = 'Последнее скачивание';
$string['digital_library_download_count_one'] = '1 скачивание';
$string['digital_library_download_count_many'] = 'Скачиваний: {$a}';
$string['digital_library_download_file'] = 'Скачать';
$string['digital_library_download_aria'] = 'Скачать {$a->file} — {$a->product}';
$string['digital_library_history_unavailable'] = 'История скачиваний недоступна';
$string['event_digital_file_downloaded'] = 'Цифровой файл загружен';

$string['task_process_commerce_mail_queue'] = 'Обработка очереди транзакционных писем Commerce';

$string['commerce_mail_purchase_access_subject'] = 'Ваши материалы CampusFR доступны';
$string['commerce_mail_purchase_receipt_subject'] = 'Подтверждение покупки CampusFR';
$string['commerce_mail_payment_pending_subject'] = 'Ваш платёж CampusFR обрабатывается';
$string['commerce_mail_payment_failed_subject'] = 'Платёж CampusFR не прошёл';
$string['commerce_mail_payment_cancelled_subject'] = 'Платёж CampusFR отменён';
$string['commerce_mail_greeting'] = 'Здравствуйте, {$a}!';
$string['commerce_mail_customer_fallback'] = 'дорогой пользователь';
$string['commerce_mail_purchase_access_intro'] = 'Покупка подтверждена, и ваши материалы уже доступны.';
$string['commerce_mail_purchase_receipt_intro'] = 'Спасибо за покупку. Ниже указана информация о вашем заказе.';
$string['commerce_mail_payment_pending_intro'] = 'Ваш платёж всё ещё обрабатывается. Новые доступы будут активированы после его подтверждения.';
$string['commerce_mail_payment_pending_help'] = 'Статус заказа можно проверить в вашем личном кабинете CampusFR.';
$string['commerce_mail_payment_failed_intro'] = 'Нам не удалось подтвердить ваш платёж.';
$string['commerce_mail_payment_failed_help'] = 'Попробуйте оплатить снова в личном кабинете CampusFR или выберите другой способ оплаты.';
$string['commerce_mail_payment_cancelled_intro'] = 'Платёж был отменён, и CampusFR не подтвердил списание средств.';
$string['commerce_mail_payment_cancelled_help'] = 'Товары останутся в корзине, и вы сможете вернуться к покупке.';
$string['commerce_mail_reference'] = 'Номер заказа';
$string['commerce_mail_quantity'] = 'Количество';
$string['commerce_mail_total'] = 'Итого';
$string['commerce_mail_payment_information'] = 'Информация об оплате';
$string['commerce_mail_payment_provider'] = 'Способ оплаты';
$string['commerce_mail_transaction_reference'] = 'Транзакция';
$string['commerce_mail_payment_status'] = 'Статус';
$string['commerce_mail_access_course'] = 'Перейти к курсу';
$string['commerce_mail_download_file'] = 'Скачать файл';
$string['commerce_mail_view_product'] = 'Открыть страницу продукта';
$string['commerce_mail_view_order'] = 'Посмотреть заказ';
$string['commerce_mail_view_purchases'] = 'Мои покупки';
$string['commerce_mail_view_resources'] = 'Мои цифровые материалы';
$string['commerce_mail_view_courses'] = 'Перейти к моим курсам';
$string['commerce_mail_product_fallback'] = 'Продукт CampusFR';
$string['commerce_mail_no_item_details'] = 'Подробная информация о материалах будет доступна в вашем личном кабинете CampusFR.';

$string['crm_commerce_nav_mail'] = 'Письма';
$string['commerce_mail_admin_title'] = 'Транзакционные письма';
$string['commerce_mail_admin_description'] = 'Контроль очереди писем Commerce, предпросмотр и повторная отправка ошибок.';
$string['commerce_mail_preview'] = 'Предпросмотр письма';
$string['attempts'] = 'Попытки';
$string['retry'] = 'Повторить';
$string['commerce_mail_type_purchase_access'] = 'Доступы готовы';
$string['commerce_mail_type_purchase_receipt'] = 'Подтверждение покупки';
$string['commerce_mail_type_payment_pending'] = 'Платёж обрабатывается';
$string['commerce_mail_type_payment_failed'] = 'Ошибка платежа';
$string['commerce_mail_type_payment_cancelled'] = 'Платёж отменён';
$string['commerce_mail_status_queued'] = 'В очереди';
$string['commerce_mail_status_processing'] = 'Обрабатывается';
$string['commerce_mail_status_sent'] = 'Отправлено';
$string['commerce_mail_status_failed'] = 'Ошибка';
$string['commerce_mail_status_cancelled'] = 'Отменено';
$string['commerce_mail_language_fr'] = 'Французский';
$string['commerce_mail_language_en'] = 'Английский';
$string['commerce_mail_language_ru'] = 'Русский';
$string['commerce_mail_filter_all'] = 'Все';
$string['commerce_mail_status_filter'] = 'Статус';
$string['commerce_mail_type_filter'] = 'Тип письма';
$string['commerce_mail_language_filter'] = 'Язык';
$string['commerce_mail_purchase_id'] = 'ID заказа';
$string['commerce_mail_search_placeholder'] = 'E-mail, получатель или ключ идемпотентности';
$string['commerce_mail_dashboard_description'] = 'Контролируйте транзакционные письма, просматривайте их содержимое и повторяйте неудачные отправки.';
$string['commerce_mail_templates_title'] = 'Шаблоны писем';
$string['commerce_mail_templates_description'] = 'Настраивайте редакционные зоны транзакционных писем, не изменяя защищённые технические блоки.';
$string['commerce_mail_templates_manage'] = 'Управление шаблонами';
$string['commerce_mail_template_type'] = 'Тип письма';
$string['commerce_mail_template_language'] = 'Язык';
$string['commerce_mail_template_enabled'] = 'Пользовательский шаблон активен';
$string['commerce_mail_template_subject'] = 'Тема';
$string['commerce_mail_template_preheader'] = 'Предзаголовок';
$string['commerce_mail_template_heading'] = 'Главный заголовок';
$string['commerce_mail_template_intro'] = 'Введение';
$string['commerce_mail_template_outro'] = 'Заключение';
$string['commerce_mail_template_signature'] = 'Подпись';
$string['commerce_mail_template_headerimage_enabled'] = 'Подготовить изображение заголовка';
$string['commerce_mail_template_headerimage_note'] = 'Загрузка и отображение изображения будут добавлены в I6.6B. Эта настройка уже подготавливает шаблон.';
$string['commerce_mail_template_tokens'] = 'Разрешённые токены';
$string['commerce_mail_template_default'] = 'Содержимое по умолчанию';
$string['commerce_mail_template_edit_title'] = '{$a->type} — {$a->language}';
$string['commerce_mail_template_edit_description'] = 'Редакционный контент будет окружать технический блок письма. Доступы, продукты, суммы и действия остаются сформированными Commerce.';
$string['commerce_mail_template_invalid_type'] = 'Недопустимый тип письма.';
$string['commerce_mail_template_invalid_language'] = 'Недопустимый язык.';
$string['commerce_mail_back_to_log'] = 'Вернуться к журналу писем';
$string['commerce_mail_template_reset'] = 'Восстановить по умолчанию';
$string['commerce_mail_template_reset_confirm'] = 'Удалить эту настройку и восстановить содержимое по умолчанию?';
$string['commerce_mail_template_reset_done'] = 'Содержимое по умолчанию восстановлено.';

$string['commerce_mail_template_headerimage_file'] = 'Изображение в шапке';
$string['commerce_mail_template_preview_title'] = 'Предпросмотр — {$a->type} — {$a->language}';
$string['commerce_mail_template_preview_description'] = 'Предпросмотр создан с демонстрационными данными. Письмо не отправляется.';
$string['plaintext'] = 'Текстовая версия';

$string['settings:commerce_mail_audit_heading'] = 'Аудит-копии писем Commerce';
$string['settings:commerce_mail_audit_heading_desc'] = 'Настройка независимой транзакционной копии, регистрируемой в очереди и не влияющей на отправку клиенту.';
$string['settings:commerce_mail_audit_copy_enabled'] = 'Включить аудит-копии';
$string['settings:commerce_mail_audit_copy_enabled_desc'] = 'Создаёт отдельное письмо для выбранных типов сообщений.';
$string['settings:commerce_mail_audit_copy_address'] = 'Адрес аудит-копий';
$string['settings:commerce_mail_audit_copy_address_desc'] = 'Технический адрес для копий, например log@campusfr.fr.';
$string['settings:commerce_mail_audit_copy_types'] = 'Типы писем для копирования';
$string['settings:commerce_mail_audit_copy_types_desc'] = 'Выберите транзакционные письма, для которых нужно создавать независимую аудит-копию.';
$string['settings:commerce_mail_audit_copy_include_attachment'] = 'Прикладывать счёт к аудит-копии';
$string['settings:commerce_mail_audit_copy_include_attachment_desc'] = 'По умолчанию отключено, чтобы не дублировать персональные данные и вложения.';

$string['commerce_mail_preview_modes'] = 'Режимы предпросмотра письма';
$string['commerce_mail_preview_desktop'] = 'Компьютер';
$string['commerce_mail_preview_mobile'] = 'Мобильный';
$string['commerce_mail_preview_text'] = 'Обычный текст';
$string['commerce_mail_preview_source'] = 'HTML-код';
$string['commerce_mail_preview_desktop_title'] = 'Предпросмотр письма на компьютере';
$string['commerce_mail_preview_mobile_title'] = 'Предпросмотр письма на мобильном устройстве';
$string['commerce_mail_health_certified'] = 'Mail Engine сертифицирован';
$string['commerce_mail_health_attention'] = 'Mail Engine требует проверки';
$string['commerce_mail_health_readonly'] = 'Проверка состояния движка транзакционных писем в режиме только для чтения.';
$string['commerce_mail_health_ok'] = 'OK';
$string['commerce_mail_health_warnings'] = 'предупреждения';
$string['commerce_mail_health_errors'] = 'ошибки';

$string['commerce_cart_upgrade_label'] = 'Апгрейд';
$string['commerce_cart_message_upgrade_not_eligible'] = 'Этот апгрейд больше недоступен для данного аккаунта.';
$string['commerce_cart_upgrade_not_eligible'] = 'Этот апгрейд больше недоступен. Обновите страницу магазина и повторите попытку.';
$string['crm_commerce_orders'] = 'Заказы';
$string['crm_commerce_orders_hint'] = 'Заказы Native Commerce, связанные с клиентом.';
$string['crm_commerce_active_grants'] = 'Активные доступы';
$string['crm_commerce_active_grants_hint'] = 'Активные Grants для курсов и цифровых ресурсов.';
$string['crm_commerce_no_purchases'] = 'Покупки Commerce для этого клиента не найдены.';
$string['crm_commerce_reference'] = 'Номер';
$string['crm_commerce_purchase_type'] = 'Тип';
$string['crm_commerce_contents'] = 'Содержимое';
$string['crm_commerce_amount'] = 'Сумма';
$string['crm_commerce_view_order'] = 'Открыть заказ';
$string['crm_commerce_type_course'] = 'Курс';
$string['crm_commerce_type_digital'] = 'Цифровой продукт';
$string['crm_commerce_type_bundle'] = 'Набор';
$string['crm_commerce_type_upgrade'] = 'Апгрейд';
$string['crm_commerce_type_mixed'] = 'Смешанная корзина';
$string['crm_commerce_status_created'] = 'Создан';
$string['crm_commerce_status_payment_pending'] = 'Ожидает оплаты';
$string['crm_commerce_status_paid'] = 'Оплачен';
$string['crm_commerce_status_fulfillment_pending'] = 'Ожидает выдачи доступа';
$string['crm_commerce_status_fulfilled'] = 'Выдан';
$string['crm_commerce_status_failed'] = 'Ошибка';
$string['crm_commerce_status_cancelled'] = 'Отменён';
$string['crm_timeline_commerce_purchase_course'] = 'Создан заказ курса';
$string['crm_timeline_commerce_purchase_digital'] = 'Создан заказ цифрового продукта';
$string['crm_timeline_commerce_purchase_bundle'] = 'Создан заказ набора';
$string['crm_timeline_commerce_purchase_upgrade'] = 'Создан заказ апгрейда';
$string['crm_timeline_commerce_purchase_mixed'] = 'Создан смешанный заказ';
$string['crm_timeline_commerce_purchase_purchase'] = 'Создан заказ';
$string['crm_timeline_commerce_purchase_description'] = '{$a->reference} · {$a->items} · {$a->amount}';
$string['crm_timeline_commerce_payment_paid'] = 'Оплата подтверждена';
$string['crm_timeline_commerce_payment_pending'] = 'Оплата ожидается';
$string['crm_timeline_commerce_payment_failed'] = 'Ошибка оплаты';
$string['crm_timeline_commerce_payment_description'] = '{$a->reference} · {$a->amount} · {$a->provider}';
$string['crm_timeline_commerce_grant_course_access'] = 'Доступ к курсу выдан';
$string['crm_timeline_commerce_grant_digital_download'] = 'Цифровой ресурс доступен';
$string['crm_timeline_commerce_grant_access'] = 'Доступ выдан';

$string['commerce_order_print'] = 'Распечатать заказ';
$string['commerce_support_page_title'] = 'Поддержка — {$a}';
$string['commerce_support_heading'] = 'Чем мы можем помочь?';
$string['commerce_support_intro'] = 'Ваш запрос будет отправлен напрямую команде CampusFR вместе с полезной информацией о заказе.';
$string['commerce_support_back_to_order'] = 'Вернуться к заказу';
$string['commerce_support_order'] = 'Заказ';
$string['commerce_support_customer'] = 'Клиент';
$string['commerce_support_email'] = 'Электронная почта';
$string['commerce_support_category'] = 'Тип обращения';
$string['commerce_support_category_payment'] = 'Оплата';
$string['commerce_support_category_course_access'] = 'Доступ к курсу';
$string['commerce_support_category_download'] = 'Скачивание';
$string['commerce_support_category_invoice'] = 'Счёт';
$string['commerce_support_category_refund'] = 'Возврат';
$string['commerce_support_category_other'] = 'Другое';
$string['commerce_support_subject'] = 'Тема';
$string['commerce_support_default_subject'] = 'Вопрос по заказу {$a}';
$string['commerce_support_message'] = 'Опишите ваш запрос';
$string['commerce_support_send'] = 'Отправить запрос';
$string['commerce_support_success'] = 'Ваш запрос отправлен команде CampusFR. Мы ответим как можно скорее.';
$string['commerce_support_unavailable'] = 'Встроенная поддержка временно недоступна. Повторите попытку позже.';
$string['commerce_support_internal_reference'] = 'Номер заказа';
$string['commerce_support_payment_status'] = 'Статус оплаты';
$string['commerce_support_fulfillment_status'] = 'Статус предоставления доступа';
$string['commerce_support_products'] = 'Связанные продукты';

$string['event_commerce_customer_action_clicked'] = 'Действие клиента Commerce отслежено';

$string['commerce_tracking_invalid'] = 'Эта ссылка отслеживания Commerce недействительна или устарела.';

$string['commerce_access_preparing'] = 'Доступ подготавливается';

$string['commerce_access_temporarily_unavailable'] = 'Доступ временно недоступен';

$string['commerce_view_order'] = 'Посмотреть заказ';

$string['profile_customer_space_title'] = 'Моё пространство CampusFR';
$string['profile_link_courses'] = 'Мои курсы';
$string['profile_link_resources'] = 'Мои ресурсы';
$string['profile_link_purchases'] = 'Мои покупки';
$string['nav_my_courses'] = 'Мои курсы';
$string['nav_my_resources'] = 'Мои ресурсы';
$string['nav_my_purchases'] = 'Мои покупки';
$string['nav_my_profile'] = 'Мой профиль';
$string['commerce_cart_clear'] = 'Очистить корзину';
$string['commerce_cart_clear_confirm'] = 'Вы действительно хотите очистить корзину?';

$string['commerce_cart_buy_now'] = 'Купить сейчас';
$string['commerce_cart_remove_from_cart'] = 'Удалить из корзины';
$string['commerce_cart_added_modal_title'] = 'Добавлено в корзину';
$string['commerce_cart_added_modal_text'] = 'Товар добавлен в корзину.';
$string['commerce_cart_clear_confirm_action'] = 'Очистить корзину';
$string['commerce_cart_message_bundle_all_owned'] = 'У вас уже есть все элементы этого предложения.';
$string['commerce_cart_message_bundle_partial_owned'] = 'В этом предложении есть один или несколько уже приобретённых вами элементов. Цена комплекта не меняется.';
$string['commerce_cart_message_buynow_success'] = 'Товар готов к оплате.';

// Активация аккаунта после гостевой покупки.
$string['commerce_guest_checkout_activation_message'] = 'Здравствуйте, {$a->firstname}!\n\nОплата подтверждена. Задайте пароль CampusFR по защищённой ссылке:\n{$a->activationurl}\n\nЭта персональная ссылка действует 48 часов.';
$string['commerce_guest_activation_title'] = 'Активируйте аккаунт CampusFR';
$string['commerce_guest_activation_title_prefix'] = 'Активируйте аккаунт';
$string['commerce_guest_activation_quick_note'] = 'Последний шаг займёт всего несколько секунд — после этого вы сразу получите доступ ко всем материалам.';
$string['commerce_guest_activation_intro'] = 'Здравствуйте, {$a->firstname}! Задайте пароль, чтобы сразу получить доступ к курсам, материалам и покупкам.';
$string['commerce_guest_activation_email'] = 'Аккаунт связан с адресом: {$a}';
$string['commerce_guest_activation_submit'] = 'Задать пароль';
$string['commerce_guest_activation_success'] = 'Пароль задан. Вы вошли в CampusFR.';
$string['commerce_guest_activation_invalid'] = 'Ссылка активации недействительна или истекла. Вернитесь на страницу заказа, чтобы получить новую ссылку.';
$string['commerce_guest_activation_failed'] = 'Не удалось активировать аккаунт. Техническая информация: {$a}';
$string['commerce_guest_activation_password_invalid'] = 'Пароль не соответствует требованиям безопасности: {$a}';
$string['commerce_guest_activation_result_title'] = 'Завершите настройку аккаунта';
$string['commerce_guest_activation_result_message'] = 'Покупка подтверждена. Задайте пароль, чтобы открыть курсы, материалы и покупки.';
$string['commerce_guest_activation_result_cta'] = 'Задать пароль';
$string['commerce_guest_existing_account_result_title'] = 'Покупка уже доступна в вашем аккаунте';
$string['commerce_guest_existing_account_result_message'] = 'Этот адрес уже связан с аккаунтом CampusFR. Войдите, чтобы увидеть заказ и получить доступ.';

$string['commerce_guest_activation_confirm_password'] = 'Подтвердите новый пароль';

$string['commerce_guest_activation_email_label'] = 'Аккаунт привязан к адресу:';
$string['commerce_guest_activation_security_title'] = 'Требования безопасности';
$string['commerce_guest_activation_security_minlength'] = 'Не менее {$a} символов';
$string['commerce_guest_activation_security_lowercase'] = 'Не менее одной строчной буквы';
$string['commerce_guest_activation_security_uppercase'] = 'Не менее одной заглавной буквы';
$string['commerce_guest_activation_security_digit'] = 'Не менее одной цифры';
$string['commerce_guest_activation_security_special'] = 'Не менее одного специального символа';
$string['commerce_guest_activation_secure_link_title'] = 'Защищённая ссылка';
$string['commerce_guest_activation_secure_link'] = 'Эта ссылка персональная и одноразовая.';
$string['commerce_guest_activation_secure_link_expiry'] = 'Эта ссылка персональная, одноразовая и действительна до {$a}.';
$string['commerce_guest_activation_email_cta'] = 'Создать пароль';

$string['commerce_guest_activation_email_expiry'] = 'Ссылка действительна до {$a}.';

$string['commerce_mail_type_account_activation'] = 'Добро пожаловать / активация';
$string['commerce_product_covers_title'] = 'Изображения продукта';
$string['commerce_product_covers_help'] = 'Загрузите отдельное изображение для каждого сценария. Если его нет, Commerce автоматически использует изображение магазина, а затем старую основную обложку.';
$string['commerce_product_cover_fallback_notice'] = 'Отдельного изображения нет. Будет использован автоматический вариант.';
$string['commerce_product_cover_role_storefront'] = 'Магазин';
$string['commerce_product_cover_role_storefront_help'] = 'Карточка продукта в магазине. Рекомендуемый формат: 4:3.';
$string['commerce_product_cover_role_product'] = 'Страница продукта';
$string['commerce_product_cover_role_product_help'] = 'Главное изображение подробной страницы. Рекомендуемый формат: 16:9.';
$string['commerce_product_cover_role_recommendation'] = 'Рекомендации';
$string['commerce_product_cover_role_recommendation_help'] = 'Компактная карточка в разделе «Мои курсы». Формат: 4:3.';
$string['commerce_product_cover_role_resources'] = 'Мои ресурсы';
$string['commerce_product_cover_role_resources_help'] = 'Библиотека цифровых материалов. Формат: 3:4.';
$string['commerce_product_cover_role_checkout'] = 'Корзина и оплата';
$string['commerce_product_cover_role_checkout_help'] = 'Компактная миниатюра в заказе.';
$string['commerce_product_cover_role_email'] = 'Письма';
$string['commerce_product_cover_role_email_help'] = 'Изображение для транзакционных писем.';
$string['commerce_product_cover_role_social'] = 'Социальные сети';
$string['commerce_product_cover_role_social_help'] = 'Предпросмотр Open Graph. Размер: 1200 × 630.';

$string['commerce_guest_activation_protected_title'] = 'Ваши данные защищены';
$string['commerce_guest_activation_protected_text'] = 'Ваша покупка подтверждена. Теперь задайте пароль, чтобы защитить аккаунт CampusFR. После этого вы автоматически войдёте в систему и сразу получите доступ к курсам, ресурсам и покупкам.';
$string['commerce_guest_activation_show_password'] = 'Показать пароль';
$string['commerce_guest_activation_hide_password'] = 'Скрыть пароль';

$string['settings_trial_conversion_product_sku'] = 'Native-продукт для конверсии Trial';
$string['settings_trial_conversion_product_sku_desc'] = 'SKU Native Commerce-продукта, который в первую очередь предлагается пользователям пробного доступа. Оставьте пустым, чтобы использовать целевой план или витрину.';
$string['settings_trial_conversion_plan_id'] = 'Целевой Legacy-план для конверсии Trial';
$string['settings_trial_conversion_plan_id_desc'] = 'ID платного Legacy-плана, связанный Native-продукт которого должен предлагаться. Явно указанный SKU имеет приоритет.';
$string['commerce_trial_conversion_bridge_notice'] = 'Ваша Trial-скидка {$a->percent}% действует до {$a->deadline} и будет автоматически применена в корзине.';

$string['commerce_trial_conversion_label'] = 'Trial-предложение';
$string['commerce_trial_conversion_adjustment'] = 'Скидка за переход с Trial';
$string['commerce_cart_message_trial_conversion_not_eligible'] = 'Это Trial-предложение больше недоступно для данного продукта. Корзина не была изменена.';

$string['unlock_subscriber_button_single'] = 'Купить курс';
$string['unlock_grammar_button_single'] = 'Купить курс';
$string['unlock_full_button_single'] = 'Купить курс';
$string['commerce_trial_price_explanation'] = 'Пробный доступ позволяет сэкономить {$a->saving}. Предложение действует до {$a->deadline}.';
$string['unlock_course_title'] = 'Требуется доступ к курсу';
$string['unlock_course_text'] = 'Купите курс, чтобы открыть это задание.';
$string['unlock_course_button'] = 'Купить курс';
$string['commerce_trial_storefront_badge'] = 'Специальное Trial-предложение';
$string['commerce_trial_storefront_discount'] = 'Trial −{$a}%';
$string['commerce_trial_storefront_explanation'] = 'Эта скидка доступна участникам с активным пробным доступом.';
$string['commerce_trial_storefront_product_promotion'] = 'Скидка';
$string['commerce_trial_storefront_final_price'] = 'Ваша Trial-цена';
$string['commerce_trial_storefront_deadline'] = 'Trial-предложение действует до {$a}.';
$string['commerce_cart_list_total'] = 'Цена до скидок';
$string['commerce_cart_product_promotions_total'] = 'Скидки на товары';
$string['commerce_cart_total_reductions'] = 'Общая сумма скидок';
$string['commerce_trial_storefront_initial_price'] = 'Начальная цена';
$string['commerce_cart_badge_course'] = 'Курс';
$string['commerce_cart_badge_digital'] = 'Цифровой материал';
$string['commerce_cart_badge_bundle'] = 'Набор';
$string['commerce_cart_badge_trial'] = 'Пробное предложение';
$string['commerce_cart_badge_upgrade'] = 'Переход на полный тариф';
$string['commerce_cart_badge_product'] = 'Товар';
$string['commerce_purchase_pricing_section'] = 'Цены и скидки';
$string['commerce_purchase_native_payment_attempt'] = 'Native-попытка оплаты';
$string['commerce_pricing_initial_product'] = 'Начальная цена товара';
$string['commerce_pricing_owned_credit'] = 'Зачёт {$a}';
$string['commerce_pricing_upgrade_price'] = 'Цена перехода';
$string['commerce_pricing_final_price'] = 'Ваша итоговая цена';
$string['commerce_pricing_details'] = 'Детали цены';
$string['commerce_pricing_initial_promotion'] = 'Первоначальная скидка';
$string['commerce_pricing_upgrade_offer'] = 'Предложение перехода';
$string['commerce_pricing_you_save'] = 'Ваша выгода';
$string['commerce_invoice_owned_credit'] = 'Зачёт ранее купленного товара';
$string['commerce_invoice_other_discount'] = 'Другие скидки';
$string['commerce_invoice_item_paid_price'] = 'Оплаченная цена товара';
$string['commerce_storefront_hide_owned'] = 'Скрыть уже купленные товары';
$string['commerce_storefront_hide_owned_help'] = 'Отключите фильтр, чтобы увидеть весь каталог.';
$string['commerce_storefront_price_standard'] = 'Цена';
$string['commerce_storefront_price_promotional'] = 'Цена со скидкой';
$string['commerce_storefront_price_trial'] = 'Ваша пробная цена';
$string['commerce_storefront_price_upgrade'] = 'Цена Upgrade';
$string['commerce_storefront_price_discovery'] = 'Цена знакомства';
$string['commerce_storefront_upgrade_offer_badge'] = 'Специальное предложение Upgrade';
$string['commerce_storefront_upgrade_owned_explanation'] = 'У вас уже есть доступ к {$a}. Ранее оплаченная сумма вычтена из цены.';
$string['commerce_pricing_initial_promotion_percent'] = 'Первоначальная скидка −{$a}%';
$string['commerce_cart_trial_discount_total'] = 'Пробная скидка';
$string['commerce_cart_upgrade_credit_total'] = 'Зачёт Upgrade';
$string['commerce_checkout_print_summary'] = 'Распечатать сводку';
$string['commerce_cart_print_detailed'] = 'Подробная корзина для печати';
$string['commerce_cart_print_detailed_subtitle'] = 'Подробности товаров, скидок и зачётов до оплаты.';
$string['commerce_cart_print_generated'] = 'Документ создан {$a}';
$string['commerce_storefront_section_hero'] = 'Редакционный Hero';
$string['commerce_storefront_section_image_text'] = 'Изображение + текст';
$string['commerce_storefront_section_video'] = 'Видео';
$string['commerce_storefront_section_program'] = 'Программа';
$string['commerce_storefront_section_instructor'] = 'Преподаватель';
$string['commerce_storefront_section_testimonials'] = 'Отзывы';
$string['commerce_storefront_section_gallery'] = 'Галерея';
$string['commerce_storefront_section_id'] = 'Технический идентификатор';
$string['commerce_storefront_section_order'] = 'Порядок';
$string['commerce_storefront_section_style'] = 'Стиль';
$string['commerce_storefront_section_visible'] = 'Показывать раздел';
$string['commerce_storefront_section_style_default'] = 'Стандартный';
$string['commerce_storefront_section_style_soft'] = 'Мягкий';
$string['commerce_storefront_section_style_accent'] = 'Акцент CampusFR';
$string['commerce_storefront_section_style_contrast'] = 'Контрастный';
$string['commerce_storefront_section_style_boxed'] = 'В рамке';
$string['commerce_storefront_section_style_full_width'] = 'На всю ширину';
$string['commerce_product_visual_format_square'] = 'Квадратное изображение — 1:1';
$string['commerce_product_visual_format_square_help'] = 'Checkout, подтверждение, компактные миниатюры и CRM. Рекомендуемый размер: {$a}.';
$string['commerce_product_visual_format_landscape'] = 'Горизонтальное изображение — 4:3';
$string['commerce_product_visual_format_landscape_help'] = 'Магазин, рекомендации и карточки товаров. Рекомендуемый размер: {$a}.';
$string['commerce_product_visual_format_wide'] = 'Широкое изображение — 16:9';
$string['commerce_product_visual_format_wide_help'] = 'Hero, видео, соцсети и Open Graph. Рекомендуемый размер: {$a}.';
$string['commerce_product_visual_format_portrait'] = 'Вертикальное изображение — 4:5';
$string['commerce_product_visual_format_portrait_help'] = 'Корзина, цифровые ресурсы и вертикальные карточки. Рекомендуемый размер: {$a}.';
$string['commerce_product_visual_ratio_ok'] = 'Соотношение сторон соответствует формату {$a}.';
$string['commerce_product_visual_ratio_warning'] = 'Файл будет принят, но его пропорции отличаются от {$a}. При показе изображение будет обрезано.';
$string['commerce_storefront_seo_title'] = 'SEO и публикация в соцсетях';
$string['commerce_storefront_seo_help'] = 'Поля локализованы. Для соцсетей автоматически используется изображение 16:9.';
$string['commerce_storefront_seo_page_title'] = 'SEO-заголовок';
$string['commerce_storefront_seo_description'] = 'Meta description';
$string['commerce_storefront_seo_description_help'] = 'Рекомендуется около 150–160 символов. Текст очищается и ограничивается 320 символами.';
$string['commerce_storefront_view_my_products'] = 'Мои продукты';
$string['commerce_product_visual_status_ok'] = 'OK';
$string['commerce_product_visual_status_fallback'] = 'Fallback';
$string['commerce_product_visual_status_missing'] = 'Нет изображения';
$string['commerce_product_visual_preview_alt'] = 'Предпросмотр изображения {$a}';
$string['commerce_product_visual_fallback_source'] = 'Fallback: {$a}';
$string['commerce_product_visual_metadata_dimensions'] = 'Размеры';
$string['commerce_product_visual_metadata_ratio'] = 'Фактический / целевой формат';
$string['commerce_product_visual_metadata_weight'] = 'Вес файла';
$string['commerce_product_visual_metadata_file'] = 'Файл';
$string['commerce_product_visual_fallback_help'] = 'Для этого формата пока нет отдельного изображения. Предпросмотр показывает текущий fallback с обрезкой под целевой формат.';
$string['commerce_product_visual_missing_help'] = 'Нет ни отдельного изображения, ни fallback. Будет использован placeholder типа продукта.';
$string['commerce_product_visual_context_preview_title'] = 'Предпросмотр в интерфейсах Commerce';
$string['commerce_product_visual_context_preview_help'] = 'Макеты используют те же CSS-классы, что и реальные страницы. Отображается текущий master, fallback или placeholder.';
$string['commerce_product_visual_context_preview_badge'] = 'Реальный CSS';
$string['commerce_product_visual_context_preview_description'] = 'Пример отображения продукта в реальном контексте.';
$string['commerce_product_visual_context_boutique'] = 'Магазин';
$string['commerce_product_visual_context_storefront'] = 'Страница продукта';
$string['commerce_product_visual_context_checkout'] = 'Checkout';
$string['commerce_product_visual_context_resources'] = 'Мои ресурсы';
$string['commerce_product_visual_context_available'] = 'Доступно';
$string['commerce_product_visual_save_format'] = 'Сохранить этот формат';
$string['commerce_product_visual_no_file_selected'] = 'Выберите изображение перед сохранением этого формата.';
$string['commerce_storefront_rich_text_editor_help'] = 'TinyMCE активирован: можно добавлять изображения, видео, файлы, ссылки и H5P. Файлы сохраняются в Moodle вместе с этим блоком.';
$string['commerce_storefront_section_h5p'] = 'H5P';
$string['commerce_storefront_image_settings'] = 'Изображение + текст';
$string['commerce_storefront_image_upload'] = 'Изображение Moodle';
$string['commerce_storefront_image_position'] = 'Положение изображения';
$string['commerce_storefront_column_ratio'] = 'Пропорции колонок';
$string['commerce_storefront_video_settings'] = 'Видео';
$string['commerce_storefront_video_source'] = 'Источник';
$string['commerce_storefront_video_upload'] = 'Файл Moodle';
$string['commerce_storefront_video_file'] = 'Видеофайл';
$string['commerce_storefront_video_ratio'] = 'Формат';
$string['commerce_storefront_video_poster'] = 'Постер';
$string['commerce_storefront_h5p_settings'] = 'Контент H5P';
$string['commerce_storefront_h5p_content'] = 'Контент из банка Moodle (необязательно)';
$string['commerce_storefront_h5p_height'] = 'Минимальная высота';
$string['commerce_storefront_h5p_help'] = 'Приоритет: загруженный файл .h5p, затем выбор из банка Moodle, затем вспомогательный URL.';
$string['commerce_storefront_h5p_none'] = 'H5P не выбран';
$string['commerce_storefront_h5p_missing'] = 'Корректный H5P-контент не настроен.';
$string['commerce_storefront_builder_sections'] = 'Структура страницы';
$string['commerce_storefront_builder_sections_help'] = 'Добавляйте и упорядочивайте блоки. Блок Commerce остаётся защищённым.';
$string['commerce_storefront_builder_add'] = 'Тип раздела';
$string['commerce_storefront_builder_add_button'] = 'Добавить раздел';
$string['commerce_storefront_builder_untitled'] = 'Без названия';
$string['commerce_storefront_builder_ready'] = 'Готово';
$string['commerce_storefront_builder_incomplete'] = 'Не заполнено';
$string['commerce_storefront_builder_empty'] = 'Редакционных блоков пока нет. Добавьте первый блок.';
$string['commerce_storefront_builder_action_first'] = 'В начало';
$string['commerce_storefront_builder_action_up'] = 'Выше';
$string['commerce_storefront_builder_action_down'] = 'Ниже';
$string['commerce_storefront_builder_action_last'] = 'В конец';
$string['commerce_storefront_builder_action_toggle'] = 'Показать или скрыть';
$string['commerce_storefront_builder_action_duplicate'] = 'Дублировать';
$string['commerce_storefront_builder_action_delete'] = 'Удалить';
$string['commerce_storefront_builder_drag_help'] = 'Перетаскивайте блоки за ручку. С клавиатуры используйте Alt + стрелка вверх или вниз, затем сохраните страницу.';
$string['commerce_storefront_builder_drag_handle'] = 'Переместить блок {$a}';
$string['commerce_storefront_repository_picker_help'] = 'Кнопки Image, Media, Link и H5P открывают файловый менеджер Moodle для загрузки с компьютера и выбора из репозиториев.';
$string['commerce_storefront_h5p_upload'] = 'Загрузить файл H5P';
$string['commerce_storefront_h5p_bank_empty'] = 'В банке контента Moodle пока нет H5P. Можно напрямую загрузить файл .h5p выше.';
$string['commerce_storefront_h5p_open_bank'] = 'Открыть банк контента';

// Visual Page Composer.
$string['commerce_storefront_composer_layout'] = 'Визуальная компоновка';
$string['commerce_storefront_composer_layout_help'] = 'Блоки с одинаковым идентификатором строки можно распределять по нескольким колонкам. На мобильных устройствах колонки автоматически располагаются вертикально.';
$string['commerce_storefront_composer_columns'] = 'Колонки';
$string['commerce_storefront_composer_column'] = 'Позиция в строке';
$string['commerce_storefront_composer_ratio'] = 'Соотношение колонок';
$string['commerce_storefront_composer_row'] = 'Идентификатор строки';
$string['commerce_storefront_composer_width'] = 'Ширина';
$string['commerce_storefront_composer_width_contained'] = 'Ограниченная';
$string['commerce_storefront_composer_width_wide'] = 'Широкая';
$string['commerce_storefront_composer_width_full'] = 'На всю ширину';
$string['commerce_storefront_composer_background'] = 'Фон';
$string['commerce_storefront_composer_background_default'] = 'По умолчанию';
$string['commerce_storefront_composer_background_soft'] = 'Мягкий';
$string['commerce_storefront_composer_background_accent'] = 'Акцентный';
$string['commerce_storefront_composer_background_contrast'] = 'Контрастный';
$string['commerce_storefront_composer_background_transparent'] = 'Прозрачный';
$string['commerce_storefront_composer_spacing'] = 'Вертикальные отступы';
$string['commerce_storefront_composer_spacing_none'] = 'Нет';
$string['commerce_storefront_composer_spacing_small'] = 'Маленькие';
$string['commerce_storefront_composer_spacing_medium'] = 'Средние';
$string['commerce_storefront_composer_spacing_large'] = 'Большие';
$string['commerce_storefront_composer_alignment'] = 'Вертикальное выравнивание';
$string['commerce_storefront_composer_alignment_start'] = 'Сверху';
$string['commerce_storefront_composer_alignment_center'] = 'По центру';
$string['commerce_storefront_composer_alignment_end'] = 'Снизу';
$string['commerce_storefront_composer_alignment_stretch'] = 'Растянуть';
$string['commerce_storefront_responsive_preview'] = 'Адаптивный предпросмотр';
$string['commerce_storefront_responsive_preview_help'] = 'Проверьте ширину отображения конструктора, не покидая страницу.';
$string['commerce_storefront_preview_desktop'] = 'Компьютер';
$string['commerce_storefront_preview_tablet'] = 'Планшет';
$string['commerce_storefront_preview_mobile'] = 'Телефон';
$string['commerce_storefront_composer_templates'] = 'Шаблоны компоновки';
$string['commerce_storefront_composer_templates_help'] = 'Добавьте готовую структуру для настройки. Существующие разделы не заменяются.';
$string['commerce_storefront_composer_template'] = 'Шаблон для вставки';
$string['commerce_storefront_composer_template_insert'] = 'Вставить шаблон';
$string['commerce_storefront_composer_template_sales'] = 'Страница продаж';
$string['commerce_storefront_composer_template_course'] = 'Курс';
$string['commerce_storefront_composer_template_digital'] = 'Цифровой продукт';
$string['commerce_storefront_composer_template_bundle'] = 'Комплект';

$string['commerce_storefront_section_timeline'] = 'Хронология';
$string['commerce_storefront_section_comparison'] = 'Сравнение';
$string['commerce_storefront_section_accordion'] = 'Аккордеон';
$string['commerce_storefront_section_style_glass'] = 'Матовое стекло';
$string['commerce_storefront_section_style_gradient'] = 'Премиум-градиент';
$string['commerce_storefront_section_style_minimal'] = 'Минималистичный';
$string['commerce_storefront_premium_presentation'] = 'Премиум-оформление';
$string['commerce_storefront_premium_presentation_default'] = 'Стандартное';
$string['commerce_storefront_premium_presentation_split'] = 'Разделённая композиция';
$string['commerce_storefront_premium_presentation_overlay'] = 'Иммерсивное наложение';
$string['commerce_storefront_premium_presentation_cards'] = 'Премиум-карточки';
$string['commerce_storefront_premium_presentation_carousel'] = 'Горизонтальная карусель';
$string['commerce_storefront_premium_presentation_masonry'] = 'Галерея masonry';
$string['commerce_storefront_premium_presentation_timeline'] = 'Хронология';
$string['commerce_storefront_premium_presentation_comparison'] = 'Сравнение';
$string['commerce_storefront_premium_presentation_premium'] = 'Премиум CampusFR';
$string['commerce_storefront_premium_presentation_statement'] = 'Акцент / переход';
$string['commerce_storefront_premium_presentation_feature'] = 'Презентация продукта';
$string['commerce_storefront_premium_presentation_commerce'] = 'Премиум-покупка';
$string['commerce_storefront_premium_animation'] = 'Анимация появления';
$string['commerce_storefront_premium_animation_none'] = 'Без анимации';
$string['commerce_storefront_premium_animation_fade'] = 'Плавное появление';
$string['commerce_storefront_premium_animation_slide_up'] = 'Появление снизу';
$string['commerce_storefront_premium_animation_zoom'] = 'Лёгкое увеличение';

$string['commerce_storefront_shell_title'] = 'Глобальный макет Storefront';
$string['commerce_storefront_commerce_position'] = 'Положение коммерческого блока';
$string['commerce_storefront_commerce_position_hero'] = 'Внутри Hero';
$string['commerce_storefront_commerce_position_below'] = 'Под Hero';
$string['commerce_storefront_commerce_position_sidebar'] = 'Закреплённая боковая панель';
$string['commerce_storefront_commerce_position_intro'] = 'После введения';
$string['commerce_storefront_commerce_position_bottom'] = 'Внизу страницы';
$string['commerce_storefront_shell_mode'] = 'Оболочка Moodle';
$string['commerce_storefront_shell_standard'] = 'Стандартный Edly';
$string['commerce_storefront_shell_fullwidth'] = 'Edly на всю ширину';
$string['commerce_storefront_shell_landing'] = 'Лендинг';
$string['commerce_storefront_shell_immersive'] = 'Иммерсивный';
$string['commerce_storefront_layout_visibility'] = 'Видимость оболочки Edly';
$string['commerce_storefront_show_header'] = 'Показывать шапку Edly';
$string['commerce_storefront_show_footer'] = 'Показывать подвал Edly';
$string['commerce_storefront_section_save'] = 'Сохранить этот блок';
$string['commerce_storefront_section_saved'] = 'Блок сохранён';
$string['commerce_storefront_section_save_error'] = 'Не удалось сохранить этот блок.';

$string['commerce_storefront_reset_title'] = 'Сброс страницы магазина';
$string['commerce_storefront_reset_help'] = 'Удаляет всю конфигурацию Storefront и медиафайлы, связанные с её блоками. Товар, цены и права доступа не изменяются.';
$string['commerce_storefront_reset_button'] = 'Удалить конфигурацию Storefront';
$string['commerce_storefront_reset_confirm_title'] = 'Удалить всю конфигурацию Storefront?';
$string['commerce_storefront_reset_confirm_help'] = 'Будут навсегда удалены блоки, настройки макета, SEO и файлы Storefront этого товара. Действие нельзя отменить.';
$string['commerce_storefront_reset_confirm_button'] = 'Да, удалить Storefront';
$string['commerce_storefront_reset_success'] = 'Конфигурация Storefront и её файлы удалены.';
$string['commerce_storefront_package_title'] = 'Перенос страницы магазина';
$string['commerce_storefront_package_help'] = 'Экспортируйте или импортируйте полную конфигурацию страницы магазина вместе с медиафайлами в файле .cfrproduct.';
$string['commerce_storefront_package_export'] = 'Экспортировать конфигурацию';
$string['commerce_storefront_package_import'] = 'Импортировать конфигурацию';
$string['commerce_storefront_package_file'] = 'Файл .cfrproduct';
$string['commerce_storefront_package_import_success'] = 'Конфигурация страницы магазина импортирована.';
$string['commerce_storefront_package_invalid'] = 'Файл Storefront недействителен или несовместим.';
$string['commerce_storefront_global_zones_title'] = 'Общая организация страницы';
$string['commerce_storefront_global_zones_help'] = 'Перетаскивайте зоны, чтобы расположить коммерческий блок в структуре страницы. С клавиатуры также работают Alt + стрелка вверх/вниз.';
$string['commerce_storefront_global_zone_hero'] = 'Hero';
$string['commerce_storefront_global_zone_commerce'] = 'Коммерческий блок';
$string['commerce_storefront_global_zone_content'] = 'Редакционный контент';
$string['commerce_storefront_global_zone_recommendations'] = 'Рекомендации';
$string['commerce_storefront_media_audit_title'] = 'Аудит медиа Storefront';

$string['commerce_storefront_image_position_left'] = 'Слева';
$string['commerce_storefront_image_position_right'] = 'Справа';

// J9A — Публичные номера и навигация продаж CRM.
$string['commerce_purchase_public_reference'] = 'Номер заказа';
$string['commerce_purchase_internal_reference'] = 'Внутренняя ссылка';
$string['commerce_purchase_internal_reference_short'] = 'Внутренняя';
$string['commerce_purchase_open_order_details'] = 'Открыть Order Details';

$string['commerce_purchase_download_invoice'] = 'Скачать счёт';
$string['commerce_purchase_open_mail_journal'] = 'Открыть журнал писем';
$string['commerce_purchase_resend_receipt'] = 'Повторно отправить квитанцию';
$string['commerce_purchase_resend_receipt_confirm'] = 'Новая квитанция будет отправлена на email клиента и записана как ручная отправка. Продолжить?';
$string['commerce_purchase_receipt_resent'] = 'Квитанция успешно отправлена повторно.';
$string['commerce_purchase_receipt_queued'] = 'Квитанция добавлена в очередь и будет отправлена повторно автоматически.';
$string['commerce_purchase_receipt_resend_failed'] = 'Не удалось повторно отправить квитанцию. Проверьте журнал писем.';
$string['commerce_purchase_resend_access'] = 'Повторно отправить доступы';
$string['commerce_purchase_resend_access_confirm'] = 'Письмо с доступами будет заново сформировано транзакционным почтовым движком Commerce и отправлено на текущий адрес {$a}. Продолжить?';
$string['commerce_purchase_access_resent_to'] = 'Письмо с доступами успешно повторно отправлено на {$a}.';
$string['commerce_purchase_access_queued_to'] = 'Письмо с доступами для {$a} добавлено в очередь и будет отправлено повторно автоматически.';
$string['commerce_purchase_access_resend_failed'] = 'Не удалось повторно отправить письмо с доступами. Проверьте журнал писем.';
$string['commerce_purchase_receipt_resent_to'] = 'Квитанция успешно повторно отправлена на {$a}.';
$string['commerce_purchase_receipt_queued_to'] = 'Квитанция для {$a} добавлена в очередь и будет отправлена повторно автоматически.';
$string['commerce_purchase_historical_email'] = 'Email на момент покупки';

// J10A — Студенческий хаб «Мой Campus».
$string['commerce_customer_hub_title'] = 'Мой Campus';
$string['commerce_customer_hub_eyebrow'] = 'Твоё личное пространство';
$string['commerce_customer_hub_welcome'] = 'Привет, {$a}!';
$string['commerce_customer_hub_intro'] = 'Здесь собраны твои курсы, ресурсы, покупки и прогресс CampusFR.';
$string['commerce_customer_hub_shortcuts'] = 'Быстрые ссылки Мой Campus';
$string['commerce_customer_hub_courses'] = 'Мои курсы';
$string['commerce_customer_hub_resources'] = 'Мои ресурсы';
$string['commerce_customer_hub_purchases'] = 'Мои покупки';
$string['commerce_customer_hub_profile'] = 'Мой профиль';
$string['commerce_customer_hub_profile_help'] = 'Данные и настройки';
$string['commerce_customer_hub_available'] = 'доступно';
$string['commerce_customer_hub_orders'] = 'заказ(ов)';
$string['commerce_customer_hub_continue'] = 'Продолжить обучение';
$string['commerce_customer_hub_view_all'] = 'Посмотреть всё';
$string['commerce_customer_hub_no_courses'] = 'В твоём пространстве пока нет доступных курсов.';
$string['commerce_customer_hub_discover'] = 'Перейти в магазин';
$string['commerce_customer_hub_xp_title'] = 'Мой прогресс';
$string['commerce_customer_hub_level'] = 'Уровень';
$string['commerce_customer_hub_total_xp'] = 'Всего XP';
$string['commerce_customer_hub_xp_30d'] = 'За 30 дней';
$string['commerce_customer_hub_xp_ranking'] = 'Место в рейтинге';
$string['commerce_customer_hub_last_activity'] = 'Последняя активность';
$string['commerce_customer_hub_xp_no_activity'] = 'Недавней активности нет';
$string['commerce_customer_hub_xp_unavailable'] = 'Прогресс LevelXP появится здесь, когда станет доступен.';

// J10A.1 — Navigation du parcours étudiant.
$string['commerce_customer_hub_shop'] = 'Магазин';
$string['commerce_customer_hub_shop_help'] = 'Курсы и материалы CampusFR';
$string['commerce_i2_my_campus'] = 'Перейти в «Мой Campus»';

$string['commerce_routes_product_title'] = 'Публичный URL продукта';
$string['commerce_routes_product_help'] = 'Укажите запоминающийся адрес для каждого языка. Оставьте пустым, чтобы сохранить технический URL.';
$string['commerce_routes_slug_fr'] = 'Французский slug';
$string['commerce_routes_slug_en'] = 'Английский slug';
$string['commerce_routes_slug_ru'] = 'Русский slug';
$string['commerce_route_not_found'] = 'Страница CampusFR не найдена.';

$string['commerce_storefront_filters_toggle'] = 'Поиск и фильтры';

$string['commerce_guest_activation_security_match'] = 'Оба пароля совпадают';


// J12E — Guest Checkout security and account finalisation.
$string['commerce_guest_checkout_other_email'] = 'Использовать другой адрес электронной почты';
$string['commerce_guest_checkout_email_valid'] = 'Адрес электронной почты указан верно';
$string['commerce_guest_checkout_email_invalid_live'] = 'Введите корректный адрес электронной почты.';
$string['commerce_guest_activation_modal_title'] = 'Завершите создание аккаунта, чтобы открыть курсы';
$string['commerce_guest_activation_modal_message'] = 'Покупка подтверждена. Создайте пароль, чтобы открыть курсы и не попадать на страницу входа, которой вы пока не можете воспользоваться.';
$string['commerce_guest_activation_modal_courses'] = 'Доступ к вашим курсам';
$string['commerce_guest_activation_modal_resources'] = 'Ваши материалы и загрузки';
$string['commerce_guest_activation_modal_orders'] = 'Ваши покупки и будущие заказы';
$string['commerce_guest_activation_modal_primary'] = 'Создать аккаунт';
$string['commerce_guest_activation_modal_later'] = 'Позже';
$string['commerce_guest_activation_ready_confirmation'] = 'Ваш аккаунт CampusFR готов.';

// J12H — Support experience and CRM Inbox.
$string['commerce_support_page_title_generic'] = 'Поддержка CampusFR';
$string['commerce_support_default_subject_generic'] = 'Запрос в поддержку CampusFR';
$string['commerce_support_back_to_campus'] = 'Вернуться в Мой Campus';
$string['commerce_support_gustave_alt'] = 'Гюстав, консультант службы поддержки CampusFR';
$string['commerce_support_visual_title'] = 'Нужна помощь?';
$string['commerce_support_visual_text'] = 'Наша команда готова вам помочь.';
$string['commerce_support_confirmation_title'] = 'Запрос успешно отправлен!';
$string['commerce_support_confirmation_intro'] = 'Мы получили ваш запрос. Команда CampusFR ответит вам в ближайшее время.';
$string['commerce_support_reference'] = 'Номер запроса';
$string['commerce_support_return_to_campus'] = 'Вернуться в Мой Campus';
$string['commerce_support_mail_technical_heading'] = 'Техническая информация';
$string['commerce_support_mail_message_heading'] = 'Сообщение клиента';
$string['commerce_support_category_account'] = 'Мой аккаунт';
$string['commerce_support_category_technical'] = 'Техническая проблема';
$string['commerce_support_category_course_question'] = 'Вопрос по курсу';
$string['commerce_support_status_paid'] = 'Оплачено';
$string['commerce_support_status_completed'] = 'Завершено';
$string['commerce_support_status_pending'] = 'Ожидает';
$string['commerce_support_status_failed'] = 'Ошибка';
$string['commerce_support_status_cancelled'] = 'Отменено';
$string['commerce_support_status_refunded'] = 'Возвращено';
$string['commerce_support_status_partial'] = 'Частично';
$string['commerce_support_status_processing'] = 'В процессе';
$string['commerce_support_status_succeeded'] = 'Успешно';
$string['commerce_customer_hub_view_profile'] = 'Открыть мой профиль';
$string['commerce_customer_hub_support'] = 'Поддержка';
$string['commerce_customer_hub_support_help_short'] = 'Нужна помощь?';
$string['commerce_storefront_currency_displayed'] = 'Валюта';
$string['crm_inbox_direction_incoming'] = 'Получено';
$string['crm_inbox_direction_outgoing'] = 'Отправлено';

// Витрина Commerce.
$string['commerce_showroom_not_found'] = 'Эта презентационная страница не найдена.';
$string['commerce_showroom_third_group_verbs_title'] = 'Глаголы 3-й группы — наконец-то без страха';
$string['commerce_showroom_third_group_verbs_description'] = 'Скоро на CampusFR: интерактивный тренажёр по глаголам третьей группы, PDF-материал и полный комплект.';
$string['commerce_showroom_eyebrow'] = 'Новинка CampusFR';
$string['commerce_showroom_foundation_note'] = 'Техническая основа J13B — финальный дизайн и маркетинговый контент появятся в J13D.';
$string['commerce_showroom_offers_heading'] = 'Выберите свой формат';
$string['commerce_showroom_offer_course'] = 'Интерактивный курс';
$string['commerce_showroom_offer_pdf'] = 'PDF-гид';
$string['commerce_showroom_offer_bundle'] = 'Комплект Курс + PDF';
$string['commerce_showroom_offer_pending'] = 'Продукт будет подключён к витрине сразу после его создания в каталоге Commerce.';
$string['commerce_showroom_price_pending'] = 'Скоро в продаже';
$string['commerce_showroom_buy_now'] = 'Купить сейчас';
$string['commerce_showroom_view_details'] = 'Подробнее';
$string['commerce_showroom_back_to_shop'] = 'Перейти в магазин';

$string['commerce_showroom_owned_access'] = 'Открыть продукт';

// J13D — Шоурум глаголов 3-й группы.
$string['commerce_showroom_hero_cta'] = 'Выбрать своё восхождение';
$string['commerce_showroom_hero_secondary_cta'] = 'Посмотреть презентацию';
$string['commerce_showroom_hero_proof'] = 'Мгновенный доступ после оплаты · тренировка до автоматизма';
$string['commerce_showroom_problem_eyebrow'] = 'Почему это кажется таким сложным?';
$string['commerce_showroom_problem_title'] = 'Глаголы 3-й группы сложны совсем не по тем причинам';
$string['commerce_showroom_problem_description'] = 'Проблема не в памяти: отдельные списки, таблицы без контекста и недостаток повторения не дают форме закрепиться.';
$string['commerce_showroom_method_title'] = 'Учить глаголы нужно так же, как учиться водить';
$string['commerce_showroom_method_description'] = 'Сначала понять, затем практиковаться и повторять, пока правильная форма не станет рефлексом.';
$string['commerce_showroom_video_title'] = 'Что ждёт вас на пути к вершине глаголов 3-й группы?';
$string['commerce_showroom_video_description'] = 'Посмотрите короткую презентацию тренажёра и узнайте, как обычное изучение глаголов превращается в настоящее восхождение к вершине Монблана.';
$string['commerce_showroom_video_placeholder'] = 'Презентационное видео скоро появится';
$string['commerce_showroom_content_eyebrow'] = 'Настоящий тренажёр, а не очередной список';
$string['commerce_showroom_content_title'] = 'Все важные глаголы в одном приключении';
$string['commerce_showroom_content_description'] = '30 этапов от базового лагеря до вершины, а Гюстав будет вашим проводником.';
$string['commerce_showroom_journey_title'] = 'Как проходит каждый этап?';
$string['commerce_showroom_journey_description'] = 'Каждая группа глаголов проходит один и тот же понятный и эффективный цикл.';
$string['commerce_showroom_exercises_title'] = 'Более 10 типов заданий';
$string['commerce_showroom_exercises_description'] =
    'Каждое упражнение по-своему тренирует память, поэтому каждый глагол вы тренируете разными способами.';
$string['commerce_showroom_offers_description'] = 'Выберите подходящий формат. Полный комплект объединяет интерактивный тренажёр и PDF-справочник.';
$string['commerce_showroom_offer_featured'] = 'Полное предложение';
$string['commerce_showroom_bonus_heading'] = 'Дополните набор для повторения';
$string['commerce_showroom_bonus_text'] = 'Используйте также карточки CampusFR по глаголам 3-й группы для быстрых повторений.';
$string['commerce_showroom_bonus_cta'] = 'Другие материалы';
$string['commerce_showroom_faq_heading'] = 'Часто задаваемые вопросы';
$string['commerce_showroom_final_eyebrow'] = 'Вершина ждёт';
$string['commerce_showroom_final_title'] = 'Готовы довести глаголы 3-й группы до автоматизма?';
$string['commerce_showroom_final_text'] = 'Выберите формат, начните первый этап и поднимитесь на вершину вместе с Гюставом.';
$string['commerce_showroom_final_cta'] = 'Начать сейчас';
$string['commerce_showroom_problem_1_title'] = 'Нет одного правила';
$string['commerce_showroom_problem_1_text'] = 'Формы меняются от глагола к глаголу, а модели накладываются друг на друга.';
$string['commerce_showroom_problem_2_title'] = 'Нужная форма исчезает';
$string['commerce_showroom_problem_2_text'] = 'Вы знаете глагол, но спряжение не приходит в нужный момент.';
$string['commerce_showroom_problem_3_title'] = 'Слишком много таблиц';
$string['commerce_showroom_problem_3_text'] = 'Прочитать спряжение недостаточно, чтобы начать использовать его в речи.';
$string['commerce_showroom_problem_4_title'] = 'Недостаточно практики';
$string['commerce_showroom_problem_4_text'] = 'Без регулярного повторения выученные формы быстро стираются.';
$string['commerce_showroom_method_1_title'] = 'Понять модель';
$string['commerce_showroom_method_1_text'] = 'Увидеть полезные формы и закономерности без лишней теории.';
$string['commerce_showroom_method_2_title'] = 'Идти по этапам';
$string['commerce_showroom_method_2_text'] = 'На каждом восхождении — шесть глаголов и понятная последовательность.';
$string['commerce_showroom_method_3_title'] = 'Создать автоматизм';
$string['commerce_showroom_method_3_text'] = 'Более десяти форматов возвращают одни и те же формы под разными углами.';
$string['commerce_showroom_stat_1_title'] = '30 этапов';
$string['commerce_showroom_stat_1_text'] = 'Понятное и мотивирующее продвижение.';
$string['commerce_showroom_stat_2_title'] = '180 глаголов';
$string['commerce_showroom_stat_2_text'] = 'Частотные глаголы и их семьи.';
$string['commerce_showroom_stat_3_title'] = 'Аудио';
$string['commerce_showroom_stat_3_text'] = 'Сначала услышать, затем воспроизвести.';
$string['commerce_showroom_stat_4_title'] = 'Умное повторение';
$string['commerce_showroom_stat_4_text'] = 'Нужные формы возвращаются вовремя.';
$string['commerce_showroom_stat_5_title'] = 'Тесты и награды';
$string['commerce_showroom_stat_5_text'] = 'Проверяйте каждый этап и следите за прогрессом.';
$string['commerce_showroom_stat_6_title'] = 'Альпийский мир';
$string['commerce_showroom_stat_6_text'] = 'Визуальное приключение вокруг Монблана.';
$string['commerce_showroom_journey_1_title'] = 'Понять значение';
$string['commerce_showroom_journey_1_text'] = 'Быстро разобраться в значении и употреблении.';
$string['commerce_showroom_journey_2_title'] = 'Услышать формы';
$string['commerce_showroom_journey_2_text'] = 'Связать спряжение с правильным произношением.';
$string['commerce_showroom_journey_3_title'] = 'Собрать';
$string['commerce_showroom_journey_3_text'] = 'Составить формы и заметить закономерности.';
$string['commerce_showroom_journey_4_title'] = 'Произвести';
$string['commerce_showroom_journey_4_text'] = 'Вставить, написать и выбрать форму в контексте.';
$string['commerce_showroom_journey_5_title'] = 'Проверить';
$string['commerce_showroom_journey_5_text'] = 'Пройти финальный квиз и укрепить слабые места.';
$string['commerce_showroom_journey_6_title'] = 'Продолжить подъём';
$string['commerce_showroom_journey_6_text'] = 'Открыть следующий этап и идти к вершине.';
$string['commerce_showroom_exercise_1_title'] = 'Перетаскивание';
$string['commerce_showroom_exercise_1_text'] = 'Соединить местоимения и формы.';
$string['commerce_showroom_exercise_2_title'] = 'Выбор ответа';
$string['commerce_showroom_exercise_2_text'] = 'Быстро найти правильную форму.';
$string['commerce_showroom_exercise_3_title'] = 'Верно или неверно';
$string['commerce_showroom_exercise_3_text'] = 'Распознать ошибочную форму.';
$string['commerce_showroom_exercise_4_title'] = 'Найти форму';
$string['commerce_showroom_exercise_4_text'] = 'Увидеть нужную форму в предложении.';
$string['commerce_showroom_exercise_5_title'] = 'Собрать слово';
$string['commerce_showroom_exercise_5_text'] = 'Восстановить глагол по буквам.';
$string['commerce_showroom_exercise_6_title'] = 'Заполнить пропуск';
$string['commerce_showroom_exercise_6_text'] = 'Вписать форму в контексте.';
$string['commerce_showroom_exercise_7_title'] = 'Аудиодиктант';
$string['commerce_showroom_exercise_7_text'] = 'Записать услышанную форму.';
$string['commerce_showroom_exercise_8_title'] = 'Перевод';
$string['commerce_showroom_exercise_8_text'] = 'Вспомнить французский инфинитив.';
$string['commerce_showroom_exercise_9_title'] = 'Быстрый ответ';
$string['commerce_showroom_exercise_9_text'] = 'Вызвать форму без паузы.';
$string['commerce_showroom_exercise_10_title'] = 'Финальный квиз';
$string['commerce_showroom_exercise_10_text'] = 'Подтвердить этап перед продолжением.';
$string['commerce_showroom_offer_course_feature_1'] = '30 интерактивных этапов';
$string['commerce_showroom_offer_course_feature_2'] = '180 глаголов 3-й группы';
$string['commerce_showroom_offer_course_feature_3'] = 'Аудио, квизы и повторение';
$string['commerce_showroom_offer_course_feature_4'] = 'Прогресс и награды';
$string['commerce_showroom_offer_pdf_feature_1'] = 'Понятный структурированный гид';
$string['commerce_showroom_offer_pdf_feature_2'] = 'Таблицы и семьи глаголов';
$string['commerce_showroom_offer_pdf_feature_3'] = 'Повторение офлайн';
$string['commerce_showroom_offer_pdf_feature_4'] = 'Мгновенное скачивание';
$string['commerce_showroom_offer_bundle_feature_1'] = 'Полный интерактивный курс';
$string['commerce_showroom_offer_bundle_feature_2'] = 'PDF-гид в комплекте';
$string['commerce_showroom_offer_bundle_feature_3'] = 'Самая выгодная цена';
$string['commerce_showroom_offer_bundle_feature_4'] = 'Всё для практики и повторения';
$string['commerce_showroom_faq_1_q'] = 'Для какого уровня подходит тренажёр?';
$string['commerce_showroom_faq_1_a'] = 'Тренажёр подходит для любого уровня подготовки. Если вы только начинаете изучать французский, он поможет заложить прочную основу. Если уже владеете языком, вы сможете систематизировать знания и уверенно освоить глаголы, встречающиеся на уровнях B1 и выше.';
$string['commerce_showroom_faq_2_q'] = 'Сколько времени потребуется?';
$string['commerce_showroom_faq_2_a'] = 'У каждого своё восхождение. Всё зависит только от вашего темпа. Вы можете пройти тренировку за один раз или разделить её на несколько занятий. Не нужно спешить или подстраиваться под жёсткий график. Главное — заниматься регулярно. Именно постоянная практика помогает довести глаголы до автоматизма.';
$string['commerce_showroom_faq_3_q'] = 'Можно ли делать упражнения повторно?';
$string['commerce_showroom_faq_3_a'] = 'Да. Все упражнения можно проходить повторно столько раз, сколько потребуется. Именно повторения помогают довести формы глаголов до автоматизма.';
$string['commerce_showroom_faq_4_q'] = 'Работает ли тренажёр на телефоне?';
$string['commerce_showroom_faq_4_a'] = 'Да. Тренажёр полностью адаптирован для компьютеров, планшетов и смартфонов. Вы сможете заниматься дома, в дороге или в любой свободный момент.';
$string['commerce_showroom_faq_5_q'] = 'Что входит в электронные карточки?';
$string['commerce_showroom_faq_5_a'] = '178 карточек со всеми глаголами 3-й группы современного французского языка. На каждой карточке есть перевод, все формы настоящего времени, participe passé, основа futur simple и озвучка носителем языка. Карточки можно использовать онлайн или распечатать.';
$string['commerce_showroom_faq_6_q'] = 'Чем отличается тренажёр от полного комплекта?';
$string['commerce_showroom_faq_6_a'] = 'Тренажёр помогает довести глаголы до автоматизма благодаря интерактивной практике. Карточки дополняют обучение: они позволяют быстро найти нужное спряжение, держать все формы под рукой и заниматься даже без доступа к интернету. Полный комплект объединяет оба формата, чтобы вы могли не только тренироваться, но и удобно повторять материал в любое время.';
$string['commerce_showroom_faq_7_q'] = 'Как получить доступ после покупки?';
$string['commerce_showroom_faq_7_a'] = 'Сразу после оплаты на вашу электронную почту придёт письмо со всей необходимой информацией для доступа. Если у вас уже есть аккаунт на платформе CampusFR, вы также получите письмо с подтверждением покупки, а тренажёр автоматически появится в разделе «Мои курсы». Никаких дополнительных действий не потребуется — можно сразу приступать к занятиям.';
$string['commerce_storefront_showroom_media_title'] = 'Шоурум';
$string['commerce_storefront_showroom_media_help'] = 'Свяжите товар со страницей шоурума и добавьте отдельное рекламное изображение. Оно будет приоритетным в карточке предложения.';
$string['commerce_storefront_showroom_key'] = 'Связанный шоурум';
$string['commerce_storefront_showroom_image'] = 'Изображение для шоурума';
$string['commerce_storefront_showroom_alt'] = 'Альтернативный текст изображения';
$string['commerce_storefront_showroom_link'] = 'Посмотреть полную презентацию';

// J13F1 — Премиальный Hero шоурума глаголов третьей группы.
$string['commerce_showroom_hero_expedition'] = 'Экспедиция на Монблан';
$string['commerce_showroom_hero_stage'] = 'Этап 0 / 30';
$string['commerce_showroom_hero_stat_verbs'] = 'глаголов до автоматизма';
$string['commerce_showroom_hero_stat_stages'] = 'последовательных этапов';
$string['commerce_showroom_hero_stat_exercises'] = 'упражнений и испытаний';
$string['commerce_showroom_hero_stat_lifetime_value'] = 'Сразу';
$string['commerce_showroom_hero_stat_lifetime'] = 'после покупки';
$string['commerce_showroom_hero_summary'] = 'Более 4 000 упражнений, квизы, аудио, видео, награды и полное восхождение к вершине Монблана.';
$string['commerce_showroom_hero_cta_start'] = 'Начать восхождение';
$string['commerce_showroom_hero_cta_resume'] = 'Продолжить восхождение';
$string['commerce_showroom_hero_cta_complete_course'] = 'Добавить курс';
$string['commerce_showroom_hero_cta_complete_pdf'] = 'Добавить PDF к снаряжению';

// J13F2 — интерактивное восхождение и обзор упражнений.
$string['commerce_showroom_ascent_eyebrow'] = 'Восхождение из 30 этапов';
$string['commerce_showroom_ascent_title'] = 'От долины до вершины: каждый этап приближает вас к автоматизму';
$string['commerce_showroom_ascent_description'] = 'Маршрут построен по нарастающей: сначала самые нужные глаголы, затем всё более сложные и редкие формы.';
$string['commerce_showroom_ascent_aria'] = 'Маршрут тренажёра из 30 этапов к вершине Монблана';
$string['commerce_showroom_ascent_stages'] = 'Этапы {$a}';
$string['commerce_showroom_ascent_1_title'] = 'Базовый лагерь';
$string['commerce_showroom_ascent_1_text'] = 'Самые необходимые глаголы и первые рефлексы. Вы осваиваете методику и обретаете уверенность.';
$string['commerce_showroom_ascent_2_title'] = 'Альпийский лес';
$string['commerce_showroom_ascent_2_text'] = 'Группы форм становятся узнаваемыми, а регулярное повторение начинает работать автоматически.';
$string['commerce_showroom_ascent_3_title'] = 'Скалистый участок';
$string['commerce_showroom_ascent_3_text'] = 'Вы отрабатываете самые частотные неправильные глаголы в разных форматах упражнений.';
$string['commerce_showroom_ascent_4_title'] = 'Переход через ледник';
$string['commerce_showroom_ascent_4_text'] = 'Редкие и сложные формы закрепляются без потери темпа и мотивации.';
$string['commerce_showroom_ascent_5_title'] = 'Вершина Монблана';
$string['commerce_showroom_ascent_5_text'] = 'Все 180 глаголов изучены, проверены и готовы естественно появляться в вашей речи.';
$string['commerce_showroom_ascent_legend_1'] = 'По одному пройденному этапу';
$string['commerce_showroom_ascent_legend_2'] = 'Маршрут открывается постепенно';
$string['commerce_showroom_ascent_legend_3'] = 'Награда на каждом финише';
$string['commerce_showroom_exercises_eyebrow'] = 'ПОСМОТРИТЕ, КАК УСТРОЕНА ТРЕНИРОВКА';
$string['commerce_showroom_exercises_aria'] = 'Выберите тип упражнения, чтобы увидеть пример';
$string['commerce_showroom_exercises_preview_label'] = 'Интерактивный пример';
$string['commerce_showroom_exercises_preview_step'] = 'Внутри этапа CampusFR';
$string['commerce_showroom_exercises_preview_caption'] = 'Нажмите на упражнение, чтобы увидеть другой способ запоминания.';

// J13F3 — offer comparison.
$string['commerce_showroom_comparison_eyebrow'] = 'ВЫБЕРИТЕ СНАРЯЖЕНИЕ';
$string['commerce_showroom_comparison_title'] = 'Сравните варианты снаряжения';
$string['commerce_showroom_comparison_description'] = 'До вершины можно добраться разными маршрутами. Выберите свой.';
$string['commerce_showroom_comparison_feature'] = 'Критерии';
$string['commerce_showroom_comparison_included'] = 'Включено';
$string['commerce_showroom_comparison_not_included'] = 'Не включено';
$string['commerce_showroom_comparison_bundle_badge'] = 'Рекомендуем';
$string['commerce_showroom_comparison_interactive_course'] = 'Полный интерактивный тренажёр';
$string['commerce_showroom_comparison_downloadable_pdf'] = 'PDF для скачивания';
$string['commerce_showroom_comparison_verbs_180'] = 'Все 180 глаголов маршрута';
$string['commerce_showroom_comparison_exercises_4000'] = 'Более 4 000 упражнений';
$string['commerce_showroom_comparison_audio_video'] = 'Аудио, видео и награды';
$string['commerce_showroom_comparison_offline_revision'] = 'Повторение без интернета';
$string['commerce_showroom_comparison_lifetime_access'] = 'Пожизненный доступ';


// J13F4 — Showroom reassurance and conversion.
$string['commerce_showroom_video_close'] = 'Закрыть видео';
$string['commerce_showroom_why_eyebrow'] = 'Метод для прочного запоминания';
$string['commerce_showroom_why_title'] = 'Почему этот метод работает';
$string['commerce_showroom_why_description'] = 'Вы не заучиваете список один раз: формы возвращаются в разных контекстах, пока не становятся естественными.';
$string['commerce_showroom_why_1_title'] = 'Умное повторение';
$string['commerce_showroom_why_1_text'] = 'Формы возвращаются в нужный момент без бессмысленной зубрёжки.';
$string['commerce_showroom_why_2_title'] = 'Разные контексты';
$string['commerce_showroom_why_2_text'] = 'Каждый глагол используется в нескольких жизненных ситуациях.';
$string['commerce_showroom_why_3_title'] = 'Аудирование и практика';
$string['commerce_showroom_why_3_text'] = 'Аудио связывает письменную форму с живой французской речью.';
$string['commerce_showroom_why_4_title'] = 'Мотивирующий прогресс';
$string['commerce_showroom_why_4_text'] = 'Квизы, этапы и награды помогают не останавливаться.';
$string['commerce_showroom_why_5_title'] = 'Долгая память';
$string['commerce_showroom_why_5_text'] = 'Регулярная практика постепенно превращает ответы в автоматизм.';
$string['commerce_showroom_trust_1_title'] = 'Безопасная оплата';
$string['commerce_showroom_trust_1_text'] = 'Защищённая оплата CampusFR';
$string['commerce_showroom_trust_2_title'] = 'Мгновенный доступ';
$string['commerce_showroom_trust_2_text'] = 'Начинайте сразу после подтверждения оплаты';
$string['commerce_showroom_trust_3_title'] = 'Доступ навсегда';
$string['commerce_showroom_trust_3_text'] = 'Возвращайтесь к тренировкам сколько нужно';
$string['commerce_showroom_trust_4_title'] = 'Поддержка CampusFR';
$string['commerce_showroom_trust_4_text'] = 'Команда ответит на ваши вопросы';
$string['commerce_showroom_testimonials_eyebrow'] = 'Учатся с CampusFR';
$string['commerce_showroom_testimonials_title'] = 'Готовы к вершине';
$string['commerce_showroom_faq_eyebrow'] = 'БРИФИНГ ПЕРЕД ВОСХОЖДЕНИЕМ';
$string['commerce_showroom_faq_description'] = 'Ответы на вопросы, которые чаще всего возникают перед началом восхождения.';
$string['commerce_showroom_support_title'] = 'Остались вопросы перед восхождением?';
$string['commerce_showroom_support_text'] = 'Мы не отправим вас на вершину без страховки 😄
Гюстав и команда CampusFR помогут с доступом, покупкой и любыми вопросами по тренажёру.';
$string['commerce_showroom_support_cta'] = 'Связаться со службой поддержки';
$string['commerce_showroom_expedition_card_label'] = 'Экспедиция Монблан';
$string['commerce_showroom_expedition_card_stage'] = 'Этап 0 из 30';
$string['commerce_showroom_expedition_card_altitude'] = 'Высота старта: 1 035 м';
$string['commerce_showroom_desktop_sticky_label'] = 'Полный комплект';

$string['commerce_showroom_status_draft'] = 'Черновик';
$string['commerce_showroom_status_review'] = 'На проверке';
$string['commerce_showroom_status_published'] = 'Опубликован';
$string['commerce_showroom_status_archived'] = 'В архиве';
$string['commerce_showroom_currency_update_error'] = 'Сейчас не удалось изменить валюту. Повторите попытку через несколько секунд.';
$string['commerce_showroom_cms_title'] = 'Showrooms Commerce';
$string['commerce_showroom_cms_create'] = 'Créer un showroom';
$string['commerce_showroom_cms_edit'] = 'Éditer le showroom';
$string['commerce_showroom_cms_key'] = 'Clé technique';
$string['commerce_showroom_cms_slugs'] = 'URLs publiques';
$string['commerce_showroom_cms_template'] = 'Template Moodle';
$string['commerce_showroom_cms_blocks'] = 'Blocs du showroom';
$string['commerce_showroom_cms_blocks_help'] = 'Le premier lot J13G enregistre l’ordre et la configuration des blocs. Le builder visuel avec glisser-déposer arrivera dans J13G2.';
$string['capability:manage_showrooms'] = 'Gérer les showrooms Commerce';
$string['commerce_showroom_builder_help'] = 'Меняйте порядок блоков перетаскиванием, включайте и отключайте их, дублируйте и редактируйте настройки прямо на странице.';
$string['commerce_showroom_builder_choose_block'] = 'Выберите тип блока';
$string['commerce_showroom_builder_preview'] = 'Предпросмотр шоурума';
$string['commerce_showroom_builder_edit_block'] = 'Настроить блок';
$string['commerce_showroom_builder_block_key'] = 'Ключ блока';
$string['commerce_showroom_builder_configuration'] = 'Конфигурация JSON';
$string['commerce_showroom_builder_configuration_help'] = 'В J13G2 расширенные настройки остаются в JSON. Формы для каждого типа блока появятся в следующей поставке.';
$string['commerce_showroom_builder_toggle'] = 'Включить или отключить';
$string['commerce_showroom_builder_confirm_delete'] = 'Удалить этот блок без возможности восстановления?';
$string['commerce_showroom_builder_saved'] = 'Шоурум обновлён.';
$string['commerce_showroom_builder_advanced_json'] = 'Расширенная конфигурация JSON';
$string['commerce_showroom_builder_live_preview'] = 'Предпросмотр блока';
$string['commerce_showroom_builder_required'] = 'Обязательное поле';
$string['commerce_showroom_choose_template'] = 'Выбрать шаблон';
$string['commerce_showroom_apply_template'] = 'Применить шаблон';
$string['commerce_showroom_export'] = 'Экспорт';
$string['commerce_showroom_import'] = 'Импорт';
$string['commerce_showroom_import_help'] = 'Вставьте содержимое файла .showroom.json, экспортированного из CampusFR.';

// J13G5 showroom publication workflow.
$string['commerce_showroom_history'] = 'История';
$string['commerce_showroom_revision'] = 'Версия';
$string['commerce_showroom_revision_action'] = 'Действие';
$string['commerce_showroom_revision_note'] = 'Комментарий к публикации';
$string['commerce_showroom_restore_revision'] = 'Восстановить';
$string['commerce_showroom_revision_restored'] = 'Версия восстановлена как черновик.';
$string['commerce_showroom_no_revisions'] = 'Сохранённых версий пока нет.';
$string['commerce_showroom_submit_review'] = 'Отправить на проверку';
$string['commerce_showroom_publish'] = 'Опубликовать';
$string['commerce_showroom_return_draft'] = 'Вернуть в черновик';
$string['commerce_showroom_submitted_review'] = 'Шоурум отправлен на проверку.';
$string['commerce_showroom_published'] = 'Шоурум опубликован, версия сохранена.';
$string['commerce_showroom_returned_draft'] = 'Шоурум снова в статусе черновика.';

$string['commerce_showroom_owned_compact'] = 'Уже приобретено';

$string['commerce_showroom_bundle_partial_owned'] = 'Один из товаров этого набора у вас уже есть. Дополните комплект выделенным предложением.';
$string['commerce_product_visual_format_showroom'] = 'Изображение Showroom — 16:9';
$string['commerce_product_visual_format_showroom_help'] = 'Карточки Showroom и маркетинговые композиции. Рекомендуемый размер: {$a}.';
$string['commerce_checkout_back_offer'] = 'Вернуться к предложению';
$string['commerce_guest_checkout_identity_title'] = 'Ваши данные';
$string['commerce_guest_checkout_identity_checkout_description'] = 'Эти данные нужны, чтобы защитить покупку и предоставить доступ к продуктам после оплаты.';

$string['commerce_checkout_terms_required'] = 'Перед продолжением необходимо принять условия продажи и политику конфиденциальности.';

$string['commerce_smart_terms'] = 'Условия использования';
$string['commerce_smart_privacy'] = 'Политика конфиденциальности';

$string['commerce_provider_experience_title'] = 'Подтвердите оплату';
$string['commerce_provider_experience_message'] = 'Сейчас вы будете перенаправлены на защищённую платёжную страницу.';
$string['commerce_provider_experience_continue'] = 'Продолжить';
$string['commerce_provider_experience_cancel'] = 'Отмена';
$string['commerce_provider_experience_other_method'] = 'Выбрать другой способ оплаты';
$string['commerce_provider_experience_stripe_title'] = 'Перейти к безопасной оплате';
$string['commerce_provider_experience_stripe_message'] = 'Проверьте покупку ещё раз перед переходом на Stripe.';
$string['commerce_provider_experience_stripe_advice'] = 'Безопасная оплата: после платежа вы автоматически вернётесь на CampusFR.';
$string['commerce_provider_experience_stripe_continue'] = 'Перейти к Stripe';
$string['commerce_provider_experience_alfa_title'] = 'Перед переходом в Alfa';
$string['commerce_provider_experience_alfa_message'] = 'При активном VPN страница Alfa может работать нестабильно.';
$string['commerce_provider_experience_alfa_advice'] = 'Перед продолжением отключите VPN, чтобы избежать проблем с загрузкой страницы Альфа-Банка.';
$string['commerce_provider_experience_alfa_continue'] = 'Перейти в Alfa';

$string['commerce_provider_experience_stay'] = 'Остаться на CampusFR';
$string['commerce_provider_experience_alfa_standard_secondary'] = 'Закрыть и выбрать другой способ оплаты';$string['commerce_cart_currency_switch'] = 'Валюта корзины';
$string['commerce_cart_currency_switch_help'] = 'Цены и скидки будут пересчитаны';
$string['commerce_cart_currency_switched'] = 'Корзина пересчитана в валюте {$a}.';
$string['commerce_cart_currency_removed_items'] = 'Недоступны в этой валюте и удалены из корзины: {$a}.';
$string['commerce_cart_currency_promotion_removed'] = 'Промокод удалён, поскольку он не действует в выбранной валюте.';
$string['commerce_provider_experience_alfa_other_currency'] = 'Оплатить в другой валюте';
$string['commerce_provider_currency_title'] = 'Выберите другую валюту';
$string['commerce_provider_currency_message'] = 'Корзина будет пересчитана с учётом цен, акций и условий для выбранной валюты. Недоступные товары могут быть удалены.';
$string['commerce_provider_currency_submit'] = 'Пересчитать корзину';
$string['commerce_provider_currency_empty'] = 'Другие валюты сейчас недоступны.';
$string['commerce_provider_currency_error'] = 'Не удалось загрузить доступные валюты. Закройте окно и попробуйте ещё раз.';

$string['commerce_cart_currency_removed_item_fallback'] = 'Один товар';

// J14F — Conditional customer promotions.
$string['commerce_cart_message_promotion_requires_login'] = 'Войдите в свой аккаунт CampusFR, чтобы воспользоваться этим предложением.';
$string['commerce_cart_message_promotion_missing_required_product'] = 'Это предложение доступно студентам, у которых уже есть необходимый продукт.';
$string['commerce_cart_message_promotion_already_owns_excluded_product'] = 'Предложение не применяется, потому что этот продукт уже приобретён.';
$string['commerce_cart_message_promotion_customer_not_eligible'] = 'Ваш аккаунт пока не соответствует условиям этого предложения.';
$string['commerce_cart_message_promotion_customer_rule_runtime_unavailable'] = 'Сейчас невозможно проверить условия этого предложения.';
$string['commerce_promotion_customer_eligibility'] = 'Условия участия клиента';
$string['commerce_promotion_customer_eligibility_help'] = 'Ограничьте промоакцию для определённых студентов в зависимости от уже приобретённых продуктов. Правила работают для промокодов и автоматических скидок.';
$string['commerce_promotion_requires_login'] = 'Только для авторизованных пользователей';
$string['commerce_promotion_requires_login_help'] = 'Студент должен войти в аккаунт до применения скидки.';
$string['commerce_promotion_eligibility_mode'] = 'Комбинация условий';
$string['commerce_promotion_eligibility_mode_help'] = '«Все» требует выполнения каждого условия. «Хотя бы одно» — только одного.';
$string['commerce_promotion_eligibility_all'] = 'Все условия';
$string['commerce_promotion_eligibility_any'] = 'Хотя бы одно условие';
$string['commerce_promotion_required_owned_products'] = 'Уже должен иметь';
$string['commerce_promotion_required_owned_products_help'] = 'Выберите продукты, которыми студент уже должен владеть.';
$string['commerce_promotion_excluded_owned_products'] = 'Ещё не должен иметь';
$string['commerce_promotion_excluded_owned_products_help'] = 'Выберите продукты, которых у студента ещё не должно быть.';
$string['commerce_promotion_eligibility_everyone'] = 'Все клиенты';
$string['commerce_promotion_eligibility_conditional'] = 'Условная промоакция';

// J14G1 — Order result polish.
$string['commerce_order_result_access_contents'] = 'Перейти в мой личный кабинет';
$string['commerce_order_result_discover_store'] = 'Перейти в магазин';

// J15A4 — Default Showroom block configuration.
$string['commerce_showroom_builder_initialise_defaults'] = 'Заполнить текущим содержанием';
$string['commerce_showroom_builder_confirm_defaults'] = 'Заполнить блоки с пустой конфигурацией? Уже настроенные блоки не будут изменены.';
$string['commerce_showroom_builder_defaults_initialised'] = 'Блоков заполнено текущим содержанием: {count}.';

// J15B — CMS content runtime.
$string['commerce_showroom_back_to_list'] = 'Вернуться к списку витрин';
$string['commerce_showroom_stats_title'] = 'Программа в цифрах';
$string['commerce_showroom_journey_eyebrow'] = 'Ваш прогресс';
$string['commerce_showroom_offers_title'] = 'Выберите предложение';
$string['commerce_showroom_method_eyebrow'] = 'Метод CampusFR';
$string['commerce_showroom_faq_title'] = 'Часто задаваемые вопросы';
$string['commerce_showroom_support_description'] = 'Мы не отправим вас на вершину без страховки 😄
Гюстав и команда CampusFR помогут с доступом, покупкой и любыми вопросами по тренажёру.';
$string['commerce_showroom_final_description'] = 'Выберите предложение и начните восхождение.';

// J15E1-2-3 — Showroom editor hardening.
$string['commerce_showroom_builder_advanced_json_help'] = 'После изменения JSON становится приоритетным. Пользовательские ключи сохраняются. Кнопка «Применить JSON к полям» обновляет визуальную форму.';
$string['commerce_showroom_builder_apply_json'] = 'Применить JSON к полям';
$string['commerce_showroom_builder_sync_json'] = 'Сформировать JSON из полей';
$string['commerce_showroom_builder_invalid_json'] = 'Некорректная конфигурация JSON.';
$string['commerce_showroom_builder_json_object_required'] = 'Конфигурация должна быть объектом JSON.';

// J15E4 — Showroom media manager.
$string['commerce_showroom_media_choose'] = 'Выбрать изображение';
$string['commerce_showroom_media_choose_video'] = 'Выбрать видео';
$string['commerce_showroom_media_remove'] = 'Удалить изображение';
$string['commerce_showroom_media_remove_video'] = 'Удалить видео';
$string['commerce_showroom_media_uploading'] = 'Обработка…';
$string['commerce_showroom_media_empty'] = 'Своё изображение не выбрано. Будет использовано стандартное.';
$string['commerce_showroom_media_empty_video'] = 'Своё видео не выбрано. Будет использован стандартный контент.';
$string['commerce_showroom_media_uploaded'] = 'Изображение сохранено.';
$string['commerce_showroom_media_uploaded_video'] = 'Видео сохранено.';

$string['commerce_showroom_video_play'] = 'Смотреть видео';
$string['commerce_showroom_video_pause'] = 'Поставить видео на паузу';
$string['commerce_showroom_video_replay'] = 'Посмотреть видео снова';

$string['commerce_mypurchases_store_link'] = 'Перейти в магазин';

// J15H.1H — Mobile offer presentation.
$string['commerce_showroom_offers_badge'] = 'Тарифы';
$string['commerce_showroom_offers_title_prefix'] = 'С чего начнётся ваше';
$string['commerce_showroom_offers_title_highlight'] = 'восхождение';
$string['commerce_showroom_offers_title_suffix'] = '?';
$string['commerce_showroom_offers_subtitle'] = 'Какой бы путь вы ни выбрали, мы будем рядом — от первого шага до самой вершины.';
$string['commerce_showroom_offers_slider_hint'] = 'Проведите в сторону, чтобы увидеть другие предложения';


// J15H.1I.1 — provisional account navigation and login guidance.
$string['commerce_guest_activation_nav_cta'] = 'Завершить создание аккаунта';
$string['commerce_guest_login_notice_title'] = 'Для аккаунта CampusFR нужно создать пароль';
$string['commerce_guest_login_notice_message'] = 'Покупка подтверждена, но пароля у вас пока нет. Завершите создание аккаунта вместо обычного входа.';
$string['commerce_guest_login_notice_cta'] = 'Создать пароль';

$string['commerce_a11y_skip_to_content'] = 'Перейти к основному содержанию';

$string['commerce_a11y_showroom_devices'] = 'Предпросмотр CampusFR на компьютере и телефоне';

$string['commerce_a11y_key_figures'] = 'Ключевые показатели';

$string['commerce_showroom_builder_image_help'] = 'PNG, JPG или WebP • Рекомендуемый размер: 1920 × 1080 px • Максимум: 20 МБ';

$string['commerce_showroom_builder_video_help'] = 'MP4 или WebM • Рекомендуется H.264, 1920 × 1080 • Максимум: 500 МБ';

$string['commerce_price_currency_delete_title'] = 'Удалить тариф в этой валюте';
$string['commerce_price_currency_delete_confirm'] = 'Удалить тариф в валюте {$a}? Товар больше не будет доступен в этой валюте. История заказов не изменится.';
$string['commerce_price_currency_deleted'] = 'Тариф в валюте {$a} удалён.';
$string['crm_commerce_nav_identities'] = 'Идентификация';
$string['commerce_identity_reconciliation_title'] = 'Сверка клиентских аккаунтов';
$string['commerce_identity_reconciliation_description'] = 'Проверка покупок Native Commerce без привязанного аккаунта Moodle и диагностика совпадений по e-mail.';
$string['commerce_identity_reconciliation_dryrun_notice'] = 'Эта страница работает только для чтения: совпадения анализируются без изменения данных. Ручная привязка выполняется только после отдельного защищённого действия.';
$string['commerce_identity_unresolved_total'] = 'Непривязанные покупки';
$string['commerce_identity_matched_on_page'] = 'Совпадения на странице';
$string['commerce_identity_not_found_on_page'] = 'Без аккаунта на странице';
$string['commerce_identity_ambiguous_on_page'] = 'Неоднозначные на странице';
$string['commerce_identity_filter_email'] = 'Фильтр по e-mail клиента';
$string['commerce_identity_reconciliation_empty'] = 'Непривязанных покупок Native Commerce по выбранным критериям нет.';
$string['commerce_identity_purchase'] = 'Покупка';
$string['commerce_identity_email'] = 'E-mail клиента';
$string['commerce_identity_diagnostic'] = 'Диагностика';
$string['commerce_identity_candidate'] = 'Аккаунт-кандидат';
$string['commerce_identity_status_matched'] = 'Одно совпадение';
$string['commerce_identity_status_not_found'] = 'Аккаунт не найден';
$string['commerce_identity_status_ambiguous'] = 'Неоднозначно';
$string['commerce_identity_status_skipped'] = 'Пропущено';
$string['commerce_identity_status_unchanged'] = 'Уже привязано';
$string['commerce_identity_status_reconciled'] = 'Привязано';
$string['commerce_identity_user_link'] = 'Пользователь #{$a}';
$string['commerce_identity_reconcile_action'] = 'Привязать';
$string['commerce_identity_reconcile_confirm'] = 'Это действие окончательно привяжет покупку Native и совместимые ресурсы к найденному аккаунту Moodle. Используйте его только после проверки результата диагностики.';
$string['commerce_identity_reconcile_success'] = 'Покупка {$a} успешно привязана.';
$string['commerce_identity_reconcile_not_applied'] = 'Привязка не выполнена. Текущий результат диагностики: {$a}.';
$string['crm_commerce_nav_personal_offers'] = 'Персональные предложения';
$string['commerce_personal_offers_title'] = 'Персональные предложения';
$string['commerce_personal_offers_description'] = 'Индивидуальные предложения Commerce, связанные с клиентом, целевым Native-продуктом и версионируемыми коммерческими условиями.';
$string['commerce_personal_offers_readonly_notice'] = 'На этом этапе экран работает только для просмотра. Создание предложений, защищённые ссылки, отзыв и использование будут добавлены в следующих этапах Personal Offer.';
$string['commerce_personal_offers_empty'] = 'Нет персональных предложений, соответствующих выбранным фильтрам.';
$string['commerce_personal_offer_id'] = 'Предложение';
$string['commerce_personal_offer_campaign'] = 'Кампания';
$string['commerce_personal_offer_email'] = 'E-mail';
$string['commerce_personal_offer_beneficiary'] = 'Получатель';
$string['commerce_personal_offer_target'] = 'Целевой продукт';
$string['commerce_personal_offer_pricing'] = 'Условия';
$string['commerce_personal_offer_validity'] = 'Срок действия';
$string['commerce_personal_offer_status'] = 'Статус';
$string['commerce_personal_offer_status_issued'] = 'Выдано';
$string['commerce_personal_offer_status_redeemed'] = 'Использовано';
$string['commerce_personal_offer_status_revoked'] = 'Отозвано';
$string['commerce_personal_offer_status_expired'] = 'Истекло';
$string['commerce_personal_offer_not_found'] = 'Персональное предложение не найдено.';
$string['commerce_personal_offer_not_revocable'] = 'Это персональное предложение больше нельзя отозвать.';
$string['commerce_personal_offer_not_redeemable'] = 'Это персональное предложение нельзя использовать.';
$string['commerce_personal_offer_purchase_not_paid'] = 'Персональное предложение может быть использовано только после успешной оплаты покупки Commerce Native.';
$string['commerce_personal_offer_identity_mismatch'] = 'Данные покупателя не соответствуют получателю персонального предложения.';
$string['commerce_personal_offer_campaign_source_missing'] = 'Исходный продукт кампании Personal Offer не найден.';

$string['commerce_personal_offer_page_title'] = 'Ваше персональное предложение';
$string['commerce_personal_offer_link_unavailable'] = 'Ссылка на персональное предложение недействительна, истекла, отозвана, уже использована или недоступна для этого аккаунта.';
$string['commerce_personal_offer_back_store'] = 'Вернуться в магазин';
$string['commerce_personal_offer_target_mismatch'] = 'Это персональное предложение не относится к данному продукту.';
$string['commerce_personal_offer_target_unavailable'] = 'Продукт этого персонального предложения сейчас недоступен.';
$string['commerce_personal_offer_currency_unavailable'] = 'Это персональное предложение недоступно в выбранной валюте.';
$string['commerce_personal_offer_cart_failed'] = 'Не удалось подготовить персональное предложение к оплате.';

$string['commerce_personal_offers_admin_notice'] = 'Персональными предложениями можно управлять на странице предложения. Экспортированные защищённые ссылки являются персональными и должны обрабатываться как конфиденциальные данные.';
$string['commerce_personal_offer_detail_title'] = 'Детали персонального предложения';
$string['commerce_personal_offer_source_purchase'] = 'Исходная покупка';
$string['commerce_personal_offer_created'] = 'Дата выпуска';
$string['commerce_personal_offer_redeemed_purchase'] = 'Покупка при использовании';
$string['commerce_personal_offer_revocation'] = 'Отзыв';
$string['commerce_personal_offer_secure_link'] = 'Защищённая персональная ссылка';
$string['commerce_personal_offer_revoke'] = 'Отозвать предложение';
$string['commerce_personal_offer_revoke_reason'] = 'Причина отзыва (необязательно)';
$string['commerce_personal_offer_reissue'] = 'Выпустить новое предложение';
$string['commerce_personal_offer_validity_days'] = 'Новый срок действия (дни)';
$string['commerce_personal_offer_metadata'] = 'Метаданные';
$string['commerce_personal_offer_revoked_success'] = 'Персональное предложение отозвано.';
$string['commerce_personal_offer_reissued_success'] = 'На основе предыдущего выпущено новое персональное предложение.';
$string['commerce_personal_offer_reissue_active'] = 'Активное предложение нельзя перевыпустить. Используйте текущую ссылку или сначала отзовите предложение.';
$string['commerce_personal_offer_stats_title'] = 'Статистика персональных предложений';
$string['commerce_personal_offer_campaign_stats'] = 'Статистика по кампаниям';
$string['commerce_personal_offer_export'] = 'Экспортировать ссылки CSV';

$string['commerce_personal_offer_campaigns'] = "Кампании персональных предложений";
$string['commerce_personal_offer_new_campaign'] = "Новая кампания";
$string['commerce_personal_offer_campaigns_empty'] = "Кампаний персональных предложений пока нет.";
$string['commerce_personal_offer_create_individual'] = "Создать индивидуальное предложение";
$string['commerce_personal_offer_create'] = "Создать предложение";
$string['commerce_personal_offer_audience'] = "Аудитория";
$string['commerce_personal_offer_audience_criteria'] = "Критерии CRM / покупок";
$string['commerce_personal_offer_audience_list'] = "Явный список (email или ID пользователей)";
$string['commerce_personal_offer_source_sku'] = "SKU исходного продукта";
$string['commerce_personal_offer_purchase_from'] = "Покупка с";
$string['commerce_personal_offer_purchase_to'] = "Покупка до";
$string['commerce_personal_offer_valid_from'] = "Действует с";
$string['commerce_personal_offer_expires_at'] = "Действует до";
$string['commerce_personal_offer_account_filter'] = "Требуется аккаунт Moodle?";
$string['commerce_personal_offer_exclude_owned'] = "Исключить клиентов, уже владеющих целевым продуктом";
$string['commerce_personal_offer_explicit_list'] = "Список email или ID пользователей";
$string['commerce_personal_offer_amounts'] = "Суммы в минимальных единицах";
$string['commerce_personal_offer_percent'] = "Скидка %";
$string['commerce_personal_offer_preview'] = "Предпросмотр / пересчитать аудиторию";
$string['commerce_personal_offer_generate'] = "Создать предложения";

// Personal Offer CRM UX.
$string['commerce_personal_offer_create_individual_help'] = 'Создайте персональное предложение для конкретного клиента. У клиента уже может быть аккаунт Moodle или только адрес электронной почты.';
$string['commerce_personal_offer_email_help'] = 'Начните вводить email существующего клиента Moodle. Можно также указать действующий email без аккаунта.';
$string['commerce_personal_offer_campaign_optional'] = 'Кампания (необязательно)';
$string['commerce_personal_offer_campaign_none'] = 'Без кампании — индивидуальное предложение';
$string['commerce_personal_offer_campaign_optional_help'] = 'Привяжите предложение к существующей CRM-кампании для отчётности или оставьте его индивидуальным.';
$string['commerce_personal_offer_source_purchase_optional'] = 'Исходная покупка (необязательно)';
$string['commerce_personal_offer_source_purchase_placeholder'] = 'Поиск по номеру заказа';
$string['commerce_personal_offer_source_purchase_help'] = 'Историческая покупка, обосновывающая предложение. Оставьте пустым для VIP-предложения, жеста лояльности или ручного таргетинга.';
$string['commerce_personal_offer_target_help'] = 'Товар Commerce Native, который клиент сможет купить по персональному предложению.';
$string['commerce_personal_offer_strategy_fixed_price'] = 'Персональная итоговая цена';
$string['commerce_personal_offer_strategy_fixed_discount'] = 'Фиксированная скидка';
$string['commerce_personal_offer_strategy_percentage_discount'] = 'Скидка в процентах';
$string['commerce_personal_offer_pricing_help'] = 'Выберите, как персональное предложение изменяет публичную цену. Применяется только одна стратегия.';
$string['commerce_personal_offer_amounts_display_title'] = 'Суммы по валютам';
$string['commerce_personal_offer_amounts_display_help'] = 'Вводите обычные суммы, как их видит клиент (например, 30,00 € или 2 990,00 ₽). Внутреннее преобразование выполняет Commerce.';
$string['commerce_personal_offer_valid_from_help'] = 'Необязательно. Оставьте пустым, чтобы предложение действовало сразу.';
$string['commerce_personal_offer_expires_at_help'] = 'Необязательно. После этой даты предложение останется в истории, но использовать его будет нельзя.';
$string['commerce_personal_offer_new_campaign_help'] = 'Определите получателей и коммерческие условия. Предложения и письма не создаются до предварительного просмотра и подтверждения аудитории.';
$string['commerce_personal_offer_campaign_identity_title'] = 'Идентификация кампании';
$string['commerce_personal_offer_campaign_name_placeholder'] = 'Напр.: Покупатели карточек — запуск тренажёра';
$string['commerce_personal_offer_campaign_name_help'] = 'Понятное название, отображаемое в CRM.';
$string['commerce_personal_offer_campaign_key'] = 'Ключ кампании';
$string['commerce_personal_offer_campaign_key_auto'] = 'Будет создан автоматически, если поле пустое';
$string['commerce_personal_offer_campaign_key_help'] = 'Стабильный технический идентификатор для идемпотентности и отчётности. Обычно оставляйте пустым.';
$string['commerce_personal_offer_audience_title'] = 'Получатели';
$string['commerce_personal_offer_audience_help'] = 'Критерии автоматически рассчитывают список по данным Commerce. Явный список позволяет выбрать клиентов вручную.';
$string['commerce_personal_offer_source_sku_help'] = 'Для кампании по критериям выберите товар, который получатели должны были купить.';
$string['commerce_personal_offer_account_all'] = 'С аккаунтом Moodle или без него';
$string['commerce_personal_offer_account_yes'] = 'Только с аккаунтом Moodle';
$string['commerce_personal_offer_account_no'] = 'Только без аккаунта Moodle';
$string['commerce_personal_offer_account_filter_help'] = 'Фильтр по наличию связи между коммерческой идентичностью клиента и Moodle.';
$string['commerce_personal_offer_purchase_from_help'] = 'Необязательная минимальная дата покупки исходного товара.';
$string['commerce_personal_offer_purchase_to_help'] = 'Необязательная максимальная дата покупки исходного товара.';
$string['commerce_personal_offer_exclude_owned_help'] = 'Рекомендуется: не предлагать товар клиентам, у которых он уже есть.';
$string['commerce_personal_offer_explicit_list_help'] = 'Используйте только для явного списка. Выберите email через автодополнение или вставьте по одному адресу в строке.';
$string['commerce_personal_offer_recipient_picker_placeholder'] = 'Начните вводить email клиента';
$string['commerce_personal_offer_explicit_list_placeholder'] = 'Один email в строке';
$string['commerce_personal_offer_offer_title'] = 'Условия предложения';
$string['commerce_personal_offer_campaigns_help'] = 'Создавайте, проверяйте и генерируйте кампании персональных предложений. Отправка писем будет отдельным этапом.';
$string['commerce_personal_offer_campaign_view_help'] = 'Проверьте рассчитанную аудиторию до генерации предложений. Пока кампания готовится, отдельных получателей можно включать и исключать.';
$string['commerce_personal_offer_metric_total'] = 'Аудитория';
$string['commerce_personal_offer_metric_eligible'] = 'Выбрано';
$string['commerce_personal_offer_metric_excluded'] = 'Исключено';
$string['commerce_personal_offer_metric_error'] = 'Ошибки';
$string['commerce_personal_offer_metric_issued'] = 'Создано предложений';
$string['commerce_personal_offer_criteria_generated_list_help'] = 'CRM-симуляция: список показывает клиентов, найденных по выбранному Legacy/Native источнику и текущим правилам. Предложения не создаются, пока вы явно не запустите генерацию.';
$string['commerce_personal_offer_reason_manual_exclusion'] = 'Исключено вручную';
$string['commerce_personal_offer_reason_target_owned'] = 'Целевой товар уже куплен';
$string['commerce_personal_offer_reason_invalid_email'] = 'Некорректный email';
$string['commerce_personal_offer_save_selection'] = 'Сохранить выбор';
$string['commerce_personal_offer_campaign_preview_empty'] = 'Выполните предварительный просмотр, чтобы рассчитать список получателей.';
$string['commerce_personal_offer_detail_help'] = 'Просмотрите получателя, коммерческие условия, жизненный цикл и защищённую ссылку предложения.';
$string['commerce_personal_offer_stats_help'] = 'Общая статистика и статистика по кампаниям персональных предложений.';

// Personal Offer email campaign (K9).
$string['commerce_mail_type_personal_offer'] = 'Персональное предложение';
$string['commerce_mail_personal_offer_subject'] = 'Персональное предложение CampusFR для вас';
$string['commerce_mail_personal_offer_cta'] = 'Открыть предложение';
$string['commerce_mail_personal_offer_card_label'] = 'Ваше персональное предложение';
$string['commerce_mail_personal_offer_expiry_label'] = 'Действует до:';
$string['task_process_personal_offer_mail_queue'] = 'Пакетная отправка писем Personal Offer';
$string['settings:personal_offer_mail_header'] = 'Письма Personal Offer';
$string['settings:personal_offer_mail_header_desc'] = 'Ограничения безопасности для коммерческих писем Personal Offer. Транзакционные письма Commerce не затрагиваются.';
$string['settings:personal_offer_mail_batch_size'] = 'Размер пакета Personal Offer';
$string['settings:personal_offer_mail_batch_size_desc'] = 'Максимальное число писем Personal Offer за один запуск планировщика. Осторожное значение по умолчанию: 20.';
$string['settings:personal_offer_mail_hourly_limit'] = 'Почасовой лимит Personal Offer';
$string['settings:personal_offer_mail_hourly_limit_desc'] = 'Максимум писем Personal Offer за скользящий час. Перед PROD настройте по реальному лимиту OVH.';
$string['commerce_personal_offer_mail_title'] = 'Отправка предложений по email';
$string['commerce_personal_offer_mail_help'] = 'Письма добавляются в очередь Commerce и постепенно отправляются cron. Постановка в очередь не запускает мгновенную массовую рассылку.';
$string['commerce_personal_offer_mail_queue_campaign'] = 'Поставить письма в очередь';
$string['commerce_personal_offer_mail_queue_single'] = 'Отправить это предложение по email';
$string['commerce_personal_offer_mail_queued_success'] = 'Письмо Personal Offer добавлено в очередь Commerce.';
$string['commerce_personal_offer_mail_campaign_queued'] = 'Email-кампания подготовлена: новых в очереди — {$a->queued}, уже существовали — {$a->existing}, ошибок — {$a->errors}.';
$string['commerce_personal_offer_mail_notqueued'] = 'Не подготовлено';
$string['commerce_personal_offer_mail_queued'] = 'В очереди';
$string['commerce_personal_offer_mail_processing'] = 'Обрабатываются';
$string['commerce_personal_offer_mail_sent'] = 'Отправлено';
$string['commerce_personal_offer_mail_failed'] = 'Ошибки';
$string['commerce_personal_offer_mail_cancelled'] = 'Отменены';
$string['commerce_personal_offer_mail_status'] = 'Email';
$string['commerce_personal_offer_mail_error'] = 'Последняя ошибка';
$string['commerce_personal_offer_mail_studio'] = 'Изменить шаблон письма';
$string['commerce_personal_offer_mail_log'] = 'Открыть журнал Commerce';
$string['commerce_personal_offer_mail_batch_notice'] = 'Отправка специально выполняется пакетами с учетом лимитов провайдера. Ошибки сохраняются в журнале Commerce и автоматически повторяются по существующей политике retry.';

// Personal Offer CRM identity/display hotfix (K9.1).
$string['commerce_identity_customer'] = 'Клиент';
$string['commerce_personal_offer_beneficiary_search'] = 'Получатель';
$string['commerce_personal_offer_beneficiary_search_placeholder'] = 'Email, имя или фамилия';
$string['commerce_personal_offer_source_basis'] = 'Основание для предложения';
$string['commerce_personal_offer_source_basis_help'] = 'Выберите, как Commerce подтверждает право на предложение: без покупки, владение продуктом или конкретная покупка.';
$string['commerce_personal_offer_source_none'] = 'Без основания — отдельное предложение / коммерческий жест';
$string['commerce_personal_offer_source_product'] = 'Владение продуктом';
$string['commerce_personal_offer_source_purchase_mode'] = 'Конкретная покупка';
$string['commerce_personal_offer_source_purchase_help'] = 'Расширенный вариант: выберите конкретный заказ. Сначала показывается ссылка CFR, а техническая ссылка cmp_ остаётся видимой для диагностики.';
$string['commerce_personal_offer_email_help'] = 'Ищите аккаунт Moodle по имени, фамилии или email. Для клиента без аккаунта Moodle можно также ввести корректный email напрямую.';

$string['commerce_personal_offer_edit'] = 'Изменить';
$string['commerce_personal_offer_edit_help'] = 'Измените коммерческие условия предложения. Исходное предложение останется в истории и будет отозвано после создания новой версии.';
$string['commerce_personal_offer_edit_replace_notice'] = 'Изменение не перезаписывает исходное предложение: будет создано новое предложение с новой защищённой ссылкой, а текущее будет отозвано.';
$string['commerce_personal_offer_delete'] = 'Удалить';
$string['commerce_personal_offer_delete_confirm'] = 'Удалить это предложение навсегда? Удаление доступно только для предложений, которые не отправлялись, не использовались и не относятся к кампании.';
$string['commerce_personal_offer_delete_not_allowed'] = 'Это предложение нельзя удалить: оно уже отправлялось, использовалось или относится к кампании. Вместо этого отзовите его.';
$string['commerce_personal_offer_deleted_success'] = 'Персональное предложение удалено.';
$string['commerce_personal_offer_edit_not_allowed'] = 'Изменять можно только активное персональное предложение.';
$string['commerce_personal_offer_replaced_success'] = 'Новая версия предложения создана, предыдущая версия отозвана.';
$string['commerce_personal_offer_terms_fixed_price_label'] = 'Персональная цена';
$string['commerce_personal_offer_terms_fixed_discount_label'] = 'Фиксированная скидка';
$string['commerce_personal_offer_terms_percentage_label'] = 'Скидка в процентах';
$string['commerce_personal_offer_ownership_native_entitlement'] = 'Native-доступ';
$string['commerce_personal_offer_ownership_native_purchase'] = 'Native-покупка';
$string['commerce_personal_offer_ownership_bundle'] = 'Владение через компоненты набора';
$string['commerce_personal_offer_ownership_legacy_digital'] = 'Legacy-покупка цифрового продукта';
$string['commerce_personal_offer_ownership_legacy_plan'] = 'Legacy-подписка';
$string['commerce_personal_offer_eligibility_free'] = 'Свободное предложение';
$string['commerce_personal_offer_eligibility_free_help'] = 'Для этого предложения не требуется исходная покупка или продукт.';
$string['commerce_personal_offer_eligibility_product'] = 'Владение продуктом';
$string['commerce_personal_offer_eligibility_purchase'] = 'Конкретная покупка';
$string['commerce_personal_offer_eligibility_campaign'] = 'Критерии кампании';
$string['commerce_personal_offer_evidence_purchase'] = 'Покупка-подтверждение';
$string['commerce_personal_offer_campaign_criteria_source'] = 'Исходный продукт кампании';
$string['commerce_personal_offer_no_campaign'] = 'Без кампании — индивидуальное предложение';
$string['commerce_personal_offer_summary_title'] = 'Кратко о предложении';
$string['commerce_personal_offer_eligibility_title'] = 'Почему клиент имеет право на предложение?';
$string['commerce_personal_offer_lifecycle_title'] = 'Срок действия и статус';
$string['commerce_personal_offer_technical_title'] = 'Технические ссылки';
$string['commerce_personal_offer_ownership_source'] = 'Источник подтверждения';
$string['commerce_personal_offer_metadata_technical'] = 'Технические метаданные';
$string['commerce_personal_offer_owned_product'] = 'Приобретённый продукт';
$string['commerce_personal_offer_product_evidence_missing'] = 'Исходный продукт не был сохранён в этом старом предложении';
$string['commerce_personal_offer_legacy_purchase_reference'] = 'Legacy-покупка цифрового продукта #{$a}';
$string['commerce_personal_offer_revoke_confirm'] = 'Отозвать это предложение? Персональная ссылка сразу перестанет работать.';
$string['commerce_personal_offer_checkout_temporary_error'] = 'Временная техническая ошибка прервала открытие предложения. Предложение не было использовано; вы можете повторить попытку.';
$string['commerce_personal_offer_checkout_badge'] = 'Персональное предложение';
$string['commerce_personal_offer_checkout_reserved_title'] = 'Это предложение предназначено лично для вас';
$string['commerce_personal_offer_checkout_reserved_for'] = 'Предложение для {$a->name} ({$a->email})';
$string['commerce_personal_offer_checkout_currency_title'] = 'Выберите валюту';
$string['commerce_personal_offer_checkout_currency_help'] = 'Персональная цена сохраняется. Показываются только валюты, доступные для этого предложения.';
$string['commerce_checkout_existing_account_login_title'] = 'Войдите, чтобы продолжить';
$string['commerce_checkout_existing_account_login_help'] = 'Аккаунт с этим адресом уже существует. Войдите здесь: корзина и персональное предложение сохранятся, после входа вы автоматически вернётесь к оплате.';
$string['commerce_checkout_existing_account_login_submit'] = 'Войти и продолжить';
$string['commerce_checkout_existing_account_login_alternative'] = 'Другой способ входа';
$string['commerce_personal_offer_order_discount_label'] = 'Персональное предложение';
$string['commerce_personal_offer_order_admin_reference'] = 'Персональное предложение (админ)';
$string['commerce_personal_offer_order_open'] = 'Открыть предложение';
$string['task_process_commerce_mail_audit_queue'] = 'Отправка аудит-копий Commerce с низким приоритетом';
$string['settings:commerce_mail_audit_batch_size'] = 'Размер пакета аудит-копий';
$string['settings:commerce_mail_audit_batch_size_desc'] = 'Максимальное число аудит-копий за один запуск. Эти письма всегда уступают приоритет клиентским письмам и кампаниям.';
$string['settings:commerce_mail_audit_hourly_limit'] = 'Почасовой лимит аудит-копий';
$string['settings:commerce_mail_audit_hourly_limit_desc'] = 'Максимальное число аудит-копий за скользящий час.';
$string['commerce_mail_resend'] = 'Отправить письмо повторно';
$string['commerce_mail_resend_confirm'] = 'Создать новую отправку этого письма? Исходная отправка останется в истории.';
$string['commerce_mail_resend_queued'] = 'Повторная отправка добавлена в очередь.';
$string['commerce_mail_resend_not_allowed'] = 'Таким способом можно повторно отправить только уже отправленное письмо.';
$string['commerce_mail_personal_offer_validity_label'] = 'Предложение действует';
$string['commerce_mail_personal_offer_valid_from_label'] = 'Действует с';
$string['commerce_mail_personal_offer_from_label'] = 'с';
$string['commerce_mail_personal_offer_to_label'] = 'по';
$string['commerce_mail_preview_description'] = 'Просматривайте точное содержимое письма, проверяйте варианты отображения и при необходимости запускайте повторную отправку.';
$string['commerce_mail_preview_font_label'] = 'Шрифт';
$string['commerce_mail_preview_font_brand'] = 'CampusFR (Nunito)';
$string['commerce_mail_preview_font_fallback'] = 'Резервный шрифт';


// J16C.2 — Exercise Explorer Builder.
$string['commerce_showroom_exercise_builder_title'] = 'Содержимое 12 упражнений';
$string['commerce_showroom_exercise_builder_content'] = 'Тексты';
$string['commerce_showroom_exercise_builder_media'] = 'Скриншоты';
$string['commerce_showroom_exercise_builder_default'] = 'Основное изображение';
$string['commerce_showroom_exercise_builder_import'] = 'Импортировать набор скриншотов';
$string['commerce_showroom_exercise_builder_import_help'] = 'Импортируйте ZIP с максимум 12 изображениями. Технические имена и русские названия из первого набора распознаются автоматически. Перед импортом выберите язык.';
$string['commerce_showroom_exercise_builder_import_button'] = 'Выбрать ZIP';
$string['commerce_showroom_exercise_builder_import_done'] = 'Сохранено изображений: {stored}; распознано упражнений: {matched}.';
$string['commerce_showroom_exercise_builder_choose_image'] = 'Выбрать';
$string['commerce_showroom_exercise_builder_remove_image'] = 'Удалить';
$string['commerce_showroom_exercise_builder_image_empty'] = 'Нет изображения';
$string['commerce_showroom_exercise_builder_image_fallback'] = 'Если локализованного изображения нет, используется основное.';

// J16C.3 — Exercise Explorer public preview.
$string['commerce_showroom_exercise_preview_unavailable'] = 'Скоро появится предпросмотр';

// J16C.4 — Exercise Explorer mobile.
$string['commerce_showroom_exercise_mobile_previous'] = 'Предыдущее упражнение';
$string['commerce_showroom_exercise_mobile_next'] = 'Следующее упражнение';
$string['commerce_showroom_exercise_mobile_counter'] = 'Упражнение';

// J16C.6 — Exercise Explorer navigation.
$string['commerce_showroom_exercise_navigation_hint'] = 'Листайте вправо-влево или используйте кнопки, чтобы посмотреть другие упражнения.';
$string['commerce_showroom_exercise_navigation_label'] = 'Навигация по упражнениям';

// J16C.6.2 — Exercise Explorer Builder UX.
$string['commerce_showroom_exercise_builder_fallback_badge'] = 'Резерв';
$string['commerce_mail_download_desktop'] = 'Обычная';
$string['commerce_mail_download_mobile'] = 'Мобильная';
$string['commerce_mail_bundle_contents'] = 'Содержимое вашего набора';
$string['commerce_mail_access_my_campus'] = 'Перейти в мой Campus';

// J16C.6.3 — Exercise Explorer preview polish.
$string['commerce_showroom_exercise_builder_localized_empty'] = 'Локализованного изображения нет';
$string['commerce_showroom_exercise_builder_localized_fallback'] = 'Основное изображение будет использовано автоматически.';

// J16C.6.5 — Exercise Explorer heading and desktop hint.
$string['commerce_showroom_exercise_desktop_hint'] = 'Нажмите на любой тип задания, чтобы посмотреть, как выглядит упражнение.';
$string['commerce_mail_receipt_price_before_discounts'] = 'Промежуточный итог';
$string['commerce_mail_receipt_discounts'] = 'Скидки';
$string['commerce_mail_receipt_total_paid'] = 'Итого оплачено';
$string['commerce_mail_payment_status_paid_value'] = 'Оплачено';
$string['commerce_mail_payment_status_pending_value'] = 'Ожидает оплаты';
$string['commerce_mail_payment_status_failed_value'] = 'Ошибка';
$string['commerce_mail_payment_status_cancelled_value'] = 'Отменено';
// J16D.2 — Comparatif mobile.
$string['commerce_showroom_comparison_swipe_hint'] = 'Листайте вправо-влево, чтобы сравнить';
$string['commerce_mail_receipt_product_promotions'] = 'Акции на товары';
$string['commerce_mail_receipt_trial_discount'] = 'Пробная скидка';
$string['commerce_mail_receipt_owned_credit'] = 'Зачтённый кредит';
$string['commerce_mail_receipt_promo_code'] = 'Промокод';
$string['commerce_mail_receipt_personal_offer'] = 'Персональное предложение';
$string['commerce_mail_receipt_other_discount'] = 'Другие скидки';
$string['commerce_mail_type_trial_welcome'] = 'Добро пожаловать — Trial';
$string['commerce_mail_trial_welcome_subject'] = 'Добро пожаловать в CampusFR — ваш пробный период начался';
$string['commerce_mail_trial_welcome_cta'] = 'Начать обучение';
$string['commerce_mail_welcome_login_email'] = 'E-MAIL ДЛЯ ВХОДА';
$string['commerce_mail_welcome_telegram_heading'] = 'Присоединяйтесь к сообществу CampusFR';
$string['commerce_mail_welcome_telegram_intro'] = 'Присоединяйтесь к нашему каналу, чтобы получать важные новости и обновления CampusFR. В группе можно общаться, спрашивать совета и двигаться вперёд вместе с другими участниками.';
$string['commerce_mail_welcome_telegram_channel'] = 'Канал CampusFR';
$string['commerce_mail_welcome_telegram_group'] = 'Группа CampusFR';
$string['commerce_mail_welcome_forgot_password'] = 'Забыли пароль?';
$string['commerce_mail_welcome_reset_password'] = 'Восстановить пароль';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_8_q'] = 'Можно ли пройти тренажёр несколько раз?';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_8_a'] = 'Конечно! Доступ к тренажёру предоставляется навсегда, поэтому вы сможете возвращаться к любым урокам и упражнениям столько раз, сколько захотите. Многие ученики проходят отдельные тренировки повторно, чтобы закрепить самые сложные глаголы и довести их до автоматизма.';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_9_q'] = 'А если у меня не получится?';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_9_a'] = 'Конечно, получится! 😊 Именно для этого мы и создали этот тренажёр. Он шаг за шагом помогает освоить даже самые сложные глаголы 3-й группы. Вы сможете повторять упражнения столько раз, сколько потребуется, а если возникнут вопросы — мы всегда будем рады помочь. Все контакты для связи с преподавателем вы найдёте внутри тренажёра в разделе «Практическая информация». Любое восхождение начинается с маленьких шагов. 😉';
$string['commerce_mail_welcome_credentials_heading'] = 'Данные для входа';
$string['commerce_mail_welcome_activation_explanation'] = 'Чтобы защитить аккаунт и выбрать пароль, нажмите кнопку ниже. Это займёт всего несколько секунд.';
$string['commerce_mail_welcome_activation_security'] = 'Ссылка для активации персональная и одноразовая. Если покупку совершали не вы, просто проигнорируйте это письмо.';
$string['commerce_guest_activation_email_expiry_soft'] = 'В целях безопасности ссылка для активации будет доступна до {$a}.';
$string['commerce_mail_welcome_postactivation'] = 'После активации аккаунта ваши курсы, материалы и покупки будут в любое время доступны в вашем пространстве CampusFR.';

// J16I2 — Showroom general configuration.
$string['commerce_showroom_config_general'] = 'Общие настройки';
$string['commerce_showroom_config_general_help'] = 'Идентификатор, статус публикации и шаблон отображения витрины.';
$string['commerce_showroom_config_key_help'] = 'Стабильный технический ключ. После публикации лучше его не менять.';
$string['commerce_showroom_config_render_template'] = 'Шаблон отображения Moodle';
$string['commerce_showroom_config_render_template_help'] = 'Определяет Mustache-шаблон публичной страницы. Это не то же самое, что шаблон набора блоков ниже.';
$string['commerce_showroom_config_urls_legacy'] = 'URL';
$string['commerce_showroom_config_urls_legacy_help'] = 'Slug пока настраиваются здесь. Мультиязычные SEO-данные будут добавлены на этапе J16I3.';
$string['commerce_showroom_config_products'] = 'Связанные продукты';
$string['commerce_showroom_config_products_help'] = 'Выберите продукты Commerce, используемые в предложениях витрины. Показываются только совместимые типы продуктов.';
$string['commerce_showroom_config_product_course'] = 'Курс / Тренажёр';
$string['commerce_showroom_config_product_pdf'] = 'Цифровой продукт / Карточки';
$string['commerce_showroom_config_product_bundle'] = 'Комплект';
$string['commerce_showroom_config_product_none'] = '— Без продукта —';
$string['commerce_showroom_config_advanced'] = 'Расширенные настройки';
$string['commerce_showroom_config_titlekey_legacy'] = 'Языковой ключ заголовка (legacy)';
$string['commerce_showroom_config_descriptionkey_legacy'] = 'Языковой ключ описания (legacy)';
$string['commerce_showroom_config_seo_legacy_help'] = 'Эти ключи остаются fallback-вариантом, если SEO-заголовок или описание CMS не заполнены.';
$string['commerce_showroom_config_settings_json'] = 'Глобальная конфигурация (JSON)';
$string['commerce_showroom_config_settings_json_help'] = 'Только для расширенных технических настроек витрины.';

// J16I3 — multilingual Showroom SEO.
$string['commerce_showroom_config_seo'] = 'SEO и публикация в соцсетях';
$string['commerce_showroom_config_seo_help'] = 'Настройте URL и метаданные отдельно для каждого языка. Если заголовок или описание пусты, используется старая языковая строка Moodle.';
$string['commerce_showroom_config_seo_slug'] = 'Slug';
$string['commerce_showroom_config_seo_title'] = 'SEO-заголовок';
$string['commerce_showroom_config_seo_title_help'] = 'Оптимально около 50–60 символов, основную тему лучше поставить ближе к началу.';
$string['commerce_showroom_config_seo_description'] = 'Meta description';
$string['commerce_showroom_config_seo_social'] = 'Публикация в соцсетях';
$string['commerce_showroom_config_seo_social_title'] = 'Заголовок для соцсетей';
$string['commerce_showroom_config_seo_social_description'] = 'Описание для соцсетей';
$string['commerce_showroom_config_seo_keywords'] = 'Ключевые слова';
$string['commerce_showroom_config_seo_keywords_help'] = 'Необязательно. Современные поисковые системы почти не учитывают meta keywords.';
$string['commerce_support_guest_contact_help'] = 'Укажите e-mail, чтобы наша команда могла вам ответить. Имя и фамилия необязательны, но мы рекомендуем их заполнить.';
$string['commerce_support_back_to_store'] = 'Вернуться в магазин';
$string['commerce_support_return_to_store'] = 'Вернуться в магазин';
$string['commerce_personal_offer_mail_image'] = 'Изображение для письма';
$string['commerce_personal_offer_mail_image_help'] = 'Необязательно. Изображение будет показано внизу письма с персональным предложением. Если изображение не выбрано, используется стандартный визуал CampusFR. JPG, PNG или WebP, максимум 8 МБ.';
$string['commerce_personal_offer_mail_image_edit_help'] = 'Загрузите новое изображение, чтобы заменить текущее. Если файл не выбран, текущее персональное изображение сохранится.';
$string['commerce_personal_offer_mail_image_upload_error'] = 'Не удалось загрузить изображение для предложения.';
$string['commerce_personal_offer_mail_image_too_large'] = 'Размер изображения превышает допустимые 8 МБ.';
$string['commerce_personal_offer_mail_image_invalid_type'] = 'Изображение должно быть корректным файлом JPG, PNG или WebP.';
$string['commerce_manual_grant_access_type'] = 'Тип доступа';
$string['commerce_manual_grant_mode_legacy'] = 'Legacy-подписка';
$string['commerce_manual_grant_mode_native'] = 'Native-продукт';
$string['commerce_manual_grant_native_section'] = 'Доступ Commerce Native';
$string['commerce_manual_grant_native_product'] = 'Продукт для предоставления';
$string['commerce_manual_grant_native_product_help'] = 'Продукт выдаётся через движок Commerce Native. Наборы раскрываются, и предоставляются все входящие в них права доступа.';
$string['commerce_manual_grant_reason'] = 'Причина предоставления';
$string['commerce_manual_grant_reason_help'] = 'Необязательно. Информация сохраняется в метаданных аудита права доступа.';
$string['commerce_manual_grant_submit'] = 'Предоставить доступ';
$string['commerce_manual_grant_success'] = 'Продукт «{$a->product}» предоставлен пользователю {$a->user}. Обработано прав Native: {$a->count}.';
$string['commerce_manual_grant_invalid_mode'] = 'Некорректный тип ручного предоставления доступа.';
$string['commerce_manual_grant_product_unavailable'] = 'Выбранный Native-продукт не найден или неактивен.';
$string['commerce_manual_grant_missing_entitlement'] = 'Для продукта {$a} не настроено эффективное право доступа Native.';
$string['commerce_manual_grant_empty_plan'] = 'Выбранный продукт не создаёт ни одного права доступа Native.';
$string['commerce_grants_title'] = 'Предоставление доступа';
$string['commerce_grants_description'] = 'Управляйте Legacy- и Native-доступами индивидуально или массово.';
$string['commerce_grants_card_description'] = 'Предоставьте доступ одному клиенту или подготовьте массовую выдачу с предварительной симуляцией.';
$string['commerce_grants_manual_title'] = 'Индивидуальная выдача';
$string['commerce_grants_manual_description'] = 'Вручную добавьте Legacy-подписку или предоставьте клиенту продукт Commerce Native.';
$string['commerce_grants_manual_action'] = 'Предоставить доступ вручную';
$string['commerce_grants_back'] = 'Назад к выдаче доступа';
$string['commerce_bulk_grant_title'] = 'Массовая выдача';
$string['commerce_bulk_grant_description'] = 'Выберите аудиторию по Legacy-плану или владению Native-продуктом и сначала проверьте точный список получателей в режиме dry-run.';
$string['commerce_bulk_grant_open'] = 'Подготовить массовую выдачу';
$string['commerce_bulk_grant_source_type'] = 'Критерий участия';
$string['commerce_bulk_grant_source_legacy'] = 'Подписка на Legacy-план';
$string['commerce_bulk_grant_source_native'] = 'Владение Native-продуктом';
$string['commerce_bulk_grant_source_plan'] = 'Исходный Legacy-план';
$string['commerce_bulk_grant_source_product'] = 'Исходный Native-продукт';
$string['commerce_bulk_grant_target_product'] = 'Продукт для предоставления';
$string['commerce_bulk_grant_target_help'] = 'На этом этапе доступ не предоставляется. Продукт используется только для проверки eligibility и будущих прав доступа.';
$string['commerce_bulk_grant_simulate'] = 'Симулировать получателей';
$string['commerce_bulk_grant_preview_title'] = 'Симуляция получателей';
$string['commerce_bulk_grant_preview_help'] = 'Список показывает аудиторию, найденную по текущим критериям. Проверьте личности и исключения перед продолжением.';
$string['commerce_bulk_grant_metric_total'] = 'Найдено клиентов';
$string['commerce_bulk_grant_metric_eligible'] = 'К выдаче';
$string['commerce_bulk_grant_metric_owned'] = 'Уже есть';
$string['commerce_bulk_grant_metric_identity'] = 'Проверить личность';
$string['commerce_bulk_grant_metric_error'] = 'Ошибки';
$string['commerce_bulk_grant_dry_run_badge'] = 'DRY-RUN';
$string['commerce_bulk_grant_no_mutation'] = 'Доступы, покупки и entitlement не создавались и не изменялись.';
$string['commerce_bulk_grant_filter_all'] = 'Все ({$a})';
$string['commerce_bulk_grant_filter_eligible'] = 'К выдаче ({$a})';
$string['commerce_bulk_grant_filter_owned'] = 'Уже есть ({$a})';
$string['commerce_bulk_grant_filter_identity'] = 'Проверить ({$a})';
$string['commerce_bulk_grant_filter_error'] = 'Ошибки ({$a})';
$string['commerce_bulk_grant_customer'] = 'Клиент';
$string['commerce_bulk_grant_moodle_account'] = 'Аккаунт Moodle';
$string['commerce_bulk_grant_evidence'] = 'Подтверждение eligibility';
$string['commerce_bulk_grant_current_ownership'] = 'Текущая ситуация';
$string['commerce_bulk_grant_decision'] = 'Решение';
$string['commerce_bulk_grant_account_unresolved'] = 'Не найден';
$string['commerce_bulk_grant_decision_eligible'] = 'К выдаче';
$string['commerce_bulk_grant_decision_already_owned'] = 'Уже есть';
$string['commerce_bulk_grant_decision_identity_review'] = 'Проверить';
$string['commerce_bulk_grant_decision_error'] = 'Ошибка';
$string['commerce_bulk_grant_planned_entitlements'] = 'Будет выдано прав: {$a}';
$string['commerce_bulk_grant_reason_missing_moodle_user'] = 'Не удалось определить аккаунт Moodle.';
$string['commerce_bulk_grant_reason_invalid_email'] = 'Некорректный e-mail аккаунта.';
$string['commerce_bulk_grant_reason_target_already_owned'] = 'У клиента уже есть целевой продукт.';
$string['commerce_bulk_grant_ownership_none'] = 'Нет доступа';
$string['commerce_bulk_grant_ownership_native_entitlement'] = 'Активное право Native';
$string['commerce_bulk_grant_ownership_native_purchase'] = 'Покупка Native';
$string['commerce_bulk_grant_ownership_bundle_components'] = 'Все компоненты набора уже есть';
$string['commerce_bulk_grant_ownership_legacy_digital_purchase'] = 'Legacy-покупка digital';
$string['commerce_bulk_grant_ownership_legacy_plan'] = 'Legacy-план';
$string['commerce_bulk_grant_next_phase_notice'] = 'На этом этапе dry-run намеренно нельзя запустить в исполнение. K14E добавит финальный выбор получателей и snapshot кампании.';
$string['commerce_bulk_grant_invalid_source_type'] = 'Некорректный тип критерия eligibility.';
$string['commerce_bulk_grant_source_product_missing'] = 'Исходный Native-продукт не найден.';
$string['crm_commerce_nav_grants'] = 'Доступы';
$string['commerce_bulk_grant_select_all'] = 'Выбрать всех подходящих';
$string['commerce_bulk_grant_select_none'] = 'Снять выбор со всех';
$string['commerce_bulk_grant_snapshot_title'] = 'Создать финальный snapshot';
$string['commerce_bulk_grant_snapshot_help'] = 'В кампанию будут зафиксированы только отмеченные пользователи, которые всё ещё подходят по условиям. Во время выполнения исходная аудитория пересчитываться не будет.';
$string['commerce_bulk_grant_campaign_name'] = 'Название кампании';
$string['commerce_bulk_grant_campaign_name_placeholder'] = 'Напр. Legacy Lifetime → Глаголы 3-й группы';
$string['commerce_bulk_grant_campaign_reason'] = 'Причина предоставления';
$string['commerce_bulk_grant_create_snapshot'] = 'Создать snapshot кампании';
$string['commerce_bulk_grant_campaign_name_required'] = 'Укажите название кампании.';
$string['commerce_bulk_grant_campaign_selection_required'] = 'Выберите хотя бы одного подходящего получателя.';
$string['commerce_bulk_grant_campaign_selection_changed'] = 'Пользователь #{$a} больше не подходит на момент создания snapshot. Запустите симуляцию повторно и проверьте список.';
$string['commerce_bulk_grant_campaign_not_launchable'] = 'Кампанию нельзя запустить в текущем статусе.';
$string['commerce_bulk_grant_campaign_retry_unavailable'] = 'Ошибки этой кампании нельзя перезапустить в текущем статусе.';
$string['commerce_bulk_grant_campaign_view_help'] = 'Проверьте зафиксированный snapshot, запустите выдачу и отслеживайте результат для каждого получателя.';
$string['commerce_bulk_grant_campaign_snapshot_title'] = 'Snapshot кампании';
$string['commerce_bulk_grant_campaign_source'] = 'Исходная аудитория';
$string['commerce_bulk_grant_campaign_metric_queued'] = 'В очереди';
$string['commerce_bulk_grant_campaign_metric_success'] = 'Успешно';
$string['commerce_bulk_grant_campaign_metric_skipped'] = 'Пропущено';
$string['commerce_bulk_grant_campaign_launch'] = 'Запустить выдачу для {$a} получателей';
$string['commerce_bulk_grant_campaign_launch_confirm'] = 'Кампания будет запущена по отображаемому snapshot. Доступы будут выдаваться пакетами через cron. Продолжить?';
$string['commerce_bulk_grant_campaign_launched'] = 'Кампания поставлена в очередь. Доступы будут выдаваться пакетами.';
$string['commerce_bulk_grant_campaign_retry'] = 'Повторить {$a} ошибок';
$string['commerce_bulk_grant_campaign_retried'] = 'Получателей, повторно поставленных в очередь: {$a}.';
$string['commerce_bulk_grant_campaign_cron_notice'] = 'Кампания выполняется. Cron обрабатывает до 25 получателей за один запуск; успешные результаты повторно не обрабатываются.';
$string['commerce_bulk_grant_campaign_attempts'] = 'Попытки';
$string['commerce_bulk_grant_campaign_error'] = 'Последняя ошибка';
$string['commerce_bulk_grant_new'] = 'Новая массовая выдача';
$string['commerce_bulk_grant_campaigns_title'] = 'Кампании выдачи доступа';
$string['commerce_bulk_grant_campaigns_empty'] = 'Массовых кампаний пока нет.';
$string['commerce_bulk_grant_campaign_status_ready'] = 'Готова';
$string['commerce_bulk_grant_campaign_status_queued'] = 'В очереди';
$string['commerce_bulk_grant_campaign_status_running'] = 'Выполняется';
$string['commerce_bulk_grant_campaign_status_completed'] = 'Завершена';
$string['commerce_bulk_grant_campaign_status_completed_errors'] = 'Завершена с ошибками';
$string['commerce_bulk_grant_member_status_queued'] = 'В очереди';
$string['commerce_bulk_grant_member_status_completed'] = 'Доступ выдан';
$string['commerce_bulk_grant_member_status_skipped'] = 'Пропущено';
$string['commerce_bulk_grant_member_status_failed'] = 'Ошибка';
$string['task_process_commerce_grant_campaigns'] = 'Обработка кампаний массовой выдачи Commerce';
$string['commerce_mail_type_grant_access'] = 'Предоставленный доступ';
$string['commerce_mail_grant_access_subject'] = 'В CampusFR вам открыт новый доступ';
$string['commerce_manual_grant_send_email'] = 'Отправить письмо «Доступы доступны»';
$string['commerce_manual_grant_send_email_help'] = 'Рекомендуется. Транзакционное письмо показывает предоставленный продукт и ссылки на доступ. Для индивидуальной выдачи отправка выполняется сразу, а при ошибке письмо остаётся в транзакционной очереди для повторной попытки.';
$string['commerce_bulk_grant_send_email'] = 'Отправить получателям письмо о доступе';
$string['commerce_bulk_grant_send_email_help'] = 'Рекомендуется. Для каждого получателя и корневого продукта создаётся одно письмо. Cron массовой выдачи не отправляет письма напрямую: они проходят через транзакционную очередь и её throttling.';
$string['commerce_bulk_grant_email_notification'] = 'Уведомление по e-mail';
$string['commerce_personal_offer_source_type'] = 'Источник eligibility';
$string['commerce_personal_offer_source_type_help'] = 'Выберите подписку или продукт, владение которым делает клиента подходящим для кампании. Симуляция не создаёт предложения.';
$string['commerce_personal_offer_source_legacy_plan'] = 'Legacy-план / подписка';
$string['commerce_personal_offer_source_legacy_digital'] = 'Legacy digital-продукт';
$string['commerce_personal_offer_source_native_product'] = 'Native-продукт';
$string['commerce_personal_offer_source_missing'] = 'Выбранный источник eligibility не найден.';
$string['commerce_personal_offer_invalid_source_type'] = 'Некорректный тип источника eligibility.';
$string['commerce_personal_offer_metric_covered'] = 'Уже есть предложение';
$string['commerce_personal_offer_metric_identity_review'] = 'Проверить личность';
$string['commerce_personal_offer_customer'] = 'Клиент';
$string['commerce_personal_offer_moodle_account'] = 'Аккаунт Moodle';
$string['commerce_personal_offer_eligibility_evidence'] = 'Подтверждение eligibility';
$string['commerce_personal_offer_existing_offer'] = 'Активное предложение';
$string['commerce_personal_offer_account_unresolved'] = 'Не найден';
$string['commerce_personal_offer_reason_ambiguous_email'] = 'Этому e-mail соответствуют несколько аккаунтов Moodle.';
$string['commerce_personal_offer_reason_account_required'] = 'Для кампании требуется определённый аккаунт Moodle.';
$string['commerce_personal_offer_reason_account_not_allowed'] = 'Кампания предназначена только для клиентов без аккаунта Moodle.';
$string['commerce_personal_offer_reason_active_offer_exists'] = 'Для этого продукта уже существует активное персональное предложение.';
$string['commerce_personal_offer_member_status_eligible'] = 'Подходит';
$string['commerce_personal_offer_member_status_covered'] = 'Уже покрыт';
$string['commerce_personal_offer_member_status_identity_review'] = 'Проверить';
$string['commerce_personal_offer_member_status_excluded'] = 'Исключён';
$string['commerce_personal_offer_member_status_error'] = 'Ошибка';
$string['commerce_personal_offer_member_status_issued'] = 'Предложение создано';
$string['commerce_personal_offer_member_status_replayed'] = 'Существующее предложение повторно использовано';
$string['commerce_personal_offer_preview'] = 'Симулировать подходящих клиентов';
$string['commerce_personal_offer_create_snapshot'] = 'Создать финальный snapshot';
$string['commerce_personal_offer_snapshot_confirm'] = 'Текущий выбор будет зафиксирован. После создания snapshot аудиторию нельзя будет пересчитать или изменить. Продолжить?';
$string['commerce_personal_offer_generate_snapshot'] = 'Создать предложения для {$a} получателей';
$string['commerce_personal_offer_generate_snapshot_confirm'] = 'Персональные предложения будут созданы для получателей, зафиксированных в snapshot. Продолжить?';
$string['commerce_personal_offer_snapshot_title'] = 'Snapshot кампании';
$string['commerce_personal_offer_snapshot_selected'] = 'Выбранные получатели';
$string['commerce_personal_offer_snapshot_date'] = 'Snapshot создан';
$string['commerce_personal_offer_snapshot_hash'] = 'Отпечаток snapshot';
$string['commerce_personal_offer_snapshot_frozen_notice'] = 'Аудитория зафиксирована. Генерация будет использовать только этот snapshot; источник eligibility повторно пересчитываться не будет.';
$string['commerce_personal_offer_snapshot_empty'] = 'Для snapshot не выбран ни один подходящий получатель.';
$string['commerce_personal_offer_snapshot_changed'] = 'Snapshot кампании был изменён или больше не соответствует зафиксированным получателям. Создайте новую кампанию вместо продолжения.';
$string['commerce_personal_offer_reason_target_acquired_after_snapshot'] = 'После snapshot клиент приобрёл целевой продукт.';
$string['commerce_personal_offer_reason_active_offer_created_after_snapshot'] = 'После snapshot было создано другое активное персональное предложение.';
$string['commerce_personal_offer_campaign_status_draft'] = 'Черновик';
$string['commerce_personal_offer_campaign_status_previewed'] = 'Симуляция выполнена';
$string['commerce_personal_offer_campaign_status_snapshot'] = 'Snapshot зафиксирован';
$string['commerce_personal_offer_campaign_status_issued'] = 'Предложения созданы';
$string['commerce_personal_offer_campaign_status_closed'] = 'Закрыта';
$string['commerce_personal_offer_retry_generation'] = 'Повторить {$a} ошибок генерации';
$string['commerce_personal_offer_mail_queue_missing'] = 'Поставить в очередь оставшиеся письма: {$a}';
$string['commerce_personal_offer_mail_queue_confirm'] = 'Недостающие письма будут добавлены в транзакционную очередь и отправлены с действующим throttling. Продолжить?';
$string['commerce_personal_offer_mail_retry_failed'] = 'Повторить письма с ошибкой: {$a}';
$string['commerce_personal_offer_mail_campaign_retried'] = 'Повторно поставлено в очередь писем: {$a->requeued}; найдено ошибок: {$a->failed}.';
$string['commerce_personal_offer_mail_expected'] = 'Ожидается';
$string['commerce_personal_offer_certification_title'] = 'Сертификация кампании';
$string['commerce_personal_offer_certification_ready'] = 'Кампания готова к сертификации: ошибок генерации нет, писем в ожидании или с ошибкой нет.';
$string['commerce_personal_offer_certification_blocked'] = 'Сертификация заблокирована: ошибок генерации — {$a->generationerrors}, получателей в ожидании — {$a->selectedpending}, незавершённых писем — {$a->mailblocking}.';
$string['commerce_personal_offer_certify_campaign'] = 'Сертифицировать и закрыть кампанию';
$string['commerce_personal_offer_certify_confirm'] = 'Кампания будет отмечена как сертифицированная и закрытая. Это подтверждает, что все ожидаемые предложения обработаны, а транзакционные письма завершены. Продолжить?';
$string['commerce_personal_offer_campaign_certified'] = 'Кампания сертифицирована и закрыта.';
$string['commerce_personal_offer_campaign_not_certifiable'] = 'Кампанию пока нельзя сертифицировать. Исправьте ошибки генерации и завершите все ожидаемые письма.';
$string['commerce_personal_offer_certified_at'] = 'Сертифицирована';
$string['commerce_personal_offer_certified_by'] = 'Сертифицировал';

// 7.95L1 — Showroom offer discovery CTA.
$string['commerce_showroom_config_offer_details_enabled'] = 'Показывать ссылку «Подробнее»';
$string['commerce_product_discovery_destination'] = 'Куда ведёт кнопка «Подробнее»';
$string['commerce_product_discovery_storefront'] = 'Карточка товара Storefront';
$string['commerce_product_discovery_showroom'] = 'Связанный Showroom';
$string['commerce_product_discovery_help'] = 'Определяет, куда ведут ссылки «Подробнее» из магазина, «Моих покупок» и других клиентских разделов. Если Showroom не опубликован, автоматически используется карточка товара Storefront.';
$string['commerce_product_show_full_presentation_cta'] = 'Показывать «Смотреть полную презентацию» на странице Storefront';
$string['commerce_product_show_full_presentation_cta_help'] = 'Страница Storefront остаётся доступной и предлагает ссылку на связанный Showroom. Автоматического перенаправления нет.';
$string['commerce_storefront_full_presentation'] = 'Смотреть полную презентацию';
$string['commerce_storefront_commerce_position_none'] = 'Скрыт (только Builder)';
$string['commerce_storefront_product_header_mode'] = 'Шапка товара';
$string['commerce_storefront_product_header_automatic'] = 'Автоматически';
$string['commerce_storefront_product_header_builder'] = 'Управляется Builder';
$string['commerce_storefront_product_header_hidden'] = 'Скрыта';
$string['commerce_storefront_product_header_help'] = 'В режиме Builder первый видимый блок Hero заменяет автоматическую шапку товара. Если блока Hero нет, автоматическая шапка остаётся видимой.';
$string['commerce_storefront_hero_layout'] = 'Макет Hero-блока';
$string['commerce_storefront_hero_layout_text_media'] = 'Текст слева / изображение справа';
$string['commerce_storefront_hero_layout_media_text'] = 'Изображение слева / текст справа';
$string['commerce_storefront_hero_layout_stacked'] = 'Вертикально';
$string['commerce_storefront_hero_layout_overlay'] = 'Текст поверх изображения';
$string['commerce_storefront_hero_ratio'] = 'Пропорции текста / изображения';
$string['commerce_storefront_hero_media_ratio'] = 'Формат изображения';
$string['commerce_storefront_media_ratio_original'] = 'Исходный формат';
$string['commerce_currency'] = 'Валюта';
$string['commerce_storefront_builder_attention'] = 'Нужно дополнить';
$string['commerce_storefront_builder_empty_status'] = 'Пусто';

$string['commerce_storefront_image_fit'] = 'Отображение изображения';
$string['commerce_storefront_image_fit_cover'] = 'Заполнить область (cover)';
$string['commerce_storefront_image_fit_contain'] = 'Показать изображение целиком (contain)';
$string['commerce_storefront_content_alignment'] = 'Выравнивание содержимого';
$string['commerce_storefront_content_alignment_left'] = 'Слева';
$string['commerce_storefront_content_alignment_center'] = 'По центру';
$string['commerce_storefront_content_alignment_right'] = 'Справа';

// Storefront locale copy and AI translation.
$string['settings:storefront_ai_translation_enabled'] = 'Включить ИИ-перевод Storefront';
$string['settings:storefront_ai_translation_enabled_desc'] = 'Разрешает конструктору Storefront использовать уже настроенный аккаунт OpenAI (ключ, проект, организация, модель и endpoint) для подготовки переводов локалей. Перед сохранением перевод всегда нужно вручную подтвердить в предпросмотре.';
$string['commerce_storefront_locale_tools_title'] = 'Языковые версии и перевод';
$string['commerce_storefront_locale_tools_help'] = 'Скопируйте существующую языковую версию или подготовьте автоматический перевод на язык, который сейчас редактируется.';
$string['commerce_storefront_locale_copy_title'] = 'Скопировать языковую версию';
$string['commerce_storefront_locale_copy_help'] = 'Копирует структуру, секции, SEO, локализованные данные и ссылки на медиа в текущую языковую версию. Глобальные настройки Commerce и slugs не изменяются.';
$string['commerce_storefront_locale_source'] = 'Исходная языковая версия';
$string['commerce_storefront_locale_copy_button'] = 'Скопировать сюда';
$string['commerce_storefront_locale_copy_confirm'] = 'Текущая языковая версия будет заменена копией исходной. Продолжить?';
$string['commerce_storefront_locale_copy_success'] = 'Языковая версия скопирована.';
$string['commerce_storefront_locale_source_empty'] = 'В исходной языковой версии нет конфигурации Storefront для копирования.';
$string['commerce_storefront_ai_translation_title'] = 'Перевести с OpenAI';
$string['commerce_storefront_ai_translation_help'] = 'Подготавливает переведённую копию исходной языковой версии. В OpenAI отправляются только текстовые поля; ID, URL, медиа, SKU и технические настройки не изменяются.';
$string['commerce_storefront_ai_translation_preview_button'] = 'Подготовить перевод';
$string['commerce_storefront_ai_translation_unavailable'] = 'Перевод Storefront через OpenAI недоступен.';
$string['commerce_storefront_ai_translation_unavailable_help'] = 'Включите ИИ-перевод Storefront в настройках плагина и проверьте, что API-ключ OpenAI и модель настроены.';
$string['commerce_storefront_ai_translation_no_content'] = 'В исходной языковой версии не найдено текстовых полей для перевода.';
$string['commerce_storefront_ai_translation_too_many_fields'] = 'В языковой версии слишком много полей для одной операции перевода (максимум: {$a}).';
$string['commerce_storefront_ai_translation_preview_expired'] = 'Этот предпросмотр перевода устарел. Создайте новый.';
$string['commerce_storefront_ai_translation_preview_title'] = 'Предпросмотр перевода';
$string['commerce_storefront_ai_translation_preview_summary'] = '{$a->source} → {$a->target}: изменено полей — {$a->count}, модель {$a->model}. Пока ничего не сохранено.';
$string['commerce_storefront_ai_translation_source_text'] = 'Исходный текст';
$string['commerce_storefront_ai_translation_target_text'] = 'Перевод';
$string['commerce_storefront_ai_translation_apply'] = 'Применить перевод';
$string['commerce_storefront_ai_translation_applied'] = 'Перевод применён к языковой версии.';
$string['subscriptions:manage_showrooms'] = 'Управление витринами Commerce';
$string['commerce_showroom_status_workflow_only'] = 'Статус изменяется только через процесс проверки и публикации.';
$string['commerce_showroom_publish_requires_block'] = 'Витрину нельзя опубликовать, пока не включён хотя бы один блок.';
$string['commerce_showroom_invalid_transition'] = 'Этот переход статуса витрины недопустим.';
$string['commerce_showroom_import_create'] = 'Создать из JSON-импорта';
$string['commerce_showroom_import_file'] = 'JSON-файл витрины';
$string['commerce_showroom_import_or_paste'] = 'Или вставьте содержимое JSON вручную ниже.';
$string['commerce_showroom_import_created_draft'] = 'Витрина импортирована и создана как черновик.';
$string['commerce_showroom_import_media_warning'] = 'JSON-файл переносит структуру, переводы и полную конфигурацию всех блоков. Медиафайлы Moodle (изображения/видео) не встраиваются в JSON: перед публикацией в production необходимо проверить ссылки или загрузить медиа заново.';
$string['commerce_showroom_export_portable'] = 'Экспортировать переносимый пакет';
$string['commerce_showroom_export_json'] = 'Экспортировать JSON';
$string['commerce_showroom_import_portable_help'] = 'Для переноса DEV → PROD рекомендуется пакет .showroom.zip: он содержит полный JSON и все медиафайлы витрины. Импорт только JSON остаётся доступным для конфигурации без файлов.';
$string['commerce_showroom_import_portable_done'] = 'Переносимая витрина импортирована как черновик: {$a->blocks} блоков, {$a->media} медиафайлов, {$a->remapped} ссылок переназначено.';
$string['commerce_showroom_export_preflight_title'] = 'Подготовка переносимого пакета';
$string['commerce_showroom_export_preflight_media'] = 'Количество медиафайлов';
$string['commerce_showroom_export_preflight_total'] = 'Общий размер медиафайлов';
$string['commerce_showroom_export_preflight_largest'] = 'Самый большой файл';
$string['commerce_showroom_export_preflight_required'] = 'Рекомендуемое временное место';
$string['commerce_showroom_export_preflight_available'] = 'Доступное временное место';
$string['commerce_showroom_export_preflight_ready'] = 'Экспорт можно запускать. Уже сжатые изображения и видео будут добавлены в ZIP без повторного сжатия.';
$string['commerce_showroom_export_portable_start'] = 'Создать и скачать пакет';
$string['commerce_showroom_export_insufficient_disk'] = 'Недостаточно временного места на диске. Требуется: {$a->required}. Доступно: {$a->available}.';
$string['commerce_showroom_export_invalid_archive'] = 'Созданный переносимый пакет повреждён или неполный.';
$string['commerce_showroom_publish_requires_slug'] = 'Витрину нельзя опубликовать, пока не настроен хотя бы один публичный slug.';
$string['commerce_showroom_publish_slug_conflict'] = 'Публичный slug «{$a}» уже используется другим маршрутом, товаром или опубликованной витриной.';
$string['commerce_storefront_bundle_includes'] = 'В комплект входит';
// 7.95M1 - CRM-профили Commerce без учётной записи Moodle.
$string['crm_commerce_guest_profile'] = 'Карточка клиента Commerce';
$string['crm_commerce_guest_profile_description'] = 'User360 для клиента с покупками Commerce или Legacy без связанной учётной записи Moodle.';
$string['crm_commerce_identity_type'] = 'Тип профиля';
$string['crm_commerce_identity_legacy_guest'] = 'Клиент Commerce / Legacy';
$string['crm_first_purchase'] = 'Первая покупка';
$string['crm_no_moodle_account'] = 'Нет связанной учётной записи Moodle';
$string['crm_commerce_native_history'] = 'История Commerce Native';
$string['crm_commerce_guest_no_actions'] = 'Для этого клиента сейчас нет доступных действий.';
$string['crm_user_account_commerce_only'] = 'Клиент Commerce · без аккаунта Moodle';
$string['commerce_showroom_publish_integrity_failed'] = 'Проверка целостности витрины перед публикацией не пройдена: {$a}.';
$string['commerce_storefront_access_bundle_contents'] = 'Перейти к моим материалам';
$string['commerce_storefront_back_to_showroom'] = 'Вернуться к презентации';
$string['commerce_personal_offer_campaign_email_title'] = 'E-mail кампании';
$string['commerce_personal_offer_campaign_email_help'] = 'Настройте маркетинговый текст писем Personal Offer. Товар, реальные цены, срок действия и защищённая CTA-кнопка по-прежнему формируются Commerce.';
$string['commerce_personal_offer_campaign_email_saved'] = 'Настройки e-mail кампании сохранены.';
$string['commerce_personal_offer_campaign_email_locked'] = 'Предложения этой кампании уже выпущены или кампания закрыта. Настройки e-mail доступны только для просмотра.';
$string['commerce_personal_offer_campaign_email_destination'] = 'Куда ведёт CTA';
$string['commerce_personal_offer_campaign_email_destination_checkout'] = 'Checkout персонального предложения';
$string['commerce_personal_offer_campaign_email_destination_showroom'] = 'Showroom';
$string['commerce_personal_offer_campaign_email_showroom'] = 'Showroom кампании';
$string['commerce_personal_offer_campaign_email_showroom_choose'] = 'Выберите совместимый опубликованный Showroom';
$string['commerce_personal_offer_campaign_email_showroom_help'] = 'Показываются только опубликованные Showroom, содержащие целевой товар кампании. Публичный URL вручную не вводится.';
$string['commerce_personal_offer_campaign_email_content'] = 'Содержание письма';
$string['commerce_personal_offer_campaign_email_content_help'] = 'Оставьте язык полностью пустым, чтобы использовать французский fallback, либо исторический Personal Offer e-mail, если французский тоже не заполнен. Маркетинговый текст и заключение редактируются в безопасном редакторе Moodle; HTML очищается перед сохранением и при формировании письма.';
$string['commerce_personal_offer_campaign_email_variables'] = 'Доступные динамические переменные';
$string['commerce_personal_offer_campaign_email_subject'] = 'Тема';
$string['commerce_personal_offer_campaign_email_body'] = 'Маркетинговый текст';
$string['commerce_personal_offer_campaign_email_cta_label'] = 'Текст CTA-кнопки';
$string['commerce_personal_offer_campaign_email_closing'] = 'Заключение';
$string['commerce_personal_offer_campaign_email_manage'] = 'Настроить e-mail кампании';
$string['commerce_personal_offer_campaign_email_fallback_active'] = 'Персональный текст кампании не настроен. Используется исторический fallback Personal Offer.';
$string['commerce_personal_offer_campaign_email_languages_configured'] = 'Персональный текст настроен для: {$a}.';
$string['commerce_personal_offer_campaign_email_destination_summary'] = 'Назначение CTA';
$string['commerce_identity_bulk_execute'] = 'Выполнить привязку';
$string['commerce_identity_bulk_execute_confirm'] = 'Выбранные привязки будут записаны. Перед записью каждая покупка проверяется заново; неоднозначная или изменившаяся идентичность не будет привязана принудительно.';
$string['commerce_identity_bulk_execute_result'] = 'Привязано покупок: {$a->done} из {$a->total}.';
$string['commerce_identity_bulk_none_selected'] = 'Выберите хотя бы одну покупку с однозначным совпадением.';
$string['commerce_identity_bulk_preview'] = 'Проверить выбранные привязки';
$string['commerce_identity_bulk_preview_description'] = 'Проверьте каждое выбранное совпадение и ожидаемые изменения до записи данных.';
$string['commerce_identity_bulk_preview_title'] = 'Dry-run массовой привязки';
$string['commerce_identity_bulk_preview_warning'] = 'Это dry-run: данные не изменены. Выполнить можно только строки, для которых по точному email всё ещё найден единственный аккаунт Moodle.';
$string['commerce_identity_dryrun_impact'] = 'Результат dry-run';
$string['commerce_identity_dryrun_impact_summary'] = 'Изменений: {$a->total}; прав: {$a->grants}; цифровых доступов: {$a->digital}; гостевых сессий: {$a->guests}; Legacy-записей: {$a->legacy}.';
$string['commerce_identity_filter_any'] = 'Поиск по email, номеру заказа или данным клиента';
$string['commerce_identity_filter_candidateuserid'] = 'ID кандидата Moodle';
$string['commerce_identity_filter_email_partial'] = 'Email содержит';
$string['commerce_identity_filter_name'] = 'Имя клиента содержит';
$string['commerce_identity_filter_purchaseid'] = 'ID покупки';
$string['commerce_identity_filter_reference'] = 'Номер заказа содержит';
$string['commerce_identity_filter_sku'] = 'SKU товара / ссылка позиции содержит';
$string['commerce_identity_filter_status'] = 'Статус диагностики';
$string['commerce_identity_results_count'] = 'По фильтрам найдено покупок без привязанного аккаунта: {$a}.';
$string['commerce_identity_select'] = 'Выбор';
$string['commerce_identity_select_purchase'] = 'Выбрать покупку {$a}';
$string['commerce_identity_nav_label'] = 'Операции с профилями';
$string['commerce_identity_nav_reconciliation'] = 'Сопоставление';
$string['commerce_identity_nav_similarities'] = 'Похожие аккаунты';
$string['commerce_identity_similarity_title'] = 'Потенциально похожие аккаунты';
$string['commerce_identity_similarity_description'] = 'Найдите аккаунты Moodle, которые могут принадлежать одному человеку, до ручного объединения.';
$string['commerce_identity_similarity_manual_only'] = 'Это только предложения для проверки. Аккаунты не объединяются, не изменяются и не блокируются автоматически.';
$string['commerce_identity_similarity_filter_query'] = 'Email, имя или фамилия';
$string['commerce_identity_similarity_filter_status'] = 'Статус аккаунта';
$string['commerce_identity_similarity_filter_minscore'] = 'Минимальный рейтинг';
$string['commerce_identity_similarity_account_active'] = 'Активен';
$string['commerce_identity_similarity_account_suspended'] = 'Заблокирован';
$string['commerce_identity_similarity_scan_summary'] = 'Проверено аккаунтов: {$a->users} · найдено совпадений: {$a->matches}';
$string['commerce_identity_similarity_truncated'] = 'Проверка ограничена {$a} недавно активными аккаунтами. При необходимости используйте поиск по конкретному клиенту.';
$string['commerce_identity_similarity_empty'] = 'По этим критериям похожих аккаунтов не найдено.';
$string['commerce_identity_similarity_score'] = 'Рейтинг';
$string['commerce_identity_similarity_account_first'] = 'Аккаунт A';
$string['commerce_identity_similarity_account_second'] = 'Аккаунт B';
$string['commerce_identity_similarity_signals'] = 'Найденные признаки';
$string['commerce_identity_similarity_reason_email_exact'] = 'Одинаковый email';
$string['commerce_identity_similarity_reason_email_local_exact'] = 'Одинаковая часть email';
$string['commerce_identity_similarity_reason_email_local_close'] = 'Похожие email';
$string['commerce_identity_similarity_reason_name_exact'] = 'Одинаковые имя и фамилия';
$string['commerce_identity_similarity_reason_name_reversed'] = 'Имя / фамилия переставлены';
$string['commerce_identity_similarity_reason_firstname_close'] = 'Похожие имена';
$string['commerce_identity_similarity_reason_lastname_close'] = 'Похожие фамилии';
$string['commerce_identity_similarity_reason_phone_exact'] = 'Одинаковый телефон';
// Personal Offer Campaign Email — M3D preview/test send.
$string['commerce_personal_offer_campaign_email_preview'] = 'Предпросмотр письма кампании';
$string['commerce_personal_offer_campaign_email_preview_help'] = 'Безопасный предпросмотр до выпуска кампании, рассчитанный по условиям кампании и серверным ценам каталога. CTA предпросмотра остаётся в административной зоне и не создаёт оффер.';
$string['commerce_personal_offer_campaign_email_preview_refresh'] = 'Обновить';
$string['commerce_personal_offer_campaign_email_test_send'] = 'Отправить тестовое письмо';
$string['commerce_personal_offer_campaign_email_test_sent'] = 'Тестовое письмо отправлено на {$a}.';
$string['commerce_identity_merge_blockers'] = 'Проверки, необходимые перед объединением';
$string['commerce_identity_merge_blocker_pedagogy'] = 'В объединяемом аккаунте #{$a->userid} есть учебная история, требующая проверки ({$a->count} элемент(ов)).';
$string['commerce_identity_merge_blocker_legacy_subscription'] = 'В объединяемом аккаунте #{$a->userid} есть {$a->count} коммерческих записей, требующих проверки.';
$string['commerce_identity_merge_blocker_already_merged'] = 'Исходный аккаунт #{$a->userid} уже использовался как источник в предыдущем объединении.';
$string['commerce_identity_merge_blocker_suspended_target'] = 'Основной аккаунт #{$a->userid} заблокирован.';
$string['commerce_identity_merge_blocker_generic'] = 'Объединение содержит блокирующий фактор и требует ручной проверки.';
$string['commerce_identity_merge_execution_warning'] = 'Действие выполняется транзакционно, но его нельзя отменить через интерфейс: учебное состояние, владение Legacy/Commerce и CRM-данные личности будут объединены в сохраняемом аккаунте, после чего исходные аккаунты будут приостановлены. Исторические журналы аудита сохраняются.';
$string['commerce_identity_merge_execution_confirm'] = 'Я подтверждаю, что проверил(а) предпросмотр и хочу окончательно объединить эти аккаунты.';
$string['commerce_identity_merge_execute'] = 'Выполнить объединение';
$string['commerce_identity_merge_confirmation_required'] = 'Необходимо явно подтвердить объединение.';
$string['commerce_identity_merge_execution_blocked'] = 'Объединение заблокировано: один или несколько аккаунтов содержат данные, которые нельзя безопасно перенести автоматически.';
$string['commerce_identity_merge_execution_success'] = 'Объединение завершено: исходных аккаунтов — {$a->sources}, основной аккаунт — #{$a->targetuserid}. Ссылка аудита: {$a->mergeuuid}.';
$string['privacy:metadata:identity_merge'] = 'Журнал административных объединений профилей.';
$string['privacy:metadata:identity_merge:targetuserid'] = 'Сохранённый основной аккаунт Moodle.';
$string['privacy:metadata:identity_merge:performedby'] = 'Администратор, выполнивший объединение.';
$string['privacy:metadata:identity_merge_source'] = 'Исходные аккаунты Moodle, участвующие в объединении.';
$string['privacy:metadata:identity_merge_source:sourceuserid'] = 'Объединённый исходный аккаунт Moodle.';
$string['privacy:metadata:identity_merge_source:sourceemail'] = 'Исторический email исходного аккаунта на момент объединения.';
$string['crm_topbar_admin_general'] = 'Общие настройки';
$string['crm_topbar_admin_users'] = 'Пользователи';
$string['crm_topbar_admin_courses'] = 'Курсы';
$string['crm_topbar_admin_grades'] = 'Оценки';
$string['crm_topbar_admin_plugins'] = 'Плагины';
$string['crm_topbar_admin_appearance'] = 'Внешний вид';
$string['crm_topbar_admin_server'] = 'Сервер';
$string['crm_topbar_admin_reports'] = 'Отчёты';
$string['crm_topbar_admin_development'] = 'Разработка';
$string['crm_topbar_admin_shortcuts'] = 'Мои административные ссылки';
$string['crm_topbar_admin_purge_caches'] = 'Очистить кэши';
$string['crm_topbar_admin_maintenance_mode'] = 'Режим обслуживания';
$string['crm_topbar_admin_subscriptions_config'] = 'Настройки Commerce';
$string['crm_topbar_admin_campus_config'] = 'Настройки Campus';
$string['commerce_identity_nav_merge'] = 'Объединить профили';
$string['commerce_identity_merge_title'] = 'Объединение аккаунтов';
$string['commerce_identity_merge_ids'] = 'ID Moodle для сравнения';
$string['commerce_identity_merge_preview'] = 'Предпросмотр объединения';
$string['commerce_identity_merge_select_account'] = 'Выбрать аккаунт Moodle #{$a}';
$string['commerce_identity_merge_prepare'] = 'Подготовить объединение';
$string['commerce_identity_merge_accounts'] = 'Сравниваемые аккаунты';
$string['commerce_identity_merge_keep'] = 'Сохранить';
$string['commerce_identity_merge_account'] = 'Аккаунт';
$string['commerce_identity_merge_pedagogy'] = 'Учебная история';
$string['commerce_identity_merge_commerce'] = 'Данные Commerce';
$string['commerce_identity_merge_account_quality'] = 'Качество аккаунта';
$string['commerce_identity_merge_recommended'] = 'Рекомендуется';
$string['commerce_identity_merge_pedagogy_summary'] = 'Курсов: {$a->courses}
Завершено курсов: {$a->completedcourses}
Завершено активностей: {$a->activities}
Оценок: {$a->grades} · средняя {$a->average}%
Учебный рейтинг: {$a->score}';
$string['commerce_identity_merge_commerce_summary'] = 'Покупок: {$a->purchases} · прав: {$a->grants} · цифровых доступов: {$a->digital}
Рейтинг Commerce: {$a->score}';
$string['commerce_identity_merge_confirmed'] = 'Подтверждённый аккаунт';
$string['commerce_identity_merge_unconfirmed'] = 'Неподтверждённый аккаунт';
$string['commerce_identity_merge_lastaccess'] = 'Последний вход: {$a}';
$string['commerce_identity_merge_recalculate'] = 'Пересчитать с этим основным аккаунтом';
$string['commerce_identity_merge_virtual_profile'] = 'Симуляция итогового профиля';
$string['commerce_identity_merge_virtual_profile_summary'] = 'Основной аккаунт: #{$a->userid} — {$a->name} — {$a->email}';
$string['commerce_identity_merge_transfer_summary'] = 'Планируемый перенос Commerce: покупок {$a->purchases}, прав {$a->grants}, цифровых доступов {$a->digital}, гостевых сессий {$a->guests}.';
$string['commerce_identity_merge_warnings'] = 'Что нужно проверить';
$string['commerce_identity_merge_warning_pedagogical_history'] = 'В объединяемом аккаунте #{$a->userid} есть учебная история. Поддерживаемое учебное состояние будет перенесено в сохраняемый аккаунт.';
$string['commerce_identity_merge_warning_shared_courses'] = 'Аккаунты участвуют в {$a->count} общих курсах. Прогресс, оценки и попытки могут конфликтовать.';
$string['commerce_identity_merge_warning_different_emails'] = 'Аккаунты используют разные email. Будет сохранён email основного аккаунта.';
$string['commerce_identity_merge_warning_suspended_target'] = 'Выбранный основной аккаунт #{$a->userid} заблокирован.';
$string['commerce_identity_merge_warning_generic'] = 'Этот пункт требует ручной проверки.';
$string['commerce_identity_nav_provisioning'] = 'Создать аккаунты';
$string['commerce_identity_provisioning_title'] = 'Создание аккаунтов для покупателей цифровых продуктов';
$string['commerce_identity_provisioning_description'] = 'Создавайте аккаунты Moodle для покупателей Legacy Digital, у которых ещё нет профиля, с dry-run и проверкой похожих аккаунтов.';
$string['commerce_identity_provisioning_safety'] = 'Во время dry-run аккаунты не создаются. Точные существующие аккаунты и неоднозначные профили никогда не дублируются. При похожем аккаунте создание требует отдельного подтверждения.';
$string['commerce_identity_provisioning_filter_query'] = 'Email, имя или фамилия';
$string['commerce_identity_provisioning_filter_status'] = 'Статус';
$string['commerce_identity_provisioning_email'] = 'Email';
$string['commerce_identity_provisioning_identity'] = 'Профиль';
$string['commerce_identity_provisioning_purchases'] = 'Legacy-покупки';
$string['commerce_identity_provisioning_status'] = 'Диагностика';
$string['commerce_identity_provisioning_details'] = 'Подробности';
$string['commerce_identity_provisioning_override'] = 'Принудительно';
$string['commerce_identity_provisioning_status_creatable'] = 'Можно создать без риска';
$string['commerce_identity_provisioning_status_existing'] = 'Аккаунт Moodle уже существует';
$string['commerce_identity_provisioning_status_ambiguous'] = 'Несколько точных аккаунтов';
$string['commerce_identity_provisioning_status_similar'] = 'Есть похожий аккаунт';
$string['commerce_identity_provisioning_status_invalid'] = 'Некорректный email';
$string['commerce_identity_provisioning_existing_user'] = 'Существующий аккаунт: #{$a}. Используйте сопоставление вместо создания дубликата.';
$string['commerce_identity_provisioning_ambiguous_users'] = 'Для этого email найдено несколько аккаунтов: {$a}. Нужна ручная проверка.';
$string['commerce_identity_provisioning_preview_selected'] = 'Предпросмотр выбранных аккаунтов';
$string['commerce_identity_provisioning_dryrun_title'] = 'Dry-run создания аккаунтов';
$string['commerce_identity_provisioning_force_similar'] = 'Создать несмотря на похожий аккаунт';
$string['commerce_identity_provisioning_confirm'] = 'Я подтверждаю, что проверил(а) dry-run и предупреждения о похожих аккаунтах.';
$string['commerce_identity_provisioning_execute'] = 'Создать подтверждённые аккаунты';
$string['commerce_identity_provisioning_confirmation_required'] = 'Необходимо явно подтвердить создание аккаунтов.';
$string['commerce_identity_provisioning_execution_summary'] = 'Создано: {$a->created} · пропущено/заблокировано: {$a->skipped} · ошибок: {$a->errors}';
$string['commerce_identity_provisioning_scan_truncated'] = 'Проверка ограничена {$a} последними Legacy-покупками. Используйте фильтр для поиска конкретного клиента.';
$string['commerce_legacy_account_activation_title'] = 'Активируйте ваш CampusFR';
$string['commerce_legacy_account_activation_intro'] = 'Здравствуйте, {$a->firstname}! Мы создали ваше пространство CampusFR, чтобы объединить покупки и материалы, связанные с {$a->email}. Выберите пароль, чтобы получить доступ.';
$string['commerce_legacy_account_activation_submit'] = 'Активировать мой аккаунт';
$string['commerce_legacy_account_activation_invalid'] = 'Эта ссылка активации недействительна или устарела.';
$string['commerce_legacy_account_activation_failed'] = 'Не удалось активировать ваше пространство CampusFR.';

$string['commerce_personal_offer_validity_title'] = 'Срок действия предложений';
$string['commerce_personal_offer_validity_help'] = 'Выберите единый срок окончания для всей кампании или индивидуальную длительность, которая рассчитывается с момента создания каждого предложения.';
$string['commerce_personal_offer_validity_mode'] = 'Режим срока действия';
$string['commerce_personal_offer_validity_fixed'] = 'Фиксированные дата и время';
$string['commerce_personal_offer_validity_duration'] = 'Длительность после создания';
$string['commerce_personal_offer_validity_duration_value'] = 'Длительность';
$string['commerce_personal_offer_validity_duration_unit'] = 'Единица';
$string['commerce_personal_offer_validity_hours'] = 'Часы';
$string['commerce_personal_offer_validity_duration_help'] = 'Отсчёт начинается с момента создания предложения. Повторная отправка того же письма не продлевает срок действия.';
$string['commerce_personal_offer_validity_timezone'] = 'Часовой пояс';
$string['commerce_personal_offer_validity_timezone_help'] = 'Используется для фиксированных даты и времени и их отображения. Для кампаний CampusFR рекомендуется Europe/Paris.';

$string['admin_event_user_legacy_digital_provisioned'] = 'Доступ к Legacy-материалу предоставлен';

$string['commerce_mail_personal_offer_direct_checkout'] = 'Перейти к оплате';

$string['commerce_personal_offer_workflow_title'] = 'Подготовка кампании';
$string['commerce_personal_offer_workflow_help'] = 'Пройдите этапы подготовки перед созданием предложений. Статусы отражают фактическое состояние кампании.';
$string['commerce_personal_offer_workflow_commercial'] = 'Коммерческое предложение';
$string['commerce_personal_offer_workflow_commercial_ready'] = 'Продукт, коммерческие условия и срок действия сохранены.';
$string['commerce_personal_offer_workflow_email'] = 'Письмо и путь клиента';
$string['commerce_personal_offer_workflow_email_ready'] = 'Персонализированный контент и направление настроены.';
$string['commerce_personal_offer_workflow_email_missing'] = 'Настройте содержание письма и направление перед отправкой.';
$string['commerce_personal_offer_workflow_audience'] = 'Аудитория';
$string['commerce_personal_offer_workflow_audience_ready'] = 'Сейчас подходящих получателей: {$a}.';
$string['commerce_personal_offer_workflow_audience_missing'] = 'Выполните предварительный просмотр кампании, чтобы рассчитать подходящих получателей.';
$string['commerce_personal_offer_workflow_snapshot'] = 'Зафиксированная выборка';
$string['commerce_personal_offer_workflow_snapshot_ready'] = 'Выборка получателей зафиксирована.';
$string['commerce_personal_offer_workflow_snapshot_missing'] = 'Создайте снимок после проверки аудитории.';
$string['commerce_personal_offer_workflow_issue'] = 'Создание предложений';
$string['commerce_personal_offer_workflow_issue_ready'] = 'Персональные предложения созданы.';
$string['commerce_personal_offer_workflow_issue_missing'] = 'Финальный этап после проверки снимка.';
$string['commerce_personal_offer_workflow_configure_email'] = 'Настроить письмо';
$string['commerce_personal_offer_workflow_preview_test'] = 'Предпросмотр / отправить тест';
$string['commerce_personal_offer_workflow_view_audience'] = 'Посмотреть аудиторию';
$string['commerce_personal_offer_workflow_showroom'] = 'Showroom';
$string['commerce_personal_offer_workflow_direct_checkout_also'] = 'в письме также доступен прямой переход к оплате';
$string['commerce_personal_offer_campaign_email_saved_preview_next'] = 'Настройки письма сохранены. Теперь проверьте предпросмотр и отправьте тестовое письмо перед созданием предложений.';

$string['commerce_personal_offer_campaign_banner_title'] = 'Баннер письма';
$string['commerce_personal_offer_campaign_banner_help'] = 'Добавьте баннер для этой кампании. Он заменит стандартную шапку CampusFR только в письмах этой кампании.';
$string['commerce_personal_offer_campaign_banner_file'] = 'Изображение баннера';
$string['commerce_personal_offer_campaign_banner_format_help'] = 'JPEG, PNG или WebP, максимум 8 МБ. Для лучшего результата используйте горизонтальное изображение примерно 1600 × 440 px.';
$string['commerce_personal_offer_campaign_banner_delete'] = 'Удалить персональный баннер и вернуть стандартную шапку';
$string['commerce_personal_offer_campaign_banner_upload_error'] = 'Не удалось загрузить баннер кампании.';
$string['commerce_personal_offer_campaign_banner_too_large'] = 'Размер баннера кампании превышает максимум 8 МБ.';
$string['commerce_personal_offer_campaign_banner_invalid_type'] = 'Баннер должен быть изображением JPEG, PNG или WebP.';
$string['commerce_identity_similarity_reason_email_name_combination'] = 'Похожие email + одинаковая фамилия';
$string['commerce_identity_legacy_link_action'] = 'Привязать к этому аккаунту';
$string['commerce_identity_legacy_link_title'] = 'Привязать Legacy-профиль к аккаунту Moodle';
$string['commerce_identity_legacy_link_description'] = 'Сохраните существующий аккаунт Moodle и привяжите к нему Legacy Digital покупки другой идентичности, не меняя email и учебный прогресс.';
$string['commerce_identity_legacy_link_dryrun'] = 'Dry-run: данные не изменяются. Внимательно проверьте обе идентичности перед подтверждением.';
$string['commerce_identity_legacy_link_source'] = 'Идентичность Legacy Digital';
$string['commerce_identity_legacy_link_target'] = 'Сохраняемый аккаунт Moodle';
$string['commerce_identity_legacy_link_purchase_count'] = 'Legacy Digital покупок: {$a}';
$string['commerce_identity_legacy_link_preserves_target'] = 'Целевой аккаунт Moodle сохраняется без изменений: email, пароль, записи на курсы, прогресс, оценки и учебная история не меняются. Заполняется только userid у непривязанных Legacy Digital покупок.';
$string['commerce_identity_legacy_link_confirm'] = 'Я подтверждаю, что обе идентичности принадлежат одному человеку и этот аккаунт Moodle нужно сохранить.';
$string['commerce_identity_legacy_link_execute'] = 'Привязать покупки к аккаунту Moodle';
$string['commerce_identity_legacy_link_success'] = 'Legacy Digital покупок привязано: {$a->count}; аккаунт Moodle #{$a->userid}.';
$string['commerce_identity_legacy_link_no_purchases'] = 'Для этого email не найдено непривязанных Legacy Digital покупок.';
$string['commerce_identity_legacy_link_similarity_too_low'] = 'Уровень сходства недостаточен для автоматического разрешения привязки. Проверьте идентичности вручную.';
$string['commerce_identity_legacy_link_confirmation_required'] = 'Необходимо явно подтвердить, что обе идентичности принадлежат одному человеку.';
$string['commerce_personal_offer_correct_beneficiary'] = 'Исправить получателя';
$string['commerce_personal_offer_correct_beneficiary_help'] = 'Используйте это исключительное исправление только для выданного персонального предложения, которое ещё не было использовано и письмо по которому ещё не отправлено. Ключ снимка кампании и защищённый токен предложения сохраняются.';
$string['commerce_personal_offer_correct_beneficiary_current'] = 'Текущий получатель';
$string['commerce_personal_offer_correct_beneficiary_email'] = 'Правильный e-mail аккаунта Moodle';
$string['commerce_personal_offer_correct_beneficiary_preview'] = 'Проверить исправление';
$string['commerce_personal_offer_correct_beneficiary_preview_title'] = 'Предпросмотр исправления получателя';
$string['commerce_personal_offer_correct_beneficiary_confirm'] = 'Подтвердить исправление получателя';
$string['commerce_personal_offer_correct_beneficiary_success'] = 'Получатель персонального предложения исправлен; неотправленное письмо при необходимости снова поставлено в очередь.';
$string['commerce_personal_offer_correct_beneficiary_unavailable'] = 'Получателя этого персонального предложения больше нельзя безопасно исправить.';
$string['commerce_personal_offer_correct_beneficiary_user_not_unique'] = 'Этому e-mail должен соответствовать ровно один активный аккаунт Moodle.';

// Commerce 7.95 M5.1 — Product Statistics 2.0.
$string['commerce_statistics_period_custom'] = 'Произвольный период';
$string['commerce_m51_title'] = 'Эффективность продукта';
$string['commerce_m51_subtitle'] = 'Оплаченные продажи, реальные выдачи и состояние платежей. Ожидающие платежи никогда не считаются продажами.';
$string['commerce_m51_paid_orders'] = 'Оплаченные заказы';
$string['commerce_m51_paid_orders_help'] = 'Заказы с этим продуктом и подтверждённым платежом.';
$string['commerce_m51_units_sold'] = 'Продано единиц';
$string['commerce_m51_units_sold_help'] = 'Сумма количества продукта в оплаченных заказах.';
$string['commerce_m51_manual_grants'] = 'Бесплатные выдачи';
$string['commerce_m51_manual_grants_help'] = 'Административные выдачи CRM без покупки. Не зависят от валюты.';
$string['commerce_m51_total_delivered'] = 'Всего выдано';
$string['commerce_m51_total_delivered_help'] = 'Проданные единицы + завершённые бесплатные заказы + административные выдачи.';
$string['commerce_m51_payment_pending'] = 'Платежи в ожидании';
$string['commerce_m51_payment_pending_help'] = 'Последняя попытка оплаты ещё ожидает завершения. Не считается продажей.';
$string['commerce_m51_payment_failed'] = 'Неуспешные платежи';
$string['commerce_m51_payment_failed_help'] = 'Последняя попытка отклонена, завершилась ошибкой или истекла.';
$string['commerce_m51_payment_cancelled'] = 'Отменённые платежи';
$string['commerce_m51_payment_cancelled_help'] = 'Последняя попытка оплаты была явно отменена.';
$string['commerce_m51_revenue_collected'] = 'Полученная выручка';
$string['commerce_m51_revenue_evolution'] = 'Динамика полученной выручки';
$string['commerce_m51_deliveries_evolution'] = 'Выдачи продукта';
$string['commerce_m51_delivery_paid'] = 'Куплено и оплачено';
$string['commerce_m51_delivery_free_order'] = 'Бесплатный заказ';
$string['commerce_m51_delivery_manual'] = 'Административная выдача';
$string['commerce_m51_payment_paid'] = 'Оплачено';
$string['commerce_m51_payment_refunded'] = 'Возвращено';
$string['commerce_m51_payment_distribution'] = 'Распределение статусов оплаты';
$string['commerce_m51_from'] = 'С';
$string['commerce_m51_until'] = 'По';
$string['commerce_m51_export_excel'] = 'Экспорт в Excel';
$string['commerce_m51_export_summary'] = 'Сводка';
$string['commerce_m51_export_orders'] = 'Заказы';
$string['commerce_m51_export_deliveries'] = 'Выдачи';

// Commerce 7.95 M5.1G — comparison trends.
$string['commerce_m51_comparison_previous'] = 'Динамика по сравнению с предыдущим периодом такой же длительности: с {$a->from} по {$a->until}.';
$string['commerce_m51_comparison_today'] = 'Динамика по сравнению со вчерашним днём за тот же временной интервал: с {$a->from} по {$a->until}.';
$string['commerce_m51_trend_new'] = 'Новое';
$string['commerce_m51_show_chart_data'] = "Показать данные графика";

// Commerce 7.95 M5.2 — product steering analytics.
$string['commerce_m52_revenue_period'] = 'Выручка за период';
$string['commerce_m52_revenue_cumulative'] = 'Накопленная выручка';
$string['commerce_m52_revenue_display'] = 'Режим отображения выручки';
$string['commerce_m52_average_order'] = 'Средний оплаченный заказ';
$string['commerce_m52_payment_quality'] = 'Платежи';
$string['commerce_m52_success_rate'] = 'Доля успешных платежей';
$string['commerce_m52_funnel'] = 'Воронка конверсии';
$string['commerce_m52_attempts'] = 'Попытки оплаты';
$string['commerce_m52_confirmed_payments'] = 'Подтверждённые платежи';
$string['commerce_m52_deliveries'] = 'Выданные единицы';
$string['commerce_m52_acquisition_origin'] = 'Источник получения';
$string['commerce_m52_acq_standard'] = 'Обычная покупка';
$string['commerce_m52_acq_promotion'] = 'Промоакция';
$string['commerce_m52_acq_personaloffer'] = 'Персональное предложение';
$string['commerce_m52_acq_free'] = 'Бесплатный заказ';
$string['commerce_m52_acq_manual'] = 'Выдано администратором';
$string['commerce_m52_provider_distribution'] = 'Полученные платежи по провайдерам';
$string['commerce_m52_provider_orders'] = 'Заказов: {$a}';
$string['commerce_m52_export_payments'] = 'Платежи';

// Commerce 7.95 M5.3 — premium global statistics.
$string['commerce_m53_export_excel'] = 'Экспорт в Excel';
$string['commerce_m53_paid_orders'] = 'Оплаченные заказы';
$string['commerce_m53_paid_customers'] = 'Платящие клиенты';
$string['commerce_m53_units_sold'] = 'Проданные единицы';
$string['commerce_m53_total_delivered'] = 'Всего выдано';
$string['commerce_m53_revenue_collected'] = 'Полученная выручка';
$string['commerce_m53_average_order'] = 'Средний чек';
$string['commerce_m53_payment_success_rate'] = 'Доля успешных платежей';
$string['commerce_m53_pending_fulfillments'] = 'Ожидают выдачи';
$string['commerce_m53_funnel'] = 'Общая воронка конверсии';
$string['commerce_m53_payment_attempts'] = 'Попытки оплаты';
$string['commerce_m53_commercial_evolution'] = 'Динамика продаж';
$string['commerce_m53_revenue_evolution'] = 'Динамика полученной выручки';
$string['commerce_m53_paid_orders_evolution'] = 'Динамика оплаченных заказов';
$string['commerce_m53_payment_health'] = 'Состояние платежей';
$string['commerce_m53_payment_distribution'] = 'Распределение статусов платежей';
$string['commerce_m53_export_summary'] = 'Сводка';
$string['commerce_m53_export_orders'] = 'Заказы';
$string['commerce_m53_export_grants'] = 'Выдачи';
$string['commerce_statistics_period_from'] = 'С';
$string['commerce_statistics_period_until'] = 'По';

// Commerce 7.95 M5.3B — branched payment journeys.
$string['commerce_m53_payment_journey'] = 'Путь платежей';
$string['commerce_m53_payment_journey_help'] = 'Каждая попытка имеет только один текущий статус. Ветви не пересекаются, поэтому их сумма не превышает количество попыток.';
$string['commerce_m53_payment_not_completed'] = 'Не завершены';
$string['commerce_m53_global_conversion'] = 'Общая конверсия: {$a->rate}% ({$a->paid} успешных платежей из {$a->attempts} попыток).';
$string['commerce_m53_deliveries_breakdown'] = 'Выдачи';
$string['commerce_m53_delivered_from_paid'] = 'Из оплаченных заказов';
$string['commerce_m53_delivered_from_free'] = 'Из бесплатных заказов';
$string['commerce_m53_delivered_from_manual'] = 'Выдано администратором';
$string['commerce_m53_acquisition_help'] = 'Эта разбивка показывает способ получения единиц и не зависит от текущего статуса платежа.';
$string['commerce_m53_product_payments'] = 'Платежи по продуктам';
$string['commerce_m53_product_payments_help'] = 'Для каждого продукта показан последний статус платежа по его заказам. Категории взаимоисключающие.';
$string['commerce_m53_product'] = 'Продукт';
$string['commerce_m53_conversion'] = 'Конверсия';

// 7.95M6 — Legacy digital identity quality and correction.
$string['admin_event_user_legacy_digital_identity_updated'] = 'Данные Legacy Digital исправлены';
$string['commerce_identity_email_quality_invalid'] = 'Некорректный email';
$string['commerce_identity_email_quality_ok'] = 'Проблем не обнаружено';
$string['commerce_identity_email_quality_suspect'] = 'Подозрительный email';
$string['commerce_identity_legacy_edit_description'] = 'Измените данные покупателя, используемые Legacy Digital покупками и будущими выборками персональных предложений.';
$string['commerce_identity_legacy_edit_detected'] = 'Обнаружена вероятная опечатка. Предлагаемый адрес: {$a}';
$string['commerce_identity_legacy_edit_scope_notice'] = 'Операция изменяет только данные Legacy Digital и никогда автоматически не меняет email аккаунта Moodle. Уже выпущенные персональные предложения остаются без изменений; при необходимости их нужно исправить или выпустить заново отдельно.';
$string['commerce_identity_legacy_edit_success'] = 'Обновлено записей Legacy Digital: {$a}.';
$string['commerce_identity_legacy_edit_title'] = 'Исправить данные Legacy Digital';
$string['commerce_identity_legacy_edit_update_same'] = 'Применить это исправление также ко всем другим Legacy Digital покупкам с точно таким же старым email';
$string['commerce_identity_legacy_quality_customer'] = 'Legacy клиент';
$string['commerce_identity_legacy_quality_description'] = 'Находите возможные ошибки в данных покупателей Legacy Digital и исправляйте email, имя или фамилию непосредственно в исходных данных.';
$string['commerce_identity_legacy_quality_diagnostic'] = 'Диагностика';
$string['commerce_identity_legacy_quality_empty'] = 'Нет адресов, соответствующих этому фильтру.';
$string['commerce_identity_legacy_quality_filter'] = 'Качество email';
$string['commerce_identity_legacy_quality_latest_purchase'] = 'Последняя покупка: #{$a}';
$string['commerce_identity_legacy_quality_notice'] = 'Проверка намеренно консервативна: она отмечает неверный синтаксис и домены, очень похожие на известные почтовые сервисы (например gmai.com → gmail.com). Неизвестные пользовательские домены не считаются ошибкой.';
$string['commerce_identity_legacy_quality_purchase'] = 'Legacy покупка';
$string['commerce_identity_legacy_quality_purchase_count'] = 'Покупок с этим email: {$a}';
$string['commerce_identity_legacy_quality_search'] = 'Поиск по email, имени или фамилии';
$string['commerce_identity_legacy_quality_suggestion'] = 'Возможное исправление: {$a}';
$string['commerce_identity_legacy_quality_title'] = 'Качество email Legacy Digital';
$string['commerce_identity_nav_legacy_quality'] = 'Качество Legacy email';

$string['commerce_identity_similarity_reason_email_domain_close'] = 'Похожий домен e-mail (возможна опечатка)';
$string['commerce_identity_similarity_reason_alternate_name'] = 'Совпадает альтернативное / фонетическое имя';
$string['commerce_identity_similarity_score_help'] = 'Оценка — это объяснимый индикатор, а не решение об автоматическом объединении. Метки показывают вклад сигналов.';

// Commerce 7.95 M7.3/M7.4 — manual merge selection and preview.
$string['commerce_identity_merge_description'] = 'Найдите и вручную выберите аккаунты Moodle, сравните их, выберите основной аккаунт и выполните только подтверждённые безопасные переносы.';
$string['commerce_identity_merge_dryrun_only'] = 'Выбор и предпросмотр не изменяют данные. Объединение выполняется только после явного подтверждения и успешного прохождения всех проверок безопасности.';
$string['commerce_identity_merge_nonmergeable'] = 'Поддерживаемый учебный прогресс и коммерческие данные будут объединены до отключения старых аккаунтов. Исторические журналы и аудит намеренно сохраняют исходные ссылки на участников; привилегированные аккаунты защищены от объединения.';
$string['commerce_identity_merge_manual_selection_title'] = 'Ручной выбор аккаунтов';
$string['commerce_identity_merge_manual_selection_help'] = 'Найдите любой аккаунт Moodle по ID, имени, фамилии, логину или e-mail. Этот выбор не зависит от механизма поиска похожих аккаунтов.';
$string['commerce_identity_merge_search_label'] = 'Найти аккаунт для добавления';
$string['commerce_identity_merge_search_placeholder'] = 'Напр. 847, natalia@example.com, Natalia Kutrowski…';
$string['commerce_identity_merge_search_results'] = 'Результаты поиска';
$string['commerce_identity_merge_search_empty'] = 'Подходящий аккаунт Moodle не найден.';
$string['commerce_identity_merge_add_account'] = 'Добавить к объединению';
$string['commerce_identity_merge_reset_selection'] = 'Сбросить выбор';
$string['commerce_identity_merge_select_two_hint'] = 'Выберите как минимум два аккаунта для предварительного просмотра объединения.';
$string['commerce_identity_merge_direction_sources'] = 'Исходный аккаунт(ы)';
$string['commerce_identity_merge_direction_target'] = 'Сохраняемый основной аккаунт';

$string['commerce_identity_merge_blocker_privileged'] = 'Аккаунт #{$a->userid} имеет привилегированные или системные права и не может участвовать в этом объединении.';
$string['commerce_identity_merge_m756_scope_title'] = 'Данные, которые будут объединены';
$string['commerce_identity_merge_m756_scope_detail'] = 'Предпросмотр обнаружил {$a->learning} учебных и {$a->commerce} коммерческих элементов в объединяемых аккаунтах. Они будут консолидированы до отключения старых аккаунтов.';
$string['commerce_identity_merge_conflicts_title'] = 'Разрешение конфликтов прогресса';
$string['commerce_identity_merge_conflicts_help'] = 'Эти элементы есть в обоих аккаунтах, но их состояния различаются. Выберите отдельно, какие данные сохранить. Однозначные данные объединяются автоматически.';
$string['commerce_identity_merge_conflict_grade'] = 'Оценка — элемент Moodle #{$a->id}';
$string['commerce_identity_merge_conflict_activity'] = 'Прогресс — активность Moodle #{$a->id}';
$string['commerce_identity_merge_conflict_recommended'] = 'Рекомендуемый выбор: аккаунт {$a}. Рекомендацию можно изменить.';
$string['commerce_identity_merge_conflict_choice'] = 'Аккаунт {$a->letter} — пользователь #{$a->userid} — значение: {$a->value}';

$string['commerce_identity_merge_certification_failed'] = 'Проверка целостности после объединения не пройдена. Изменения не были сохранены.';

$string['commerce_identity_merge_certification_title'] = 'Объединение сертифицировано';

$string['commerce_identity_merge_certification_summary'] = 'Пройдено проверок целостности: {$a->checks}. Сохранено ручных решений по учебным данным: {$a->decisions}.';

$string['commerce_identity_merge_certification_primary_account_active'] = 'Основной аккаунт активен и доступен.';

$string['commerce_identity_merge_certification_merged_account_suspended'] = 'Старых аккаунтов корректно отключено: {$a}.';

$string['commerce_identity_merge_certification_ownership_transferred'] = 'Проверок коммерческих данных пройдено: {$a}. Поддерживаемые данные больше не привязаны к старым аккаунтам.';

$string['commerce_identity_merge_certification_learning_state_transferred'] = 'Проверок учебных данных пройдено: {$a}. Поддерживаемый прогресс привязан к сохранённому аккаунту.';

$string['commerce_identity_merge_certification_manual_learning_decision_applied'] = 'Ручных решений по учебным данным применено и проверено: {$a}.';

$string['commerce_identity_merge_certification_customer_email_aligned'] = 'Проверок коммерческой идентичности пройдено: {$a}. Активные права используют email сохранённого аккаунта.';

$string['commerce_identity_merge_certification_audit'] = 'Ссылка аудита: {$a}. Детали переносов и ручные решения сохранены в истории объединения.';

$string['user360_merge_history_title'] = 'История объединений';

$string['user360_merge_history_description'] = 'Сертифицированная история аккаунтов, объединённых с этой учётной записью.';

$string['user360_merge_certified'] = 'Объединение сертифицировано';

$string['user360_merge_completed'] = 'Объединение завершено';

$string['user360_merge_retained_account'] = 'Этот аккаунт был сохранён как основной.';

$string['user360_merge_absorbed_accounts'] = 'Объединённые аккаунты:';

$string['user360_merge_absorbed_notice'] = 'Этот аккаунт был объединён с другим аккаунтом.';

$string['user360_merge_open_retained'] = 'Открыть сохранённый аккаунт';

$string['user360_merge_summary'] = 'Перенесено элементов: {$a->transfers} · ручных решений: {$a->decisions} · проверок пройдено: {$a->checks}';

$string['user360_merge_performed_by'] = 'Объединение выполнил(а): {$a}.';

$string['user360_merge_audit_reference'] = 'Ссылка аудита: {$a}';

$string['user360_merge_view_details'] = 'Посмотреть детали переноса';

$string['user360_merge_transfer_accounts'] = 'Отключённые аккаунты';

$string['user360_merge_transfer_notes'] = 'Заметки CRM';

$string['user360_merge_transfer_scores'] = 'Оценки CRM';

$string['user360_merge_transfer_inbox'] = 'Контакты Inbox';

$string['user360_merge_transfer_tags'] = 'Теги';

$string['user360_merge_transfer_tags_deduplicated'] = 'Объединённые дубликаты тегов';

$string['user360_merge_transfer_learning'] = 'Учебные данные';

$string['user360_merge_transfer_legacy'] = 'Данные Legacy';

$string['user360_merge_transfer_commerce'] = 'Данные Commerce';

// Сверка платежей Alfa.
$string['commerce_alfa_reconciliation_payment_not_found'] = 'Попытка платежа Commerce не найдена.';
$string['commerce_alfa_reconciliation_attempt_not_found'] = 'Для этой покупки не найдена попытка платежа Alfa.';
$string['commerce_alfa_reconciliation_wrong_provider'] = 'Этот платёж не относится к Alfa и не может быть обработан сверкой Alfa.';
$string['commerce_alfa_reconciliation_missing_orderid'] = 'Отсутствует идентификатор заказа Alfa; безопасная сверка платежа невозможна.';
$string['commerce_alfa_reconciliation_not_safe'] = 'Сверка Alfa отклонена: данные Alfa и Campus недостаточно совпадают: {$a}';

$string['commerce_alfa_crm_title'] = 'Сверка платежа Alfa';

$string['commerce_alfa_crm_description'] = 'Проверьте фактический статус платежа в банке перед автоматическим восстановлением оплаты, доступа и писем CampusFR.';

$string['commerce_alfa_crm_live_warning'] = 'Данные Alfa на этой странице проверяются напрямую в банке. Простое открытие страницы не изменяет данные CampusFR.';

$string['commerce_alfa_crm_provider_error'] = 'Не удалось проверить платёж в Alfa: {$a}';

$string['commerce_alfa_crm_state_complete'] = 'Платёж уже полностью обработан';

$string['commerce_alfa_crm_state_complete_help'] = 'Данные CampusFR и Alfa согласованы. Дополнительная сверка не требуется.';

$string['commerce_alfa_crm_state_reconcilable'] = 'Alfa подтверждает зачисление, платёж можно сверить';

$string['commerce_alfa_crm_state_reconcilable_help'] = 'Все проверки безопасности совпадают. CampusFR может запустить обычный процесс подтверждения платежа и восстановления доступа.';

$string['commerce_alfa_crm_state_blocked'] = 'Сверка заблокирована';

$string['commerce_alfa_crm_state_blocked_help'] = 'Как минимум одна проверка не совпадает. Никаких действий с платежом выполнено не будет.';

$string['commerce_alfa_crm_campus_section'] = 'Статус CampusFR';

$string['commerce_alfa_crm_alfa_section'] = 'Статус Alfa в реальном времени';

$string['commerce_alfa_crm_payment_id'] = 'Попытка оплаты';

$string['commerce_alfa_crm_order_id'] = 'Ссылка Alfa';

$string['commerce_alfa_crm_order_status'] = 'Статус заказа Alfa';

$string['commerce_alfa_crm_payment_state'] = 'Состояние платежа Alfa';

$string['commerce_alfa_crm_deposited_amount'] = 'Фактически зачисленная сумма';

$string['commerce_alfa_crm_checks_section'] = 'Проверки безопасности';

$string['commerce_alfa_crm_check_provider_paid'] = 'Alfa подтверждает, что платёж зачислен';

$string['commerce_alfa_crm_check_amount'] = 'Сумма Alfa совпадает с суммой CampusFR';

$string['commerce_alfa_crm_check_currency'] = 'Валюта Alfa совпадает с валютой CampusFR';

$string['commerce_alfa_crm_check_approved'] = 'Подтверждённая сумма совпадает с ожидаемой';

$string['commerce_alfa_crm_check_deposited'] = 'Зачисленная сумма совпадает с ожидаемой';

$string['commerce_alfa_crm_check_ok'] = 'Совпадает';

$string['commerce_alfa_crm_check_failed'] = 'Проверить';

$string['commerce_alfa_crm_blockers'] = 'Сверку нельзя выполнить по следующим причинам:';

$string['commerce_alfa_crm_refresh'] = 'Проверить в Alfa ещё раз';

$string['commerce_alfa_crm_execute'] = 'Сверить платёж и восстановить доступ';

$string['commerce_alfa_crm_execute_confirm'] = 'CampusFR ещё раз запросит статус в Alfa и, только если все проверки останутся успешными, запустит обычный процесс оплаты: подтвердит заказ, создаст права доступа и отправит письма. Продолжить?';

$string['commerce_alfa_crm_success'] = 'Платёж Alfa успешно сверен. Заказ и доступ CampusFR восстановлены.';

$string['commerce_alfa_crm_verify'] = 'Проверить в Alfa';

$string['commerce_alfa_crm_verify_short'] = 'Проверить Alfa';

$string['commerce_alfa_crm_purchase_panel'] = 'Платёж Alfa';

$string['commerce_alfa_crm_purchase_pending_help'] = 'Этот платёж Alfa ещё не полностью завершён в CampusFR. Можно выполнить проверку в банке в реальном времени без изменения заказа.';

$string['commerce_alfa_crm_purchase_complete_help'] = 'Этот заказ Alfa уже завершён. Проверка в реальном времени остаётся доступной для контроля согласованности с банком.';

$string['commerce_alfa_reconciliation_blocker_provider_not_paid'] = 'Alfa пока не подтверждает зачисление платежа.';

$string['commerce_alfa_reconciliation_blocker_amount_mismatch'] = 'Сумма, возвращённая Alfa, не совпадает с суммой CampusFR.';

$string['commerce_alfa_reconciliation_blocker_currency_mismatch'] = 'Валюта, возвращённая Alfa, не совпадает с валютой CampusFR.';

$string['commerce_alfa_reconciliation_blocker_approved_amount_mismatch'] = 'Сумма, подтверждённая Alfa, не совпадает с ожидаемой.';

$string['commerce_alfa_reconciliation_blocker_deposited_amount_mismatch'] = 'Фактически зачисленная сумма Alfa не совпадает с ожидаемой.';

$string['commerce_alfa_reconciliation_blocker_provider_event_not_completed'] = 'Статус Alfa не соответствует окончательно подтверждённому платежу.';

$string['task_reconcile_alfa_payments'] = 'Автоматическая сверка платежей Alfa';

$string['settings:alfa_reconciliation_header'] = 'Автоматическая сверка Alfa';

$string['settings:alfa_reconciliation_header_desc'] = 'Автоматически восстанавливает платежи, зачисленные Alfa, если возврат браузера или серверное уведомление не завершили заказ CampusFR.';

$string['settings:alfa_reconciliation_cron_enabled'] = 'Включить автоматическую сверку Alfa';

$string['settings:alfa_reconciliation_cron_enabled_desc'] = 'Задача cron проверяет в Alfa платежи CampusFR, которые всё ещё ожидают подтверждения, и завершает только те, для которых Alfa подтверждает статус, сумму и валюту.';

$string['settings:alfa_reconciliation_min_age'] = 'Минимальная задержка перед проверкой (секунды)';

$string['settings:alfa_reconciliation_min_age_desc'] = 'Минимальное время после создания платежа до автоматической проверки. Рекомендуемое значение: 300 секунд.';

$string['settings:alfa_reconciliation_max_age'] = 'Максимальный возраст проверяемого платежа (секунды)';

$string['settings:alfa_reconciliation_max_age_desc'] = 'Более старые платежи автоматически не проверяются. Рекомендуемое значение: 172800 секунд (48 часов).';

$string['settings:alfa_reconciliation_batch_size'] = 'Максимум платежей за один запуск';

$string['settings:alfa_reconciliation_batch_size_desc'] = 'Ограничивает число запросов к Alfa за один запуск cron. Рекомендуемое значение: 20.';

$string['commerce_alfa_confirmation_title'] = 'Подтверждаем ваш платёж…';

$string['commerce_alfa_confirmation_message'] = 'Обычно это занимает всего несколько секунд.';

$string['commerce_alfa_confirmation_security_title'] = 'Ваши данные защищены';

$string['commerce_alfa_confirmation_security_message'] = 'Мы напрямую проверяем транзакцию у платёжного провайдера.';

$string['commerce_alfa_confirmation_confirmed_title'] = 'Платёж подтверждён!';

$string['commerce_alfa_confirmation_confirmed_message'] = 'Доступ уже готов. Перенаправляем вас…';

$string['commerce_provider_transition_title'] = 'Подготавливаем безопасную оплату…';

$string['commerce_provider_transition_message'] = 'Ещё мгновение: открываем защищённую страницу оплаты.';

$string['commerce_provider_transition_security_title'] = 'Безопасное соединение';

$string['commerce_provider_transition_security_message'] = 'Сейчас вы будете перенаправлены к платёжному провайдеру.';

$string['commerce_provider_transition_alfa'] = 'Переход к Alfa';

$string['commerce_provider_transition_default'] = 'Переход к платёжному провайдеру';

$string['commerce_payment_splash_preview_title'] = 'Предпросмотр экранов оплаты';

$string['commerce_payment_splash_preview_outbound'] = 'Переход к провайдеру';

$string['commerce_payment_splash_preview_return'] = 'Подтверждение платежа';

$string['task_reconcile_stripe_payments'] = 'Автоматическая сверка платежей Stripe';
$string['stripe_reconciliation_heading'] = 'Автоматическая сверка Stripe';
$string['stripe_reconciliation_desc'] = 'Резервный механизм: проверяет в Stripe платежи Campus, оставшиеся в ожидании, и выдаёт доступ только для действительно оплаченных Checkout Sessions.';
$string['stripe_reconciliation_cron_enabled'] = 'Включить автоматическую сверку Stripe';
$string['stripe_reconciliation_cron_enabled_desc'] = 'Периодически проверяет ожидающие платежи Stripe. Перед выдачей доступа должны совпадать статус Stripe, сумма и валюта.';
$string['stripe_reconciliation_batch_size'] = 'Максимальный размер пакета Stripe';
$string['stripe_reconciliation_min_age'] = 'Минимальный возраст перед проверкой Stripe (сек.)';
$string['stripe_reconciliation_max_age'] = 'Максимальный возраст проверяемых платежей Stripe (сек.)';
$string['commerce_stripe_reconciliation_payment_not_found'] = 'Платёж Stripe не найден.';
$string['commerce_stripe_reconciliation_not_safe'] = 'Невозможно безопасно выполнить сверку Stripe: {$a}';
$string['commerce_stripe_reconciliation_wrong_provider'] = 'Этот платёж не относится к Stripe.';
$string['commerce_stripe_reconciliation_missing_session'] = 'В платеже отсутствует Stripe Checkout Session.';

$string['commerce_guest_unfinished_recovery_title'] = 'Мы нашли вашу незавершённую оплату';

$string['commerce_guest_unfinished_recovery_message'] = 'Ваш временный аккаунт CampusFR сохранён. Вы можете продолжить оплату без пароля; после подтверждения платежа мы предложим создать пароль.';

// 7.95M10 - Advanced personal-offer audiences.
$string['commerce_personal_offer_m10_sources_title'] = 'Дополнительные источники аудитории';
$string['commerce_personal_offer_m10_sources_help'] = 'Основной источник и добавленные здесь источники объединяются условием ИЛИ. Клиент попадает в исходную аудиторию, если найден хотя бы в одном из них.';
$string['commerce_personal_offer_m10_add_source_or'] = '+ Добавить источник ИЛИ';
$string['commerce_personal_offer_m10_filters_title'] = 'Расширенные фильтры аудитории';
$string['commerce_personal_offer_m10_filters_help'] = 'Правила внутри одной группы объединяются условием И. Группы между собой объединяются условием ИЛИ. Наличие продукта проверяется по данным Native и Legacy.';
$string['commerce_personal_offer_m10_add_rule'] = '+ Добавить правило';
$string['commerce_personal_offer_m10_add_or_group'] = '+ Добавить группу ИЛИ';
$string['commerce_personal_offer_m10_filters_example'] = 'Пример: есть карточки (Native или Legacy), И нет тренажёра Native, И нет тренажёра Legacy.';
$string['commerce_personal_offer_m10_group_first'] = 'Все правила этой группы должны выполняться (И)';
$string['commerce_personal_offer_m10_group_or'] = 'ИЛИ — группа {n}';
$string['commerce_personal_offer_m10_operator_owns'] = 'Есть продукт';
$string['commerce_personal_offer_m10_operator_not_owns'] = 'Нет продукта';
$string['commerce_personal_offer_m10_source_native_prefix'] = 'Native';
$string['commerce_personal_offer_m10_source_legacy_digital_prefix'] = 'Legacy digital';
$string['commerce_personal_offer_m10_source_legacy_plan_prefix'] = 'Legacy подписка';
$string['commerce_personal_offer_reason_advanced_rules_not_matched'] = 'Не соответствует расширенным фильтрам аудитории.';


// 7.95M11 - Personal Offer campaign collision policy.
$string['commerce_personal_offer_m11_collision_title'] = 'Уже есть активное предложение на этот товар';
$string['commerce_personal_offer_m11_collision_help'] = 'Выберите, что делать, если у участника кампании уже есть активное персональное предложение на предлагаемый товар.';
$string['commerce_personal_offer_m11_collision_skip'] = 'Исключить получателя (текущее поведение)';
$string['commerce_personal_offer_m11_collision_replace'] = 'Заменить активное предложение новым предложением этой кампании';
$string['commerce_personal_offer_m11_collision_resend'] = 'Сохранить активное предложение и отправить новое письмо кампании';
$string['commerce_personal_offer_m11_collision_warning'] = 'Предложение, связанное с незавершённым платежом, никогда не заменяется. При замене старая ссылка отзывается, а история сохраняется.';
$string['commerce_personal_offer_reason_m11_will_replace'] = 'Подходит: существующее активное предложение будет заменено предложением этой кампании.';
$string['commerce_personal_offer_reason_m11_will_resend'] = 'Подходит: существующее активное предложение будет сохранено и использовано в новом письме.';
$string['commerce_personal_offer_reason_m11_reused'] = 'Существующее активное предложение повторно использовано для новой рассылки.';
$string['commerce_personal_offer_reason_m11_payment_in_progress'] = 'Предложение не заменено: по нему сейчас выполняется платёж.';
$string['crm_commerce_nav_unfinished_checkouts'] = 'Незавершённые оформления заказа';

// M9.3 / M9.4 — Guest checkout recovery CRM and safe cleanup.
$string['commerce_guest_crm_title'] = 'Незавершённые гостевые оформления';
$string['commerce_guest_crm_help'] = 'Диагностика и восстановление временных аккаунтов, для которых покупка не была завершена.';
$string['commerce_guest_crm_empty'] = 'Нет незавершённых гостевых оформлений, требующих обработки.';
$string['commerce_guest_crm_filter_all'] = 'Все';
$string['commerce_guest_crm_class_pending_purchase'] = 'Платёж ожидается';
$string['commerce_guest_crm_class_multiple_pending'] = 'Несколько ожидающих покупок';
$string['commerce_guest_crm_class_stuck_identity'] = 'Учётная запись заблокирована';
$string['commerce_guest_crm_class_provisional_no_purchase'] = 'Временный аккаунт без покупки';
$string['commerce_guest_crm_class_provider_paid_pending'] = 'Оплачено у провайдера';
$string['commerce_guest_crm_source_summary'] = 'Исходная сессия #{$a->session} ({$a->status}) · продолжение {$a->purchase} · заблокированных сессий {$a->stuck}';
$string['commerce_guest_crm_user360'] = 'Открыть User360';
$string['commerce_guest_crm_repair'] = 'Исправить сессии';
$string['commerce_guest_crm_pending_purchases'] = 'Ожидающие покупки';
$string['commerce_guest_crm_open_purchase'] = 'Открыть покупку';
$string['commerce_guest_crm_use_for_resume'] = 'Использовать для продолжения';
$string['commerce_guest_crm_current_resume'] = 'Текущее продолжение';
$string['commerce_guest_crm_check_provider'] = 'Проверить / сверить у провайдера';
$string['commerce_guest_crm_no_purchase_help'] = 'Этот временный аккаунт не создал покупку. Платёж нельзя создавать искусственно; аккаунт можно сохранить или очистить после настроенного срока.';
$string['commerce_guest_crm_action_repaired'] = 'Заблокированные сессии исправлены.';
$string['commerce_guest_crm_action_resume_selected'] = 'Эта покупка выбрана для продолжения checkout.';
$string['commerce_guest_crm_action_reconciled'] = 'Платёж проверен и сверён.';
$string['commerce_guest_crm_candidate_not_found'] = 'Этот аккаунт больше не является незавершённым гостевым checkout.';
$string['commerce_guest_crm_source_not_found'] = 'Исходная гостевая сессия не найдена.';
$string['commerce_guest_crm_provider_not_supported'] = 'Этот провайдер не поддерживает автоматическую CRM-сверку.';
$string['commerce_guest_crm_source_session'] = 'Исходная сессия';
$string['commerce_guest_crm_resume_purchase'] = 'Покупка для продолжения';
$string['commerce_guest_crm_stuck_sessions'] = 'Заблокированные сессии';
$string['commerce_guest_crm_open_case'] = 'Обработать незавершённый checkout';
$string['user360_guest_checkout_recovery_title'] = 'Незавершённый гостевой checkout';
$string['user360_guest_checkout_recovery_description'] = 'Диагностика и восстановление временного аккаунта.';
$string['task_cleanup_abandoned_guest_checkouts'] = 'Очистка старых брошенных гостевых checkout';
$string['settings:guest_checkout_cleanup_header'] = 'Очистка брошенных Guest Checkout';
$string['settings:guest_checkout_cleanup_header_desc'] = 'Удаляются только старые временные аккаунты без покупок и бизнес-активности. По умолчанию отключено.';
$string['settings:guest_checkout_cleanup_enabled'] = 'Включить автоматическую очистку';
$string['settings:guest_checkout_cleanup_enabled_desc'] = 'По умолчанию отключено. Cron обрабатывает только checkout_* аккаунты без подтверждения, технически заблокированные, без покупок, доступов, grant, записей на курсы и предложений.';
$string['settings:guest_checkout_cleanup_age_days'] = 'Минимальный возраст перед очисткой (дни)';
$string['settings:guest_checkout_cleanup_age_days_desc'] = 'Более новый временный аккаунт никогда не удаляется.';
$string['settings:guest_checkout_cleanup_batch_size'] = 'Максимальный размер пакета очистки';
$string['settings:guest_checkout_cleanup_batch_size_desc'] = 'Максимальное число аккаунтов за один запуск.';

$string['admin_event_user_legacy_digital_linked'] = 'Покупка Legacy Digital привязана к аккаунту Moodle';
$string['admin_event_commerce_personal_offer_beneficiary_corrected'] = 'Получатель персонального предложения исправлен';

// M9.3a — live provider status in unfinished checkout CRM.
$string['commerce_guest_crm_provider_live_status'] = 'Провайдер: {$a->status}';
$string['commerce_guest_crm_provider_paid_pending'] = 'Оплачено у провайдера — требуется сверка';
$string['commerce_guest_crm_provider_probe_unavailable'] = 'Статус провайдера недоступен';

// M11.1 — optional secondary campaign CTA.
$string['commerce_personal_offer_campaign_email_secondary_cta_label'] = 'Текст дополнительной кнопки';
$string['commerce_personal_offer_campaign_email_secondary_cta_url'] = 'URL дополнительной кнопки';
$string['commerce_personal_offer_campaign_email_secondary_cta_help'] = 'Необязательно. Заполните текст и URL вместе, затем вставьте {{secondary_cta}} точно в то место маркетингового текста, где должна появиться кнопка. Без этого маркера дополнительная кнопка не отображается.';
// M9.2b — resilient public Commerce access errors.
$string['commerce_public_access_denied'] = 'Эта страница больше недоступна из текущей сессии. Вы можете продолжить в своём пространстве CampusFR.';
$string['commerce_mail_delivery_accepted'] = 'Письмо принято почтовым транспортом';
$string['commerce_mail_delivery_sent_at'] = 'Отправлено: {$a}';
$string['commerce_mail_delivery_attempts'] = 'Попыток: {$a}';
$string['commerce_mail_delivery_transport_smtp'] = 'SMTP-транспорт: {$a}';
$string['commerce_mail_delivery_transport_local'] = 'Локальный почтовый транспорт';
$string['commerce_mail_delivery_disclaimer'] = 'Этот статус подтверждает, что CampusFR успешно передал письмо настроенному почтовому транспорту. Он не гарантирует доставку письма во входящие получателя.';

// M13 — Identity UX, relationship graph and preferred login identity transfer.
$string['commerce_personal_offer_identity_conflict_title'] = 'Это предложение связано с другим аккаунтом';
$string['commerce_personal_offer_identity_conflict_message'] = 'Персональное предложение было отправлено на {$a->offeremail}, но сейчас вы вошли как {$a->currentemail}. В целях безопасности предложение нельзя использовать с текущим аккаунтом.';
$string['commerce_personal_offer_identity_conflict_continue'] = 'Выйти и продолжить с этим предложением';
$string['commerce_personal_offer_identity_conflict_cancel'] = 'Остаться в текущем аккаунте';
$string['commerce_identity_nav_relationships'] = 'Связи идентичностей';
$string['commerce_identity_relationships_title'] = 'Связи идентичностей и история email';
$string['commerce_identity_relationships_description'] = 'Просматривайте известные исторические адреса и потенциальные связи аккаунтов для ручной проверки. Никакие аккаунты не связываются и не объединяются автоматически.';
$string['commerce_identity_relationships_userid'] = 'ID пользователя Moodle';
$string['commerce_identity_relationships_inspect'] = 'Проверить идентичность';
$string['user360_identity_graph_title'] = 'Email и идентичности';
$string['user360_identity_graph_help'] = 'Известная история email и потенциально связанные аккаунты. Потенциальные совпадения служат только сигналом для ручной проверки.';
$string['user360_identity_current'] = 'Текущий';
$string['user360_identity_historical'] = 'Исторический';
$string['user360_identity_potential_title'] = 'Потенциально связанные аккаунты';
$string['user360_identity_potential_help'] = 'Эти аккаунты не связаны. Совпадение может быть случайным — всегда проверяйте личность вручную.';
$string['user360_identity_source_moodle_current'] = 'Текущий аккаунт Moodle';
$string['user360_identity_source_commerce_purchase'] = 'Покупка Commerce';
$string['user360_identity_source_legacy_digital'] = 'Legacy digital покупка';
$string['user360_identity_source_personal_offer'] = 'Персональное предложение';
$string['user360_identity_source_merged_account'] = 'Объединённый аккаунт';
$string['user360_identity_source_merge_identity_history'] = 'История идентичности при объединении';
$string['commerce_identity_merge_preferred_identity_title'] = 'Данные для входа после объединения';
$string['commerce_identity_merge_preferred_identity_help'] = 'Сохраните рекомендованный аккаунт с учебной историей, но при необходимости перенесите email и логин другого сравниваемого аккаунта.';
$string['commerce_identity_merge_preferred_identity_choice'] = '#{$a->userid} — {$a->email} · логин {$a->username}';
$string['commerce_identity_merge_preferred_identity_safety'] = 'Username и email меняются местами независимо от выбора пароля ниже. Сохранённый аккаунт сохраняет Moodle ID, учебную историю, имя и данные Commerce. Поглощённый аккаунт блокируется и получает прежние данные входа сохранённого аккаунта.';
$string['commerce_identity_merge_preferred_identity_invalid'] = 'Запрошенные данные для входа нельзя безопасно перенести.';
$string['commerce_identity_merge_preferred_password_title'] = 'Пароль после объединения';
$string['commerce_identity_merge_preferred_password_help'] = 'Явно выберите, пароль какого аккаунта будет использовать сохранённый аккаунт Moodle. По умолчанию сохраняется его собственный пароль.';
$string['commerce_identity_merge_preferred_password_choice'] = 'Сохранить пароль аккаунта #{$a->userid} — {$a->email}';
$string['commerce_identity_merge_preferred_password_unavailable'] = 'недоступно для внешней аутентификации';
$string['commerce_identity_merge_preferred_password_safety'] = 'Перенос пароля возможен только между двумя аккаунтами с ручной аутентификацией Moodle. Хеши паролей никогда не отображаются и не записываются в аудит объединения.';
$string['commerce_identity_merge_preferred_password_invalid'] = 'Выбранный владелец пароля недопустим для этого объединения.';
$string['commerce_identity_merge_preferred_password_manual_only'] = 'Пароль можно перенести только между двумя аккаунтами с ручной аутентификацией Moodle.';
$string['user360_merge_identity_transfer'] = 'Данные для входа изменены при объединении: {$a->oldemail} → {$a->newemail} (идентичность взята из аккаунта #{$a->sourceuserid}).';
$string['commerce_identity_relationships_email'] = 'Email (для Legacy/Commerce идентичностей без аккаунта)';
$string['user360_identity_source_external_current'] = 'Внешняя / Commerce идентичность без аккаунта';


// Identity merge final-state preview.
$string['commerce_identity_merge_final_state_title'] = 'Итоговое состояние после объединения';
$string['commerce_identity_merge_final_state_help'] = 'Проверьте, какой аккаунт останется активным, какие данные для входа он получит и какие данные будут сохранены или объединены.';
$string['commerce_identity_merge_final_retained_title'] = 'Аккаунт, который останется активным';
$string['commerce_identity_merge_final_retained_help'] = 'Именно этим аккаунтом клиент будет пользоваться после объединения.';
$string['commerce_identity_merge_final_absorbed_title'] = 'Поглощаемые и заблокированные аккаунты';
$string['commerce_identity_merge_final_absorbed_help'] = 'После объединения клиент больше не сможет использовать эти аккаунты.';
$string['commerce_identity_merge_final_active_badge'] = 'АКТИВЕН';
$string['commerce_identity_merge_final_suspended_badge'] = 'ЗАБЛОКИРОВАН';
$string['commerce_identity_merge_final_identity_title'] = 'Итоговые данные аккаунта';
$string['commerce_identity_merge_final_moodle_id'] = 'Moodle ID';
$string['commerce_identity_merge_final_name'] = 'Имя аккаунта';
$string['commerce_identity_merge_final_email'] = 'Email для входа';
$string['commerce_identity_merge_final_username'] = 'Логин (username)';
$string['commerce_identity_merge_final_status'] = 'Статус';
$string['commerce_identity_merge_final_status_value'] = 'Активен · сохранённый аккаунт';
$string['commerce_identity_merge_final_learning_title'] = 'Учебные данные';
$string['commerce_identity_merge_final_courses'] = 'текущих записей на курсы';
$string['commerce_identity_merge_final_completed_courses'] = 'текущих завершённых курсов';
$string['commerce_identity_merge_final_activities'] = 'текущих завершённых активностей';
$string['commerce_identity_merge_final_grades'] = 'текущих оценок';
$string['commerce_identity_merge_final_grades_value'] = '{$a->count} · среднее {$a->average}%';
$string['commerce_identity_merge_final_learning_consolidation'] = 'Будет объединено ещё {$a} учебных записей из поглощаемых аккаунтов. Дубликаты и конфликты обрабатываются согласно выбранным ниже решениям, поэтому итоговые счётчики могут измениться.';
$string['commerce_identity_merge_final_learning_unchanged'] = 'На поглощаемых аккаунтах дополнительных учебных данных не найдено: эти показатели останутся без изменений.';
$string['commerce_identity_merge_final_commerce_title'] = 'Итоговые данные Commerce';
$string['commerce_identity_merge_final_purchases'] = 'покупок';
$string['commerce_identity_merge_final_grants'] = 'прав доступа';
$string['commerce_identity_merge_final_digital'] = 'цифровых доступов';
$string['commerce_identity_merge_final_guests'] = 'гостевых сессий';
$string['commerce_identity_merge_final_commerce_consolidated'] = 'Все поддерживаемые данные Commerce будут привязаны к сохранённому аккаунту.';
$string['commerce_identity_merge_final_after_merge'] = 'Статус после объединения';
$string['commerce_identity_merge_final_absorbed_status'] = 'Заблокирован · недоступен';
$string['commerce_identity_merge_final_absorbed_identity_swap_note'] = 'Данные для входа этого аккаунта будут перенесены в сохранённый аккаунт. Взамен он получит прежние идентификаторы сохранённого аккаунта, чтобы освободить выбранные email и логин.';
$string['commerce_identity_merge_final_absorbed_regular_note'] = 'Этот аккаунт будет заблокирован после объединения поддерживаемых данных.';
$string['commerce_identity_merge_final_transferred_from'] = 'перенесено из #{$a}';
$string['commerce_identity_merge_final_replaced_by_target'] = 'прежние данные #{$a}';
$string['commerce_identity_merge_final_sentence'] = 'Результат: клиент будет входить с адресом {$a->email} в Moodle-аккаунт #{$a->userid}. В нём сохранится текущая учебная история ({$a->courses} курсов, {$a->activities} завершённых активностей, {$a->grades} оценок) и будет собрано {$a->purchases} покупок Commerce. {$a->sources} исходных аккаунтов будут заблокированы.';
$string['commerce_identity_merge_technical_title'] = 'Полная техническая информация';
$string['commerce_identity_merge_technical_badge'] = 'Скрыто по умолчанию';
$string['commerce_identity_merge_technical_help'] = 'Показывает идентификаторы до/после, обнаруженные объёмы данных и параметры объединения для проверки или аудита.';
$string['commerce_identity_merge_warning_different_emails_transfer'] = 'В аккаунтах используются разные email. Выбранные данные для входа будут перенесены в основной аккаунт, поэтому его текущий адрес больше не останется email для входа.';
$string['commerce_identity_merge_final_confirmation'] = 'Вы собираетесь сохранить Moodle-аккаунт #{$a->userid}, установить {$a->email} как итоговый email для входа и заблокировать {$a->sources} исходных аккаунтов. Перед подтверждением ещё раз проверьте итоговое состояние выше.';

// Detailed irreversible merge preview.
$string['commerce_identity_merge_technical_help_detailed'] = 'Проверьте точный список курсов, покупок, Legacy-записей, прав доступа и изменений идентичности, которые будут объединены. Эти данные доступны только для чтения и показывают текущее состояние до подтверждения.';
$string['commerce_identity_merge_technical_courses'] = 'Курсы и история обучения';
$string['commerce_identity_merge_technical_purchases'] = 'История покупок Commerce';
$string['commerce_identity_merge_technical_legacy'] = 'Legacy-подписки и цифровые покупки';
$string['commerce_identity_merge_technical_rights'] = 'Права и цифровые доступы';
$string['commerce_identity_merge_technical_identity_audit'] = 'Идентичность и аудит объединения';
$string['commerce_identity_merge_detail_origin'] = 'Исходный аккаунт';
$string['commerce_identity_merge_detail_course'] = 'Курс';
$string['commerce_identity_merge_detail_enrolment'] = 'Запись на курс';
$string['commerce_identity_merge_detail_completion'] = 'Завершение';
$string['commerce_identity_merge_detail_activity_grade'] = 'Активности / оценки';
$string['commerce_identity_merge_detail_decision'] = 'Результат объединения';
$string['commerce_identity_merge_detail_purchase'] = 'Покупка';
$string['commerce_identity_merge_detail_product'] = 'Продукт / идентичность';
$string['commerce_identity_merge_detail_status_amount'] = 'Статус / сумма';
$string['commerce_identity_merge_detail_legacy_link'] = 'Связь Legacy';
$string['commerce_identity_merge_detail_record'] = 'Запись';
$string['commerce_identity_merge_detail_period_access'] = 'Период / доступ';
$string['commerce_identity_merge_detail_status'] = 'Статус';
$string['commerce_identity_merge_detail_none'] = 'Подходящих записей не найдено.';
$string['commerce_identity_merge_detail_already_target'] = 'Уже принадлежит сохраняемому аккаунту; будет сохранено без изменений.';
$string['commerce_identity_merge_detail_course_consolidate'] = 'Также присутствует в сохраняемом аккаунте; данные обучения будут объединены по существующим правилам разрешения конфликтов.';
$string['commerce_identity_merge_detail_course_transfer'] = 'Будет перенесено в сохраняемый аккаунт.';
$string['commerce_identity_merge_detail_purchase_transfer'] = 'userid покупки будет переназначен на сохраняемый аккаунт #{$a}.';
$string['commerce_identity_merge_detail_legacy_transfer'] = 'Legacy-запись будет переназначена на сохраняемый аккаунт #{$a}.';
$string['commerce_identity_merge_detail_right_transfer'] = 'Получатель права/доступа будет переназначен на сохраняемый аккаунт #{$a}.';
$string['commerce_identity_merge_detail_enrolment_value'] = '{$a->status} · с {$a->date}';
$string['commerce_identity_merge_detail_completed_on'] = 'Завершено: {$a}';
$string['commerce_identity_merge_detail_not_completed'] = 'Не завершено';
$string['commerce_identity_merge_detail_activity_grade_value'] = 'выполнено активностей: {$a->activities} · оценок: {$a->grades} · средняя {$a->average} · последний доступ {$a->lastaccess}';
$string['commerce_identity_merge_detail_download_value'] = 'Скачивания {$a->count}/{$a->max}';

$string['commerce_identity_merge_detail_course_roles'] = 'Роль(и): {$a->roles} · период: {$a->start} → {$a->end}';

$string['commerce_personal_offer_campaign_footer_title'] = 'Финальное изображение';
$string['commerce_personal_offer_campaign_footer_help'] = 'Необязательное изображение кампании. Загрузите его здесь и поставьте {{image}} именно в том месте текста письма, где оно должно появиться. Без {{image}} новый редактор его не выводит.';
$string['commerce_personal_offer_campaign_footer_delete'] = 'Удалить текущее финальное изображение';
$string['commerce_personal_offer_campaign_footer_upload_error'] = 'Не удалось загрузить финальное изображение кампании.';
$string['commerce_personal_offer_campaign_footer_too_large'] = 'Размер финального изображения кампании не должен превышать 8 МБ.';
$string['commerce_personal_offer_campaign_footer_invalid_type'] = 'Финальное изображение должно быть в формате JPEG, PNG или WebP.';
$string['commerce_showroom_config_social_image'] = 'Изображение для публикации';
$string['commerce_showroom_config_social_image_help'] = 'Это изображение используется в превью Telegram, WhatsApp, Facebook и других соцсетей. Если оно не задано, витрина автоматически использует изображение одного из предложений.';
$string['commerce_showroom_config_social_image_choose'] = 'Выбрать изображение';
$string['commerce_showroom_config_social_image_format_help'] = 'PNG, JPG или WebP, до 20 МБ. Рекомендуемый размер: 1200 × 630 пикселей.';
$string['commerce_showroom_config_social_image_remove'] = 'Удалить пользовательское изображение для публикации';
$string['commerce_showroom_promotion_until_short'] = 'До {$a->date}, {$a->time}';
