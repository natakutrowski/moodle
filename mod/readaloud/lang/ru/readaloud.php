<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English strings for readaloud
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Poodll ReadAloud';
$string['modulenameplural'] = 'Poodll ReadAloud';
$string['modulename_help'] =
        'ReadAloud помогает учащимся тренировать чтение вслух и получать обратную связь. Активность может оцениваться полностью автоматически и помогает преподавателям оценивать и понимать уровень навыков чтения на иностранном языке. Процесс выглядит следующим образом:

1. Учащийся ПРОСЛУШИВАЕТ текст, подготовленный преподавателем.

2. Учащийся ТРЕНИРУЕТСЯ читать текст построчно, используя микрофон.

3. Учащийся ЧИТАЕТ весь текст вслух.

4. Учащийся и преподаватель могут просмотреть ОБРАТНУЮ СВЯЗЬ и РЕЗУЛЬТАТЫ.';
$string['readaloudfieldset'] = 'Пользовательский пример набора полей';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'Это содержимое всплывающей подсказки для поля readaloudname. Поддерживается синтаксис Markdown.';
// $string['readaloud'] = 'readaloud';
$string['activitylink'] = 'Ссылка на следующее задание';
$string['activitylink_help'] = 'Чтобы после завершения попытки предложить переход к другому элементу курса, выберите его из выпадающего списка.';
$string['activitylinkname'] = 'Перейти к следующему заданию: {$a}';
$string['pluginadministration'] = 'Администрирование ReadAloud';
$string['pluginname'] = 'Poodll ReadAloud';
$string['readaloud:addinstance'] = 'Добавить новую активность ReadAloud';
$string['readaloud:view'] = 'Просматривать ReadAloud';
$string['readaloud:view'] = 'Предпросмотр ReadAloud';
$string['readaloud:itemview'] = 'Просматривать элементы';
$string['readaloud:itemedit'] = 'Редактировать элементы';
$string['readaloud:tts'] = 'Использовать синтез речи (TTS)';
$string['readaloud:manageattempts'] = 'Управлять попытками ReadAloud';
$string['readaloud:manage'] = 'Управлять экземплярами ReadAloud';
$string['readaloud:preview'] = 'Просматривать активности ReadAloud';
$string['readaloud:submit'] = 'Отправлять попытки ReadAloud';
$string['readaloud:viewreports'] = 'Просматривать оценки и отчёты ReadAloud';
$string['readaloud:pushtoclones'] = 'Копировать настройки в клоны';
$string['privacy:metadata'] = 'Плагин Poodll ReadAloud хранит персональные данные.';

$string['id'] = 'ID';
$string['name'] = 'Название';
$string['timecreated'] = 'Дата создания';
$string['basicheading'] = 'Основной отчёт';
$string['attemptsheading'] = 'Отчёт по попыткам';
// $string['attemptsbyuserheading'] = 'Отчёт по попыткам пользователя';
$string['attemptssummaryheading'] = 'Сводный отчёт по попыткам';
$string['gradingheading'] = 'Оценивание последних попыток каждого пользователя.';
$string['machinegradingheading'] = 'Автоматическая оценка последних попыток каждого пользователя.';
$string['gradingbyuserheading'] = 'Оценивание всех попыток пользователя: {$a}';
$string['machinegradingbyuserheading'] = 'Автоматическая оценка попыток пользователя: {$a}';
$string['totalattempts'] = 'Попытки';
$string['overview'] = 'Обзор';
$string['overview_help'] = 'Справка по обзору';
$string['view'] = 'Просмотр';
$string['preview'] = 'Предпросмотр';
$string['viewreports'] = 'Просмотреть отчёты';
$string['reports'] = 'Отчёты';
$string['viewgrading'] = 'Просмотреть оценивание';
$string['grading'] = 'Оценивание';
$string['gradenow'] = 'Оценить сейчас';
$string['cannotgradenow'] = ' - ';
// $string['gradenowtitle'] = 'Оценивание: {$a}';
$string['showingattempt'] = 'Показана попытка пользователя: {$a}';
$string['showingmachinegradedattempt'] = 'Показана автоматически оценённая попытка пользователя: {$a}';
$string['basicreport'] = 'Основной отчёт';
$string['returntoreports'] = 'Вернуться к отчётам';
$string['returntogradinghome'] = 'Вернуться к оцениванию';
$string['returntomachinegradinghome'] = 'Вернуться к автоматическому оцениванию';
$string['exportexcel'] = 'Экспорт в CSV';
// $string['mingradedetails'] = 'Минимальная оценка (%) для завершения активности.';
$string['mingrade'] = 'Минимальная оценка';
$string['deletealluserdata'] = 'Удалить все пользовательские данные';
$string['maxattempts'] = 'Макс. количество попыток';
$string['unlimited'] = 'без ограничений';
$string['gradeoptions'] = 'Параметры оценивания';
$string['gradeoptions_help'] =
        'Если пользователь выполнил несколько попыток, этот параметр определяет, какая попытка будет использована для выставления оценки.';
$string['gradeoptions_details'] =
        'Обратите внимание: этот параметр влияет только на запись в журнале оценок. На странице оценивания ReadAloud всегда отображается последняя попытка.';
$string['gradenone'] = 'Без оценки';
$string['gradelowest'] = 'Попытка с наименьшим баллом';
$string['gradehighest'] = 'Попытка с наибольшим баллом';
$string['gradelatest'] = 'Баллы последней попытки';
$string['gradeaverage'] = 'Средний балл по всем попыткам';
// $string['defaultsettings'] = 'Настройки по умолчанию';
$string['exceededattempts'] = 'Вы использовали максимальное количество попыток ({$a}).';
$string['exceededallattempts'] = 'Вы использовали все доступные попытки.';
$string['readaloudtask'] = 'Задание ReadAloud';
$string['passagelabel'] = 'Текст для чтения';
$string['welcomelabel'] = 'Инструкции по умолчанию';
$string['welcomelabel_details'] = 'Инструкции по умолчанию. Их можно изменить при создании новой активности ReadAloud.';
$string['feedbacklabel'] = 'Обратная связь по умолчанию';
$string['feedbacklabel_details'] = 'Текст по умолчанию, отображаемый в поле обратной связи при создании новой активности ReadAloud.';
$string['welcomelabel'] = 'Инструкции перед выполнением';
$string['feedbacklabel'] = 'Инструкции после выполнения';
$string['alternatives'] = 'Альтернативные варианты';
$string['alternatives_descr'] =
        'Укажите допустимые варианты для отдельных слов текста. Один набор слов в строке. Например: their|there|they\'re. Подробнее см. в <a href="https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity">документации</a>.';
$string['attemptsheading'] = 'Отчёт по попыткам';
$string['attemptsreport'] = 'Отчёт по попыткам';
$string['attemptssummaryheading'] = 'Сводный отчёт по попыткам';
$string['attemptssummaryreport'] = 'Сводный отчёт по попыткам';
$string['audiofile'] = 'Аудио';
$string['averages'] = 'Среднее';
$string['basicheading'] = 'Основной отчёт';
$string['basicreport'] = 'Основной отчёт';
$string['beginreading'] = 'Начать чтение';
$string['cannotgradenow'] = ' - ';
$string['complete'] = 'Завершено';
$string['defaultfeedback'] = 'Спасибо за чтение.';
$string['defaultwelcome'] = 'Выполните это задание, следуя инструкциям на экране. Вы прослушаете текст, потренируетесь читать его, затем прочитаете его вслух и сможете просмотреть результаты в отчёте. При необходимости разрешите доступ к микрофону.';
$string['deletealluserdata'] = 'Удалить все пользовательские данные';
$string['done'] = 'Готово';
$string['enabletts'] = 'Включить TTS (экспериментально)';
$string['enabletts_details'] = 'На данный момент функция TTS ещё не реализована.';
$string['errorheader'] = 'Ошибка';
$string['evaluatedmessage'] = 'Ваша последняя попытка получена. Результаты оценки показаны ниже.';
$string['exceededallattempts'] = 'Вы использовали все доступные попытки.';
$string['exceededattempts'] = 'Вы использовали максимальное количество попыток ({$a}).';
$string['exportexcel'] = 'Экспорт в CSV';
$string['feedbacklabel_details'] = 'Текст по умолчанию, отображаемый в поле обратной связи при создании новой активности ReadAloud.';
$string['gotnosound'] = 'Мы не смогли услышать вашу запись. Проверьте разрешения и настройки микрофона и попробуйте снова.';
$string['gradehighest'] = 'Попытка с наибольшим баллом';
$string['gradelatest'] = 'Баллы последней попытки';
$string['gradenone'] = 'Без оценки';
$string['gradenow'] = 'Оценить сейчас';
$string['gradeoptions'] = 'Параметры оценивания';
$string['gradeoptions_details'] =
        'Обратите внимание: этот параметр определяет запись в журнале оценок. Страница оценивания ReadAloud не изменяется и всегда отображает последнюю попытку.';
