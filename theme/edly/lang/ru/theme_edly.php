<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Родительская тема: boost
 *
 * @package   theme_edly
 * @copyright HiBootstrap
 *
 */

// Защита от прямого доступа по URL.
defined('MOODLE_INTERNAL') || die();

$string['choosereadme'] = 'Тема Moodle Edly Education & LMS';
$string['pluginname'] = 'Edly';

$string['edly_settings_menu'] = 'Опции';
$string['edly_page_settings_menu'] = 'Настройки страницы';

// Название второй вкладки в настройках темы.
$string['advancedsettings'] = 'Расширенные настройки';
// Цвет бренда.
$string['brandcolor'] = 'Цвет бренда';
// Описание цвета бренда.
$string['brandcolor_desc'] = 'Основной цвет.';
$string['secondarycolor'] = 'Дополнительный цвет';
$string['secondarycolor_desc'] = 'Вторичный цвет.';
$string['footer_bg'] = 'Цвет фона подвала';
// Описание в селекторе тем админа.
$string['configtitle'] = 'Настройки Edly';
// Название первой вкладки настроек.
$string['generalsettings'] = 'Общие настройки';
// Дополнительные файлы пресетов.
$string['presetfiles'] = 'Дополнительные файлы пресетов темы';
// Подсказка к пресетам.
$string['presetfiles_desc'] = 'Файлы пресетов могут существенно менять внешний вид темы. См. <a href=https://docs.moodle.org/dev/Boost_Presets>Boost presets</a> о создании и обмене пресетами, а также <a href=http://moodle.net/boost>репозиторий пресетов</a> с готовыми вариантами.';
// Выбор пресета.
$string['preset'] = 'Пресет темы';
// Подсказка к пресету.
$string['preset_desc'] = 'Выберите пресет для изменения внешнего вида темы.';
// Сырый SCSS.
$string['rawscss'] = 'Необработанный SCSS';
// Подсказка к сырому SCSS.
$string['rawscss_desc'] = 'Здесь можно указать SCSS или CSS, который будет добавлен в конец таблицы стилей.';
// Начальный сырой SCSS.
$string['rawscsspre'] = 'Начальный SCSS';
// Подсказка к начальному SCSS.
$string['rawscsspre_desc'] = 'Здесь можно указать инициализирующий SCSS-код — он будет подключён раньше всего. Чаще всего здесь задают переменные.';
$string['region-side-pre'] = 'Правая боковая панель';
$string['iconset_edly'] = 'Все иконки';
$string['region-side-pre'] = 'Справа';
$string['region-user-notif'] = 'Уведомления пользователя';
$string['region-user-messages'] = 'Сообщения пользователя';
$string['region-fullwidth-top'] = 'Полная ширина (верх)';
$string['region-fullwidth-bottom'] = 'Полная ширина (низ)';
$string['region-above-content'] = 'Над контентом';
$string['region-below-content'] = 'Под контентом';
$string['total_student'] = 'Студенты';
$string['last_updated'] = 'Последнее обновление ';

// Настройки темы
$string['logo_settings']    = 'Логотип';
$string['header_logos']     = 'Логотип в шапке';
$string['logo_visibility']  = 'Отображение логотипа';

$string['main_logo']                = 'Основной логотип';
$string['main_logo_desc']           = 'Основной логотип вашего сайта.';
$string['logo_image_width']         = 'Ширина изображения основного логотипа';
$string['logo_image_width_desc']    = 'Ширина в пикселях для основного логотипа. Укажите только число, без «px».';
$string['logo_image_height']        = 'Высота изображения основного логотипа';
$string['logo_image_height_desc']   = 'Высота в пикселях для основного логотипа. Укажите только число, без «px».';

$string['hide_banner']        = 'Ссылки страниц, для которых нужно скрыть баннер';
$string['hide_banner_desc']   = 'Каждую ссылку указывайте с новой строки';

$string['hide_page_bottom_content']        = 'Ссылки страниц, для которых нужно скрыть нижний контент';
$string['hide_page_bottom_content_desc']   = 'Каждую ссылку указывайте с новой строки. Внимание: не работает в режиме редактирования.';

