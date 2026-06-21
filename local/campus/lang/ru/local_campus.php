<?php
$string['pluginname']     = 'Campus — Страница курса';
$string['view_trial']     = 'Перейти к пробному курсу';
$string['view_real']      = 'Перейти к курсу';
$string['course_hidden']  = 'Этот курс скрыт от вас.';
$string['course_notfound']= 'Курс не найден.';
$string['subscribe_now'] = 'Купить сейчас';
$string['close'] = 'Закрыть';

$string['set_trialcourses'] = 'Пробные курсы (ID через запятую)';
$string['set_trialcourses_desc'] = 'Укажите ID пробных курсов, например: 12,34,56.';
$string['set_trialdays'] = 'Длительность пробного периода (дней)';
$string['set_trialrole'] = 'Роль для пробных аккаунтов';
$string['set_trialrole_desc'] = 'Короткое имя роли (например, trialstudent). Установщик создаст роль по умолчанию.';
$string['set_deleteafterdays'] = 'Удаление пробных аккаунтов (дней после истечения, 0 = никогда)';
$string['set_deleteafterdays_desc'] = 'Сколько дней после окончания пробного периода хранить аккаунт перед удалением (0 = никогда).';
$string['set_trialusernameprefix'] = 'Префикс имени пользователя для пробных аккаунтов';
$string['set_trialusernameprefix_desc'] = 'Напр., trial_ → получится trial_jdoe';
$string['set_trialemailprefix'] = 'Префикс e-mail для пробных аккаунтов';
$string['set_trialemailprefix_desc'] = 'Напр., trial+ → получится trial+jdoe@… (или trial+md5@принудительном домене)';
$string['set_trialemaildomain'] = 'Принудительный домен e-mail (необязательно)';
$string['set_trialemaildomain_desc'] = 'Оставьте пустым, чтобы сохранить исходный домен; иначе укажите новый (напр., noreply.campusfr.invalid).';

$string['rolename_trialstudent'] = 'Студент (пробный)';
$string['roledesc_trialstudent'] = 'Роль только для просмотра для пробного доступа.';
$string['cron_trial_maint'] = 'Campus — Напоминания и очистка пробных доступов';

$string['trial_popup_title'] = 'Попробуйте первый урок бесплатно!';
$string['trial_popup_lead']  = 'Без ограничения по времени.<br>Без банковской карты.<span class="hero-emoji">🛡</span>';
$string['trial_popup_tos']   = 'Я принимаю Условия использования и Политику конфиденциальности.';
$string['trial_popup_accept']= 'Пожалуйста, подтвердите согласие с условиями.';
$string['trial_firstname']   = 'Имя';
$string['trial_lastname']    = 'Фамилия';
$string['trial_email']       = 'E-mail';
$string['trial_btn_continue']= 'Начать обучение';
$string['trial_btn_subscribe']= 'Купить курс';
$string['trial_expired_msg'] = 'Ваш пробный период завершён. Купите курс, чтобы продолжить.';
$string['trial_tos_html'] =
    'Создавая аккаунт, вы принимаете <a href="{$a->policyurl}" target="_blank" rel="noopener">Политику конфиденциальности</a> '
    .'и <a href="{$a->termsurl}" target="_blank" rel="noopener">Условия использования</a>.';
$string['trial_footer_note'] =
    "Бесплатный и безлимитный доступ к первому уроку каждого курса.";
$string['trial_firstname_ph'] = 'Ваше имя';
$string['trial_lastname_ph']  = 'Ваша фамилия';
$string['trial_email_ph']     = 'Ваш e-mail';


$string['mail_trial_started_subject'] = 'Ваш пробный период начался';
$string['mail_trial_started_body']    = 'Здравствуйте, {$a->firstname}, ваш 7-дневный пробный период начался!';
$string['mail_trial_rem3_subject']    = 'Напоминание: пробный период продолжается — {$a}';
$string['mail_trial_rem3_body']       = '<p>Bonjour, {$a->firstname} !</p> 
<p>Надеемся, ваши первые дни в Campus<small><sup>FR</sup></small> проходят интересно и с пользой.</p> 

