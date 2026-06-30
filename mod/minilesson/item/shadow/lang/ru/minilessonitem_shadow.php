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
 * English language pack for Video Shadowing
 *
 * @package    minilessonitem_shadow
 * @category   string
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['additem'] = 'Видео Shadowing';
$string['aihelper_placeholder_shadow'] = 'Например: Добавьте знаки препинания и исправьте ошибки в орфографии и использовании заглавных букв.';
$string['enablesubtitlefetch'] = 'Включить кнопку загрузки субтитров';
$string['enablesubtitlefetch_details'] = 'Показывает кнопку «Загрузить субтитры» в форме элемента, которая загружает субтитры видео YouTube в редактор субтитров. Обратите внимание: загрузка субтитров может работать нестабильно и перестать работать в любой момент. Это вспомогательная функция, доступность которой Poodll не гарантирует.';
$string['error:badtimestamp'] = 'Время начала и окончания фрагмента должно быть в формате hh:mm:ss, например 00:01:30.';
$string['error:subtitlefetchdisabled'] = 'Загрузка субтитров отключена на этом сайте.';
$string['error:badshadowlines'] = 'Строки для повторения должны быть указаны как * (все строки) или как список номеров строк через запятую, например: 1,4,5,6.';
$string['error:badvtt'] = 'Не удалось обработать субтитры. Укажите корректный файл WebVTT, содержащий хотя бы один временной сегмент.';
$string['error:noshadowlines'] = 'Ни один из выбранных номеров строк не соответствует строкам субтитров в пределах времени начала и окончания фрагмента.';
$string['fetchvtt'] = 'Загрузить субтитры';
$string['fetchvtt_disabled'] = 'Автоматическая загрузка субтитров в настоящее время отключена.';
$string['fetchvtt_failed'] = 'Не удалось загрузить субтитры с YouTube.';
$string['fetchvtt_fetching'] = 'Загрузка...';
$string['fetchvtt_invalidurl'] = 'Сначала введите корректную ссылку на YouTube или идентификатор видео из 11 символов.';
$string['fetchvtt_overwrite'] = 'Текущие субтитры в редакторе будут заменены. Продолжить?';
$string['fetchvtt_overwrite_title'] = 'Заменить субтитры?';
$string['error:nocuesinclip'] = 'В пределах времени начала и окончания фрагмента нет ни одной полностью подходящей строки субтитров. Измените время или субтитры.';
$string['error:novideoid'] = 'Необходимо указать URL или идентификатор видео YouTube.';
$string['error:startafterend'] = 'Время окончания фрагмента должно быть позже времени начала.';
$string['item_desc'] = 'Элемент «Видео Shadowing» воспроизводит фрагмент YouTube построчно. Учащиеся выполняют упражнение Shadowing: слушают каждую строку субтитров и одновременно повторяют её вместе с повторным воспроизведением видео, пока слова подсвечиваются.';
$string['loopcount'] = 'Количество повторений каждой строки';
$string['loopcount_desc'] = 'Сколько раз каждая строка будет повторяться, чтобы учащийся мог её произнести.';
$string['loopindicator'] = 'Shadowing: {$a->current} / {$a->total}';
$string['oknext'] = 'ОК / Далее';
$string['pluginname'] = 'Видео Shadowing';
$string['privacy:metadata'] = 'Плагин «Видео Shadowing» не хранит персональные данные.';
$string['retry'] = 'Повторить';
$string['rotatedevice'] = 'Поверните устройство в портретный режим, чтобы продолжить.';
$string['shadow_instructions1'] = 'Посмотрите видео. Затем повторяйте каждую строку: сначала прослушайте её, затем произнесите одновременно с повторным воспроизведением видео.';
$string['shadowlines'] = 'Строки для повторения';
$string['shadowpause'] = 'Пауза между повторениями (сек.)';
$string['shadowlines_desc'] = 'Номера строк субтитров для повторения, начиная с 1 в списке субтитров ниже (например: 1,4,5,6). Используйте * для повторения всех строк. Остальные строки всё равно будут отображаться во время просмотра.';
$string['shadowvtt'] = 'Субтитры (WebVTT)';
$string['shadowvtt_desc'] = 'Вставьте или отредактируйте субтитры WebVTT для этого фрагмента в области ниже.';
$string['startshadowing'] = 'Начать Shadowing';
$string['watchhint'] = 'Нажмите «Воспроизвести» и посмотрите фрагмент. После его окончания нажмите «Начать Shadowing».';
$string['wordhighlight'] = 'Включить подсветку каждого слова';
$string['wordhighlight_details'] = 'Подсвечивает каждое слово в момент его произнесения, используя временные метки слов в субтитрах. В некоторых видео временные метки YouTube могут быть неточными — в этом случае отключите эту функцию, чтобы подсвечивались целые строки. Если функция отключена, при загрузке субтитров также не загружаются временные метки слов.';
$string['ytclipdetails'] = 'Фрагмент YouTube (ID/URL, время начала и окончания)';