$string['mobile_logo']              = 'Мобильный логотип';
$string['mobile_logo_desc']         = 'Логотип для мобильной версии.';
$string['mobile_logo_width']        = 'Ширина мобильного логотипа';
$string['mobile_logo_width_desc']   = 'Ширина в пикселях для мобильного логотипа. Укажите только число, без «px».';
$string['mobile_logo_height']       = 'Высота мобильного логотипа';
$string['mobile_logo_height_desc']  = 'Высота в пикселях для мобильного логотипа. Укажите только число, без «px».';

$string['footersettings']           = 'Подвал';
$string['footer_copyright']         = 'Текст копирайта';
$string['footer_logo_sec']          = 'Логотип в подвале';
$string['footer_logo_visibility']   = 'Отображение логотипа в подвале';
$string['main_footer_logo']         = 'Логотип подвала';
$string['main_footer_logo_desc']    = 'Логотип в подвале вашего сайта.';
$string['footer_logo_width']        = 'Ширина логотипа подвала';
$string['footer_logo_width_desc']   = 'Ширина в пикселях для логотипа подвала. Укажите только число, без «px».';
$string['footer_logo_height']       = 'Высота логотипа подвала';
$string['footer_logo_height_desc']  = 'Высота в пикселях для логотипа подвала. Укажите только число, без «px».';

$string['header_settings']      = 'Шапка';
$string['top_header']           = 'Верхняя панель';
$string['header_search']        = 'Поиск в шапке';
$string['search_placeholder']   = 'Подсказка в поле поиска';
$string['header_search_desc']   = 'Настройки функции поиска в шапке.';
$string['header_settings']      = 'Шапка';
$string['header_left_btn_text']      = 'Текст левой ссылки в шапке';
$string['header_left_btn_text_desc'] = 'Текст ссылки в шапке (отображается только для незалогиненных пользователей).';
$string['top_header_content']      = 'Содержимое верхней панели';
$string['top_header_content_desc'] = 'Поддерживается HTML';
$string['top_header_right_content']      = 'Содержимое справа (верхняя панель)';
$string['top_header_right_content_desc'] = 'Поддерживается HTML';
$string['header_left_btn_url']       = 'URL левой ссылки в шапке';
$string['header_left_btn_url_desc']  = 'Ссылка для текста ссылки в шапке. Примечание: оставьте пустым для URL входа по умолчанию.';
$string['header_btn_url']       = 'URL иконки-кнопки в шапке';
$string['header_btn_url_desc']  = 'Ссылка для кнопки в шапке. Примечание: оставьте пустым для URL регистрации по умолчанию.';
$string['header_btn_icon']      = 'Иконка кнопки в шапке';
$string['header_btn_icon_desc'] = 'Иконка для кнопки в шапке.';