<p>Если вы захотите продолжить обучение, скидка {$a->dpct}% на все подписки будет доступна для вас ещё 24 часа.</p>

<p>Дальше будет действовать обычная цена.</p>

<p>Оформить подписку по сниженной цене можно здесь:</p>';
$string['mail_trial_rem3_body2']       = '<p>После активации подписки вы сразу получите полный доступ ко всем урокам и будущим обновлениям — всему, что помогает учить французский уверенно, системно и с удовольствием. </p>

<p>Если появятся вопросы — просто напишите нам на <a href="mailto:{$a}">{$a}</a>. Мы всегда рядом.</p>

<p>À très bientôt,<br>
<p>Ната и команда Campus<small><sup>FR</sup></small></p>';
$string['mail_trial_rem3_button']       = 'Оформить подписку со скидкой {$a}%';
$string['mail_trial_expired_subject'] = 'Ваш пробный период истёк — {$a}';
$string['mail_trial_expired_body']    = 'Здравствуйте, {$a->firstname}, ваш 7-дневный пробный период завершился.';
$string['mail_trial_cta_subscribe']   = 'Оформить подписку';
$string['mail_trial_cta_continue']    = 'Продолжить пробный доступ';
$string['mail_trial_rem3_subject_generic']    = '⏳ Ещё 24 часа на подписку со скидкой {$a}% 🇫🇷';
$string['mail_trial_expired_subject_generic'] = 'Ваш пробный период истёк';

$string['cataloguetitle'] = 'Каталог';
$string['catalogueheading'] = 'Курсы по уровням';
$string['cataloguesub'] = 'Просмотрите наши курсы';
$string['moreinfo'] = 'Подробнее';
$string['trial_access_label'] = 'Перейти к пробному курсу';
$string['cta_connected'] = 'Перейти к курсу';
$string['nocoursesconfigured'] = 'Нет курсов, настроенных для отображения.';
$string['set_subscribercourses'] = 'Курсы для подписчиков (ID, через запятую)';
$string['set_subscribercourses_desc'] = 'Курсы видны только подписчикам. Посетители и пользователи на пробном доступе их не видят.';
$string['back_to_all_courses'] = '← Вернуться ко всем курсам';

$string['tab_catalogue'] = 'Каталог';
$string['tab_mycourses'] = 'Мои курсы';
$string['mycourses_title'] = 'Мои курсы';
$string['mycourses_sub'] = 'Обзор ваших курсов';
$string['mycourses_empty'] = 'Вы пока не записаны ни на один курс.';
$string['mycourses_browse'] = 'Перейти в каталог';
$string['cta_connected'] = 'Продолжить';
$string['cta_connected_start'] = 'Начать';
$string['cta_connected_resume'] = 'Продолжить';
$string['completed'] = 'выполнено';
$string['completed_badge'] = 'Завершено';
$string['notenrolled'] = 'Не записан(а)';
$string['course_not_started'] = 'Вы ещё не начали этот курс';
$string['resume_here'] = 'Продолжить с последнего места';
$string['congrats_completed'] = 'Поздравляем! Вы завершили этот курс.';
$string['browse_catalog'] = 'Перейти в каталог';
$string['access_trial_courses'] = 'Доступ к пробным курсам';
$string['subscribe_now'] = 'Купить сейчас';
$string['mycourses_empty'] = 'Войдите, чтобы получить доступ к вашим курсам. Вы также можете купить новые курсы.';
$string['no_courses_banner_title'] = 'В данный момент нет доступных курсов.';
$string['no_courses_banner_text']  = 'Просмотрите каталог и выберите курс.';
$string['login_now']               = 'Войти';
$string['browse_catalog']          = 'Перейти в каталог';
$string['access_trial_courses']    = 'Открыть пробные курсы';
$string['subscribe_now']           = 'Купить сейчас';
$string['hint_go_to_header_cta']   = 'Купить или войти здесь';