$string['gradeoptions_help'] =
        'Если пользователь выполнил несколько попыток чтения, этот параметр определяет, какая попытка будет использоваться для выставления оценки.';
$string['grading'] = 'Оценивание';
$string['gradingbyuserheading'] = 'Оценивание всех попыток пользователя: {$a}';
$string['gradingheading'] = 'Оценивание последних попыток каждого пользователя.';
$string['hiddenevaluationmessage'] = 'Ваша попытка получена. Спасибо!';
$string['highest'] = 'Наивысший';
$string['id'] = 'ID';
$string['instructions'] = 'Инструкции';
$string['locked'] = 'Заблокировано';
$string['machinegradingbyuserheading'] = 'Автоматическая оценка попыток пользователя: {$a}';
$string['machinegradingheading'] = 'Автоматическая оценка последних попыток каждого пользователя.';
$string['maxattempts'] = 'Макс. количество попыток';
$string['mingrade'] = 'Минимальная оценка';
$string['modulename'] = 'Poodll ReadAloud';
$string['modulename_help'] =
        'ReadAloud помогает учащимся тренировать чтение вслух и получать обратную связь. Активность может оцениваться полностью автоматически и помогает преподавателям оценивать и понимать уровень навыков чтения на иностранном языке. Процесс выглядит следующим образом:
1. Учащийся ПРОСЛУШИВАЕТ текст, подготовленный преподавателем.
2. Учащийся ТРЕНИРУЕТСЯ читать текст построчно, используя микрофон.
3. Учащийся ЧИТАЕТ весь текст вслух.
4. Учащийся ПРОВЕРЯЕТ ПОНИМАНИЕ с помощью теста (необязательно).
5. Учащийся и преподаватель могут просмотреть ОБРАТНУЮ СВЯЗЬ и РЕЗУЛЬТАТЫ.';
$string['modulenameplural'] = 'Poodll ReadAloud';
$string['name'] = 'Название';
$string['notaddedtogradebook'] = 'Это была тренировка в режиме Shadow, поэтому результат не был добавлен в журнал оценок.';
$string['notgradedyet'] = 'Ваша работа получена, но ещё не оценена. Это может занять несколько минут.';
$string['notmanuallygradedyet'] = 'Ваша работа получена, но ещё не проверена.';
$string['overview_help'] = 'Справка по обзору';
$string['passagelabel'] = 'Текст для чтения';
$string['pluginadministration'] = 'Администрирование ReadAloud';
$string['pluginname'] = 'Poodll ReadAloud';
$string['preview'] = 'Предпросмотр';
$string['privacy:metadata'] = 'Плагин Poodll ReadAloud хранит персональные данные.';
$string['processing'] = 'Обработка';
$string['readaloud:addinstance'] = 'Добавить новую активность ReadAloud';

$string['readaloud:manage'] = 'Управлять экземплярами ReadAloud';
$string['readaloud:manageattempts'] = 'Управлять попытками ReadAloud';
$string['readaloud:preview'] = 'Просматривать активности ReadAloud';
$string['readaloud:pushtoclones'] = 'Копировать настройки в клоны';
$string['readaloud:submit'] = 'Отправлять попытки ReadAloud';
$string['readaloud:viewreports'] = 'Просматривать оценки и отчёты ReadAloud';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'Это содержимое всплывающей подсказки для поля readaloudname. Поддерживается синтаксис Markdown.';

$string['readaloudtask'] = 'Задание ReadAloud';
$string['reattempt'] = 'Попробовать снова';
$string['reports'] = 'Отчёты';
$string['returntogradinghome'] = 'Вернуться к оцениванию';
$string['returntomachinegradinghome'] = 'Вернуться к автоматическому оцениванию';
$string['returntoreports'] = 'Вернуться к отчётам';
$string['saveandnext'] = 'Сохранить и далее';
$string['showingattempt'] = 'Показана попытка пользователя: {$a}';
$string['showingmachinegradedattempt'] = 'Показана автоматически оценённая попытка пользователя: {$a}';
$string['submitted'] = 'отправлено';
$string['timelimit'] = 'Ограничение по времени';
$string['totalattempts'] = 'Попытки';

$string['unlimited'] = 'без ограничений';
$string['uploadconverterror'] =
        'Произошла ошибка при отправке файла на сервер. Ваша работа НЕ была получена. Обновите страницу и попробуйте снова.';
$string['username'] = 'Пользователь';
$string['view'] = 'Просмотр';
$string['viewgrading'] = 'Просмотреть оценивание';
$string['viewreports'] = 'Просмотреть отчёт';

$string['welcomelabel_details'] = 'Инструкции по умолчанию. Их можно изменить при создании новой активности ReadAloud.';

$string['wpm'] = 'Слов/мин';

// We hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Язык текста';
$string['deleteattemptconfirm'] = "Вы уверены, что хотите удалить эту попытку?";
$string['deletenow'] = '';
$string['allowearlyexit'] = 'Разрешить досрочное завершение';
$string['allowearlyexit_details'] =
        'Если включено, учащиеся смогут завершить задание до истечения времени, нажав кнопку завершения. Скорость чтения (WPM) будет рассчитана по фактическому времени записи.';
$string['allowearlyexit_defaultdetails'] =
        'Устанавливает значение по умолчанию для параметра «Разрешить досрочное завершение». Может быть переопределено в настройках активности. Если включено, учащиеся смогут завершить задание раньше установленного времени. Скорость чтения (WPM) будет рассчитана по фактическому времени записи.';
$string['itemsperpage'] = 'Элементов на странице';
$string['accuracy'] = 'Точность';
$string['accuracy_p'] = 'Точн.(%)';
$string['av_accuracy_p'] = 'Ср. точн.(%)';
$string['h_accuracy_p'] = 'Макс. точн.(%)';
$string['mistakes'] = 'Ошибки';
$string['grade'] = 'Оценка';
$string['grade_p'] = 'Итоговая оценка (%)';
$string['readgrade_p'] = 'Оценка за чтение (%)';
$string['quizscore_p'] = 'Оценка за тест (%)';
$string['av_readgrade_p'] = 'Ср. оценка за чтение (%)';
$string['h_readgrade_p'] = 'Макс. оценка за чтение (%)';
$string['av_quizscore_p'] = 'Ср. оценка за тест (%)';
$string['h_quizscore_p'] = 'Макс. оценка за тест (%)';
$string['av_wpm'] = 'Ср. WPM';
$string['h_wpm'] = 'Макс. WPM';
$string['targetwpm'] = 'Целевой WPM';
$string['targetwpm_details'] =
        'Целевое значение WPM по умолчанию. Оно используется как максимальное значение при расчёте оценки в журнале. Если учащийся достигает или превышает это значение, он получает 100%. Также может быть задано отдельно для каждой активности.';
$string['targetwpm_help'] =
        'Целевое значение WPM. Если учащийся достигает или превышает это значение, он получает 100% за скорость чтения.';
$string['passage'] = 'Текст для чтения';
$string['passage_help'] = "Текст, который будет показан учащемуся для чтения.";
$string['passage_descr'] = "Введите текст для чтения выше. Если вы хотите автоматически сгенерировать аудио, длина текста не должна превышать 3000 символов.";
$string['timelimit_help'] = "Устанавливает ограничение времени на чтение. Время чтения используется при расчёте WPM. При необходимости также включите параметр «Разрешить досрочное завершение».";
$string['ttslanguage_help'] = "Этот параметр используется для распознавания речи и синтеза речи.";
$string['ttsvoice_descr'] = "Голос синтезатора речи для озвучивания текста. Символ + означает более высокое качество голоса. Символ ! означает, что вам потребуется вручную добавить паузы в разделе «Эталонное аудио».";
$string['ttsvoice_help'] = "Голос синтезатора речи для озвучивания текста. Выберите голос, соответствующий языку текста. Символ + означает более высокое качество голоса. Символ ! означает необходимость вручную добавить паузы в разделе «Эталонное аудио». Также в этом разделе можно записать или загрузить собственное эталонное аудио.";
$string['ttsspeed_help'] = "Скорость озвучивания текста синтезатором речи. Для учащихся рекомендуется использовать «Медленно» или «Очень медленно», однако это может немного искажать звучание.";
$string['alternatives_help'] = "Укажите допустимые варианты для отдельных слов текста. Один набор слов в строке. Например: their|there|they're. Подробнее см. в <a href=\"https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity\">документации</a>.";