$string['social_target'] = 'Открытие ссылок на соцсети';
$string['social_target_desc'] = 'Определяет, открывать ли социальные ссылки на той же странице или в новом окне.';
$string['social_settings'] = 'Соцсети';
$string['edly_facebook_url'] = 'Ссылка на Facebook';
$string['edly_facebook_url_desc'] = 'Ссылка на профиль вашей компании в Facebook.';
$string['edly_twitter_url'] = 'Ссылка на Twitter';
$string['edly_twitter_url_desc'] = 'Ссылка на профиль вашей компании в Twitter.';
$string['edly_instagram_url'] = 'Ссылка на Instagram';
$string['edly_instagram_url_desc'] = 'Ссылка на профиль вашей компании в Instagram.';
$string['edly_dribbble_url'] = 'Ссылка на Dribbble';
$string['edly_dribbble_url_desc'] = 'Ссылка на профиль вашей компании в Dribbble.';
$string['edly_tiktok_url'] = 'Ссылка на TikTok';
$string['edly_tiktok_url_desc'] = 'Ссылка на профиль вашей компании в TikTok.';
$string['edly_pinterest_url'] = 'Ссылка на Pinterest';
$string['edly_pinterest_url_desc'] = 'Ссылка на профиль вашей компании в Pinterest.';
$string['edly_google_url'] = 'Ссылка на Google';
$string['edly_google_url_desc'] = 'Ссылка на профиль вашей компании в Google.';
$string['edly_youtube_url'] = 'Ссылка на YouTube';
$string['edly_youtube_url_desc'] = 'Ссылка на профиль вашей компании на YouTube.';
$string['edly_vk_url'] = 'Ссылка на VK';
$string['edly_vk_url_desc'] = 'Ссылка на профиль вашей компании во ВКонтакте.';
$string['edly_500px_url'] = 'Ссылка на 500px';
$string['edly_500px_url_desc'] = 'Ссылка на профиль вашей компании на 500px.';
$string['edly_behance_url'] = 'Ссылка на Behance';
$string['edly_behance_url_desc'] = 'Ссылка на профиль вашей компании на Behance.';
$string['edly_digg_url'] = 'Ссылка на Digg';
$string['edly_digg_url_desc'] = 'Ссылка на профиль вашей компании на Digg.';
$string['edly_flickr_url'] = 'Ссылка на Flickr';
$string['edly_flickr_url_desc'] = 'Ссылка на профиль вашей компании на Flickr.';
$string['edly_foursquare_url'] = 'Ссылка на Foursquare';
$string['edly_foursquare_url_desc'] = 'Ссылка на профиль вашей компании на Foursquare.';
$string['edly_linkedin_url'] = 'Ссылка на LinkedIn';
$string['edly_linkedin_url_desc'] = 'Ссылка на профиль вашей компании в LinkedIn.';
$string['edly_medium_url'] = 'Ссылка на Medium';
$string['edly_medium_url_desc'] = 'Ссылка на профиль вашей компании на Medium.';
$string['edly_meetup_url'] = 'Ссылка на Meetup';
$string['edly_meetup_url_desc'] = 'Ссылка на профиль вашей компании на Meetup.';
$string['edly_snapchat_url'] = 'Ссылка на Snapchat';
$string['edly_snapchat_url_desc'] = 'Ссылка на профиль вашей компании в Snapchat.';
$string['edly_tumblr_url'] = 'Ссылка на Tumblr';
$string['edly_tumblr_url_desc'] = 'Ссылка на профиль вашей компании на Tumblr.';
$string['edly_vimeo_url'] = 'Ссылка на Vimeo';
$string['edly_vimeo_url_desc'] = 'Ссылка на профиль вашей компании на Vimeo.';
$string['edly_wechat_url'] = 'Ссылка на WeChat';
$string['edly_wechat_url_desc'] = 'Ссылка на профиль вашей компании в WeChat.';
$string['edly_whatsapp_url'] = 'Ссылка на WhatsApp';
$string['edly_whatsapp_url_desc'] = 'Ссылка на профиль вашей компании в WhatsApp.';
$string['edly_wordpress_url'] = 'Ссылка на WordPress';
$string['edly_wordpress_url_desc'] = 'Ссылка на профиль вашей компании на WordPress.';
$string['edly_weibo_url'] = 'Ссылка на Weibo';
$string['edly_weibo_url_desc'] = 'Ссылка на профиль вашей компании на Weibo.';
$string['edly_telegram_url'] = 'Ссылка на Telegram';
$string['edly_telegram_url_desc'] = 'Ссылка на профиль вашей компании в Telegram.';
$string['edly_moodle_url'] = 'Ссылка на Moodle';
$string['edly_moodle_url_desc'] = 'Ссылка на профиль вашей компании на Moodle.';
$string['edly_amazon_url'] = 'Ссылка на Amazon';
$string['edly_amazon_url_desc'] = 'Ссылка на профиль вашей компании на Amazon.';
$string['edly_slideshare_url'] = 'Ссылка на SlideShare';
$string['edly_slideshare_url_desc'] = 'Ссылка на профиль вашей компании на SlideShare.';
$string['edly_soundcloud_url'] = 'Ссылка на Soundcloud';
$string['edly_soundcloud_url_desc'] = 'Ссылка на профиль вашей компании на Soundcloud.';
$string['edly_leanpub_url'] = 'Ссылка на Leanpub';
$string['edly_leanpub_url_desc'] = 'Ссылка на профиль вашей компании на Leanpub.';
$string['edly_xing_url'] = 'Ссылка на Xing';
$string['edly_xing_url_desc'] = 'Ссылка на профиль вашей компании на Xing.';
$string['edly_bitcoin_url'] = 'Ссылка на Bitcoin';
$string['edly_bitcoin_url_desc'] = 'Ссылка на профиль вашей компании, связанный с Bitcoin.';
$string['edly_twitch_url'] = 'Ссылка на Twitch';
$string['edly_twitch_url_desc'] = 'Ссылка на профиль вашей компании на Twitch.';
$string['edly_github_url'] = 'Ссылка на GitHub';
$string['edly_github_url_desc'] = 'Ссылка на профиль вашей компании на GitHub.';
$string['edly_gitlab_url'] = 'Ссылка на GitLab';
$string['edly_gitlab_url_desc'] = 'Ссылка на профиль вашей компании на GitLab.';
$string['edly_forumbee_url'] = 'Ссылка на Forumbee';
$string['edly_forumbee_url_desc'] = 'Ссылка на профиль вашей компании на Forumbee.';
$string['edly_trello_url'] = 'Ссылка на Trello';
$string['edly_trello_url_desc'] = 'Ссылка на профиль вашей компании на Trello.';
$string['edly_weixin_url'] = 'Ссылка на Weixin';
$string['edly_weixin_url_desc'] = 'Ссылка на профиль вашей компании в Weixin.';
$string['edly_slack_url'] = 'Ссылка на Slack';
$string['edly_slack_url_desc'] = 'Ссылка на рабочее пространство вашей компании в Slack.';