$string['admin_native_page'] = 'Стандартная страница Moodle';
$string['admin_show_hidden'] = 'Показать также скрытые курсы';
$string['admin_hide_hidden'] = 'Скрыть скрытые курсы';

$string['trial_password']      = 'Пароль';
$string['trial_password_ph']   = 'Придумайте пароль';
$string['trial_password_help'] = 'Минимум 8 символов. Этот пароль понадобится для входа.';
$string['trial_password_min']  = 'Пароль должен содержать не менее 8 символов.';
$string['trial_password_policy_error'] = 'Пароль не соответствует политике безопасности. {$a}';
$string['emailalreadysubscribed']='Этот адрес уже связан с аккаунтом. Пожалуйста, войдите, чтобы начать пробный период.';
$string['trial_already_subscribed_html'] =
    'У вас уже есть активная подписка. Пожалуйста, <a href="{$a->login}" class="link-primary" target="_top" rel="noopener">войдите</a>.';
$string['trial_expired_html'] =
    'Пробный период завершён. <a href="{$a->subscribe}" class="link-primary" rel="noopener" data-subs-modal="1">Оформите подписку</a>, чтобы продолжить.';
$string['trial_discount_banner_title'] = 'Скидка −{$a}% для пробного доступа.';
$string['trial_discount_banner_body']  = 'Осталось: ';
$string['trial_discount_banner_cta']   = 'Купить курс';
$string['trial_days_word']             = 'дн.';

$string['trial_banner_reminder_title'] = 'Напоминание о пробном доступе.';
$string['trial_banner_reminder_body']  = 'Ваш пробный доступ заканчивается {$a}. Чтобы продолжить, оформите подписку.';
$string['trial_banner_expired_html']   = 'Пробный доступ истёк <strong>{$a->date}</strong>. <a href="{$a->url}" class="link-primary" data-subs-modal="1">Оформите подписку</a>, чтобы продолжить.';

$string['sub_expiry_banner'] = 'Ваша подписка « {$a->plan} » истекает <strong>{$a->date}</strong> (через {$a->days} дн.).';
$string['login_suspended_html'] = 'Ваша учётная запись <strong>заблокирована</strong> (пробный период завершён). '
    .'Пожалуйста, <a class="link-primary" href="{$a->link}">оформите подписку</a>, чтобы восстановить доступ.';

$string['set_trialdays'] = 'Длительность пробного периода (дни)';
$string['set_trialdays_desc'] = 'Количество дней бесплатного доступа (J). По умолчанию: 7.';

$string['set_trial_suspend_after_days'] = 'Блокировка учётной записи (J + N дней)';
$string['set_trial_suspend_after_days_desc'] = 'Через сколько дней после окончания пробного периода (J) блокировать учётную запись (пользователь не сможет войти). По умолчанию: 30.';

$string['set_trial_delete_after_days'] = 'Удаление учётной записи (J + N дней)';
$string['set_trial_delete_after_days_desc'] = 'Через сколько дней после окончания пробного периода (J) безвозвратно удалять учётную запись (если нет другой активной подписки). По умолчанию: 90.';

$string['mail_trial_discount_line'] = 'Действует скидка <strong>{$a->pct}%</strong> до <strong>{$a->date}</strong>.';

$string['trial_presuspend_subject'] = 'Скоро ваш пробный аккаунт будет заблокирован';
$string['trial_presuspend_body']    = 'Здравствуйте, {$a->firstname}!<br>Ваш пробный аккаунт будет заблокирован <strong>{$a->date}</strong>. '
    .'Чтобы сохранить доступ, оформите подписку уже сейчас.';

$string['trial_suspended_subject']  = 'Ваш пробный аккаунт заблокирован';
$string['trial_suspended_body']     = 'Здравствуйте, {$a->firstname}!<br>Ваш пробный аккаунт был заблокирован <strong>{$a->sdate}</strong>. '
    .'Без действий он будет удалён <strong>{$a->ddate}</strong>. '
    .'Вы можете восстановить доступ, оформив подписку.';