$string['accadjust'] = 'Фиксированная корректировка';
$string['accadjust_details'] =
        'Количество ошибок чтения, которое будет компенсироваться при расчёте WPM. Используется только при выборе метода «Фиксированная корректировка». Это позволяет уменьшить влияние ошибок автоматической транскрипции.';
$string['accadjust_help'] =
        'Это значение должно максимально соответствовать среднему количеству ошибок автоматической транскрипции для данного текста.';

$string['accadjustmethod'] = 'Корректировка WPM (ИИ)';
$string['accadjustmethod_details'] =
        'Позволяет скорректировать WPM, игнорируя или частично компенсируя ошибки чтения, обнаруженные ИИ. По умолчанию все ошибки вычитаются из итогового значения WPM.';
$string['accadjustmethod_help'] =
        'Доступны три режима: без корректировки, фиксированная корректировка или игнорирование ошибок при расчёте WPM.';
$string['accmethod_none'] = 'Без корректировки';
$string['accmethod_auto'] = 'Автоматическая корректировка';
$string['accmethod_fixed'] = 'Фиксированная корректировка';
$string['accmethod_noerrors'] = 'Игнорировать все ошибки';

$string['apiuser'] = 'Пользователь Poodll API';
$string['apiuser_details'] = 'Имя пользователя учётной записи Poodll, используемой для авторизации на этом сайте.';
$string['apisecret'] = 'Секретный ключ Poodll API';
$string['enableai'] = 'Включить ИИ';
$string['enableai_details'] = 'ReadAloud может оценивать результаты попыток учащихся с помощью искусственного интеллекта. Включите этот параметр, чтобы использовать эту возможность.';

$string['useast1'] = 'Восток США';
$string['tokyo'] = 'Токио, Япония';
$string['sydney'] = 'Сидней, Австралия';
$string['dublin'] = 'Дублин, Ирландия';
$string['capetown'] = 'Кейптаун, ЮАР';
$string['bahrain'] = 'Бахрейн';
$string['ottawa'] = 'Оттава, Канада';
$string['frankfurt'] = 'Франкфурт, Германия';
$string['london'] = 'Лондон, Великобритания';
$string['saopaulo'] = 'Сан-Паулу, Бразилия';
$string['singapore'] = 'Сингапур';
$string['mumbai'] = 'Мумбаи, Индия';
$string['ningxia'] = 'Нинся, Китай';
$string['forever'] = 'Никогда не истекает';

$string['azureapikey'] = 'Ключ Azure Speech API';
$string['azureapiregion'] = 'Регион Azure Speech';
$string['otherapikeys'] = 'Другие API-ключи (BYOK)';

$string['en-us'] = 'Английский (США)';
$string['es-us'] = 'Испанский (США)';
$string['en-au'] = 'Английский (Австралия)';
$string['en-ph'] = 'Английский (Филиппины)';
$string['en-gb'] = 'Английский (Великобритания)';
$string['fr-ca'] = 'Французский (Канада)';
$string['fr-fr'] = 'Французский (Франция)';
$string['it-it'] = 'Итальянский (Италия)';
$string['pt-br'] = 'Португальский (Бразилия)';
$string['en-in'] = 'Английский (Индия)';
$string['es-es'] = 'Испанский (Испания)';
$string['fr-fr'] = 'Французский (Франция)';
$string['fil-ph'] = 'Филиппинский';
$string['de-de'] = 'Немецкий (Германия)';
$string['de-ch'] = 'Немецкий (Швейцария)';
$string['de-at'] = 'Немецкий (Австрия)';
$string['da-dk'] = 'Датский (Дания)';
$string['hi-in'] = 'Хинди';
$string['ko-kr'] = 'Корейский';
$string['ar-ae'] = 'Арабский (Персидский залив)';
$string['ar-sa'] = 'Арабский (современный литературный)';
$string['zh-cn'] = 'Китайский (мандаринский, материковый Китай)';
$string['nl-nl'] = 'Нидерландский (Нидерланды)';
$string['nl-be'] = 'Нидерландский (Бельгия)';
$string['en-ie'] = 'Английский (Ирландия)';
$string['en-wl'] = 'Английский (Уэльс)';
$string['en-ab'] = 'Английский (Шотландия)';
$string['en-nz'] = 'Английский (Новая Зеландия)';
$string['en-za'] = 'Английский (ЮАР)';
$string['fa-ir'] = 'Персидский';

$string['he-il'] = 'Иврит';
$string['id-id'] = 'Индонезийский';
$string['ja-jp'] = 'Японский';
$string['ms-my'] = 'Малайский';
$string['mi-nz'] = 'Маори';
$string['pt-pt'] = 'Португальский (Португалия)';
$string['ru-ru'] = 'Русский';
$string['ta-in'] = 'Тамильский';
$string['te-in'] = 'Телугу';
$string['tr-tr'] = 'Турецкий';

$string['uk-ua'] = 'Украинский';
$string['eu-es'] = 'Баскский';
$string['fi-fi'] = 'Финский';
$string['hu-hu'] = 'Венгерский';

$string['sv-se'] = 'Шведский';
$string['no-no'] = 'Норвежский';
$string['nb-no'] = 'Норвежский (букмол)';
$string['nn-no'] = 'Норвежский (нюнорск)';
$string['pl-pl'] = 'Польский';
$string['ro-ro'] = 'Румынский';

$string['bg-bg'] = 'Болгарский'; // Bulgarian
$string['cs-cz'] = 'Чешский'; // Czech
$string['el-gr'] = 'Греческий'; // Greek
$string['hr-hr'] = 'Хорватский'; // Croatian
$string['lt-lt'] = 'Литовский'; // Lithuanian
$string['lv-lv'] = 'Латышский'; // Latvian
$string['sk-sk'] = 'Словацкий'; // Slovak
$string['sl-si'] = 'Словенский'; // Slovenian
$string['so-so'] = 'Сомалийский'; // Slovenian
$string['ps-af'] = 'Пушту'; // Afghan Pashto
$string['is-is'] = 'Исландский'; // Icelandic
$string['mk-mk'] = 'Македонский'; // Macedonian
$string['sr-rs'] = 'Сербский'; // Serbian
$string['vi-vn'] = 'Вьетнамский'; // Vietnamese

$string['awsregion'] = 'Регион AWS';
$string['region'] = 'Регион AWS';
$string['awsregion_details'] = 'Выберите ближайший к вам регион. Ваши данные будут храниться в пределах этого региона. Регион Кейптаун поддерживает только английский и немецкий языки.';
$string['expiredays'] = 'Сколько дней хранить файл';
$string['aigradenow'] = 'Оценить с помощью ИИ';

$string['machinegrading'] = 'Автоматические оценки';
$string['viewmachinegrading'] = 'Автоматическая оценка';
$string['review'] = 'Проверка';
$string['regrade'] = 'Переоценить';

$string['spotcheckbutton'] = "Режим выборочной проверки";
$string['gradingbutton'] = "Режим оценивания";
$string['transcriptcheckbutton'] = "Режим проверки транскрипта";
$string['doclear'] = "Очистить все маркеры";

$string['gradethisattempt'] = "Оценить эту попытку";
$string['rawwpm'] = "WPM";
$string['rawaccuracy_p'] = 'Точн.(%)';
$string['rawgrade_p'] = 'Оценка (%)';
$string['adjustedwpm'] = "Скорр. WPM";
$string['adjustedaccuracy_p'] = 'Скорр. точн.(%)';
$string['adjustedgrade_p'] = 'Скорр. оценка (%)';

$string['evaluationview'] = "Отображение оценки";
$string['evaluationview_details'] = "Что показывать учащимся после выполнения попытки и получения оценки";
$string['humanpostattempt'] = "Отображение оценки (ручная)";
$string['machinepostattempt'] = "Отображение оценки (автоматическая)";
$string['machinepostattempt_details'] = "Что показывать учащимся после выполнения попытки и получения автоматической оценки";
$string['postattempt_none'] = "Показывать текст. Не показывать оценку и ошибки.";
$string['postattempt_eval'] = "Показывать текст и оценку (WPM, точность, оценка)";
$string['postattempt_evalerrorsnograde'] = "Показывать текст, оценку (WPM, точность) и ошибки";
$string['postattempt_evalerrors'] = "Показывать текст, оценку (WPM, точность, оценка) и ошибки";

