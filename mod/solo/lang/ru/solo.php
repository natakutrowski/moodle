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
 * English strings for solo
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_solo
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Poodll Solo';
$string['modulenameplural'] = 'Poodll Solo';
$string['modulename_help'] = 'Poodll Solo — это активность, предназначенная для практики устной речи студентов. Студенты записывают свою речь на заданную тему, расшифровывают её и получают обратную связь по своей работе.';
// $string['solofieldset'] = 'Пользовательский пример набора полей';
$string['soloname'] = 'Poodll Solo';
$string['soloname_help'] = 'Это содержимое всплывающей подсказки, связанной с полем soloname. Поддерживается синтаксис Markdown.';
$string['solo'] = 'solo';
$string['activitylink'] = 'Ссылка на следующую активность';
$string['activitylink_help'] = 'Чтобы предоставить ссылку после завершения попытки на другую активность в курсе, выберите активность из выпадающего списка.';
$string['activitylinkname'] = 'Перейти к следующей активности: {$a}';
$string['pluginadministration'] = 'Администрирование Poodll Solo';
$string['pluginname'] = 'Poodll Solo';
// $string['someadminsetting'] = 'Некоторые настройки администратора';
// $string['someadminsetting_details'] = 'Дополнительная информация о настройках администратора';
// $string['someinstancesetting'] = 'Некоторые настройки экземпляра';
// $string['someinstancesetting_details'] = 'Дополнительная информация о настройках экземпляра';
$string['solosettings'] = 'Настройки Solo';
$string['solo:addinstance'] = 'Добавить новый Poodll Solo';
$string['solo:view'] = 'Просмотреть Poodll Solo';
$string['solo:viewreports'] = 'Просмотр отчетов Poodll Solo';
$string['solo:selecttopics'] = 'Выбирать темы для использования в активности';
$string['solo:managetopics'] = 'Управлять темами (добавление/редактирование/удаление)';
$string['solo:attemptview'] = 'Просмотр попыток';
$string['solo:attemptedit'] = 'Редактирование попыток';
$string['solo:manageattempts'] = 'Может управлять попытками Poodll Solo';
$string['solo:manage'] = 'Может управлять экземплярами Poodll Solo';
$string['solo:submit'] = 'Может отправлять попытки Poodll Solo';
$string['solo:grades'] = 'Просмотр оценок Solo';
$string['privacy:metadata'] = 'Плагин Poodll Solo сохраняет личные данные.';
$string['privacy:metadata:solo'] = 'Плагин Poodll Solo сохраняет личные данные.';
$string['privacy:metadata:attemptstable'] = 'Таблица попыток Poodll Solo.';
$string['privacy:metadata:attemptstatstable'] = 'Сохраняет статистику и данные о подаче студентов.';
$string['privacy:metadata:transcript'] = 'Расшифровка студенческой подачи';
$string['privacy:metadata:grade'] = 'Итоговая оценка за попытку';
$string['privacy:metadata:aigrade'] = 'Оценка, рассчитанная ИИ, за попытку';
$string['privacy:metadata:words'] = 'Общее количество слов в подаче';
$string['privacy:metadata:uniquewords'] = 'Общее количество уникальных слов в подаче';
$string['privacy:metadata:longwords'] = 'Общее количество длинных слов в подаче';
$string['privacy:metadata:turns'] = 'Общее количество предложений в подаче';
$string['privacy:metadata:avturn'] = 'Средняя длина предложений в подаче';
$string['privacy:metadata:longestturn'] = 'Самое длинное предложение в подаче';
$string['privacy:metadata:targetwords'] = 'Общее количество целевых слов в подаче';
$string['privacy:metadata:totaltargetwords'] = 'Общее количество целевых слов в активности';
$string['privacy:metadata:aiaccuracy'] = 'Сходство между ручной и ИИ-расшифровкой';
$string['privacy:metadata:cefrlevel'] = 'Оценочный уровень CEFR';
$string['privacy:metadata:wpm'] = 'Скорость речи в словах в минуту';
$string['privacy:metadata:speakingtime'] = 'Общее время речи (в секундах)';
$string['privacy:metadata:relevance'] = 'Оценочная релевантность содержания подачи к теме';
$string['id'] = 'ID';
$string['name'] = 'Имя';
$string['timecreated'] = 'Дата создания';
$string['basicheading'] = 'Основной отчёт';
$string['totalattempts'] = 'Попытки';
$string['overview'] = 'Обзор';
$string['overview_help'] = 'Справка по обзору';
$string['view'] = 'Просмотр';
$string['preview'] = 'Предпросмотр';
$string['viewreports'] = 'Просмотр отчётов';
$string['reports'] = 'Мои отчёты';
$string['reports'] = 'Отчёты';
// $string['viewgrading']='Просмотр оценок';
$string['showingattempt'] = 'Показ попытки для: {$a}';
$string['basicreport'] = 'Основной отчёт';
$string['returntoreports'] = 'Вернуться к отчётам';
$string['returntotop'] = 'Вернуться к началу';
$string['exportexcel'] = 'Экспортировать в CSV';
$string['deletealluserdata'] = 'Удалить все пользовательские данные';
// $string['maxattempts'] ='Максимум попыток';
// $string['unlimited'] ='Неограниченно';
// $string['defaultsettings'] ='Настройки по умолчанию';
// $string['exceededattempts'] ='Вы завершили максимальное количество попыток: {$a}.';
// $string['solotask'] ='Poodll Solo Задание';
$string['gotnosound'] = 'Мы не слышим вас. Пожалуйста, проверьте настройки микрофона и попробуйте снова.';
$string['done'] = 'Готово';
$string['submit'] = 'Отправить';
$string['processing'] = 'Обработка';
$string['feedbackheader'] = 'Завершено';
// $string['beginreading'] = 'Начать чтение';
$string['errorheader'] = 'Ошибка';
// $string['uploadconverterror'] = 'Произошла ошибка при отправке вашего файла на сервер. Ваша попытка НЕ была получена. Пожалуйста, обновите страницу и попробуйте снова.';
$string['attemptsreport'] = 'Отчёт по попыткам';
$string['submitted'] = 'Отправлено';
$string['id'] = 'ID';
$string['username'] = 'Пользователь';
$string['audiofile'] = 'Аудиофайл';
$string['timecreated'] = 'Дата создания';
$string['nodataavailable'] = 'Данные пока недоступны';
$string['saveandnext'] = 'Сохранить ... и продолжить';
$string['next'] = 'Далее';
$string['start'] = 'Начать';
$string['startrecording'] = 'Нажмите, чтобы начать запись';
$string['stoprecording'] = 'Нажмите ещё раз, чтобы остановить запись';
$string['finish'] = 'Завершить';
$string['done'] = 'Готово';
$string['reattempt'] = 'Попробовать снова';
$string['notgradedyet'] = 'Ваша попытка была получена, но ещё не оценена';
$string['enabletts'] = 'Включить TTS (экспериментально)';
$string['enabletts_details'] = 'TTS пока не реализован';
// мы используем эту настройку как для TTS, так и для STT... плохо... но они всегда одинаковые, не так ли?
$string['ttslanguage'] = 'Целевой язык';
$string['deleteattemptconfirm'] = "Вы уверены, что хотите удалить эту попытку?";
$string['deletenow'] = 'Удалить сейчас';
$string['attemptsperpage'] = 'Попыток на страницу';
$string['attemptsperpage_details'] = 'Это задаёт количество строк, которые будут отображаться в отчётах или списках попыток.';
$string['gradingsperpage'] = 'Оценок на страницу';
$string['gradingsperpage_details'] = 'Это задаёт количество попыток для ручного оценивания, которые будут отображены на странице оценки одновременно.';