$string['mail_trial_expired_hint_suspend'] = 'Учётная запись останется активной (вход возможен) до <strong>{$a}</strong>. '
    .'Чтобы снова получить доступ к курсам, оформите подписку.';

$string['myaccompt'] = 'Мой счет';

// Подтверждение пароля + «глаз»
$string['trial_password_confirm']      = 'Подтвердите пароль';
$string['trial_password_confirm_ph']   = 'Повторите пароль';
$string['trial_password_confirm_help'] = 'Введите тот же самый пароль ещё раз для подтверждения.';
$string['trial_password_toggle']       = 'Показать или скрыть пароль';
$string['trial_password_mismatch']     = 'Пароли не совпадают.';

// Начало пробного периода – дополнительные данные
$string['mail_trial_started_credentials'] = 'Вот ваши данные для входа:<br>
Имя пользователя: {$a->username}<br>
Пароль: {$a->password}<br>
Войти можно по этой ссылке: <a href="{$a->login_url}">Войти в Campus<small><sup>FR</sup></small></a>.';

$string['mail_trial_started_mycourses'] = 'Ко всем пробным курсам вы можете перейти здесь:
<a href="{$a->mycourses_url}">Мои курсы</a>.';

// Таблица с данными для входа в пробный период
$string['trial_username_label'] = 'E-mail для входа';
$string['trial_password_label'] = 'Пароль';
$string['mail_trial_security_hint'] = 'Пожалуйста, держите эти данные в секрете. Для безопасности вы можете в любой момент сменить пароль в настройках аккаунта CampusFR.';
$string['mail_trial_started_mycourses'] = 'Вы можете в любое время открыть свои пробные курсы в личном кабинете Campus<small><sup>FR</sup></small>.';
$string['course_progress_ratio'] = '{$a->done} / {$a->total} выполненных элементов';

$string['trial_phone']      = 'Телефон';
$string['trial_phone_ph']   = 'Ваш номер телефона';
$string['trial_phone_help'] = 'Телефон нужен, чтобы мы могли быстро ответить на ваши вопросы и помочь вам начать обучение.';
$string['trial_phone_label'] = 'Телефон';
$string['mail_trial_reset_hint'] = '<p>Если Вы забыли пароль, перейдите по этой ссылке для восстановления.</p>' .
'👉 <a href="{$a->url}">Восстановить пароль</a></p>';

// Тема письма
$string['mail_trial_started_subject'] = 'Вы создали аккаунт Campus<small><sup>FR</sup></small> 🎉';

// Основной текст
$string['mail_trial_started_body'] =
    '<p>Salut, {$a->firstname}!</p>' .
    '<p>Добро пожаловать в школу французского языка Campus<small><sup>FR</sup></small> — Ваш аккаунт успешно создан.</p>';

// Абзац про "Mes cours"
$string['mail_trial_started_mycourses'] =
    '<p>Мы уже открыли первый бесплатный урок, пройдите его сегодня и сделайте шаг навстречу своим целям!</p>' .
'👉 <a href="{$a->url}">Начать обучение</a></p>';

$string['mail_trial_desc'] =
    '<p>В Campus<small><sup>FR</sup></small> вас ждут: </p>' .
    '<ul><li>понятные видеоуроки по грамматике</li>' .
    '    <li>практика с настоящим французом</li>' .
    '    <li>задания с мгновенной проверкой</li>' .
    '</ul>' .
    '<p>Французский язык кажется сложным только на первый взгляд. <br/>' .
    'На уроках Наты Кутровски вы быстро поймёте его логику и начнёте получать удовольствие от обучения.</p>' .
    '<p>Уделяйте занятиям всего по 20 минут в день и прогресс не заставит себя ждать.</p>' .
    '<p>До встречи на уроках!</p>' .
    '<p>С уважением, <br/>' .
    'команда Campus<small><sup>FR</sup></small>.</p>';

// Строка про скидку
$string['mail_trial_discount_line'] =
    '<p>Оформите полную подписку в течение первых {$a->duration} дней после активации пробного доступа и получите скидку {$a->pct}% на продолжение обучения в Campus<small><sup>FR</sup></small>.</p>' .