$string['attemptsperpage'] = "Попыток на странице: ";
$string['backtotop'] = "Проверить результаты";
$string['transcript'] = "Транскрипт";
$string['quickgrade'] = "Быстрое оценивание";
$string['ok'] = "OK";
$string['ng'] = "Не OK";
$string['notok'] = "Не OK";
$string['machinegrademethod'] = "Ручное/автоматическое оценивание";
$string['machinegrademethod_help'] = "Использовать ручные или автоматические оценки в журнале оценок.";
$string['machinegradenone'] = "Никогда не использовать автоматическую оценку";
$string['machinegradehybrid'] = "Использовать ручную или автоматическую оценку";
$string['machinegrademachineonly'] = "Всегда использовать автоматическую оценку";
$string['admintab'] = "Администратор";
$string['viewadmintab'] = 'Просмотреть вкладку администратора';
$string['machineregradeall'] = 'Сохранить и переоценить все попытки';
$string['pushalltogradebook'] = 'Повторно отправить оценки в журнал оценок';
$string['currenterrorestimate'] = 'Текущая оценка ошибок: {$a}';
$string['admintabtitle'] = 'Администратор';
$string['admintabinstructions'] =
        'На этой странице можно редактировать альтернативные варианты для текста, просматривая сводку ошибок транскрипции. После сохранения все попытки будут переоценены, а скорректированные оценки отправлены в журнал оценок.';

$string['noattemptsregrade'] = 'Нет попыток для переоценки';
$string['machineregraded'] = 'Успешно переоценено попыток: {$a->done}. Пропущено попыток: {$a->skipped}.';
$string['machinegradespushed'] = 'Оценки успешно отправлены в журнал оценок';

$string['notimelimit'] = 'Без ограничения времени';
$string['xsecs'] = '{$a} секунд';
$string['onemin'] = '1 минута';
$string['xmins'] = '{$a} минут';
$string['oneminxsecs'] = '1 минута {$a} секунд';
$string['xminsecs'] = '{$a->minutes} минут {$a->seconds} секунд';

$string['postattemptheader'] = 'Параметры после попытки';
$string['recordingaiheader'] = 'Параметры записи и ИИ';
$string['grader'] = 'Оценено';
$string['grader_ai'] = 'ИИ';
$string['grader_human'] = 'Преподаватель';
$string['grader_ungraded'] = 'Не оценено';

$string['displaysubs'] = '{$a->subscriptionname}: истекает {$a->expiredate}';
$string['noapiuser'] = "Пользователь API не указан. ReadAloud не будет работать корректно.";
$string['noapisecret'] = "Секретный ключ API не указан. ReadAloud не будет работать корректно.";
$string['credentialsinvalid'] = "Указанные пользователь API и секретный ключ не подходят для получения доступа. Проверьте их.";
$string['appauthorised'] = "Poodll ReadAloud авторизован для этого сайта.";
$string['appnotauthorised'] = "Poodll ReadAloud НЕ авторизован для этого сайта.";
$string['refreshtoken'] = "Обновить информацию о лицензии";
$string['notokenincache'] = "Обновите, чтобы увидеть информацию о лицензии. Если возникла проблема, обратитесь в поддержку Poodll.";
// These errors are displayed on activity page.
$string['nocredentials'] = 'Пользователь API и секретный ключ не указаны. Укажите их на <a href="{$a}">странице настроек.</a> Получить их можно на <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'Пользователь API и секретный ключ были отклонены, доступ не получен. Проверьте их на <a href="{$a}">странице настроек.</a> Получить их можно на <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "Для этого сайта/плагина нет активной подписки.";

$string['privacy:metadata:attemptid'] = 'Уникальный идентификатор попытки ReadAloud пользователя.';
$string['privacy:metadata:readaloudid'] = 'Уникальный идентификатор экземпляра активности ReadAloud.';
$string['privacy:metadata:userid'] = 'ID пользователя для попытки ReadAloud.';
$string['privacy:metadata:filename'] = 'URL файлов отправленных аудиозаписей.';
$string['privacy:metadata:wpm'] = 'Показатель слов в минуту для попытки.';
$string['privacy:metadata:accuracy'] = 'Показатель точности для попытки.';
$string['privacy:metadata:sessionscore'] = 'Итоговый балл сессии для попытки.';
$string['privacy:metadata:sessiontime'] = 'Время сессии (время записи) для попытки.';
$string['privacy:metadata:sessionerrors']
        = 'Ошибки чтения для попытки.';
$string['privacy:metadata:sessionendword'] = 'Позиция последнего слова для попытки.';
$string['privacy:metadata:errorcount'] = 'Количество ошибок чтения для попытки.';
$string['privacy:metadata:timemodified'] = 'Время последнего изменения попытки.';
$string['privacy:metadata:attempttable'] = 'Хранит оценки и другие пользовательские данные, связанные с попыткой чтения вслух.';
$string['privacy:metadata:aitable'] =
        'Хранит оценки и другие пользовательские данные, связанные с попыткой чтения вслух, оцененной автоматически.';
$string['privacy:metadata:transcriptpurpose'] = 'Краткие транскрипты записей.';
$string['privacy:metadata:fulltranscriptpurpose'] = 'Полные транскрипты записей.';
$string['privacy:metadata:cloudpoodllcom:userid'] =
        'Плагин ReadAloud включает ID пользователя Moodle в URL записей и транскриптов.';
$string['privacy:metadata:cloudpoodllcom'] = 'Плагин ReadAloud хранит записи в бакетах AWS S3 через cloud.poodll.com.';

$string['mistranscriptions_summary'] = 'Сводка ошибок транскрипции.';
$string['nomistranscriptions'] = 'Ошибок транскрипции нет.';
$string['passageindex'] = 'Индекс в тексте';
$string['passageword'] = 'Слово в тексте';
$string['mistranscriptions'] = 'Ошибки транскрипции';
$string['mistrans_count'] = 'Количество';
$string['total_mistranscriptions'] = 'Всего ошибок транскрипции: {$a}';
$string['startreading'] = 'Читать';
$string['readagain'] = 'Прочитать снова';
$string['transcriber_guided'] = 'Guided STT (Poodll)';
$string['transcriber_strict'] = 'Open STT (Strict)';

$string['stricttranscribe'] = 'Транскрайбер текста';
$string['stricttranscribe_details'] = 'Транскрайбер, который используется для чтения полного текста.';

$string['sessionscoremethod'] = 'Расчёт оценки';
$string['sessionscoremethod_help'] = 'Значение (%) для журнала оценок рассчитывается как процент: либо WPM / Target_WPM (обычный режим), либо (WPM - ошибки) / Target_WPM (строгий режим).';
$string['sessionscorenormal'] = 'Обычный: общее количество правильных слов в минуту / Target_WPM';
$string['sessionscorestrict'] = 'Строгий: (общее количество правильных слов - ошибки) в минуту / Target WPM';
$string['modelaudio'] = 'Эталонное аудио';
$string['ttsvoice'] = 'Голос TTS';
$string['enablepreview'] = 'Включить режим прослушивания';
$string['enableshadow'] = 'Включить режим тренировки (Shadowing)';
$string['enablelandr'] = 'Включить режим тренировки (Listen and Repeat)';
$string['savemodelaudio'] = 'Сохранить запись';
$string['uploadmodelaudio'] = 'Загрузить аудиофайл';
$string['modelaudioclear'] = 'Очистить аудио';
$string['modelaudiobreaksgenerate'] = 'Повторно сгенерировать разметку эталонного аудио';
$string['modelaudio_recordinstructions'] = 'Запишите здесь аудио, которое будет использоваться как эталонное. При желании можно загрузить аудио, нажав кнопку загрузки. Синхронизация текста с аудио и точками пауз может занять несколько минут.';
$string['modelaudio_playerinstructions'] = 'Текущее эталонное аудио можно прослушать с помощью плеера ниже.';
$string['modelaudio_breaksinstructions'] = 'Нажимайте на слова в тексте ниже, чтобы добавить паузу в этой точке воспроизведения аудио в режимах прослушивания и тренировки. Система автоматически синхронизирует аудио и текст. Включите <i>ручное время пауз</i>, чтобы привязывать добавленные паузы к текущей позиции воспроизведения аудио.';
$string['modelaudio_recordtitle'] = 'Записать эталонное аудио';
$string['modelaudio_playertitle'] = 'Прослушать эталонное аудио';
$string['modelaudio_breakstitle'] = 'Разметить эталонное аудио';
$string['viewmodeltranscript'] = 'Просмотреть транскрипт эталона';

$string['ttsspeed'] = 'Скорость TTS';
$string['mediumspeed'] = 'Средняя';
$string['slowspeed'] = 'Медленная';
$string['extraslowspeed'] = 'Очень медленная';