$string['apiuser'] = 'Пользователь Poodll API';
$string['apiuser_details'] = 'Имя пользователя учетной записи Poodll, которое авторизует Poodll на этом сайте.';
$string['apisecret'] = 'Секрет Poodll API';
$string['apisecret_details'] = 'Секрет Poodll API. Подробнее см. <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">здесь</a>';
$string['enableai'] = 'Включить ИИ';
$string['enableai_details'] = 'Poodll Solo может оценивать результаты попытки студента с помощью ИИ. Поставьте галочку, чтобы включить.';

$string['useast1'] = 'Восток США';
$string['tokyo'] = 'Токио, Япония';
$string['sydney'] = 'Сидней, Австралия';
$string['dublin'] = 'Дублин, Ирландия';
$string['ottawa'] = 'Оттава, Канада';
$string['frankfurt'] = 'Франкфурт, Германия';
$string['london'] = 'Лондон, Великобритания';
$string['saopaulo'] = 'Сан-Паулу, Бразилия';
$string['singapore'] = 'Сингапур';
$string['mumbai'] = 'Мумбаи, Индия';
$string['capetown'] = 'Кейптаун, Южная Африка';
$string['bahrain'] = 'Бахрейн';

$string['forever'] = 'Никогда не истекает';

$string['en-us'] = 'Английский (США)';
$string['en-nz'] = 'Английский (Новая Зеландия)';
$string['en-za'] = 'Английский (Южная Африка)';
$string['es-us'] = 'Испанский (США)';
$string['en-au'] = 'Английский (Австралия)';
$string['en-gb'] = 'Английский (Великобритания)';
$string['fr-ca'] = 'Французский (Канада)';
$string['fr-fr'] = 'Французский (Франция)';
$string['fil-ph'] = 'Филиппинский';
$string['it-it'] = 'Итальянский (Италия)';
$string['pt-br'] = 'Португальский (Бразилия)';
$string['en-in'] = 'Английский (Индия)';
$string['es-es'] = 'Испанский (Испания)';
$string['fr-fr'] = 'Французский (Франция)';
$string['de-de'] = 'Немецкий (Германия)';
$string['de-at'] = 'Немецкий (Австрия)';
$string['da-dk'] = 'Датский (Дания)';
$string['hi-in'] = 'Хинди';
$string['ko-kr'] = 'Корейский';
$string['ar-ae'] = 'Арабский (Персидский залив)';
$string['ar-sa'] = 'Арабский (Современный стандартный)';
$string['zh-cn'] = 'Китайский (Мандарин, материковый Китай)';
$string['nl-nl'] = 'Нидерландский';
$string['nl-be'] = 'Нидерландский (Бельгия)';
$string['en-ie'] = 'Английский (Ирландия)';
$string['en-wl'] = 'Английский (Уэльс)';
$string['en-ab'] = 'Английский (Шотландия)';
$string['fa-ir'] = 'Фарси';
$string['de-ch'] = 'Немецкий (Швейцария)';
$string['he-il'] = 'Иврит';
$string['id-id'] = 'Индонезийский';
$string['ja-jp'] = 'Японский';
$string['ms-my'] = 'Малайский';
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
$string['nb-no'] = 'Норвежский (Bokmål)'; // не используется
$string['pl-pl'] = 'Польский';
$string['ro-ro'] = 'Румынский';
$string['mi-nz'] = 'Маори';

$string['bg-bg'] = 'Болгарский'; 
$string['cs-cz'] = 'Чешский'; 
$string['el-gr'] = 'Греческий'; 
$string['hr-hr'] = 'Хорватский'; 
$string['hu-hu'] = 'Венгерский'; 
$string['lt-lt'] = 'Литовский'; 
$string['lv-lv'] = 'Латышский'; 
$string['sk-sk'] = 'Словацкий'; 
$string['sl-si'] = 'Словенский'; 
$string['is-is'] = 'Исландский'; 
$string['mk-mk'] = 'Македонский'; 
$string['no-no'] = 'Норвежский'; 
$string['sr-rs'] = 'Сербский'; 
$string['vi-vn'] = 'Вьетнамский'; 

