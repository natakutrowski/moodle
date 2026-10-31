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
 * Language file.
 *
 * @package    enrol_xp
 * @copyright  2017 Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// CampusFR custom FR/RU translations - enrol_xp 1.0.8a.

$string['adhocsyncscheduled'] = 'Отсутствующие записи на курсы будут синхронизированы в ближайшие минуты (при выполнении cron).';
$string['course'] = 'Перейти к курсу';
$string['invalidlevel'] = 'Уровень должен быть числом не меньше 2.';
$string['levelincourse'] = 'Курс, в котором необходимо достичь уровня';
$string['levelninvalid'] = '{$a} (недопустимо)';
$string['leveltoattain'] = 'Требуемый уровень';
$string['messageprovider:welcomemessage'] = 'Приветственное сообщение при зачислении через Level Up XP';
$string['noteaboutexistingenrolment'] = 'Этот способ зачисления не действует, если пользователь уже записан на курс, даже если его запись устарела, приостановлена или отключена.';
$string['notethat'] = 'Примечание';
$string['pluginname'] = 'Зачисление через Level Up XP';
$string['privacy:metadata'] = 'Этот плагин не хранит сведения о пользователях.';
$string['syncpossiblemissing'] = 'Синхронизировать и найти возможные отсутствующие записи на курсы.';
$string['welcomemessage'] = 'Приветственное сообщение';
$string['welcomemessage_help'] = 'Сообщение, которое отправляется пользователям при зачислении на курс. Оставьте поле пустым, если не хотите отправлять сообщение.

Доступные подстановки:

* [level] — уровень, которого нужно было достичь
* [fullname] — полное имя пользователя
* [firstname] — имя пользователя

Поддерживается формат [Markdown](https://en.wikipedia.org/wiki/Markdown).
';
$string['wholesite'] = 'Весь сайт';
$string['xp:config'] = 'Настраивать способы зачисления';
$string['xp:unenrol'] = 'Отчислять пользователей';
$string['youhavebeenenrolled'] = 'Вы зачислены на новый курс.';