$string['welcomemenu'] = 'Выберите один из вариантов ниже.';
$string['returnmenu'] = 'Вернуться в меню';
$string['attemptno'] = 'Попытка {$a}';
$string['previewhelp'] = "Прослушайте, как диктор читает текст вслух. Вам не нужно читать вслух.";
$string['readhelp'] = "Прочитайте текст вслух. Говорите в естественном для вас темпе.";
$string['shadowhelp'] = "Читайте текст вслух вместе с преподавателем. Рекомендуется использовать наушники.";
$string['practicehelp'] = "Слушайте диктора. Повторяйте после каждого предложения и проверяйте произношение.";
$string['quizhelp'] = "Прочитайте текст про себя. Затем ответьте на вопросы по тексту.";
$string['quizfinishedhelp'] = "Проверьте свои результаты. Насколько хорошо вы поняли текст?";
$string['playbutton'] = "Воспроизвести";
$string['recordbutton'] = "Записать";
$string['stopbutton'] = "Стоп";
$string['taptolisten'] = "Нажмите, чтобы послушать";

$string['returntomenu'] = "Вернуться в меню";
$string['fullreport'] = "Посмотреть полный отчёт";
$string['fullreportnoeval'] = "Посмотреть текст";

$string['secs_till_check'] = 'Проверка результатов через: ';
$string['checking'] = ' ... проверка ... ';

$string['recorder'] = 'Тип аудиорекордера';
$string['recorder_help'] = 'Выберите тип аудиорекордера, который лучше всего подходит вашим учащимся и ситуации.';
$string['defaultrecorder'] = 'Рекордер по умолчанию';
$string['defaultrecorder_details'] = 'Выберите рекордер, который будет показываться учащимся по умолчанию.';
$string['rec_readaloud'] = 'Проверка микрофона, затем старт';
$string['rec_once'] = 'Сразу начать';
$string['rec_upload'] = 'Загрузка (для разработчиков/админов)';

$string['close'] = 'Закрыть';
$string['modelaudiowarning'] = "Эталонное аудио не размечено.";
$string['modelaudiobreaksclear'] = ' Очистить разметку эталонного аудио';
$string['savemodelaudiomarkup'] = ' Сохранить разметку эталонного аудио';
$string['enablesetuptab'] = "Включить вкладку настройки";
$string['enablesetuptab_details'] = "Показывать администраторам вкладку с настройками экземпляра активности. В большинстве случаев не особенно полезно.";
$string['setup'] = "Настройка";
$string['manualbreaktiming'] = ' Ручное время пауз';

// rsquestions
$string['numeric'] = 'Должно быть числом ';
$string['iteminuse'] = 'Этот элемент является частью истории попыток пользователей. Его нельзя удалить.';

// Questions.
$string['rsquestions'] = 'Вопросы';
$string['managersquestions'] = 'Управление вопросами';
$string['correctanswer'] = 'Правильный ответ';
$string['incorrectanswer'] = 'Неправильный ответ';
$string['whatdonow'] = 'Добавьте или отредактируйте вопросы для теста после чтения.';
$string['editingitem'] = 'Редактирование вопроса';
$string['createaitem'] = 'Создать вопрос';
$string['edit'] = 'Редактировать';
$string['item'] = 'Элемент';
$string['itemtitle'] = 'Название вопроса';
$string['itemcontents'] = 'Текст вопроса';
$string['answer'] = 'Ответ';
$string['saveitem'] = 'Сохранить элемент';
$string['itemname'] = 'Название вопроса';
$string['itemorder'] = 'Порядок элемента';
$string['actions'] = 'Действия';
$string['edititem'] = 'Редактировать элемент';
$string['previewitem'] = 'Предпросмотр элемента';
$string['duplicateitem'] = 'Дублировать элемент';
$string['confirmitemdelete'] = 'Вы уверены, что хотите <i>УДАЛИТЬ</i> элемент? : {$a}';
$string['confirmitemdeletetitle'] = 'Точно удалить элемент?';
$string['noitems'] = 'В этом тесте нет вопросов';
$string['textchoice'] = 'Выбор из текстовой области';
$string['textboxchoice'] = 'Выбор из текстового поля';
$string['quiz'] = 'Тест';
$string['waiting'] = '-- ожидание --';
$string['waitingforteacher'] = 'Ваш преподаватель скоро проверит ваше чтение.';
$string['quizcompletedwarning'] = "Тест завершён. Нажмите, чтобы просмотреть.";

$string['notmasterinstance'] = 'Нельзя отправить настройки из этой активности ReadAloud, если в настройках активности не отмечен параметр «Master instance».';
$string['push'] = 'Отправить';
$string['pushpage'] = 'Страница отправки настроек';
$string['pushalternatives'] = 'Отправить альтернативные варианты';
$string['pushalternatives_done'] = 'Альтернативные варианты отправлены';

$string['pushpassage'] = 'Отправить текст и связанные настройки';
$string['pushpassage_done'] = 'Текст отправлен';

$string['pushquestions'] = 'Отправить вопросы';
$string['pushquestions_done'] = 'Вопросы отправлены';

$string['pushtargetwpm'] = 'Целевой WPM';
$string['pushtargetwpm_done'] = 'Целевой WPM отправлен';

$string['pushtimelimit'] = 'Ограничение по времени';
$string['pushtimelimit_done'] = 'Ограничение по времени отправлено';

$string['pushcanexitearly'] = 'Можно завершить раньше';
$string['pushcanexitearly_done'] = 'Настройка досрочного завершения отправлена';

$string['pushmodes'] = 'Режимы';
$string['pushmodes_done'] = 'Режимы отправлены';

$string['pushgradesettings'] = 'Настройки оценивания';
$string['pushgradesettings_done'] = 'Настройки оценивания отправлены';

$string['pushttsmodelaudio'] = 'Отправить TTS и эталонное аудио';
$string['pushttsmodelaudio_done'] = 'TTS и эталонное аудио отправлены';

$string['masterinstance'] = 'Основной экземпляр';
$string['masterinstance_details'] = 'Основной экземпляр позволяет автору отправлять отдельные настройки одной активности ReadAloud в существующие копии этой же активности. У них должно быть точно такое же название.';

$string['pushpage_explanation'] = "Используйте кнопки на этой странице, чтобы отправить настройки из этого экземпляра ReadAloud в его клоны, то есть активности с таким же названием. Будьте осторожны: отменить это действие нельзя, поэтому убедитесь в своём намерении перед использованием.";
$string['pushpage_clonecount'] = 'У этой активности есть клоны: {$a}. <br><br>';
$string['pushpage_noclones'] = 'Эта активность является основным экземпляром, но других активностей с таким же названием, то есть клонов, нет. Поэтому отправлять настройки некуда. Проверьте, что выбрана правильная активность. Если вы просто тестируете, продублируйте эту активность и переименуйте копию так же, как эту.<br><br>';

$string['disableshadowgrading'] = "Отключить оценивание режима Shadow";
$string['disableshadowgrading_details'] = "Если включено, попытки, выполненные в режиме Shadow, будут оцениваться, но результат не будет передаваться в журнал оценок.";
$string['developer'] = "Разработчик";

$string['freetrial'] = "Получить данные Cloud Poodll API и бесплатный пробный доступ";
$string['freetrial_desc'] = "Должно появиться диалоговое окно, позволяющее зарегистрироваться на бесплатный пробный доступ Poodll. После регистрации войдите в панель участника, чтобы получить пользователя API и секретный ключ, а также зарегистрировать URL вашего сайта.";
$string['fillcredentials'] = "Указать пользователя API и секретный ключ из существующих данных";
$string['viewstart'] = "Открытие активности";
$string['viewend'] = "Закрытие активности";
$string['viewstart_help'] = "Если задано, учащийся не сможет войти в активность до указанной даты/времени начала.";
$string['viewend_help'] = "Если задано, учащийся не сможет войти в активность после указанной даты/времени закрытия.";
$string['activitydate:submissionsdue'] = 'Срок сдачи:';
$string['activitydate:submissionsopen'] = 'Открывается:';
$string['activitydate:submissionsopened'] = 'Открыто:';
$string['open'] = "Открыто: ";
$string['until'] = "До: ";
$string['activityopenscloses'] = "Даты открытия/закрытия активности";
$string['nottsvoice'] = "Нет голоса TTS";

$string['guidedtranscriptionadmin'] = "Администрирование управляемой транскрипции";
$string['usecorpus'] = "Тип управляемой транскрипции";
$string['usecorpuschanged'] = "Тип управляемой транскрипции изменён";

$string['applysettingsrange'] = "Применить настройку к:";
$string['apply_activity'] = "этой активности";
$string['apply_course'] = "активностям этого курса";
$string['apply_site'] = "активностям этого сайта";