// less used Indial languages here
$string['as-in'] = 'Ассамский';
$string['aw-aw'] = 'Авадхи';
$string['bn-in'] = 'Бенгальский';
$string['bh-in'] = 'Бходжпури';
$string['gu-in'] = 'Гуджарати';
$string['kn-in'] = 'Каннада';
$string['ml-in'] = 'Малаялам';
$string['mr-in'] = 'Маратхи';
$string['mw-in'] = 'Марвади';
$string['or-in'] = 'Одиа (Ория)';
$string['pa-ing'] = 'Пенджабский (Гурмухи)';
$string['pa-in'] = 'Пенджабский (Шахмукхи)';
$string['sa-in'] = 'Санскрит';
$string['ur-in'] = 'Урду';

$string['awsregion'] = 'Регион AWS';
$string['region'] = 'Регион AWS';
$string['expiredays'] = 'Дни хранения файла';

$string['attemptsperpage'] = "Показать попыток на странице: ";
$string['backtotop'] = "Вернуться к курсу";
$string['transcript'] = "Транскрипт";
$string['ok'] = "ОК";

$string['notimelimit'] = 'Без ограничения времени';
$string['xsecs'] = '{$a} секунд';
$string['onemin'] = '1 минута';
$string['xmins'] = '{$a} минут';
$string['oneminxsecs'] = '1 минута {$a} секунд';
$string['xminsecs'] = '{$a->minutes} минут {$a->seconds} секунд';

$string['postattemptheader'] = 'Параметры после попытки';
$string['recordingaiheader'] = 'Запись и параметры ИИ';

$string['displaysubs'] = '{$a->subscriptionname} : истекает {$a->expiredate}';
$string['noapiuser'] = "Не указан пользователь API. Poodll Solo не будет работать правильно.";
$string['noapisecret'] = "Не указан секрет API. Poodll Solo не будет работать правильно.";
$string['credentialsinvalid'] = "Указанные пользователь API и секрет не могут получить доступ. Пожалуйста, проверьте их.";
$string['appauthorised'] = "Poodll Solo авторизован для этого сайта.";
$string['appnotauthorised'] = "Poodll Solo НЕ авторизован для этого сайта.";
$string['refreshtoken'] = "Обновить информацию о лицензии";
$string['notokenincache'] = "Обновите, чтобы увидеть информацию о лицензии. Обратитесь в службу поддержки Poodll, если возникла проблема.";
$string['novalidcredentials'] = 'Указанные пользователь API и секрет были отклонены и не могут получить доступ. Пожалуйста, проверьте их на <a href="{$a}">странице настроек.</a> Вы можете получить их на <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "Для этого сайта/плагина нет активной подписки.";

$string['privacy:metadata:attemptid'] = 'Уникальный идентификатор попытки Poodll Solo пользователя.';
$string['privacy:metadata:attempttable'] = 'Хранит оценки и другие данные пользователя, связанные с попыткой Poodll Solo.';
$string['privacy:metadata:cloudpoodllcom'] = 'Плагин Poodll Solo сохраняет записи в AWS S3 через cloud.poodll.com.';
$string['privacy:metadata:cloudpoodllcom:userid'] = 'Плагин Poodll Solo включает ID пользователя Moodle в URL записей и транскриптов';
$string['privacy:metadata:filename'] = 'URL-адреса файлов отправленных записей.';
$string['privacy:metadata:jsontranscriptpurpose'] = 'Полные транскрипты записей.';
$string['privacy:metadata:soloid'] = 'Уникальный идентификатор экземпляра активности Poodll Solo.';
$string['privacy:metadata:timemodified'] = 'Последнее время изменения попытки';
$string['privacy:metadata:transcriptpurpose'] = 'Короткие транскрипты записей.';
$string['privacy:metadata:userid'] = 'ID пользователя для попытки Poodll Solo.';

// attempts
$string['attempts'] = 'Попытки';
$string['manageattempts'] = 'Управление попытками';
$string['editingattempt'] = 'Редактирование попытки';
$string['attempt'] = 'Попытка';
$string['attempttitle'] = 'Название попытки';
$string['attemptcontents'] = 'Описание попытки';
$string['actions'] = 'Действия';
$string['deleteattempt'] = 'Удалить попытку';
$string['confirmattemptdelete'] = 'Вы уверены, что хотите <i>УДАЛИТЬ</i> попытку?';
$string['confirmattemptdeletetitle'] = 'Действительно удалить попытку?';
$string['confirmattemptdeletealltitle'] = 'Действительно удалить ВСЕ попытки?';
$string['confirmattemptdeleteall'] = 'Вы уверены, что хотите <i>УДАЛИТЬ ВСЕ</i> попытки?';
$string['noattempts'] = 'В этом упражнении нет попыток';
$string['attemptdetails'] = 'Детали попытки: {$a}';
$string['attemptsummary'] = 'Сводка попытки: {$a}';
$string['viewreport'] = 'Просмотр отчета';
$string['timemodified'] = 'Последнее изменение';
$string['edit'] = 'Редактировать';
$string['attemptname'] = 'Попытка';
$string['nodataavailable'] = 'Нет доступных данных';
$string['transcriber'] = 'Транскриптор';
$string['transcriber_details'] = 'Движок для транскрипции.';
$string['transcriber_open'] = 'Открытая транскрипция';
$string['transcriber_none'] = 'Без транскрипции';
$string['transcriptnotready'] = '<i>Транскрипция пока не готова</i>';
$string['transcripttitle'] = 'Транскрипт';


