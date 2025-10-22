<?php
$string['pluginname']     = 'Campus — Страница курса';
$string['view_trial']     = 'Перейти к пробному курсу';
$string['view_real']      = 'Перейти к курсу';
$string['course_hidden']  = 'Этот курс скрыт от вас.';
$string['course_notfound']= 'Курс не найден.';
$string['subscribe_now'] = 'Оформить подписку';
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

$string['trial_popup_title'] = 'Бесплатный пробный доступ на 7 дней';
$string['trial_popup_lead']  = 'Мгновенный доступ к пробным курсам. Банковская карта не требуется.';
$string['trial_popup_tos']   = 'Я принимаю Условия использования и Политику конфиденциальности.';
$string['trial_popup_accept']= 'Пожалуйста, подтвердите согласие с условиями.';
$string['trial_firstname']   = 'Имя';
$string['trial_lastname']    = 'Фамилия';
$string['trial_email']       = 'E-mail';
$string['trial_btn_continue']= 'Продолжить';
$string['trial_btn_subscribe']= 'Оформить подписку';
$string['trial_expired_msg'] = 'Ваш пробный период завершён. Оформите подписку, чтобы продолжить.';
$string['trial_tos_html'] = 'Я принимаю <a href="{$a}" target="_blank" rel="noopener">Политику конфиденциальности</a>.';

$string['mail_trial_started_subject'] = 'Ваш пробный доступ активирован';
$string['mail_trial_started_body']    = 'Здравствуйте, {$a->firstname}! Ваш 7-дневный пробный доступ активирован.';
$string['mail_trial_rem3_subject']    = 'Напоминание: пробный доступ — {$a}';
$string['mail_trial_rem3_body']       = 'Здравствуйте, {$a->firstname}! У вас ещё осталось несколько дней пробного доступа.';
$string['mail_trial_expired_subject'] = 'Пробный доступ истёк — {$a}';
$string['mail_trial_expired_body']    = 'Здравствуйте, {$a->firstname}! Ваш 7-дневный пробный доступ завершён.';
$string['mail_trial_cta_subscribe']   = 'Оформить подписку';
$string['mail_trial_cta_continue']    = 'Продолжить пробный доступ';
$string['mail_trial_rem3_subject_generic']   = 'Напоминание: ваш пробный доступ активен';
$string['mail_trial_expired_subject_generic']= 'Ваш пробный доступ истёк';