$string['corpusrange'] = "Диапазон корпуса";
$string['corpusrange_course'] = "Этот курс";
$string['corpusrange_site'] = "Этот сайт";
$string['guidedtrans_corpus'] = "Использовать корпус (все тексты ReadAloud)";
$string['guidedtrans_passage'] = "Использовать текст этой активности";
$string['guidedtransinstructions'] = "При использовании управляемой транскрипции транскрайбер будет направлять транскрипт в сторону подсказки, то есть слов/фраз из текста этой активности или из полного корпуса текстов ReadAloud. Использование полного корпуса текстов ReadAloud позволит выявлять больше ошибок чтения.";
$string['pushcorpus_details'] = "Корпус курса/сайта будет обновляться автоматически, но при необходимости вы можете использовать кнопку ниже, чтобы обновить и отправить корпус. Это создаст подсказку на основе выбранного диапазона корпуса и настроит все активности ReadAloud с управляемой транскрипцией в этом диапазоне на использование этой подсказки.";
$string['pushcorpus_button'] = "Обновить и отправить подсказку корпуса";
$string['corpuspushed'] = "Подсказка корпуса отправлена";
$string['passagekey'] = 'Ключ текста';
$string['passagekey_details'] =
        'Ключ текста — это просто тег, который будет экспортироваться в CSV вместе с некоторыми отчётами, чтобы упростить дальнейшую обработку этих отчётов в таблице. Его можно оставить пустым.';
$string['passagekey_help'] =
        'Ключ текста — это просто тег, который будет экспортироваться в CSV вместе с некоторыми отчётами, чтобы упростить дальнейшую обработку этих отчётов в таблице.';

$string['courseattemptsreport'] = 'Отчёт по попыткам курса';
$string['courseattemptsheading'] = 'Отчёт по попыткам курса';
$string['studentid'] = "№ студ.";
$string['studentname'] = "Имя студента";
$string['activityname'] = "Название RA";
$string['errorcount'] = "Кол-во ошибок";
$string['activitywords'] = "Кол-во слов в тексте";
$string['readingtime'] = "Время чтения (сек.)";
$string['oralreadingscore'] = "Оценка устного чтения";
$string['oralreadingscore_p'] = 'Оценка устного чтения (%)';
$string['reportsmenutoptext'] = "Просматривайте попытки в активностях ReadAloud с помощью отчётов ниже.";
$string['courseattempts_explanation'] = "Все попытки в активностях ReadAloud в рамках этого курса";
$string['attemptssummary_explanation'] = "Сводка попыток ReadAloud по пользователям в этой активности.";

$string['customfont'] = "Пользовательский шрифт";
$string['customfont_help'] = "Название шрифта, который переопределит стандартный шрифт сайта при отображении этого текста. Написание и регистр должны точно совпадать, например Andika или Comic Sans MS";
$string['advancedheader'] = "Дополнительно";

$string['missedwords'] = "Пропущенные слова";
$string['missedwordsheading'] = "Пропущенные слова";
$string['missedwordsreport'] = "Пропущенные слова";
$string['missedwords_explanation'] = "Самые частые ошибочные слова в последних попытках";
$string['missed_count'] = "Количество пропусков";
$string['rank'] = "Ранг";

$string['unit_percent'] = "%";

$string['totalwords'] = 'Всего слов';
$string['sentences'] = 'Предложения';
$string['uniquewords'] = 'Уникальные слова';
$string['ideacount'] = 'Понятия';
$string['relevance'] = 'Релевантность';
$string['original'] = 'Оригинал';
$string['corrected'] = 'Исправлено';

$string['confirm_cancel_recording'] = "Отменить запись и выйти из этой попытки?";
$string['confirm_read_again'] = "Отменить это чтение и сделать новую попытку?";
$string['aitextutilsshow'] = "Показать AI-инструменты для текста (бета)";
$string['aitextutilshide'] = "Скрыть AI-инструменты для текста (бета)";
$string['textgenerator_instructions'] = "Введите короткое описание научно-популярной темы и нажмите кнопку, чтобы сгенерировать текст. Он часто может быть фактически неточным. Будьте осторожны при использовании с учащимися.";
$string['textsimplifier_instructions'] = "Выберите уровень упрощения и нажмите кнопку, чтобы упростить текст. Текст будет упрощён примерно до выбранного вами уровня.";
$string['article-topic-here'] = "например, плюсы и минусы социальных сетей";
$string['generate-text'] = "Сгенерировать текст";
$string['simplify-text'] = "Упростить текст";
$string['entersomething'] = "Введите тему, чтобы сгенерировать текст";
$string['text-too-long-100'] = "Тема должна быть не длиннее 100 символов. Просто опишите тему, не пишите полное предложение и не добавляйте дополнительные инструкции.";
$string['textoverwriteconfirm'] = "Подтверждение перезаписи";
$string['reallyoverwritepassage'] = "Перезаписать текущий текст?";
$string['overwrite'] = "Перезаписать";
$string['cancel'] = "Отмена";
$string['datatables_info'] = "Показаны записи с _START_ по _END_ из _TOTAL_";
$string['datatables_infoempty'] = "Показаны записи с 0 по 0 из 0";
$string['datatables_lengthmenu'] = "Показать _MENU_ записей";
$string['datatables_search'] = "Поиск:";
$string['datatables_zerorecords'] = "Совпадающих записей не найдено";
$string['datatables_paginate_first'] = "Первая";
$string['datatables_paginate_last'] = "Последняя";
$string['datatables_paginate_next'] = "Следующая";
$string['datatables_paginate_previous'] = "Предыдущая";
$string['datatables_emptytable'] = "Нет данных в таблице";
$string['datatables_aria_sortascending'] = "активируйте для сортировки столбца по возрастанию";
$string['datatables_aria_sortdescending'] = "активируйте для сортировки столбца по убыванию";
$string['one_simplest'] = "один (самый простой)";
$string['two'] = "два";
$string['three'] = "три";
$string['four'] = "четыре";
$string['five'] = "пять";
$string['passagepicture'] = 'Изображение к тексту';
$string['passagepicture_descr'] = 'Добавить изображение в шапку активности.';
$string['stdashboardid'] = 'ID панели студента';
$string['eventreadaloudattemptsubmitted'] = 'Попытка ReadAloud отправлена';
$string['bulkdelete'] = 'Удалить выбранное';
$string['bulkdeletequestion'] = 'Вы уверены, что хотите удалить выбранный вопрос?';
$string['addquestion'] = 'Добавить вопрос';
$string['multichoice'] = 'Множественный выбор';
$string['multiaudio'] = 'Аудио с выбором ответа';
$string['dictation'] = 'Диктант';
$string['dictationchat'] = 'Диктант-чат';
$string['speechcards'] = 'Речевые карточки';
$string['listenrepeat'] = 'Слушать и повторять';
$string['page'] = 'Страница с контентом';
$string['smartframe'] = 'SmartFrame';
$string['shortanswer'] = 'Краткий ответ';
$string['lgapfill'] = 'Заполнение пропусков на слух';
$string['sgapfill'] = 'Устное заполнение пропусков';
$string['tgapfill'] = 'Письменное заполнение пропусков';
$string['spacegame'] = 'Космическая игра';
$string['freewriting'] = 'Свободное письмо';
$string['freespeaking'] = 'Свободная речь';
$string['fluency'] = 'Беглость';
$string['passagereading'] = 'Чтение текста';
$string['conversation'] = 'Диалог';
$string['pagelayout'] = 'Макет страницы';
$string['newitem'] = 'Элемент: {$a}';

$string['completiondetail:mingrade'] = 'Минимальная оценка';
$string['completiondetail:allsteps'] = 'Все этапы';
$string['completionallsteps'] = 'Все этапы';
$string['allsteps'] = 'Все этапы';
$string['completionallsteps_help'] = 'Для завершения активности необходимо пройти все этапы';
$string['mingrade_help'] = 'Минимальная оценка ReadAloud (%) для завершения этой активности.';
$string['allsteps_help'] = 'Для завершения активности необходимо пройти все этапы';

$string['d_question'] = 'Элемент';
$string['freespeaking_instructions1'] = 'Используйте микрофон, чтобы записать ответ на вопрос.';
$string['freewriting_instructions1'] = 'Введите ответ на вопрос в текстовое поле ниже.';
$string['lg_instructions1'] = 'Инструкции для заполнения пропусков на слух';
$string['sg_instructions1'] = 'Инструкции для устного заполнения пропусков';
$string['tg_instructions1'] = 'Инструкции для письменного заполнения пропусков';
$string['multiaudio_instructions1'] = 'Выберите правильный ответ. Используйте микрофон, чтобы прочитать его вслух.';
$string['multichoice_instructions1'] = 'Выберите правильный ответ.';
$string['shortanswer_instructions1'] = 'Ответьте на вопрос с помощью микрофона.';
$string['iteminstructions'] = 'Инструкции к элементу';
$string['chooselayout'] = 'Выберите макет';
$string['layoutauto'] = 'Авто';
$string['layoutvertical'] = 'Вертикальный';
$string['layouthorizontal'] = 'Горизонтальный';
$string['layoutmagazine'] = 'Журнальный';
$string['mediaprompts'] = "Медиа-подсказки";

