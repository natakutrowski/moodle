<?php
// CampusFR translation integration preview: latest official FR where available, then legacy DEV translations.
// Missing keys use Moodle's English fallback; see translation_gaps.csv.
defined('MOODLE_INTERNAL') || die();

$string['additem'] = 'Audio Chat';
$string['cantchat_ningxia'] = 'Чат НЕДОСТУПЕН. Чтобы использовать этот тип элемента, необходимо указать действительный ключ {$a} в административных настройках активности MiniLesson. Чат также недоступен в Китае (регион Нинся).';
$string['gemini'] = 'Gemini';
$string['cloudpoodll'] = 'Cloud Poodll';
$string['item_desc'] = 'Элемент Audio Chat позволяет настроить аудиоразговор с ИИ-партнёром. Его можно использовать для обсуждения темы или вопросов по изображению. Промпты для ИИ-бота вводятся в свободной форме и могут редактироваться.';
$string['openai'] = 'OpenAI';
$string['pluginname'] = 'Audio chat';
$string['privacy:metadata'] = 'Плагин Audio chat не хранит персональные данные.';
$string['provider'] = 'Провайдер';
$string['provider_details'] = 'Выберите ИИ-провайдера для элемента Audio Chat. Cloud Poodll должен работать сразу, за исключением региона Нинся. Для других провайдеров необходимо указать API-ключ в административных настройках BYOK MiniLesson.';
$string['unknown'] = 'Неизвестно';

// CampusFR 1.1.60a — newly translated strings (FR/RU).
$string['audiochat_silenceduration'] = 'Пауза перед ответом ИИ';
$string['audiochat_silenceduration_details'] = 'Длительность паузы в миллисекундах, после которой ИИ считает реплику ученика законченной и начинает отвечать. Увеличьте значение, если ученику нужно больше времени, чтобы подумать и сделать паузу посреди фразы. Уменьшите его для более быстрого и естественного диалога. Значение по умолчанию — 3500 (3,5 секунды). При значении вне диапазона от 500 до 30000 используется значение по умолчанию. Настройка действует только после того, как ученик начал говорить, и при включённой автоматической отправке ответа.';
$string['geminilivemodel'] = 'Модель Gemini Live';
$string['geminilivemodel_details'] = 'Модель Gemini Live, используемая при выборе провайдера Gemini. Gemini 3.8 Live — текущая модель Google по умолчанию для голосового общения с низкой задержкой. Предварительная версия 3.1 устарела, и Google рекомендует перейти на более новую. Настройка действует для провайдеров Gemini и Cloud Poodll, но не для OpenAI.';
$string['gradingfailed'] = 'ИИ не смог оценить этот диалог, поэтому выставлена оценка 0. Расшифровка разговора сохранена.';
$string['additem'] = 'Аудиочат'; // CampusFR translated label.
$string['pluginname'] = 'Аудиочат'; // CampusFR translated label.