// $string['createattempt'] = 'Create Attempt';
// $string['addtopic'] = 'Add Topic';
// $string['deletetopic'] = 'Delete Topic';
// $string['edittopic'] = 'Edit Topic';
// $string['editingtopic'] = 'Editing Topic';
// $string['savetopic'] = 'Save Topic';
// $string['createtopic'] = 'Create Topic';
$string['topicformtitle'] = 'Добавить/редактировать Тему';
$string['topiclevelcustom'] = 'Пользовательская';
$string['topiclevelcourse'] = 'Курс';
$string['grades'] = 'Оценки';
$string['managegrades'] = 'Управление оценками';
$string['topics'] = 'Темы';
$string['managetopics'] = 'Управление темами';
$string['topicname'] = 'Тема';
$string['topiclevel'] = 'Уровень';
$string['topicicon'] = 'Иконка';
$string['topictargetwords'] = 'Целевые слова';
$string['targetwords'] = 'Целевые слова';
$string['tips'] = 'Советы для говорения';
$string['targetspeakingtime'] = 'Целевое время говорения';
$string['userselections'] = 'Выбор пользователя';
$string['selftranscribe'] = 'Транскрибировать свою речь';
$string['xminutes'] = '{$a}:00 минут';
$string['convlength'] = 'Целевое время';
$string['words'] = 'Слова';
$string['speakingtopic'] = 'Тема для говорения';
$string['speakingtips'] = 'Советы для говорения';
$string['speakingtips_default'] = 'Говорите просто, медленно и четко.';
$string['users'] = 'Партнёры';
$string['topic'] = 'Тема';

$string['attempt_prepare'] = 'Подготовка';
$string['attempt_record'] = 'Запись';
$string['attempt_transcribe'] = 'Печатать';
$string['attempt_model'] = 'Образец ответа';

$string['step_prepareinstructions'] = 'Проверьте тему для говорения и целевые задачи из приведённых ниже опций. Когда будете готовы, переходите на следующую страницу и начинайте говорить.';
$string['step_prepareinstructions_norecording'] = 'Проверьте тему и цели ниже. Когда будете готовы, переходите на следующую страницу и начинайте.';
$string['step_mediarecordinstructions'] = 'Используйте записывающее устройство ниже, чтобы записать свою речь. Удачи.';
$string['step_posttranscriberecordinstructions'] = 'Используйте записывающее устройство ниже, чтобы записать свою речь.';
$string['step_selftranscribeinstructions'] = 'Прослушайте свою запись и введите/проверьте то, что вы сказали, в редакторе ниже. Не изменяйте то, что вы сказали. Исправляйте только орфографические или пунктуационные ошибки.';
$string['step_prerecord_transcribeinstructions'] = 'Проверьте инструкции и целевые задачи, затем введите свой ответ в текстовый редактор ниже.';
$string['step_writtensubmissioninstructions'] = 'Проверьте инструкции и цели, затем введите свой ответ в текстовый редактор ниже.';
$string['step_modelinstructions'] = 'Проверьте образец ответа ниже.';

$string['audioreplay'] = 'Прослушайте свою аудиозапись для этой попытки';
$string['videoreplay'] = 'Просмотрите свое видео для этой попытки';
$string['selftranscript'] = 'Собственная транскрипция';
$string['stats'] = 'Статистика';
$string['stats_words_title'] = 'Слова';
$string['stats_words'] = 'Всего слов';
$string['stats_turns_title'] = 'Предложения';
$string['stats_turns'] = 'Всего предложений';
$string['stats_avturn'] = 'Средняя длина предложения';
$string['stats_longestturn'] = 'Длина самого длинного предложения';
$string['stats_targetwords'] = 'Целевые слова';
$string['stats_aiaccuracy'] = 'Чёткость речи';
$string['stats_uniquewords'] = 'Уникальные слова';
$string['stats_longwords'] = 'Длинные слова';
$string['stats_ideacount'] = 'Концепции';
$string['stats_cefrlevel'] = 'Уровень CEFR (оценка)';
$string['stats_relevance'] = 'Релевантность (оценка)';
$string['stats_wpm'] = 'Слов в минуту';
$string['more_stats'] = 'Больше статистики';

$string['multiattempts'] = 'Разрешить несколько попыток';
$string['multiattempts_details'] = 'Если отмечено, ученик может выбрать перезаписать существующую попытку новой.';
$string['attemptsheading'] = 'Попытки';
$string['incompleteattemptsheading'] = 'Незавершённые попытки';
$string['incompleteattemptsreport'] = 'Незавершённые попытки';
$string['partners'] = 'Партнёры';
$string['turns'] = 'Предложения';
$string['ATL'] = 'Ср. длина предложения';
$string['LTL'] = 'Самое длинное предложение';
$string['TW'] = 'Всего слов';
$string['CEFR'] = 'CEFR';
$string['idnumber'] = 'Номер ID';

$string['audiorecording'] = 'Аудиозапись';
$string['videorecording'] = 'Видеозапись';
$string['voicerecording'] = 'Запись голоса';
$string['attemptnumberheader'] = 'Номер попытки';
$string['attemptnumber'] = 'Попытка №{$a}';
$string['summaryuserattemptheadertitle'] = 'Детали речи';
$string['summaryuserattemptheaderintro'] = '';
$string['summaryheadertitle'] = 'Детали вашей речи';
$string['summaryheadertitle_norecording'] = 'Результаты попытки';
$string['summaryheaderintro'] = 'Проверьте детали и результаты своей речи ниже. Как вы думаете, стало ли лучше? Вы должны становиться лучше с каждой попыткой.';

$string['fonticonexplanation'] = 'Добавьте графический значок, представляющий тему. Используйте FontAwesome для этого. Шаблон: fa-xxx, где xxx — это имя значка. Поиск значков на: <a href="https://fontawesome.com/v4.7.0/icons">https://fontawesome.com/v4.7.0/icons</a>';

$string['targetwordsexplanation'] = 'Добавляйте целевые слова, каждое с новой строки.';

$string['maxconvlength_details'] = 'Лимит времени для записи аудио';
$string['maxconvlength'] = 'Лимит времени';
$string['heard'] = 'Слышимость';
$string['SPL'] = 'Правописание';
$string['ACC'] = 'Точность';

$string['postattemptedit'] = 'Разрешить редактирование после завершения';
$string['postattemptedit_details'] = 'Разрешить ученику редактировать свою отправку после завершения.';
$string['dopostattemptedit'] = 'Редактировать последнюю попытку';

