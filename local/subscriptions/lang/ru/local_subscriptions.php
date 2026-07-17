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