$string['banner_shape_image']              = 'Фоновая фигура баннера 1';
$string['banner_shape_image_desc']         = 'Изображение 1 для формы/фигуры баннера.';

$string['banner_shape_image2']              = 'Фоновая фигура баннера 2';
$string['banner_shape_image2_desc']         = 'Изображение 2 для формы/фигуры баннера.';

$string['offcanvas_social_title']              = 'Заголовок соцсетей в левом модальном сайдбаре';
$string['offcanvas_social_title_desc']         = 'Заголовок блока соцсетей в модальном меню сайта.';

$string['back_to_top'] = 'Наверх';
$string['back_to_top_desc'] = 'Показать или скрыть кнопку «наверх» на фронтенде.';

$string['hide_global_banner'] = 'Глобальный баннер';
$string['hide_global_banner_desc'] = 'Показать или скрыть баннер на всём сайте. Если скрыт глобально, поле hide_banner работать не будет.';

$string['hide_guest_access_curriculum'] = 'Программа курса для гостей';
$string['hide_guest_access_curriculum_desc'] = 'Показать или скрыть программу курса для гостевых пользователей.';

$string['preloader'] = 'Прелоадер';
$string['preloader_desc'] = 'Показать или скрыть прелоадер сайта.';

$string['footer_info'] = 'Информация в подвале';
$string['footer_info_desc'] = '';
$string['footer_col_1'] = 'Колонка подвала 1';
$string['footer_col_2'] = 'Колонка подвала 2';
$string['footer_col_3'] = 'Колонка подвала 3';
$string['footer_col_4'] = 'Колонка подвала 4';
$string['footer_col_5'] = 'Колонка подвала 5';
$string['footer_col_title'] = 'Заголовок колонки';
$string['footer_col_title_desc'] = 'Заголовок для колонки подвала.';
$string['footer_col_body'] = 'Содержимое колонки';
$string['footer_col_body_desc'] = 'Текст для колонки подвала. Допускается HTML.';

// Конец настроек темы