$string['gradesid'] = 'ID';
$string['gradesfirst'] = 'Имя';
$string['gradeslast'] = 'Фамилия';
$string['gradesaiscore'] = 'Оценка ИИ';
$string['gradesclarity'] = 'Чёткость';
$string['gradeswords'] = 'Всего слов';
$string['gradestargetwords'] = 'Целевые слова';
$string['gradesmethod'] = 'Оценено';
$string['gradesturns'] = 'Предложения';
$string['gradesavturnlength'] = 'Средняя длина предложения';
$string['gradesactions'] = 'Действия';
$string['gradesgraded'] = 'Оценено';
$string['gradesgradedno'] = 'Не оценено';
$string['gradesgradedyes'] = 'Оценено';
$string['gradesgrade'] = 'Оценка';
$string['gradeschoose'] = 'Выберите одно:';
$string['gradesstudent'] = 'Ученик';
$string['gradesdatapoint'] = 'Точка данных';
$string['gradesrubric'] = 'Рубрика';
$string['gradestranscript'] = 'Транскрипция';
$string['gradesfeedback'] = 'Обратная связь:';
$string['gradessubmit'] = 'Отправить';
$string['gradesubmissions'] = 'Оценить отправки';
$string['gradesgrader'] = 'Оценено';
$string['humangraded'] = 'Учитель';
$string['autograded'] = 'Авто';
$string['gradeheader'] = 'Оценка';
$string['gradelabel'] = '{$a}%';


$string['gradeitem:solo'] = 'Poodll Solo';
$string['developer'] = 'Разработчик';
$string['dopopupgrade'] = 'Оценка пользователя: ';

$string['detailedattemptsreport'] = 'Отчёт исследователя';
$string['detailedattemptsheading'] = 'Отчёт исследователя';
$string['detailedattempts'] = 'Отчёт исследователя';

$string['classprogressreport'] = 'Прогресс класса';
$string['classprogressheading'] = 'Прогресс класса';
$string['classprogress'] = 'Прогресс класса';

$string['myprogressreport'] = 'Мой прогресс';
$string['myprogressheading'] = 'Мой прогресс: {$a}';
$string['myprogress'] = 'Мой прогресс';

$string['userattempts'] = 'Попытки пользователя';
$string['userattemptsheading'] = 'Попытки {$a}';
$string['userattempts'] = 'Попытки пользователя';

$string['myattempts'] = 'Мои попытки';
$string['myattemptsheading'] = 'Мои попытки: {$a} ';
$string['myattempts'] = 'Мои попытки';

$string['downloadaudio'] = 'Скачать аудио';
$string['downloadaudioheading'] = 'Скачать аудио';
$string['downloadaudioreport'] = 'Скачать аудио';
$string['file'] = 'Файл';
$string['teachereval'] = 'Оценка учителя';
$string['autoeval'] = 'Автоматическая оценка';
$string['spellingeval'] = 'Оценка правописания';
$string['grammareval'] = 'Оценка грамматики';
$string['nogrammarerrors'] = 'Ошибок в грамматике не найдено.';
$string['possiblegrammarerrors'] = 'Возможные ошибки в грамматике:';
$string['possiblespellingerrors'] = 'Возможные ошибки в правописании:';
$string['nospellingerrors'] = 'Ошибок в правописании не найдено.';
$string['completedsteps'] = 'Завершённые шаги';
$string['completionallsteps'] = 'Завершить, когда все шаги пройдены';
$string['completionallsteps_help'] = 'Завершить, когда все шаги пройдены';
$string['yes'] = 'Да';
$string['no'] = 'Нет';

$string['speakingtopic_help'] = 'Краткие инструкции для учеников о том, на какую тему нужно говорить.';
$string['targetwords_help'] = 'Целевые слова или фразы, которые ученик должен использовать во время речи. Каждое с новой строки.';

$string['avturns'] = 'Сред. предложения';
$string['avatl'] = 'Сред. длина предложения';
$string['avltl'] = 'Макс. длина предложения';
$string['avw'] = 'Сред. количество слов';
$string['GRM'] = 'Грамматика';
$string['avtw'] = 'Сред. целевые слова';
$string['avspell'] = 'Сред. правописание';
$string['avacc'] = 'Сред. точность';
$string['tabular'] = 'Табличный вид';

$string['grade'] = 'Оценка';

$string['reportmenuinstructions'] = "Просмотрите отчёты, выбрав отчёт из кнопок ниже.";
$string['totalgradeables'] = 'Оценка {$a} студентов';

$string['myreports'] = 'Мои отчёты';
$string['stats_autogrammarscore'] = 'Грамматика';
$string['stats_autospellscore'] = 'Правописание';
$string['stats_clarity'] = 'Чёткость';

$string['tnav_grammar'] = 'Грамматика {$a}';
$string['tnav_spelling'] = 'Правописание {$a}';
$string['tnav_clarity'] = 'Чёткость {$a}';

$string['bigword'] = 'сложное слово';
$string['spellingmistake'] = 'ошибка в правописании';
$string['grammarmistake'] = 'ошибка в грамматике';
$string['targetwordspoken'] = 'целевое слово произнесено';
$string['sentence'] = 'предложение';
$string['aggroup'] = 'Автооценка';
$string['aggroup_help'] = 'Установите формулу, которая будет использоваться для автоматической оценки речи студентов';

$string['recorderaudio'] = 'Аудио-рекордер';
$string['recordervideo'] = 'Видео-рекордер';
$string['recorderskin'] = 'Стиль рекордера';
$string['recordertype'] = 'Тип записи';

$string['skinplain'] = 'Простой';
$string['skinbmr'] = 'Burnt Rose';
$string['skinfresh'] = 'Свежий (только аудио)';
$string['skin123'] = 'Один Два Три';
$string['skinonce'] = 'Один раз';
$string['skinsolo'] = 'Соло';
$string['skinupload'] = 'Загрузить';