$string['addmedia'] = 'Изображение / аудио или видео';
$string['addttsaudio'] = 'Аудио TTS';
$string['addtextarea'] = 'Текстовый блок';
$string["reallydeletemediaprompt"] = "Действительно удалить медиа: ";
$string["deletemediaprompt"] = "Удалить медиа?";
$string["choosemediaprompt"] = "Выберите тип медиа...";
$string["deletefilesfirst"] = "Удалите все файлы, которые вы добавили вручную. Они не будут удалены автоматически.";
$string["cleartextfirst"] = "Очистите весь контент, который вы добавили вручную. Он не будет удалён автоматически.";

$string['itemmedia'] = 'Изображение, аудио или видео для показа';
$string['itemttsquestion'] = 'Текст подсказки TTS';
$string['itemttsquestionvoice'] = 'Голос подсказки TTS';
$string['itemtextarea'] = 'Текстовый блок';

$string['choosevoiceoption'] = 'Параметры подсказки TTS';
$string['autoplay'] = 'Автовоспроизведение';
$string["itemsettingsheadings"] = "Настройки элемента";

$string['enterresponses'] = 'Введите список правильных ответов в текстовое поле ниже. Каждый ответ должен быть на новой строке.';
$string['correctresponses'] = 'Правильные ответы';
$string['choosevoice'] = "Выберите голос диктора для подсказки";
$string['choosemultiaudiovoice'] = "Выберите голос диктора для ответа";
$string['showoptionsastext'] = 'Показывать ответы как текст';
$string['showtextprompt'] = 'Показывать текстовую подсказку';
$string['textprompt_words'] = 'Показывать полный текст';
$string['textprompt_dots'] = 'Показывать точки вместо букв';
$string['listenorread'] = "Отображать варианты как";
$string['listenorread_read'] = 'обычный текст';
$string['listenorread_listen'] = 'аудиоплееры + точки';
$string['listenorread_listenandread'] = 'аудиоплееры + обычный текст';
$string['listenorread_image'] = 'изображения + обычный текст';
$string['confirmchoice_formlabel'] = "Обязательная попытка (нельзя пропустить)";
$string['continue'] = "Продолжить <i class='fa fa-arrow-right'></i>";
$string['confirmchoice'] = "Проверить";
$string['listeninggapfill'] = 'Заполнение пропусков на слух';
$string['speakinggapfill'] = 'Устное заполнение пропусков';
$string['typinggapfill'] = 'Письменное заполнение пропусков';
$string['gapfillitemsdesc'] = 'Введите список элементов в текстовое поле ниже. Каждый элемент должен быть на новой строке. Пропуски букв должны быть заключены в квадратные скобки: [ ]. Формат:<br>Текстовая подсказка | подсказка<br>Например: This is my d[og]| a common pet';
$string['listeninggapfillitemsdesc'] = 'Введите список элементов в текстовое поле ниже. Каждый элемент должен быть на новой строке. Пропуски букв должны быть заключены в квадратные скобки: [ ]. Формат:<br>Текстовая подсказка<br>Например: This is my d[og]';
$string['readsentences'] = 'Читать предложения (TTS)';
$string['readsentences_desc'] = 'Если включено, каждое предложение будет прочитано вслух. Это будет формат диктанта.';
$string['allowretry'] = 'Разрешить повторную попытку';
$string['allowretry_desc'] = 'Если включено, учащиеся смогут отправить новую попытку, если предыдущий ответ был неправильным.';
$string['hidestartpage'] = 'Скрыть стартовую страницу';
$string['hidestartpage_desc'] = 'Если включено, элемент активности начнётся сразу после загрузки.';
$string['sentenceprompts'] = 'Предложения (подсказки)';
$string['relevancetype'] = 'Тип релевантности';
$string['relevancetype_none'] = 'Релевантность не учитывается';
$string['relevancetype_question'] = 'Релевантность вопросу (тексту элемента)';
$string['relevancetype_modelanswer'] = 'Релевантность эталонному ответу';
$string['freewritingdesc'] = 'Задайте целевое количество слов, критерии оценивания и инструкции для обратной связи ИИ. Учащиеся вводят ответ на тему и получают оценку и обратную связь от ИИ.';
$string['freespeakingdesc'] = '<b>Free Speaking — это тип элемента в бета-версии.</b> В разных браузерах и на разных мобильных устройствах поведение может отличаться.<br/><br/> Задайте целевое количество слов, критерии оценивания и инструкции для обратной связи ИИ. Учащиеся записывают устный ответ на тему и получают оценку и обратную связь от ИИ.';
$string['freespeaking_default_aigrade'] = 'Вычитайте 1 балл за каждую грамматическую ошибку, но не снижайте оценку за орфографические ошибки или пунктуацию.';
$string['freespeaking_default_aigradefeedback'] = 'Просто объясните каждую грамматическую ошибку.';
$string['freewriting_default_aigrade'] = 'Вычитайте 1 балл за каждую грамматическую, орфографическую или пунктуационную ошибку.';
$string['freewriting_default_aigradefeedback'] = 'Просто объясните каждую ошибку.';
$string['writehere'] = 'Пишите здесь...';
$string['submit'] = 'Отправить';
$string['fs_totalmarks_instructions'] = 'Общее количество баллов, которое этот элемент Free Speaking даёт в оценку за тест.';
$string['fw_totalmarks_instructions'] = 'Общее количество баллов, которое этот элемент Free Writing даёт в оценку за тест.';
$string['targetwordcount_title'] = 'Целевое количество слов';
$string['totalmarks'] = 'Всего баллов';
$string['aigrade_instructions'] = 'Инструкции по оцениванию для ИИ';
$string['aigrade_feedback'] = 'Инструкции для обратной связи ИИ';
$string['aigrade_feedback_language'] = 'Язык обратной связи ИИ';
$string["aigrade_feedback_title"] = "Обратная связь";

$string['action'] = 'Действие';
$string['order'] = 'Порядок';
$string['deletebuttonlabel'] = 'УДАЛИТЬ';
$string['totalscore'] = 'Балл';
$string['reattempttitle'] = "Повторить тест";
$string['reattemptbody'] = "Хотите пройти этот тест ещё раз?";
$string['questiontext'] = "Вопрос";
$string['check'] = "Проверить";
$string['skip'] = "Пропустить";
$string['start'] = "Начать";
$string['score'] = "Балл";
$string['currentwordcount'] = "Количество слов";
$string['showcorrections'] = "Показать исправления в тексте";
$string['hidecorrections'] = "Скрыть исправления в тексте";
$string['reallyreattempt'] = 'Ваша предыдущая попытка будет перезаписана. Вы уверены, что хотите попробовать снова?';
$string['answerdetails'] = 'Детали ответа';

$string["allowmicaccess"] = "Разрешите доступ к микрофону.";
$string["nomicdetected"] = "Микрофон не обнаружен.";
$string["speechnotrecognized"] = "Мы не смогли распознать вашу речь.";
$string['gapfill_results'] = 'Результаты';
$string['loading'] = 'Загрузка...';
$string['dc_results'] = 'Результаты';

$string["quizsettingsheader"] = "Настройки теста";
$string["quizscore"] = "Результат теста";
$string["showqtitles"] = "Показывать названия вопросов";
$string["showqtitles_help"] = "Показывать названия вопросов";
$string["showqreview"] = "Показывать обзор теста";
$string["showqreview_help"] = "Показывать обзор теста";
$string["qfinishscreen"] = "Экран завершения теста";
$string["qfinishscreen_details"] = "После завершения теста можно показать простой экран, полный экран или пользовательский экран. Пользовательский экран — это страница, которую вы можете оформить самостоятельно.";
$string["qfinishscreen_help"] = "После завершения теста можно показать простой экран, полный экран или пользовательский экран. Пользовательский экран — это страница, которую вы можете оформить самостоятельно.";
$string["qfinishscreen_simple"] = "Простой — только балл";
$string["qfinishscreen_full"] = "Полный — балл и детали вопросов";
$string["qfinishscreen_custom"] = "Пользовательский";
$string["qfinishscreencustom"] = "Пользовательский экран завершения";
$string["qfinishscreencustom_help"] = "Пользовательский экран — это расширенная функция, которая позволяет создать собственный экран завершения с помощью синтаксиса Mustache и переменных. Некоторые переменные: {total}, {courseurl}, {coursename}, {yellowstars}, {graystars}, {reattempturl}, а также массив {results}, где каждый элемент содержит переменные {title}, {grade}, {yellowstars} и {graystars}.";