'<p>Скидка действует только в эти три дня, дальше будет стандартная цена.</p>';

$string['mail_trial_discount_btn'] =
    'Купить полную подписку на CampusFR со скидкой {$a->pct}%';

// Кнопки
$string['mail_trial_cta_continue']  = 'Открыть пробные курсы';
$string['mail_trial_cta_subscribe'] = 'Оформить полную подписку';

// Подписи в таблице
$string['trial_username_label'] = 'E-mail для входа';
$string['trial_phone_label']    = 'Телефон';

// Подсказка по безопасности (fallback)
$string['mail_trial_security_hint'] =
    'Для безопасности аккаунта лучше использовать отдельный пароль для Campus<small><sup>FR</sup></small> ' .
    'и время от времени менять его при необходимости.';

// Подсказка со ссылкой на сброс пароля

$string['trial_phone_country_placeholder'] = 'Код';
$string['trial_password_toggle_show'] = 'Показать пароль';
$string['trial_password_toggle_hide'] = 'Скрыть пароль';
$string['trial_welcome_banner_html'] =
    'Добро пожаловать в Campus<small><sup>FR</sup></small>! Ваш пробный доступ активирован. ' .
    'Начните с уровня, который вам подходит (A1 или A2) — первый урок бесплатный. ' .
    'Вы можете в любой момент найти свои курсы на странице «Мои курсы».';

$string['mail_trial_started_support'] =
    '<p>Весь ваш прогресс на платформе (выполненные задания, набранные баллы-круассаны) никуда не исчезнет. При оформлении полной подписки вы просто продолжите обучение с того места, где остановились.</p> ' .

'<p>Это письмо отправлено автоматически.</br>' .
'Если у вас появятся вопросы, напишите нам на <a href="mailto:{$a->url}">{$a->url}</a> — мы будем рады помочь.</p>' .

'<p>Желаем вам радости от каждого маленького результата, интересных занятий и уверенного прогресса во французском ❤️</p>' .

'<p>Ната и команда Campus<small><sup>FR</sup></small></p>';

$string['trial_discount_reminder_days'] = 'Через сколько дней отправлять письмо со скидкой (в днях)';
$string['trial_discount_reminder_days_desc'] =
    'Количество дней после начала пробного доступа, через которое отправляется письмо со скидкой. '
  . 'По умолчанию: 2 дня.';

$string['phone_country_group_popular'] = 'Популярные страны';
$string['phone_country_group_all']     = 'Все страны';

$string['trialreport_title'] = 'Отчёт по пробным подпискам';
$string['trialreport_col_firstname'] = 'Имя';
$string['trialreport_col_lastname'] = 'Фамилия';
$string['trialreport_col_email'] = 'E-mail';
$string['trialreport_col_phone'] = 'Телефон (с кодом страны)';
$string['trialreport_col_country'] = 'Страна';
$string['trialreport_col_start_date'] = 'Дата начала';
$string['trialreport_col_end_date'] = 'Дата окончания';
$string['trialreport_col_status'] = 'Статус';

$string['trialreport_export_xls'] = 'Сохранить в XLS';
$string['trialreport_export_csv'] = 'Сохранить в CSV';

$string['task_cleanup_notifications'] = 'Очистка системных уведомлений (обновления Moodle и входы в аккаунт)';

$string['audio_not_found_title'] = 'Аудио не найдено';
$string['audio_not_found_message'] = 'Это аудио не найдено или больше недоступно.';
$string['audio_back_to_home'] = 'Вернуться на главную';
$string['audio_player_instruction'] = 'Нажмите на кнопку воспроизведения, чтобы прослушать аудио.';
$string['audio_browser_not_supported'] = 'Ваш браузер не поддерживает воспроизведение аудио.';

$string['other_courses_available_title'] = 'Открыть другие курсы';
$string['other_courses_available_text'] = 'Вы можете продолжить обучение и добавить другие доступные курсы CampusFR.';
$string['trial_badge'] = 'Пробный доступ';