$string['totalunique'] = 'Общее количество уникальных слов';
$string['totalwords'] = 'Общее количество слов';
$string['gradewordgoal'] = 'Цель по количеству слов';
$string['gradewordgoal_help'] = 'Установите количество слов, которое студент должен произнести, чтобы получить максимальные баллы при использовании автооценки. Подробнее см. в разделе "Оценивание" этой формы.';
$string['displaygradewordgoal'] = '{$a} слов';

$string['ag_overgradewordgoal'] = ' / Цель по словам ) x ';
$string['ag_pointsper'] = ' баллов за ';
$string['enabletranscription'] = 'Ручная транскрипция';
$string['enabletranscription_details'] = 'Требовать от студентов вручную транскрибировать свою речь';
$string['enableautograde'] = 'Включить автооценку';
$string['enableautograde_details'] = 'Автооценка рассчитает предварительную оценку для ваших студентов, которую вы можете позже изменить или использовать как есть.';
$string['rating_poor'] = 'Спасибо';
$string['rating_fair'] = 'Спасибо';
$string['rating_good'] = 'Хорошо';
$string['rating_verygood'] = 'Очень хорошо';
$string['rating_excellent'] = 'Отлично!';
$string['toggleplayinstructions'] = '(Нажмите клавишу ESC, чтобы начать и остановить аудиоплеер.)';
$string['prerecordtranscriptinstructions'] = 'Введите свой ответ в текстовое поле ниже. На следующем шаге вы прочитаете его вслух';

// Повторная попытка
$string['reattempt'] = 'Попробовать снова';
$string['reattempttitle'] = 'Точно попробовать снова?';
$string['reattemptbody'] = 'Если вы продолжите, ваша предыдущая попытка будет заменена на эту. Продолжить?';

$string['secs_till_check'] = 'проверка результатов через ... ';
$string['checking'] = ' ... проверка ... ';
$string['notgradedyet'] = 'Ваш ответ получен, но ещё не оценён. Это может занять несколько минут.';
$string['evaluatedmessage'] = 'Ваша последняя попытка была получена, и результаты показаны ниже.';
$string['moreattemptdetails'] = "Дополнительные детали попытки";
$string['transcriptevaluation'] = "Оценка транскрипции";
$string['transcriptevaluationdetails'] = "Подчёркнутые слова показывают различия между вашей транскрипцией и автоматической транскрипцией.";
$string['uploading'] = ' ... загрузка ... ';

// Опции медиа
$string['mediaoptions'] = 'Медиа опции';
$string['addmedia'] = 'Добавить медиа';
$string['addtext'] = 'Добавить текст';
$string['addiframe'] = 'Добавить iFrame';
$string['addttsaudio'] = 'Добавить TTS аудио';
$string['addytclip'] = 'Добавить YouTube/Vimeo';

$string['speakingtargetsheader'] = 'Цели речи';
$string['languageandrecordingheader'] = 'Язык и запись';
$string['autogradingheader'] = 'Автооценка';
$string['enablesetuptab'] = "Включить вкладку настройки";
$string['enablesetuptab_details'] = "Показать вкладку с настройками активности для администраторов. Обычно не особо полезно.";
$string['setup'] = "Настройка";

$string['nosetup'] = "Активность не готова";
$string['addsetup'] = "Настроить активность";
$string['waitforsetup'] = "Тема для этой активности ещё не установлена. Вы не сможете продолжить, пока учитель её не добавит.";
$string['letsaddsetup'] = "Тема для этой активности ещё не установлена. Давайте добавим её.";
$string['noattemptfound'] = "Попытка не найдена";
$string['viewattempt'] = "Посмотреть";
$string['attemptfor'] = 'Попытка: {$a}';
$string['audioandstats'] = "Аудио и статистика";

$string['content_iframe_help'] = 'Вставьте код встраивания iframe (только HTML) для любого медиа, которое должно быть показано студентам.';
$string['content_media_help'] = 'Загрузите аудио, видео или изображение, которое будет показано студентам.';
$string['content_tts_help'] = 'Контент для преобразования текста в речь.';
$string['content_media'] = 'Изображение, аудио или видео';
$string['content_iframe'] = 'Вставить код iframe';
$string['content_text'] = 'Текстовый контент';
$string['content_text_help'] = 'Добавьте текстовый контент, сопровождающий вашу тему';
$string['content_tts'] = 'Текст для преобразования в речь (TTS)';
$string['content_ttsvoice'] = 'Голос говорящего';
$string['content_ttsspeed'] = 'Скорость говорящего';
$string['content_ytid'] = "ID видео YouTube";
$string['content_ytstart'] = "Начальные секунды";
$string['content_ytend'] = "Конечные секунды";
$string['ytclipdetails'] = "Клип YouTube/Vimeo";
$string['freetrial'] = "Получить API-учетные данные Cloud Poodll и бесплатную пробную версию";
$string['freetrial_desc'] = "Появится диалоговое окно, позволяющее вам зарегистрироваться для бесплатной пробной версии Poodll. После регистрации вы должны войти в панель управления участника, чтобы получить ваш API пользователь и секрет. И зарегистрировать URL вашего сайта.";
$string['fillcredentials'] = "Установить API пользователь и секрет с существующими учетными данными";
$string['viewstart'] = "Открытие активности";
$string['viewend'] = "Закрытие активности";
$string['viewstart_help'] = "Если установлено, не позволяет студенту войти в активность до указанной даты/времени.";
$string['viewend_help'] = "Если установлено, не позволяет студенту войти в активность после указанной даты/времени.";
$string['activitydate:submissionsdue'] = 'Срок сдачи:';
$string['activitydate:submissionsopen'] = 'Открывается:';
$string['activitydate:submissionsopened'] = 'Открыто:';
$string['activityisnotopenyet'] = "Эта активность ещё не открыта.";
$string['activityisclosed'] = "Эта активность закрыта.";
$string['open'] = "Открытие: ";
$string['until'] = "До: ";
$string['activityopenscloses'] = "Даты открытия/закрытия активности";
$string['solo:preview'] = 'Может предварительно просматривать активности Solo';
$string['modelanswer'] = "Эталонный ответ";
$string['modelanswerheader'] = "Эталонный ответ";
$string['modelanswerinstructions'] = "Эталонный ответ используется в качестве 'правильного ответа', с которым могут быть рассчитаны оценки по критерию схожести (релевантности) для автоматической оценки. Он не показывается студентам. Используйте ниже предоставленные медиаопции, чтобы отобразить видео или плеер с преобразованием текста в речь для студентов на этапе эталонного ответа.";
$string['audiorec_heading'] = "Аудиозапись";
$string['videorec_heading'] = "Видеозапись";
$string['grammarcorrection'] = "Предложенные исправления:";
$string['step_none'] = 'Нет';
$string['step_record'] = 'Запись';
$string['step_transcribe'] = 'Самостоятельная транскрипция';
$string['step_model'] = 'Эталон';
$string['seq_PRTM'] = 'Подготовка -> Запись -> Транскрипция -> Эталон (если установлен)';
$string['seq_PRMT'] = 'Подготовка -> Запись -> Эталон -> Транскрипция';
$string['seq_PRM'] = 'Подготовка -> Запись -> Эталон (если установлен)';
$string['seq_PTRM'] = 'Подготовка -> Ввод текста -> Запись -> Эталон (если установлен)';
$string['seq_PTM'] = 'Подготовка -> Ввод текста -> Эталон (если установлен)';
$string['seq_RM'] = 'Запись -> Эталон (если установлен)';
$string['activitysteps'] = "Этапы активности";
$string['preloadtranscript'] = 'Предзагрузка транскрипции';
$string['preloadtranscript_details'] = 'Загружает транскрипцию в редактор, так что студенту остаётся только внести правки. Обратите внимание, что транскрипция может занять несколько минут.';