// Modes.
$string['home'] = 'Главная';
$string['mode_listen'] = 'Слушать';
$string['mode_practice'] = 'Практика';
$string['mode_quiz'] = 'Тест';
$string['mode_read'] = 'Читать';
$string['mode_shadow'] = 'Shadow';
$string['mode_report'] = 'Отчёт';

$string['next'] = 'Далее';
$string['prev'] = 'Назад';
$string['taptospeak'] = 'Нажмите, чтобы говорить';

$string['enablenativelanguage'] = "Включить родной язык";
$string['enablenativelanguage_details'] = 'Если включено, учащийся сможет выбрать свой родной язык. Он заменит язык обратной связи по умолчанию, на котором ИИ возвращает результаты Free Writing и Free Speaking в тесте. Сейчас язык должен быть <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">настроен в Poodll WordCards</a>.';
$string['letsadditems'] = 'Давайте добавим вопросы!';
$string['additems'] = 'Добавить вопросы теста';
$string['numberonly'] = 'Только числа';
$string['aigrade_modelanswer'] = 'Эталонный ответ';
$string['enableread'] = 'Включить чтение';
$string['enablequiz'] = 'Включить тест';
$string['activitysteps'] = 'Этапы активности';
$string['activitystepsdetails'] = 'Настройте учебные этапы этой активности ReadAloud.';
$string['error_nosteps'] = 'Необходимо включить хотя бы один этап.';
$string['alternatestreaming'] = 'Включить альтернативную потоковую передачу';
$string['alternatestreaming_details'] = 'Передаёт записанное аудио потоком для открытой транскрипции. Немного медленнее стандартной браузерной транскрипции и работает только на английском. По умолчанию включено в мобильном приложении.';
$string['cloudpoodllserver'] = 'Сервер Cloud Poodll';
$string['cloudpoodllserver_details'] = 'Сервер, используемый для Cloud Poodll. Меняйте только в том случае, если Poodll предоставил другой сервер.';

$string['almost'] = 'Почти...';
$string['almost_desc'] = 'Вы неправильно произнесли некоторые слова. Хотите попробовать снова или продолжить?';
$string['continue'] = 'Продолжить';
$string['dontshowtilltheend'] = "Не показывать до конца";
$string['imready'] = "Я готов(а)";
$string['incorrect'] = 'Неправильно';
$string['incorrect_desc'] = "Вы произнесли это неправильно. Хотите попробовать снова или продолжить?";
$string['keeplistening'] = 'Продолжить слушать';
$string['keeppracticing'] = 'Продолжить практику';
$string['listen'] = 'Слушать';
$string['listenorpractice'] = 'Вы можете продолжить слушать или начать практику.';
$string['nextsentence'] = 'Следующее предложение';
$string['noquestions'] = 'Нет вопросов для отображения.';
$string['practice'] = 'Практика';
$string['practicecomplete'] = 'Отлично, вы завершили тренировку!';
$string['practicecomplete_desc'] = 'Похоже, вы готовы прочитать весь текст.';
$string['question'] = 'Вопрос?';
$string['questions'] = 'Вопросы';
$string['quizresults'] = 'Результаты теста';
$string['quiztime'] = 'Время теста';
$string['quiztimehelp'] = 'Пройдите тест, чтобы дополнительно проверить навык чтения.';
$string['readaloudresults'] = 'Результаты чтения вслух';
$string['readingpassage'] = 'Текст для чтения';
$string['readreporthelp'] = "Проверьте свои результаты. Насколько хорошо вы поняли текст?";
$string['readreportdummyhelp'] = "Ваши результаты уже обрабатываются... пожалуйста, подождите...";
$string['nowevaluatingreading'] = "Мы оцениваем ваше чтение... подождите немного...";

$string['takethequiz'] = 'Пройти тест';
$string['timetopractice'] = 'Закончили слушать?';
$string['tryagain'] = 'Попробовать снова';
$string['viewfinalreport'] = 'Посмотреть итоговый отчёт';
$string['viewfinalreportintro'] = 'Ваши полные результаты и сводка прогресса.';
$string['finalreporthelp'] = 'Ваши полные результаты и сводка прогресса.';
$string['welldone'] = 'Отлично!';
$string['welldone_desc'] = 'Вы правильно произнесли все слова!';
$string['quitlistening'] = 'Завершить прослушивание';
$string['improveyourscore'] = 'Хотите попробовать улучшить свой результат?';
$string['reallyreattemptquiz'] = 'Повторное прохождение теста перезапишет вашу предыдущую попытку. Вы уверены, что хотите попробовать снова?';
$string['quizreattempt'] = 'Можно повторить тест';
$string['quizreattempt_help'] = 'Разрешить учащемуся повторить тест в рамках текущей попытки.';
$string['readreattempt'] = 'Можно повторить чтение';
$string['readreattempt_help'] = 'Разрешить учащемуся повторить чтение в рамках текущей попытки.';

$string['azureapikey_details'] = 'Это API-ключ для использования сервисов Azure Speech с ReadAloud. Он необязателен. В основном предназначен для пользователей из материкового Китая. Подробнее см. <a href= "https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/overview">здесь</a>.';
$string['azureapiregion_details'] = 'Это регион для вашего API-ключа Azure Speech. Если у вас его нет, вы можете получить его на портале Azure.';
$string['machinegrademethod_details'] = "Использовать автоматические или ручные оценки в журнале оценок.";
$string['sessionscoremethod_details'] = 'Как рассчитывается значение (%) для журнала оценок.';
$string['ttslanguage_details'] = 'Этот параметр используется для распознавания речи и синтеза речи.';
$string['itemsperpage_details'] = 'Задаёт количество строк, отображаемых в отчётах или списках попыток.';
$string['stdashboardid_details'] = 'Если установлен блок панели студента, укажите здесь ID этого блока.';

// Duplicate strings.
$string['readaloud:view'] = 'Предпросмотр ReadAloud';
$string['readaloud:view'] = 'Просмотр ReadAloud';
$string['readaloud:itemedit'] = 'Редактировать вопросы';
$string['readaloud:itemedit'] = 'Редактировать элементы';
$string['readaloud:itemview'] = 'Просматривать вопросы';
$string['readaloud:itemview'] = 'Просматривать элементы';
$string['timecreated'] = 'Дата создания';
$string['timecreated'] = 'Дата создания';
$string['welcomelabel'] = 'Инструкции по умолчанию';
$string['welcomelabel'] = 'Инструкции перед попыткой';
$string['feedbacklabel'] = 'Инструкции после попытки';
$string['feedbacklabel'] = 'Обратная связь по умолчанию';
$string['nodataavailable'] = 'Нет доступных данных';
$string['nodataavailable'] = 'Данных пока нет';
$string['transcriber'] = 'Построчный транскрайбер';
$string['transcriber'] = 'Транскрайбер';
$string['transcriber_details'] = 'Механизм транскрипции, который будет использоваться';
$string['transcriber_details'] = 'Механизм транскрипции, который будет использоваться для построчного чтения.';
$string['correct'] = 'Правильно';
$string['correct'] = 'Правильно';
$string['itemtype'] = 'Тип элемента';
$string['itemtype'] = 'Тип элемента';
$string['deleteitem'] = 'Удалить элемент';
$string['deleteitem'] = 'Удалить элемент';
$string['guidedtrans_corpus'] = "Использовать тексты корпуса";
$string['guidedtrans_corpus'] = "Использовать корпус (все тексты ReadAloud)";
$string['reattemptquiz'] = 'Повторить тест';
$string['reattemptquiz'] = 'Повторить тест?';
$string['addtextarea_instructions'] = 'Введите текст, который хотите показать в элементе урока.';
$string['addttsaudio_instructions'] = 'Введите текст, который должен быть озвучен TTS.';
$string['addmedia_instructions'] = 'Выберите тип медиа, который хотите показать в элементе урока.';

// Account dashboard.
$string['accountdashboard'] = 'Панель аккаунта';
$string['audio'] = 'Аудио';
$string['end'] = 'Окончание';
$string['failedfetchsubreport'] = 'Не удалось получить отчёт по подписке';
$string['maxmonth'] = 'Максимум за месяц';
$string['ninety_days'] = '90 дней';
$string['no_subscriptions'] = 'Нет подписок.';
$string['oneeighty_days'] = '180 дней';
$string['per_plugin'] = 'По плагинам за последний год';
$string['per_recording_type'] = 'По типу записи';
$string['poodll_users'] = 'Пользователи Poodll';
$string['recording_min'] = 'Минуты записи';
$string['recordings'] = 'Записи';
$string['start'] = 'Начало';
$string['subscription'] = 'Подписка';
$string['thirty_days'] = '30 дней';
$string['threehundredsixtyfive_days'] = '365 дней';
$string['video'] = 'Видео';