// Константы плагина Edly: Админка/бэкенд
$string['config_title'] = 'Заголовок';
$string['config_top_title'] = 'Верхний заголовок';
$string['config_title_desc'] = 'Основной заголовок элемента.';
$string['config_body'] = 'Текст';
$string['config_image_heading'] = 'Изображения';
$string['config_items'] = 'Элементы';
$string['config_item'] = 'Элемент ';
$string['config_number'] = 'Число';
$string['config_number_prefix'] = 'Префикс числа';
$string['config_icon'] = 'Иконка';
$string['config_button_link'] = 'Ссылка кнопки';
$string['config_button_text'] = 'Текст кнопки';
$string['config_price'] = 'Цена';
$string['config_enrol_btn'] = 'Кнопка записи';
$string['config_enrol_btn_text'] = 'Текст кнопки записи';
$string['select_from_dropdown'] = 'Пожалуйста, выберите элемент в выпадающем списке ниже.';
$string['select_from_dropdown_multiple'] = 'Пожалуйста, выберите несколько элементов в списке ниже (не более 2).';
$string['config_group_courses_filter'] = 'Включить фильтрацию';
$string['config_icon_class'] = 'Иконка';
$string['config_icon_class_desc'] = 'Выберите иконку для элемента.';
$string['config_text'] = 'Текст';
$string['config_image'] = 'Ссылка на изображение';
$string['config_video'] = 'Ссылка на видео YouTube';
$string['config_style'] = 'Стиль секции';
$string['config_class'] = 'CSS-класс секции';
$string['config_placeholder'] = 'Текст плейсхолдера';
$string['config_btn'] = 'Текст кнопки';
$string['config_contact_from_code'] = 'Код формы';
$string['course_buy_access'] = 'Платный доступ к курсу';
$string['course_enrolled'] = 'Вы зачислены';
$string['course_enrolled_text'] = 'В настоящее время вы зачислены на этот курс.';
$string['course_enrolled_teacher'] = 'Вы преподаёте';
$string['course_enrolled_teacher_text'] = 'Сейчас вы являетесь преподавателем на этом курсе.';
$string['course_error_title'] = 'Ошибка зачисления';
$string['course_error_text'] = 'Администратор ещё не настроил зачисление через PayPal или Stripe для этого курса.';
$string['course_price'] = 'Цена';
$string['course_currency'] = '$';
$string['site_currency'] = 'Введите валюту сайта';
$string['free_course_price'] = 'Заголовок «Курс бесплатный»';
$string['config_price'] = 'Заголовок цены';
$string['course_enrolment'] = 'Записаться';
$string['course_enrolment_free'] = 'Присоединиться и записаться';
$string['course_free_access'] = 'Бесплатное зачисление';
$string['course_free'] = 'Бесплатно';
$string['course_students'] = 'Студенты';
$string['config_alltitle'] = 'Текст «Все»';
$string['config_social_heading'] = 'Социальные ссылки';
$string['config_link'] = 'Ссылка';
$string['config_top_title'] = 'Верхний заголовок';
$string['config_content'] = 'Содержимое';
$string['config_button'] = 'Текст кнопки';
$string['course_total_students'] = 'Всего: ';
$string['course_format'] = 'Формат: ';
$string['course_total_announcements'] = 'Всего объявлений: ';
$string['config_btn_img'] = 'URL иконки кнопки';
$string['config_quote'] = 'Цитата';
$string['config_video_title'] = 'Заголовок видео';
$string['config_by_text'] = 'Подпись «Автор»';
$string['config_name_text'] = 'Имя';
$string['config_name_link'] = 'Ссылка на имя';
$string['config_text_items'] = 'Элемент слайдера (текст)';
$string['config_btn_icon'] = 'Иконка кнопки';
$string['config_bg_img'] = 'URL фонового изображения секции';
$string['config_student_title'] = 'Заголовок «Всего студентов»';
$string['config_bottom_body'] = 'Нижний контент';
$string['config_number_suffix'] = 'Суффикс числа';
$string['config_fun_heading'] = 'Факты';
$string['config_img'] = 'Изображение';
$string['config_date'] = 'Дата';
$string['config_location'] = 'Место';
 // Константы плагина Edly: Админка/бэкенд

$string['region-left'] = 'Левая область';
$string['banner_settings'] = 'Баннер';
$string['config_subtitle'] = 'Верхний заголовок';

$string['favicon'] = 'Фавикон';
$string['favicon_desc'] = 'Фавикон сайта. Рекомендуемый размер — 16×16 px.';

$string['total_student_singular'] = 'Студент';
$string['total_student_plural']   = 'Студентов';

$string['trial_courses'] = 'Курсы с ссылкой «демо» (вместо даты и числа студентов)';
$string['trial_only_guests'] = 'Показывать ссылку на демо-курс только гостям';
$string['trial_access'] = 'Перейти к демо-курсу';
$string['image'] = 'Картина';

$string['locked_title'] = 'Доступно по подписке';
$string['locked_desc'] = 'Оформите подписку, чтобы открыть эту активность.';
$string['locked_subscribe'] = 'Оформить подписку';

$string['mobilemenu_button'] = 'Меню';
$string['mobilemenu_open_aria'] = 'Открыть меню';
$string['mobilemenu_title'] = 'Меню';
$string['mobilemenu_close_aria'] = 'Закрыть';