$string['enabletts'] = 'Включить TTS';
$string['enabletts_help'] = 'Позволяет студентам прослушивать свою транскрипцию, озвученную голосом TTS';
$string['enabletts_details'] = 'Позволяет студентам прослушивать свою транскрипцию, озвученную голосом TTS';
$string['default_enabletts'] = 'Включить TTS (по умолчанию)';

$string['nopasting'] = 'Отключить вставку';
$string['nopasting_help'] = 'Запретить пользователям вставлять текст в область транскрипции/текстовую область.';
$string['nopasting_details'] = 'Запретить пользователям вставлять текст в область транскрипции/текстовую область.';

$string['preloadtranscript'] = 'Предзагрузка автоматической транскрипции';
$string['preloadtranscript_help'] = 'Это загрузит автоматическую транскрипцию пользователя в область ручной транскрипции. Пользователь сможет только внести коррективы.';
$string['preloadtranscript_details'] = 'Это загрузит автоматическую транскрипцию пользователя в область ручной транскрипции. Пользователь сможет только внести коррективы.';

$string['enablesuggestions'] = 'Включить предложения ИИ';
$string['enablesuggestions_help'] = 'Позволить ИИ предлагать более корректную версию транскрипции студента. Результаты могут быть непредсказуемыми. Поэтому они пока не связаны с оцениванием.';
$string['enablesuggestions_details'] = 'Позволить ИИ предлагать более корректную версию транскрипции студента. Результаты могут быть непредсказуемыми. Поэтому они пока не связаны с оцениванием.';
$string['default_enablesuggestions'] = 'Включить предложения ИИ (по умолчанию)';

$string['enablegallery'] = 'Включить Галерею';
$string['enablegallery_help'] = 'Позволить студентам прослушивать записи других студентов по данной теме';
$string['enablegallery_details'] = 'Позволить студентам прослушивать записи других студентов по данной теме';
$string['nosuggestions'] = "Нет предложений.";
$string['checkgrammarandspelling'] = 'Проверить грамматику и орфографию';
$string['grammarandspellingsuggestions'] = 'Получить предложения по грамматике и орфографии';
$string['important'] = 'Важно';
$string['noemptyselftranscript'] = 'Пожалуйста, введите что-нибудь в текстовое поле перед уходом с этой страницы.';
$string['noemptyrecording'] = 'Пожалуйста, запишите и загрузите аудио перед уходом с этой страницы.';
$string['donotwaitfortranscript'] = 'Я не хочу ждать транскрипции.';

$string['enablelocalpost'] = "Включить Локальную Публикацию";
$string['enablelocalpost_details'] = "Это экспериментальная настройка для пользователей в материковом Китае. Локальная публикация отправляет записи аудио на сервер Moodle, который затем передаёт их на наши облачные серверы. Это *возможно* улучшит надёжность для пользователей с медленным подключением.";

$string['gradeequals'] = 'Оценка = ';
$string['bonusgrade'] = 'Бонусные Оценки';
$string['relevancegrade'] = 'Автооценка - Сходство/Релевантность';
$string['relevancegrade_help'] = 'Сходство — это показатель, генерируемый ИИ, показывающий, насколько семантически схож ответ студента с эталонным текстом. Если оценка сходства студента ниже порогового значения (x %), их общая оценка уменьшается пропорционально. Сходство рассчитывается только для ответов на английском языке.';
$string['relevancegrade_details'] = 'Уменьшить оценку за ответы на английском, если их семантическое сходство с эталонным ответом ниже порогового значения (x %). Если эталонный ответ не указан, это игнорируется.';
$string['relevance_none'] = 'Сходство не учитывается';
$string['relevance_broad'] = 'Широко схожий (50%)';
$string['relevance_quite'] = 'Довольно схожий (70%)';
$string['relevance_very'] = 'Очень схожий (80%)';
$string['relevance_extreme'] = 'Чрезвычайно схожий (90%)';

$string['suggestionsgrade'] = 'Автооценка - Предложения';
$string['suggestionsgrade_none'] = 'Предложения не влияют на оценку';
$string['suggestionsgrade_use'] = 'Предложения снижают оценку';
$string['suggestionsgrade_details'] = 'Если предложения влияют на оценку, то процентная разница между транскрипцией и предложенным текстом уменьшает оценку пропорционально.';
$string['suggestionsgrade_help'] = 'Если предложения влияют на оценку, то процентная разница между транскрипцией и предложенным текстом уменьшает оценку пропорционально.';

$string['fetching_auto_transcript'] = 'Получение транскрипции. Пожалуйста, подождите ...';
$string['no_grammar_corrections'] = 'Ошибок в грамматике нет. Отлично!';
$string['showcorrections'] = 'Показать исправления';
$string['hidecorrections'] = 'Скрыть исправления';

$string['pushpage'] = 'Страница Публикации';
$string['pushinstructions'] = 'На этой странице вы можете выбрать настройку активности, значение которой будет применено ко всем другим активностям в этом курсе. Вы можете расширить область действия на весь сайт, если вы администратор. Вы можете сузить область действия только до активностей с одинаковым названием. <BR> <b>Будьте очень осторожны</b>, здесь нет шага подтверждения. Настройки применяются сразу после нажатия кнопки «Сохранить». Убедитесь, что вы хотите это сделать.';
$string['pushformheading'] = 'Применить Настройки к другим активностям';
$string['pushaction'] = 'Настройка для применения';
$string['pushsitelevel'] = 'Применить ко всему сайту (иначе — только курсу)';
$string['pushsamename'] = 'Применить только к активностям с таким же названием';
$string['pushdone'] = 'Применение завершено. Обновлено {$a} записей.';

$string['layout'] = 'Макет';
$string['layout_standard'] = 'Стандартный';
$string['layout_narrow'] = 'Узкий';

$string['showgrammar'] = "Показать оценку грамматики";
$string['showspelling'] = "Показать оценку орфографии";
$string['showopts_no'] = "Не показывать.";
$string['showopts_yes'] = "Показать.";

$string['ttsspeed'] = 'Скорость TTS';
$string['mediumspeed'] = 'Средняя';
$string['slowspeed'] = 'Медленная';
$string['extraslowspeed'] = 'Очень медленная';

$string['modelanswer_help'] = 'Введите хороший и полный пример ответа на тему. Он будет использоваться как часть процесса оценки.';

$string['backtotranscriptedit'] = "Вернуться к редактированию";
$string['waitingforteacher'] = "Ваш учитель проверит вашу попытку. Спасибо!";
$string['gradesdate'] = 'Дата';

$string['prompttester'] = 'Тестер Оценки ИИ';
$string['prompttester_help'] = 'Используйте это для тестирования оценки ИИ. Введите пример ответа и посмотрите, как он будет оценен.';
$string['sampleanswerempty'] = 'Для тестирования оценки ИИ необходимо ввести пример ответа.';
$string['sampleanswerevaluate'] = 'Оценить';
$string['sampleanswer'] = 'Пример ответа';
$string['sampleanswerinstructions'] = 'Пример ответа используется для того, чтобы помочь вам протестировать оценку и инструкции по обратной связи, указанные выше. Введите пример ответа, похожий на реальный ответ студента, и нажмите "Оценить", чтобы увидеть, как ИИ на него отреагирует.';
$string['sampleanswer_help'] = 'Введите ответ, похожий на реальный ответ студента, и посмотрите, как ИИ его обработает.';

$string['markscheme'] = 'Инструкции по Оценке ИИ';
$string['markscheme_help'] = 'Инструкции ИИ о том, как оценивать ответ студента.';
$string['feedbackscheme'] = 'Инструкции по Обратной Связи ИИ';
$string['feedbackscheme_help'] = 'Инструкции ИИ о том, как предоставлять обратную связь по ответу студента.';
$string['feedbacklanguage'] = 'Язык Обратной Связи ИИ';
$string['stats_aigrade'] = 'Оценка ИИ';
$string['relevance_model'] = 'Релевантность - схожесть с эталонным ответом';
$string['relevance_question'] = 'Релевантность - к теме вопроса';

$string['aifeedback'] = 'Обратная связь ИИ:';
$string['autogradelog'] = 'Журнал Автооценки';
$string['yourtranscript'] = 'Ваша Транскрипция:';
$string['estimated'] = 'предполагаемый';
$string['ideacount'] = 'Идея/Концепт';
$string['aigradepreviewheader'] = 'Предварительный просмотр оценки ИИ';
$string['showttspassage'] = 'Прочитать вслух текст';
$string['resultsdisplay'] = 'Отображение результатов';
$string['starrating_use'] = 'Оценка по 5 звёздам';
$string['starrating_none'] = '% оценка + график пончиков';
$string['starrating'] = 'Оценка звёздами';
$string['starrating_help'] = 'Использовать систему оценки по 5 звёздам.';
$string['leveltypes'] = 'Оценочные уровни';
$string['leveltypes_help'] = 'Эта настройка определяет, какие уровни сертификации будут отображаться на экране. Уровни IELTS и TOEFL отображаются только если целью является английский язык.';
$string['showcefrlevel'] = 'Уровень CEFR';
$string['showieltslevel'] = 'Уровень IELTS';
$string['showtoefllevel'] = 'Уровень TOEFL';
$string['showgenericlevel'] = 'Общий уровень';
$string['beginner'] = 'Начальный';
$string['intermediate'] = 'Средний';
$string['highintermediate'] = 'Продвинутый Средний';
$string['lowadvanced'] = 'Низкий Продвинутый';
$string['advanced'] = 'Продвинутый';
$string['upperadvanced'] = 'Высокий Продвинутый';
$string['stats_ieltslevel'] = 'Уровень IELTS';
$string['stats_toefllevel'] = 'Уровень TOEFL';
$string['stats_genericlevel'] = 'Уровень языка';
$string['enablenativelanguage'] = "Включить Родной Язык";
$string['enablenativelanguage_details'] = 'Если включено, студент может выбрать свой родной язык. Это переопределит язык обратной связи, который ИИ возвращает с результатами. Язык должен быть установлен в <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">Poodll WordCards</a>, и он будет применяться здесь.';
$string['teacherfeedback'] = 'Обратная связь учителя';