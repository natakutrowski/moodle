<?php
// This file is part of Level Up XP.
//
// Level Up XP is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

/**
 * Language file.
 *
 * @package    block_xp
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @codingStandardsIgnoreFile
 */

// CampusFR custom FR/RU translations - block_xp 21.0a.

$string['actionrules'] = 'Правила действий';
$string['actionrules_help'] = 'Правила действий позволяют создавать условия, определяющие, когда и сколько баллов начисляется учащимся.

Для каждого действия условия проверяются в том порядке, в котором они отображаются на экране. Как только условие выполнено, баллы начисляются, а остальные условия для этого же действия больше не проверяются.

Для отдельных условий можно задать лимиты. Когда условие достигает своего лимита, баллы начисляться не будут, а всё действие будет пропущено.

[Подробнее](https://docs.levelup.plus/xp/docs/action-rules?ref=blockxp_help)';
$string['actionrulesintro'] = 'Начисляет учащимся баллы за выполненные ими действия.';
$string['actions'] = 'Действия';
$string['activityname'] = 'Название активности';
$string['activityname_help'] = 'Текст, который должно содержать (или которому должно быть равно) название активности. Регистрозависимость не учитывается.';
$string['activityoresourceis'] = 'Активность или ресурс — {$a}';
$string['addacondition'] = 'Добавить условие';
$string['addaction'] = 'Добавить действие';
$string['addanaction'] = 'Добавить действие';
$string['addarule'] = 'Добавить правило';
$string['addclient'] = 'Добавить клиент';
$string['addcondition'] = 'Добавить условие';
$string['addcourses'] = 'Добавить курсы';
$string['addondeactivated'] = 'XP+ отключён';
$string['addondeactivatedinfo'] = 'Плагины XP несовместимы друг с другом, поэтому XP+ был отключён. Ожидается версия {$a->localxpversion} Level Up XP+ (local_xp).';
$string['addonnotactivated'] = 'Дополнение не активировано.';
$string['adminaisettings'] = 'Настройки ИИ';
$string['adminaisettings_desc'] = 'Настройте подключение ИИ-приложений и помощников к Level Up XP через MCP. [Открыть документацию](https://docs.levelup.plus/xp/docs/ai-mcp)';
$string['ai'] = 'ИИ';
$string['aiaccessrevoked'] = 'Все ранее предоставленные доступы ИИ отозваны.';
$string['aiactiveaccess'] = 'Для вашей учётной записи действует доступ ИИ. Он может сохраняться в Moodle после отключения ИИ-помощника.';
$string['aichatgptinstructions'] = '1. В ChatGPT откройте раздел настройки, затем Plugins.
2. Выберите создание приложения MCP.
3. Назовите приложение «Level Up XP» и укажите в разделе подключения следующий URL сервера MCP:

    `{$a->mcpurl}`

4. Нажмите «Создать», затем войдите в Moodle и разрешите подключение.
5. Включите плагин Level Up XP в тех беседах, где хотите его использовать.

Дополнительные сведения приведены в [документации](https://docs.levelup.plus/xp/docs/ai-mcp/connect).';
$string['aiclaudeinstructions'] = '1. В Claude откройте раздел настройки, затем Connectors.
2. Выберите добавление пользовательского коннектора. Если такой возможности нет, попросите администратора рабочей области Claude добавить коннектор.
3. Назовите коннектор «Level Up XP» и укажите следующий URL удалённого сервера MCP:

   `{$a->mcpurl}`

4. Добавьте коннектор, затем войдите в Moodle и разрешите подключение.
5. Включите коннектор Level Up XP в беседах, где хотите использовать Claude.

Дополнительные сведения приведены в [документации](https://docs.levelup.plus/xp/docs/ai-mcp/connect).';
$string['aiconnect'] = 'Подключить ИИ-помощника';
$string['aiconnectintro'] = 'Здесь представлены варианты подключения, доступные по решению администратора.';
$string['aicustomassistant'] = 'Инструкции для пользовательского помощника';
$string['aicurrentaccess'] = 'Текущий доступ ИИ';
$string['ainoproviders'] = 'Варианты подключения ИИ не настроены. Обратитесь к администратору за инструкциями.';
$string['ainoactiveaccess'] = 'Сейчас у вашей учётной записи нет активного доступа ИИ.';
$string['aipageintro'] = 'Подключите ИИ-помощника, чтобы задавать вопросы о Level Up XP, изучать настройки курса, анализировать прогресс учащихся и получать практические идеи по улучшению геймификации.';
$string['aipagetitle'] = 'Использование ИИ с Level Up XP';
$string['aipagetitle_help'] = 'После подключения ИИ-помощники могут использовать инструменты Level Up XP, доступ к которым предоставил администратор.

Вы можете попросить объяснить, как учащиеся получают баллы и переходят на новые уровни, обобщить сведения о курсах и учащихся, помочь разобраться в текущих настройках и предложить улучшения с учётом ваших целей.

Доступная помощнику информация зависит от ваших прав в Moodle и инструментов, включённых на сайте.

[Подробнее](https://docs.levelup.plus/xp/docs/ai-mcp?ref=blockxp_help)';
$string['aipermissions'] = 'Разрешения ИИ';
$string['aipermissionsintro'] = 'Администратор установил эти разрешения для ИИ-помощников. Ваши права доступа в Moodle также действуют.';
$string['airevokeallaccess'] = 'Отозвать все доступы ИИ';
$string['airevokeallaccessconfirm'] = 'Отозвать все доступы ИИ? Все ИИ-помощники, имеющие доступ к вашей учётной записи, потеряют его.';
$string['aistartingprompt'] = 'Стартовый запрос';
$string['aistartingpromptintro'] = 'Используйте следующий запрос, чтобы узнать, что умеет ИИ-помощник и чем он может быть полезен.';
$string['aistartingpromptcourse'] = 'Мой курс называется «{$a}».';
$string['aistartingpromptoverall'] = 'Я работаю в общем контексте сайта.';
$string['aistartingprompttext'] = 'Помоги мне освоить Level Up XP. Сначала расскажи, чем ты можешь помочь с учётом доступных инструментов, сведений и разрешений. Затем кратко опиши текущие настройки Level Up XP и предложи три полезных вопроса, которые я мог бы задать дальше.';
$string['adminnoticeaddondeactivatedsubject'] = 'Плагин XP+ отключён!';
$string['adminnoticeaddondeactivatedmessage'] = 'Level Up XP+ отключён!

Это предупреждение отправлено потому, что Level Up XP+ был отключён для предотвращения возможных проблем. Плагины Level Up XP (block_xp) и Level Up XP+ (local_xp) сейчас несовместимы. Это может произойти, если XP обновлён до новой основной версии, а XP+ ещё не обновлён.

Несовместимость может привести к потере функций, ошибкам и другим непредвиденным последствиям. Для её устранения обновите Level Up XP+.

- Версия Level Up XP (block_xp): {$a->blockxpversion}
- Версия Level Up XP+ (local_xp): {$a->localxpversion}
- Ожидаемая версия Level Up XP+: {$a->localxpversionexpected}

Дополнительные материалы:

- [Документация по обновлению](https://docs.levelup.plus/xp/docs/upgrade)
- [Документация по отключению XP+](https://docs.levelup.plus/xp/docs/addon-deactivated)
- [Документация по совместимости](https://docs.levelup.plus/xp/docs/requirements-compatibility)';
$string['adminnoticefooter'] = 'Это уведомление отправлено всем администраторам. Чтобы отключить все административные уведомления, откройте настройки Level Up XP.';
$string['adminnoticeoutofsyncmessage'] = 'Предупреждение о несовместимости Level Up XP и Level Up XP+!

Плагины Level Up XP (block_xp) и Level Up XP+ (local_xp) сейчас не синхронизированы и несовместимы. Это может произойти, если XP обновлён до новой основной версии, а XP+ остался на старой версии.

Несовместимость может привести к потере функций, ошибкам и другим непредвиденным последствиям. Для её устранения обновите Level Up XP+.

**Важно!** В дальнейшем при несовпадении версий Level Up XP+ будет автоматически отключаться. Чтобы этого избежать, не обновляйте Level Up XP до новой основной версии без одновременного обновления Level Up XP+.

- Версия Level Up XP (block_xp): {$a->blockxpversion}
- Версия Level Up XP+ (local_xp): {$a->localxpversion}
- Ожидаемая версия Level Up XP+: {$a->localxpversionexpected}

Дополнительные материалы:

- [Документация по обновлению](https://docs.levelup.plus/xp/docs/upgrade)
- [Документация по совместимости](https://docs.levelup.plus/xp/docs/requirements-compatibility)';
$string['adminnoticeoutofsyncsubject'] = 'Несовместимость плагинов XP!';
$string['adminnoticeupdatesmessage'] = 'Доступны следующие обновления Level Up XP:

{$a}

Дополнительные материалы:

- [Документация по обновлению](https://docs.levelup.plus/xp/docs/upgrade)';
$string['adminnoticeupdatessubject'] = 'Доступны обновления Level Up XP';
$string['adminnotices'] = 'Админ-уведомления';
$string['adminnotices_desc'] = 'Если эта настройка включена, администраторы сайта могут время от времени получать важные уведомления о совместимости, безопасности и доступности новых версий Level Up XP и XP+.';
$string['adminscanearnxp'] = 'Админы могут получать баллы';
$string['adminscanearnxp_desc'] = 'По умолчанию администраторы не входят в группу пользователей, получающих баллы, потому что у них всегда есть право _block/xp:earnxp_ и они могли бы зарабатывать их везде. Этой настройкой вы можете разрешить администраторам тоже получать баллы.';
$string['admindefaultactionrulesintro'] = 'Следующие правила действий будут использоваться по умолчанию.';
$string['admindefaultrulesintro'] = 'Следующие правила будут использоваться по умолчанию в курсах, где добавлен блок.';
$string['admindefaultsettingsintro'] = 'Нижеприведённые параметры будут использоваться по умолчанию при добавлении блока в курс. Некоторые настройки можно заблокировать — тогда их значение будет строго применяться во всех экземплярах плагина.';
$string['admindefaultvisualsintro'] = 'Следующие параметры внешнего вида будут использоваться по умолчанию при добавлении блока в курс.';
$string['additionalresources'] = 'Дополнительные материалы';
$string['addlevel'] = 'Добавить уровень';
$string['addoninstallationerror'] = 'Обнаружена проблема с дополнением (local_xp): похоже, оно установлено некорректно. Администратору следует завершить установку.';
$string['allcoursesreset'] = 'Все курсы были сброшены.';
$string['alreadyused'] = 'Уже используется';
$string['anonymity'] = 'Анонимность';
$string['anonymity_help'] = 'Определяет, видят ли участники имена и аватары друг друга.';
$string['appauthorisation'] = 'Авторизация приложения';
$string['appauthorisationerror'] = 'Ошибка авторизации приложения';
$string['apppermissionchange'] = 'Начислять баллы и отправлять уведомления учащимся.';
$string['apppermissionpersonal'] = 'Читать сведения об учащихся и их активности XP.';
$string['apppermissionread'] = 'Читать сведения о курсах и Level Up XP.';
$string['apply'] = 'Применить';
$string['appwantstoaccessyouraccount'] = 'Приложение {$a} запрашивает доступ к вашей учётной записи.';
$string['appwillbeallowedto'] = 'Это приложение сможет:';
$string['authorisewillredirectto'] = 'После авторизации произойдёт перенаправление на адрес:';
$string['authorise'] = 'Авторизовать';
$string['authorisationurl'] = 'URL авторизации';
$string['availabilityinfonotincourse'] = 'Требуется контекст курса.';
$string['awardaxpwhen'] = '<strong>{$a}</strong> баллов начисляется, когда:';
$string['badgeaward'] = 'Значок к выдаче';
$string['badgeawarddesc'] = 'Значок, который выдается при достижении уровня.';
$string['basepoints'] = 'Базовые баллы';
$string['basepointslineardesc'] = 'Минимальный прирост между уровнями.';
$string['basepointsrelativedesc'] = 'Количество баллов для старта.';
$string['blockappearance'] = 'Внешний вид блока';
$string['blockappearancemovedtopluginsettings'] = 'Настройки внешнего вида блока перенесены на страницу настроек плагина.';
$string['cachedef_block_count'] = 'Количество экземпляров блока';
$string['cachedef_filters'] = 'Фильтры уровней';
$string['cachedef_metadata'] = 'Метаданные';
$string['cachedef_ruleevent_eventslist'] = 'Список некоторых событий';
$string['canjoinfromdatex'] = 'Вы сможете присоединиться с {$a}.';
$string['cannotbesetindefaults'] = 'Это нельзя задать в значениях по умолчанию.';
$string['cannotearnpoints'] = 'Нельзя получать баллы.';
$string['cannotshowblockconfig'] = 'Обычно здесь показываются настройки внешнего вида, но блок не найден. Чтобы изменить внешний вид блока, вернитесь [сюда]({$a}) (или туда, где вы добавили блок), включите режим редактирования и выберите «Настроить» в меню блока. Если блока нет, добавьте его в курс снова.';
$string['cannotshowblockconfigsys'] = 'Обычно здесь показываются настройки внешнего вида, но блок не найден. Возможно, его нет на [главной странице]({$a->fp}) и [панели по умолчанию]({$a->mysys}) пользователей — или он присутствует на обеих. Чтобы редактировать настройки здесь, убедитесь, что блок отображается только на одной из этих страниц.';
$string['certificateobtained'] = 'Сертификат получен';
$string['changecourse'] = 'Сменить курс';
$string['changetocourse'] = 'Перейти к курсу';
$string['changetositewide'] = 'Назад к режиму «на весь сайт»';
$string['cheatguard'] = 'Защита от накрутки';
$string['cheatguardrequiresshorttimelimit'] = 'Периоды продолжительностью более нескольких часов не поддерживаются.';
$string['cheatguardsettingsmovednotice'] = 'Настройки защиты от накрутки перенесены на [страницу правил событий]({$a->url}).';
$string['checkaddoncompatibility'] = 'Совместимость дополнения Level Up XP';
$string['chooseacondition'] = 'Выберите условие';
$string['cimd'] = 'CIMD';
$string['clearfilter'] = 'Сбросить фильтр';
$string['clientid'] = 'Идентификатор клиента';
$string['clicktoselectcm'] = 'Нажмите, чтобы выбрать активность или ресурс';
$string['cmselector'] = 'Выбор элемента курса';
$string['coefxp'] = 'Коэффициент алгоритма';
$string['colon'] = '{$a->a}: {$a->b}';
$string['comparisonmethod'] = 'Метод сравнения';
$string['compatibilitycheck'] = 'Проверка совместимости';
$string['completionrules'] = 'Правила завершения';
$string['completionrules_help'] = 'Правила завершения разбиты на три категории: завершение активности, завершение раздела и завершение курса. Добавленные условия в этих категориях определяют, когда и сколько баллов начислять.

Правила оцениваются в том порядке, в каком они показаны на экране. Как только условие выполнено — соответствующие баллы начисляются, а дальнейшие правила не оцениваются.

[Подробнее](https://docs.levelup.plus/xp/docs/completion-rules?ref=blockxp_help)';
$string['completionrulesintro'] = 'Начисляйте баллы студентам по мере завершения ими активностей, разделов или курсов.';
$string['completionruleslegacyusednotice'] = 'У вас есть существующие «Правила событий», использующие условия завершения. Настоятельно рекомендуем удалить их в пользу методов ниже, поскольку одновременное использование «Правил событий» и «Правил завершения» может привести к двойному начислению баллов.';
$string['condition'] = 'Условие';
$string['conditions'] = 'Условия';
$string['configdescription'] = 'Вступление';
$string['configdescription_help'] = 'Короткое приветственное сообщение, отображаемое в блоке. Студенты могут его скрыть — в этом случае оно больше не будет показываться.';
$string['configheader'] = 'Настройки';
$string['configtitle'] = 'Заголовок';
$string['configtitle_help'] = 'Заголовок блока.';
$string['configblockrankingsnapshot'] = 'Показывать мини-рейтинг';
$string['configblockrankingsnapshot_help'] = 'Мини-рейтинг показывает место пользователя и двух соседей выше и ниже него (если включён основной рейтинг и отображение мест).';
$string['configrecentactivity'] = 'Показывать недавние награды';
$string['configrecentactivity_help'] = 'Если включено, блок показывает короткий список последних событий, за которые студент получил баллы.';
$string['congratulationsyouleveledup'] = 'Поздравляем!';
$string['contactadminforinstructions'] = 'Администратор не предоставил инструкции по подключению ИИ-помощника. Уточните у него порядок подключения.';
$string['coolthanks'] = 'Отлично, спасибо!';
$string['copiedexcl'] = 'Скопировано!';
$string['coursea'] = 'Курс «{$a}»';
$string['courselog'] = 'Журнал';
$string['courselogintro'] = 'Журнал показывает зафиксированные действия и количество начисленных за них баллов.';
$string['coursereport'] = 'Отчёт';
$string['coursereportintro'] = 'Отчёт содержит информацию по каждому участнику и позволяет управлять ими индивидуально или группой.';
$string['courseselectedcolon'] = 'Выбранный курс:';
$string['coursesettings'] = 'Настройки курса';
$string['currencysign'] = 'Символ баллов';
$string['currencysign_help'] = 'Позволяет изменить символ, отображаемый рядом с количеством баллов, вместо обозначения «очки опыта».  
Выберите один из предложенных символов или загрузите собственный!';
$string['currencysignxp'] = 'XP (очки опыта)';
$string['currentgrants'] = 'Действующие разрешения';
$string['currentgrantsinfo'] = 'Пользователей: {$a->users}; действующих разрешений: {$a->grants}';
$string['customizelevels'] = 'Настроить уровни';
$string['dangerzone'] = 'Опасная зона';
$string['dataformat'] = 'Формат';
$string['defaultactionrules'] = 'Правила действий по умолчанию';
$string['defaultlevels'] = 'Уровни по умолчанию';
$string['defaultrules'] = 'Правила по умолчанию';
$string['defaultsettings'] = 'Настройки по умолчанию';
$string['defaultvisuals'] = 'Внешний вид по умолчанию';
$string['deleteoauthclientconfirm'] = 'Удалить этот клиент OAuth? Действие нельзя отменить; все связанные токены также будут удалены.';
$string['deletecondition'] = 'Удалить условие';
$string['deleterule'] = 'Удалить правило';
$string['description'] = 'Описание';
$string['difference'] = 'Разн.';
$string['difficulty'] = 'Метод расчёта баллов';
$string['difficultyflat'] = 'Равномерно';
$string['difficultyflatdesc'] = 'Для каждого уровня требуется одинаковое количество баллов.';
$string['difficultylinear'] = 'Возрастающе';
$string['difficultylineardesc'] = 'Каждый следующий уровень требует всё больше баллов.';
$string['difficultylinearincrdesc'] = 'Количество баллов, используемое для постепенного усложнения.';
$string['difficultypointincrease'] = 'Прирост баллов';
$string['difficultyrelative'] = 'Экспоненциально';
$string['difficultyrelativedesc'] = 'Уровни становятся всё сложнее по экспоненте.';
$string['difficultyrelativeincrdesc'] = 'Процент увеличения количества баллов по сравнению с предыдущим уровнем.';
$string['discoverlevelupplus'] = 'Больше возможностей с Level Up XP+';
$string['dismissnotice'] = 'Закрыть уведомление';
$string['displayeveryone'] = 'Показать всех';
$string['displaynneighbours'] = 'Показать {$a} соседей';
$string['displayoneneigbour'] = 'Показать одного соседа';
$string['displayparticipantsidentity'] = 'Показывать имена участников';
$string['displayrank'] = 'Показывать место';
$string['displayrelativerank'] = 'Показывать относительное место';
$string['documentation'] = 'Документация';
$string['drops'] = 'Дропы';
$string['dropsintro'] = 'Дропы — это фрагменты кода, встроенные в контент, которые начисляют баллы, когда пользователь их встречает.';
$string['drops_help'] = '  
В видеоиграх персонажи могут _ронять_ предметы или очки опыта, которые игрок подбирает. Эти предметы называются «дропами».

В Level Up XP дропы — это шорткоды (например `[xpdrop id=1 secret=abcdef]`), которые преподаватель может вставить в обычный контент Moodle. Когда студент встречает такой шорткод, ему начисляются баллы.

На данный момент дропы невидимы для пользователей и дают баллы только при первом обнаружении.

Примеры использования:
- Добавьте дроп в обратную связь теста, доступную только при идеальном результате;
- Поместите дроп глубоко в тексте — в награду за внимательное чтение;
- Добавьте дроп в интересную дискуссию на форуме;
- Разместите дроп на труднодоступной странице урока.

[Подробнее](https://docs.levelup.plus/xp/docs/how-to/use-drops?ref=blockxp_help)
';
$string['editcondition'] = 'Редактировать условие';
$string['editlimits'] = 'Редактировать лимиты';
$string['editingdefaultsettingsincoursemodenotice'] = '**Внимание!** Сейчас вы редактируете значения по умолчанию, а не активные настройки курса. Чтобы изменить параметры конкретного курса, откройте блок XP в нём и выберите ссылку «Настройки».';
$string['editingdefaultsettingsinwholesitemodenotice'] = '**Внимание!** Сейчас вы редактируете значения по умолчанию, а не активные настройки. Так как Level Up XP используется на всём сайте, скорее всего вы хотите изменить глобальные настройки. [Перейдите сюда]({$a->url}) или откройте настройки из самого блока XP.';
$string['embedleaderboard'] = 'Встроить таблицу лидеров';
$string['enablechatgpt'] = 'Разрешить подключения ChatGPT';
$string['enablechatgpt_desc'] = 'Разрешить пользователям подключать ChatGPT к Level Up XP. Пользователи увидят инструкции по настройке, а регистрация OAuth будет выполняться автоматически.';
$string['enablecheatguard'] = 'Включить защиту от накрутки';
$string['enablecheatguard_help'] = 'Защита от накрутки предотвращает простейшие способы обмана, например многократное обновление страницы или повтор одного и того же действия.  
[Подробнее](https://docs.levelup.plus/xp/docs/getting-started/cheat-guard?ref=blockxp_help)';
$string['enableclaude'] = 'Разрешить подключения Claude';
$string['enableclaude_desc'] = 'Разрешить пользователям подключать Claude к Level Up XP. Пользователи увидят инструкции по настройке, а регистрация OAuth будет выполняться автоматически.';
$string['enableglobalworld'] = 'Общий уровень';
$string['enableglobalworlddesc'] = 'Если Level Up XP работает в режиме «По курсам», этот параметр также позволяет добавить блок на информационную панель или главную страницу сайта. Там баллы из выбранных курсов объединяются для отображения общего уровня и рейтинга. [Подробнее](https://docs.levelup.plus/xp/docs/global-level)';
$string['enableinfos'] = 'Включить страницу информации';
$string['enableinfos_help'] = 'Если выбрать «Нет», студенты не смогут просматривать информационную страницу.';
$string['enableladder'] = 'Включить таблицу лидеров';
$string['enableladder_help'] = 'Если выбрать «Нет», студенты не смогут видеть таблицу лидеров.';
$string['enablelevelupnotif'] = 'Показывать уведомление о новом уровне';
$string['enablelevelupnotif_help'] = 'Если выбрать «Да», при достижении нового уровня студенту появится всплывающее поздравление.';
$string['enablemcp'] = 'Включить доступ по MCP';
$string['enablemcp_desc'] = 'Разрешить уполномоченным пользователям подключать ИИ-приложения и помощников к Level Up XP через MCP. У пользователей должно быть право `local/xp:usemcp`, а подключения должны использовать авторизованные клиенты OAuth.';
$string['enablexpgain'] = 'Включить начисление баллов';
$string['enablexpgain_help'] = 'Если выбрать «Нет», никто не будет получать баллы в этом курсе. Полезно для заморозки прогресса или временного отключения.  
Также это можно ограничить правом _block/xp:earnxp_.';
$string['entersearchterm'] = 'Введите поисковый запрос';
$string['envcheckaddonincompatibilitymessage'] = 'Плагин Level Up XP+ (local_xp) несовместим с Level Up XP (block_xp). Это приведёт к отключению XP+. Чтобы избежать этого, обновите оба плагина. Подробнее — https://docs.levelup.plus/xp/docs/compatibility.';
$string['erroraddondeactivated'] = 'Level Up XP+ отключён. Подробности см. в [документации]({$a->docsurl}).';
$string['errorcontextcoursemismatchforwholesite'] = 'Эта страница Level Up XP больше недоступна по указанному адресу. Если вы открыли ссылку или закладку, возможно, настройки изменились после её создания.';
$string['errorcontextcoursemismatchpercourse'] = 'Эта страница Level Up XP больше недоступна по указанному адресу. Если вы открыли ссылку или закладку, возможно, настройки изменились после её создания. Откройте Level Up XP из курса.';
$string['errorfeaturenotenabled'] = 'Эта функция не включена.';
$string['errorformvalues'] = 'Некоторые значения формы некорректны, исправьте ошибки.';
$string['errorlevelsincorrect'] = 'Минимальное количество уровней — 2.';
$string['errornotalllevelsbadgesprovided'] = 'Не все значки уровней заданы. Отсутствуют: {$a}.';
$string['errorunknownevent'] = 'Ошибка: неизвестное событие';
$string['errorunknownmodule'] = 'Ошибка: неизвестный модуль';
$string['errorxprequiredlowerthanpreviouslevel'] = 'Требуемое количество баллов меньше или равно предыдущему уровню.';
$string['eventsrules'] = 'Правила событий';
$string['eventsrules_help'] = 'Плагин использует события Moodle для начисления баллов за действия студентов.  
Используйте форму ниже, чтобы добавить собственные правила или изменить существующие.  

Рекомендуется просмотреть страницу _Журнала_, чтобы увидеть, какие события фиксируются при действиях студентов.  

Дополнительно:  
- [Как рассчитываются баллы опыта?](https://docs.levelup.plus/xp/docs/getting-started/points-calculation?ref=blockxp_help)  
- [Отладка правил](https://docs.levelup.plus/xp/docs/troubleshooting/event-rule-not-working?ref=blockxp_help)';
$string['eventsrulesintro'] = 'Отслеживайте события и начисляйте баллы учащимся по мере их выполнения. Сейчас мы рекомендуем использовать более новые правила «Действие» и «Завершение».';
$string['event_user_leveledup'] = 'Пользователь повысил уровень';
$string['eventis'] = 'Событие — {$a}';
$string['eventname'] = 'Название события';
$string['eventproperty'] = 'Свойство события';
$string['eventtime'] = 'Время события';
$string['export'] = 'Экспорт';
$string['exportdata'] = 'Экспортировать данные';
$string['filterbyrule'] = 'Фильтр по правилу';
$string['filterbyuser'] = 'Фильтр по пользователю';
$string['filterellipsis'] = 'Фильтр...';
$string['filtermodules'] = 'Фильтр модулей';
$string['filterparticipants'] = 'Фильтр участников';
$string['forever'] = 'Навсегда';
$string['give'] = 'дать';
$string['globalpoints'] = 'Суммарные баллы';
$string['globalpointsempty'] = 'Курсы не выбраны';
$string['globalpointsemptyintro'] = 'Добавьте курсы, чтобы выбрать источники баллов.';
$string['globalpointsintro'] = 'Общее количество баллов каждого пользователя в этом контексте равно сумме его баллов во всех учитываемых курсах. Оно изменяется при изменении баллов пользователя в любом из этих курсов.';
$string['gotofullladder'] = 'Перейти к полной таблице лидеров';
$string['graderules'] = 'Правила оценок';
$string['graderules_help'] = '  
Студенты получают столько баллов, сколько составляет их оценка.  
Оценки 5/10 и 5/100 обе дадут студенту 5 баллов.  
Если оценка студента меняется несколько раз, он получает баллы в размере максимальной из полученных оценок.  
Баллы никогда не отнимаются, отрицательные оценки игнорируются.  

Пример: Алиса сдаёт задание и получает 40/100 — в _Level Up XP_ ей начисляется 40 баллов.  
При второй попытке она получает 25/100, но её баллы в _Level Up XP_ не меняются.  
В третий раз Алиса получает 60/100, и ей начисляется ещё 20 баллов; всего 60.  

[Подробнее в документации Level Up XP](https://docs.levelup.plus/xp/docs/how-to/grade-based-rewards?ref=blockxp_help)
';
$string['graderulesintro'] = 'Правила оценок позволяют начислять студентам баллы, равные их оценкам.';
$string['hasname'] = 'Имя указано';
$string['hasnoname'] = 'Без имени';
$string['hasdescription'] = 'Есть описание';
$string['hasnodescription'] = 'Без описания';
$string['haspopupmessage'] = 'Всплывающее сообщение задано';
$string['hasnopopupmessage'] = 'Без всплывающего сообщения';
$string['hasbadgeaward'] = 'Выбран значок для награждения';
$string['hasnobadgeaward'] = 'Без значка для награждения';
$string['hideparticipantsidentity'] = 'Скрывать имена участников';
$string['hiderank'] = 'Скрыть место';
$string['importpoints'] = 'Импорт баллов';
$string['importpoints_help'] = 'Импорт можно использовать для _увеличения_ количества баллов учащихся или для _замены_ их количества указанным значением.

Обратите внимание: формат импорта __отличается__ от формата экспортируемого отчёта. Требуемый формат описан в [документации](https://docs.levelup.plus/xp/docs/how-to/import-points/importing-points-from-csv?ref=blockxp_help), где также доступен [пример файла](https://docs.levelup.plus/xp/docs/how-to/import-points/importing-points-from-csv?ref=blockxp_help#sample-file).';
$string['importpointsintro'] = 'Импортировать баллы из CSV-файла и при необходимости отправить сообщение получателю.';
$string['incourses'] = 'В курсах';
$string['ineffective'] = 'Неэффективно';
$string['infos'] = 'Информация';
$string['infos_help'] = 'Информационная страница даёт студентам обзор уровней и количества баллов, необходимых для их достижения. Она также показывает названия и описания уровней.';
$string['infosintro'] = 'Информационная страница отображает список уровней и основные сведения о них.';
$string['installed'] = 'Установлено';
$string['instructions'] = 'Инструкции';
$string['instructions_help'] = 'Инструкции отображаются на информационной странице. Используйте их, чтобы объяснить, как зарабатывать баллы, какие награды доступны и т.д.';
$string['intotal'] = 'Всего';
$string['invalid'] = 'Недействительно';
$string['invalidhint'] = 'Недопустимая причина';
$string['invalidoauthrequest'] = 'Недопустимый запрос OAuth: {$a}';
$string['invalidxp'] = 'Некорректное значение баллов';
$string['join'] = 'Присоединиться';
$string['joinleadeboardconfirmnote'] = 'Отлично! Мы рады, что вы с нами!  
Имейте в виду: после вступления нужно подождать некоторое время, прежде чем вы сможете покинуть таблицу лидеров.';
$string['joinleadeboardlockednote'] = 'Вы не можете присоединиться к таблице лидеров.';
$string['joinleaderboard'] = 'Присоединиться к рейтингу';
$string['keeplogs'] = 'Хранить журналы';
$string['keeplogsdesc'] = 'Период, после которого логи удаляются из базы данных. Логи играют важную роль: они используются для отслеживания начисленных баллов, определения недавней активности и многого другого. Удаление логов может повлиять на распределение баллов со временем.';
$string['ladder'] = 'Таблица лидеров';
$string['ladder_help'] = 'Таблица лидеров показывает рейтинг студентов по баллам.  
При использовании в курсе с группами она может создавать рейтинг для каждой группы.  
Доступно несколько параметров для кастомизации таблицы и опыта студентов.';
$string['ladderintro'] = 'Таблица лидеров показывает рейтинг участников на основе их общих баллов.';
$string['ladderadditionalcols'] = 'Дополнительные колонки';
$string['ladderadditionalcols_help'] = 'Эта настройка определяет, какие дополнительные колонки отображаются в рейтинге.  
Удерживайте CTRL или CMD при нажатии, чтобы выбрать несколько или снять выделение.';
$string['ladderempty'] = 'Рейтинг пока пуст — загляните позже!';
$string['ladderisodefault'] = 'По умолчанию (режим групп)';
$string['ladderisocohorts'] = 'По когортам';
$string['ladderiso'] = 'Изолировать участников';
$string['ladderiso_help'] = 'Создаёт отдельные рейтинги для разных групп.  

- **По умолчанию (режим групп)** — следует режиму групп курса и создаёт рейтинги для каждой группы;  
- **По когортам** — в рейтинге показываются только участники одной когорты.  

[Подробнее](https://docs.levelup.plus/xp/docs/leaderboard-isolation)';
$string['ladderparticipation'] = 'Участие';
$string['ladderparticipation_help'] = 'Определяет, обязательно ли участие в рейтинге или добровольное.  

- **Автоматически, без выхода** — все пользователи в рейтинге и не могут выйти;  
- **Автоматически, с возможностью выхода** — все добавляются в рейтинг, но могут выйти;  
- **По желанию** — нужно вступить в рейтинг вручную.  

После вступления или выхода пользователь может передумать.  
Однако, чтобы избежать злоупотреблений, вышедшие могут повторно выйти только через 3 дня.  

[Подробнее](https://docs.levelup.plus/xp/docs/leaderboard-opt-out)';
$string['ladderparticipationforced'] = 'Автоматически, без выхода';
$string['ladderparticipationoptin'] = 'По желанию (вступление)';
$string['ladderparticipationoptout'] = 'Автоматически, с возможностью выхода';
$string['ladderparticipationreset'] = 'Удалить информацию об участии всех пользователей';
$string['ladderparticipationreset_help'] = 'Если отмечено, данные об участии всех пользователей будут очищены — всем придётся вступать или выходить заново.';
$string['laddersettingsmovednotice'] = 'Настройки рейтинга перенесены на [страницу рейтинга]({$a->url}).';
$string['lastmetadatacheck'] = 'Последняя проверка метаданных';
$string['learnmore'] = 'Подробнее';
$string['leave'] = 'Покинуть';
$string['leaveleadeboardconfirmnote'] = 'Вы уверены, что хотите покинуть рейтинг?  
После выхода вы потеряете доступ к результатам, но сможете вернуться в любое время.';
$string['leaveleadeboardlockednote'] = 'Вы не можете выйти из рейтинга.';
$string['leaveleadeboardlockeduntilnote'] = 'Вы не можете выйти из рейтинга до {$a}.';
$string['leaveleaderboard'] = 'Покинуть рейтинг';
$string['level'] = 'Уровень';
$string['levelbadge'] = 'Значок уровня';
$string['levelbadges'] = 'Значки уровней';
$string['levelbadges_help'] = 'Загрузите изображения, чтобы изменить внешний вид отдельных уровней.

Файлы должны иметь имена формата [level].[расширение файла], например 1.png, 2.jpg и т. д.

Рекомендуемый размер — 100 × 100 пикселей. Поддерживаются форматы GIF, JPEG, PNG и SVG.';
$string['leveldescriptiondesc'] = 'Короткое описание уровня, отображаемое студентам на информационной странице.';
$string['levelpointsstart'] = 'Начало';
$string['levelpointslength'] = 'Длина (интервал)';
$string['levels'] = 'Уровни';
$string['levelsappearance'] = 'Внешний вид уровней';
$string['levelssaved'] = 'Уровни сохранены.';
$string['levelup'] = 'Повысить уровень!';
$string['levelupoptionsunavailableforlevelone'] = 'Параметры, связанные с достижением уровня, недоступны для первого уровня.';
$string['levelupplus'] = 'Level Up XP+';
$string['levelx'] = 'Уровень #{$a}';
$string['limitparticipants'] = 'Ограничить участников';
$string['limitparticipants_help'] = 'Эта настройка определяет, кто показывается в рейтинге. «Соседи» — это участники выше и ниже текущего. Например, при варианте «Показать 2 соседей» будут видны два участника выше и два ниже пользователя.';
$string['limits'] = 'Лимиты';
$string['logging'] = 'Журналирование';
$string['manageoauthclients'] = 'Управление клиентами OAuth';
$string['manual'] = 'Вручную';
$string['manually'] = 'Вручную';
$string['maxactionspertime'] = 'Макс. действий за интервал';
$string['maxactionspertime_help'] = 'Максимальное число действий, учитываемых для начисления баллов в заданный интервал. Последующие игнорируются. Если пусто или 0 — не ограничивается.';
$string['maxcolon'] = 'Макс.:';
$string['maxlevelexcl'] = 'макс. уровень!';
$string['mcpconnectinstructions'] = 'Инструкции по подключению';
$string['mcpconnectinstructions_desc'] = 'Вы можете добавить сведения, помогающие пользователям получить доступ к ИИ-помощнику и подключить его к Level Up XP. Если включён ChatGPT или Claude, эти сведения появятся над соответствующими инструкциями. Также можно направить пользователей к пользовательскому ИИ-агенту.';
$string['mcpdisabledadminnotice'] = 'Обратите внимание: все функции MCP сейчас отключены. См. административную настройку local_xp/enablemcp.';
$string['mcprisksallowed'] = 'Разрешения инструментов MCP';
$string['mcprisksallowed_desc'] = 'Ограничьте доступные инструменты MCP, выбрав разрешённые права. Инструмент будет недоступен, если ему требуется невыбранное разрешение. Права пользователей продолжают действовать. Ознакомьтесь с [полным списком инструментов]({$a->toolslisturl}) и связанными рисками.';
$string['mcpriskpersonal'] = 'Доступ к персональным данным';
$string['mcpriskread'] = 'Чтение информации';
$string['mcpriskwrite'] = 'Изменение данных или выполнение действий';
$string['mcptools'] = 'Инструменты MCP';
$string['mcptools_desc'] = 'Список инструментов MCP, доступных в Level Up XP.';
$string['mcpurl'] = 'URL сервера MCP';
$string['mcpurl_desc'] = 'URL для подключения ИИ-приложений и помощников к Level Up XP. Требуется аутентификация OAuth.';
$string['menu'] = 'Меню';
$string['messageprovider:adminnotice'] = 'Админ-уведомление';
$string['missing'] = 'Отсутствует';
$string['movecondition'] = 'Переместить условие';
$string['moverule'] = 'Переместить правило';
$string['name'] = 'Название';
$string['namecontains'] = 'Содержит «{$a}»';
$string['nameequalsto'] = 'Равно «{$a}»';
$string['navactionrules'] = 'Правила действий';
$string['navai'] = 'ИИ';
$string['navbardisplay'] = 'Показывать уровень на панели навигации';
$string['navbardisplay_desc'] = 'Если параметр включён, уровень пользователя отображается на верхней панели навигации. Когда баллы учитываются отдельно для каждого курса, уровень обычно показывается только внутри курсов. Другие настройки плагина могут влиять на отображаемый уровень и место его показа. Работа функции зависит от темы оформления и может быть некорректной в сторонних темах. [Подробнее](https://docs.levelup.plus/xp/docs/navbar-display)';
$string['navbarshowoverall'] = 'Общий уровень на панели навигации';
$string['navbarshowoveralldesc'] = 'Показывать общий уровень пользователя на верхней панели навигации, в том числе внутри курсов. Для этого должны быть включены общий уровень и отображение уровня на панели навигации.';
$string['navcompletionrules'] = 'Завершение';
$string['navdrops'] = 'Дропы';
$string['naveventrules'] = 'Правила событий';
$string['navgraderules'] = 'Правила оценок';
$string['navimport'] = 'Импорт';
$string['navinfos'] = 'Инфо';
$string['navladder'] = 'Рейтинг';
$string['navlevels'] = 'Уровни';
$string['navlevelssetup'] = 'Настройка';
$string['navlog'] = 'Журнал';
$string['navpoints'] = 'Баллы';
$string['navpromo'] = 'XP+';
$string['navreport'] = 'Отчёт';
$string['navsettings'] = 'Настройки';
$string['navvisuals'] = 'Внешний вид';
$string['newversioninstallednotice'] = 'Установлена новая версия! Посмотрите, что нового, в [заметках о выпуске]({$a->releasenotesurl}).';
$string['nextlevelin'] = 'до следующего уровня';
$string['noactionsyet'] = 'Действий пока нет!';
$string['noactionsyetintro'] = 'Начните с добавления действия для отслеживания.';
$string['noconditionsyet'] = 'Пока нет условий';
$string['noconditionsyetintro'] = 'Начните с добавления условия.';
$string['nodescription'] = 'Без описания';
$string['noissuesidentified'] = 'Проблем не обнаружено';
$string['nolimit'] = 'Без лимита';
$string['nologsrecordedyet'] = 'Журналы пока не записаны.';
$string['noname'] = 'Без названия';
$string['noneareavailable'] = 'Нет доступных';
$string['notallowed'] = 'Не разрешено';
$string['notecompatibilityissues'] = 'Обратите внимание на возможные проблемы совместимости ниже:';
$string['notesomesettingslocked'] = 'Некоторые параметры нельзя изменить, если они заблокированы администратором.';
$string['nothingmatchesfilter'] = 'Ничего не соответствует фильтру.';
$string['notingroupcontactsomeone'] = 'Для доступа к этой странице необходимо состоять в группе, но сейчас вы не состоите ни в одной. Свяжитесь с ответственным за курс.';
$string['notparticipating'] = 'Не участвует';
$string['notranked'] = 'Без места в рейтинге';
$string['notyetused'] = 'Ещё не использовалось';
$string['nperhoursmall'] = '{$a}/ч';
$string['nperdaysmall'] = '{$a}/день';
$string['nperweeksmall'] = '{$a}/нед.';
$string['npermonthsmall'] = '{$a}/мес.';
$string['ntimes'] = '{$a} раз(а)';
$string['numberoflevels'] = 'Количество уровней';
$string['oauthclient'] = 'Клиент OAuth';
$string['oauthclients'] = 'Клиенты OAuth';
$string['oauthclients_help'] = 'Клиенты OAuth';
$string['oauthclients_desc'] = 'ИИ-приложения и помощники проходят аутентификацию в Level Up XP через OAuth. Клиенты OAuth могут создаваться вручную или регистрироваться автоматически.';
$string['occasionally'] = 'Периодически';
$string['once'] = 'Один раз';
$string['onceperactivity'] = 'Один раз на активность';
$string['onceperassignment'] = 'Один раз на задание';
$string['onceperchapter'] = 'Один раз на главу';
$string['oncepercontentpiece'] = 'Один раз на единицу контента';
$string['oncepercourse'] = 'Один раз на курс';
$string['onceperdiscussion'] = 'Один раз на обсуждение';
$string['onceperforum'] = 'Один раз на форум';
$string['onceperpage'] = 'Один раз на страницу';
$string['onceperquiz'] = 'Один раз на тест';
$string['onlyauthoriseifyoutrustapp'] = 'Продолжайте только в том случае, если доверяете этому приложению.';
$string['onlyparticipantscanaccessranking'] = 'Только участники рейтинга могут видеть результаты.';
$string['outofsync'] = 'Несовместимость плагинов XP';
$string['outofsyncinfo'] = 'Плагины XP несовместимы друг с другом, что может вызвать ошибки. В будущем XP+ автоматически отключится. Ожидается версия {$a->localxpversion} Level Up XP+ (local_xp).';
$string['overalllimit'] = 'Общий лимит';
$string['overalllimitdesc'] = 'Общий лимит определяет, сколько раз условие может начислять баллы.';
$string['overalllimit_help'] = 'Общий лимит определяет, сколько раз условие может начислять баллы.

После достижения лимита за это действие баллы больше начисляться не будут. Используйте общий лимит, чтобы ограничить максимальное количество начислений за определённый период времени.

[Подробнее](https://docs.levelup.plus/xp/docs/action-rules/limits)';
$string['pagecurrentnotvisibletoviewers'] = 'Эта страница не видна студентам.';
$string['pagecurrentvisibletoviewers'] = 'Эта страница видна студентам.';
$string['pagesettings'] = 'Настройки страницы';
$string['participant'] = 'Участник';
$string['participants'] = 'Участники';
$string['participatesinleaderboard'] = 'Участвует в рейтинге';
$string['participatesnotinleaderboard'] = 'Не участвует в рейтинге';
$string['participatetolevelup'] = 'Участвуйте в курсе, зарабатывайте очки опыта и повышайте уровень!';
$string['participating'] = 'Участвует';
$string['peractivity'] = 'На активность';
$string['perassignment'] = 'На задание';
$string['perchapter'] = 'На главу';
$string['percontentpiece'] = 'На единицу контента';
$string['percourse'] = 'На курс';
$string['percourseonly'] = 'Только по курсам';
$string['perday'] = 'В день';
$string['perdiscussion'] = 'На обсуждение';
$string['perforum'] = 'На форум';
$string['perhour'] = 'В час';
$string['permonth'] = 'В месяц';
$string['perpage'] = 'На страницу';
$string['perpagecolon'] = 'На странице:';
$string['perquiz'] = 'На тест';
$string['perweek'] = 'В неделю';
$string['pickaconditiontype'] = 'Выберите тип условия';
$string['pkcerequired'] = 'Требуется PKCE';
$string['pluginavailabilityxpdesc'] = 'Позволяет ограничивать доступ к активностям на основе уровня студента.';
$string['pluginenrolxpdesc'] = 'Позволяет автоматически зачислять в курсы в зависимости от уровня в другом курсе.';
$string['pluginshortcodesdesc'] = 'Позволяет вставлять в контент элементы XP (баллы, уровень, рейтинг и т. д.) и показывать/скрывать контент в зависимости от уровня студента.';
$string['pluginname'] = 'Level Up XP';
$string['pluginnotenabled'] = 'Плагин «{$a->name}» ({$a->component}) не включён.';
$string['pluginoutdated'] = 'Плагин «{$a->name}» ({$a->component}) устарел, требуется версия «{$a->release}».';
$string['pluginshortcodesrequiredtousefeature'] = 'Для этой функции необходимо установить и включить плагин [Shortcodes](https://docs.levelup.plus/xp/docs/getting-started/installation/recommended-plugins).';
$string['pluginsoutofsync'] = '__Несовместимость плагинов XP!__  

Между Level Up XP и Level Up XP+ обнаружены проблемы совместимости. В будущем XP+ автоматически отключится, если будет несовместим. Чтобы избежать этого, обратитесь к администратору сайта. [Подробнее]({$a->url})';
$string['pluginxmaybeincompatible'] = 'Эта версия {$a->name} ({$a->component}) может быть несовместима с Moodle {$a->version}.';
$string['points'] = 'Баллы';
$string['pointstoaward'] = 'Количество баллов для начисления';
$string['pointstoaward_help'] = 'Количество баллов, которые будут начислены при выполнении условия.';
$string['pointsintimelinker'] = 'в';
$string['pointsperlevel'] = 'Баллов за уровень';
$string['pointsrequired'] = 'Необходимые баллы';
$string['popupnotificationmessage'] = 'Текст всплывающего уведомления';
$string['popupnotificationmessagedesc'] = 'Необязательное сообщение, показываемое во всплывающем окне при достижении уровня.';
$string['potentialmoodleincompatibility'] = 'Возможная несовместимость с Moodle';
$string['previewpopupnotification'] = 'Предпросмотр уведомления';
$string['privacy:path:addon'] = 'Дополнение';
$string['privacy:path:level'] = 'Уровень';
$string['privacy:path:logs'] = 'Журналы';
$string['privacy:path:userflags'] = 'Флаги пользователя';
$string['privacy:metadata:log'] = 'Хранит журнал событий';
$string['privacy:metadata:logs'] = 'Хранит лог начисления баллов';
$string['privacy:metadata:log:eventname'] = 'Название события';
$string['privacy:metadata:log:reason'] = 'Причина';
$string['privacy:metadata:log:subtype'] = 'Подтип причины.';
$string['privacy:metadata:log:time'] = 'Дата события';
$string['privacy:metadata:log:userid'] = 'Пользователь, получивший баллы';
$string['privacy:metadata:log:xp'] = 'Количество начисленных баллов';
$string['privacy:metadata:prefintro'] = 'Фиксирует, скрывал ли пользователь приветственное сообщение блока';
$string['privacy:metadata:preflevelup'] = 'Фиксирует, должен ли пользователь видеть уведомление о повышении уровня';
$string['privacy:metadata:prefnotices'] = 'Фиксирует, закрывал ли пользователь уведомление поддержки';
$string['privacy:metadata:prefseenpromo'] = 'Фиксирует, когда пользователь открывал промостраницу';
$string['privacy:metadata:prefladderpagesize'] = 'Предпочитаемое количество строк в рейтинге';
$string['privacy:metadata:xp'] = 'Хранит количество баллов и уровень пользователей';
$string['privacy:metadata:xp:xp'] = 'Баллы пользователя';
$string['privacy:metadata:xp:userid'] = 'Пользователь';
$string['progress'] = 'Прогресс';
$string['progressbar'] = 'Полоса прогресса';
$string['promoaskadmin'] = 'Узнайте у администратора о Level Up XP+.';
$string['promogetnow'] = 'Получить XP+';
$string['promointro'] = 'Level Up XP+ добавляет новые способы начисления баллов, больше настроек рейтингов и наград, пользовательские отчёты и интеграции с другими системами.';
$string['promointroinstalled'] = 'Дополнение Level Up XP+ установлено в вашей системе, и все его функции включены.';
$string['property:action'] = 'Действие события';
$string['property:component'] = 'Компонент события';
$string['property:crud'] = 'CRUD-свойство события';
$string['property:eventname'] = 'Название события';
$string['property:target'] = 'Цель события';
$string['provisionstates'] = 'Автоматическое создание записей пользователей';
$string['provisionstates_desc'] = 'По умолчанию пользователи появляются в рейтинге (а также в отчёте при режиме всего сайта) только после того, как XP обнаружит их. Автоматическое добавление пользователей — расширенная функция, которая создаёт отсутствующие записи для пользователей, приблизительно определённых по их ролям. Это выполняется периодически с помощью запланированной задачи, по умолчанию ежедневно. [Подробнее](https://docs.levelup.plus/xp/docs/automatic-user-provisioning)';
$string['questpromonotice'] = 'Повысьте геймификацию — откройте [Level Up Quest]({$a->questurl}).';
$string['quickeditpoints'] = 'Быстрое редактирование баллов';
$string['rank'] = 'Место';
$string['ranked'] = 'В рейтинге';
$string['ranking'] = 'Рейтинг';
$string['ranking_help'] = 'Место — это позиция пользователя в общем рейтинге. Относительное место показывает разницу в баллах между пользователем и соседями.';
$string['reallydeleteuserstate'] = 'Удаление пользователя нужно только для исключения его из рейтинга. В других случаях лучше обнулить баллы.  
Удаление не влияет на возможность получать баллы в будущем.  
В режиме «на весь сайт» удаление делает пользователя невидимым в отчёте и не позволит потом вернуть ему баллы. В режиме «по курсам» студент всё ещё может появляться в отчёте курса.  
Удалить баллы этого пользователя?';
$string['reallydeleteuserstateandlogs'] = 'Удаление пользователя исключает его из рейтинга и стирает все связанные журналы.  
Это может позволить ему снова получать баллы за прошлые действия. Если нужно только сбросить прогресс, лучше обнулить баллы.  
Удаление не влияет на возможность зарабатывать баллы в будущем.  
В режиме «на весь сайт» пользователь исчезнет из отчёта навсегда. В режиме «по курсам» — останется видимым, если зачислен в курс.  
Удалить баллы и журналы этого пользователя?';
$string['reallyresetallcoursessettingstodefaults'] = 'Сбросить ВСЕ настройки курсов к значениям по умолчанию? Это действие необратимо.';
$string['reallyresetallcoursestodefaults'] = 'Сбросить ВСЕ правила курсов к значениям по умолчанию? Это действие необратимо.';
$string['reallyresetcourserulestodefaults'] = 'Сбросить правила курса к значениям по умолчанию?';
$string['reallyresetallcourselevelstodefaults'] = 'Сбросить уровни во всех курсах к значениям по умолчанию?';
$string['reallyresetcourselevelstodefaults'] = 'Сбросить уровни курса к значениям по умолчанию?';
$string['reallyresetallcoursevisualstodefaults'] = 'Сбросить внешний вид уровней во всех курсах к значениям по умолчанию?';
$string['reallyresetcoursevisualstodefaults'] = 'Сбросить внешний вид уровней к значениям по умолчанию?';
$string['reallyresetdata'] = 'Сбросить уровни и баллы всех пользователей этого курса?';
$string['reallyresetgroupdata'] = 'Сбросить уровни и баллы всех в этой группе?';
$string['reallyreverttopluginsdefaults'] = 'Вернуть правила по умолчанию, предлагаемые плагином?';
$string['reason'] = 'Причина';
$string['reasonactivityviewed'] = 'Активность просмотрена';
$string['reasonassignfeedbackread'] = 'Отзыв прочитан';
$string['reasonassignsubmitted'] = 'Задание отправлено';
$string['reasonchapterread'] = 'Глава прочитана';
$string['reasondatabaseentrycreated'] = 'Запись в базе данных создана';
$string['reasondiscussioncreated'] = 'Обсуждение создано';
$string['reasondiscussionread'] = 'Обсуждение прочитано';
$string['reasondiscussionrepliedto'] = 'Ответ в обсуждении';
$string['reasonfeedbackanswered'] = 'Обратная связь заполнена';
$string['reasonglossaryentrypublished'] = 'Запись глоссария опубликована';
$string['reasonlessoncontentviewed'] = 'Содержимое урока просмотрено';
$string['reasonlessonendreached'] = 'Урок завершён';
$string['reasonlessonstarted'] = 'Урок начат';
$string['reasonquizattemptfinished'] = 'Попытка теста завершена';
$string['reasonquizattemptstarted'] = 'Попытка теста начата';
$string['rawlogourl'] = 'Прямой URL логотипа';
$string['recentrewards'] = 'Недавние награды';
$string['recommended'] = 'Рекомендуется';
$string['recommendedplugins'] = 'Рекомендуемые плагины';
$string['redirecturls'] = 'URL перенаправления';
$string['releasenotes'] = 'Заметки о выпуске';
$string['remaining'] = 'осталось';
$string['removeall'] = 'Удалить всё';
$string['removefilter'] = 'Удалить фильтр';
$string['repeatsallowed'] = 'Повторы разрешены';
$string['repetitionlimit'] = 'Лимит повторений';
$string['repetitionlimitdesc'] = 'Лимит повторений определяет, когда пользователи могут повторять похожие действия, чтобы снова получать баллы.';
$string['repetitionlimit_help'] = 'Лимит повторений определяет, могут ли пользователи повторять похожие действия и снова получать баллы.

Цель лимита повторений — предотвращать злоупотребления и поощрять более широкую вовлечённость. Например, в форуме можно ограничить повторы до одного раза на обсуждение.

Применяются и общий лимит, и лимит повторений. Когда достигается любой из них, баллы больше не начисляются.

[Подробнее](https://docs.levelup.plus/xp/docs/action-rules/limits)';
$string['repetitionlimitset'] = 'Лимит повторений задан';
$string['repetitiontimeframe'] = 'Период повторений';
$string['reportisempty'] = 'Отчёт пуст: студенты ещё не получили баллы.';
$string['reportisemptyenrolstudents'] = 'Отчёт пуст — возможно, в курс ещё не зачислены студенты?';
$string['requiresplugin'] = 'Требуется плагин «{$a->name}» ({$a->component}).';
$string['resetcoursedata'] = 'Сбросить данные курса';
$string['resetallcoursestodefaults'] = 'Сбросить все курсы';
$string['resetallcoursestodefaultsintro'] = 'Нажмите кнопку ниже, чтобы сбросить все курсы к значениям по умолчанию.';
$string['resetallcoursessettingstodefaults'] = 'Следующая ссылка позволяет [сбросить все курсы к настройкам по умолчанию]({$a->url}). Сначала сохраните изменения. Это перезапишет все настройки курсов и необратимо. На уровни, внешний вид и правила не влияет — для их сброса используйте соответствующие страницы.';
$string['resetcourses'] = 'Сбросить курсы';
$string['resetgroupdata'] = 'Сбросить данные группы';
$string['resetladderparticiptionofeveryone'] = 'Сбросить статусы участия всех пользователей';
$string['resettodefaults'] = 'Сбросить к значениям по умолчанию';
$string['resultsfilteredforn'] = 'Отфильтрованные результаты для {$a}.';
$string['resultsfilteredforarule'] = 'Результаты отфильтрованы по правилу.';
$string['resultsfilteredforrulen'] = 'Результаты отфильтрованы по правилу «{$a}».';
$string['reward'] = 'Награда';
$string['requires'] = 'Требует';
$string['reveal'] = 'Показать';
$string['reverttopluginsdefaults'] = 'Вернуть настройки плагина по умолчанию';
$string['reverttopluginsdefaultsintro'] = 'Нажмите кнопку, чтобы вернуть настройки выше к значениям, предлагаемым плагином. Это не влияет на существующие курсы.';
$string['revokegrants'] = 'Отозвать разрешения';
$string['revokegrantsconfirm'] = 'Отозвать все разрешения этого клиента OAuth? Пользователям с действующими разрешениями потребуется снова авторизовать этот клиент.';
$string['riskdelete'] = 'Запись';
$string['riskpersonal'] = 'Персональные данные';
$string['riskpersonal_desc'] = 'Может получать доступ к персональным данным пользователей.';
$string['riskread'] = 'Чтение';
$string['risks'] = 'Риски';
$string['riskwrite'] = 'Запись';
$string['rule'] = 'Правило';
$string['rule:contains'] = 'содержит';
$string['rule:eq'] = 'равно';
$string['rule:eqs'] = 'строго равно';
$string['rule:gt'] = 'больше';
$string['rule:gte'] = 'больше или равно';
$string['rule:lt'] = 'меньше';
$string['rule:lte'] = 'меньше или равно';
$string['rule:regex'] = 'соответствует регулярному выражению';
$string['ruleadded'] = 'Условие добавлено.';
$string['rulecm'] = 'Активность или ресурс';
$string['rulecm_help'] = 'Условие выполняется, когда событие происходит в указанной активности или ресурсе.';
$string['rulecmdesc'] = 'Активность или ресурс: "{$a->contextname}".';
$string['rulecmdescwithcourse'] = 'Активность или ресурс: "{$a->contextname}" в курсе "{$a->coursename}".';
$string['rulecminfo'] = 'Условие требует, чтобы действие происходило в конкретной активности или ресурсе.';
$string['ruleevent'] = 'Конкретное событие';
$string['ruleeventdesc'] = 'Событие — "{$a->eventname}"';
$string['ruleeventinfo'] = 'Выберите действие из списка доступных событий.';
$string['rulefilteryalreadyusedbyaction'] = 'Это условие уже используется этим действием и не может быть добавлено несколько раз.';
$string['rulefiltercm'] = 'Определённая активность';
$string['rulefiltercmdesc'] = 'Выбрать конкретную активность или ресурс курса.';
$string['rulefiltercmname'] = 'Название активности';
$string['rulefiltercmnamedesc'] = 'Условие на основе названия активности.';
$string['rulefiltercmtag'] = 'Тег активности';
$string['rulefiltercmtagdesc'] = 'Это условие сработает, если у активности есть определённый тег.';
$string['rulefiltercmtagfield'] = 'Название тега';
$string['rulefiltercmtaghelp'] = 'Введите название тега точно так же, как при добавлении тега к активности.';
$string['rulefilteranycm'] = 'Любая активность';
$string['rulefilteranycmdesc'] = 'Соответствует любой активности.';
$string['rulefilteranycourse'] = 'Любой курс';
$string['rulefilteranycoursedesc'] = 'Соответствует любому курсу.';
$string['rulefilteranysection'] = 'Любой раздел';
$string['rulefilteranysectiondesc'] = 'Соответствует любому разделу.';
$string['rulefilterany'] = 'Любое';
$string['rulefilteranydesc'] = 'Соответствует чему угодно.';
$string['rulefilternone'] = 'Ничего';
$string['rulefiltersection'] = 'Определённый раздел';
$string['rulefiltersectiondesc'] = 'Выбрать конкретный раздел курса.';
$string['rulefilterthiscourse'] = 'Этот курс';
$string['rulefilterthiscoursedesc'] = 'Применяется к текущему курсу.';
$string['ruleproperty'] = 'Свойство события';
$string['rulepropertydesc'] = 'Свойство "{$a->property}" {$a->compare} "{$a->value}".';
$string['rulepropertyinfo'] = 'Условие для опытных пользователей, знакомых с системой событий Moodle.';
$string['ruleset'] = 'Набор условий';
$string['ruleset:all'] = 'ВСЕ условия истинны';
$string['ruleset:any'] = 'ЛЮБОЕ условие истинно';
$string['ruleset:none'] = 'НИ одно условие не истинно';
$string['rulesetinfo'] = 'Объединяет несколько условий в одно.';
$string['ruletypeanswerfeedback'] = 'Ответить на вопросы обратной связи';
$string['ruletypeanswerfeedbackdesc'] = 'Когда пользователь отвечает на вопросы в активности обратной связи.';
$string['ruletypecmcompletion'] = 'Завершение активности';
$string['ruletypecmcompletiondesc'] = 'Начислять баллы, когда активность помечена как завершённая.';
$string['ruletypecoursecompletion'] = 'Завершение курса';
$string['ruletypecoursecompletiondesc'] = 'Начислять баллы при завершении курса.';
$string['ruletypecreatedatabaseentry'] = 'Создать запись в базе данных';
$string['ruletypecreatedatabaseentrydesc'] = 'Когда пользователь создаёт новую запись в активности базы данных.';
$string['ruletypecreateforumdiscussion'] = 'Создать обсуждение на форуме';
$string['ruletypecreateforumdiscussiondesc'] = 'Когда пользователь создаёт новое обсуждение в активности форума.';
$string['ruletypefinishquizattempt'] = 'Завершить попытку теста';
$string['ruletypefinishquizattemptdesc'] = 'Когда пользователь завершает попытку теста.';
$string['ruletypeobtaincertificate'] = 'Получить сертификат';
$string['ruletypeobtaincertificatedesc'] = 'Когда пользователю выдаётся сертификат о прохождении курса.';
$string['ruletypepublishglossaryentry'] = 'Опубликовать запись глоссария';
$string['ruletypepublishglossaryentrydesc'] = 'Когда запись пользователя в глоссарии опубликована.';
$string['ruletypereachlessonend'] = 'Достичь конца урока';
$string['ruletypereachlessonenddesc'] = 'Когда пользователь доходит до конца активности «Урок».';
$string['ruletypereadassignfeedback'] = 'Прочитать отзыв к заданию';
$string['ruletypereadassignfeedbackdesc'] = 'Когда пользователь читает отзыв к своей отправленной работе.';
$string['ruletypereadchapter'] = 'Прочитать главу';
$string['ruletypereadchapterdesc'] = 'Когда пользователь открывает главу активности «Книга».';
$string['ruletypereadforumdiscussion'] = 'Прочитать обсуждение на форуме';
$string['ruletypereadforumdiscussiondesc'] = 'Когда пользователь просматривает обсуждение в активности форума.';
$string['ruletypereplyforumdiscussion'] = 'Ответить в обсуждении форума';
$string['ruletypereplyforumdiscussiondesc'] = 'Когда пользователь публикует ответ в обсуждении форума.';
$string['ruletypesectioncompletion'] = 'Завершение раздела';
$string['ruletypesectioncompletiondesc'] = 'Когда все активности в разделе курса отмечены как завершённые.';
$string['rulesscope'] = 'Область применения';
$string['rulesscope_help'] = 'Область определяет, когда правила действуют.

Правила могут быть двух уровней — для всего сайта или для отдельных курсов.  
Сначала применяются правила курса, затем — общие.  

- **Сайт** — действуют на всём сайте, если в курсе нет своих правил;  
- **Курс** — действуют только внутри курса и имеют приоритет над общими.';
$string['ruletypestartlesson'] = 'Начать урок';
$string['ruletypestartlessondesc'] = 'Когда пользователь начинает активность «Урок».';
$string['ruletypestartquizattempt'] = 'Начать попытку теста';
$string['ruletypestartquizattemptdesc'] = 'Когда пользователь начинает попытку в тесте.';
$string['ruletypesubmitassignment'] = 'Отправить задание';
$string['ruletypesubmitassignmentdesc'] = 'Когда пользователь отправляет выполненное задание.';
$string['ruletypeviewactivity'] = 'Просмотреть активность';
$string['ruletypeviewactivitydesc'] = 'Когда пользователь открывает страницу активности.';
$string['ruletypeviewconsumecontent'] = 'Просмотреть контент';
$string['ruletypeviewconsumecontentdesc'] = 'Когда пользователь просматривает любой тип контента в широком смысле.';
$string['ruletypeviewcourse'] = 'Просмотреть страницу курса';
$string['ruletypeviewcoursedesc'] = 'Когда пользователь открывает страницу курса.';
$string['ruletypeviewlessoncontent'] = 'Просмотреть содержимое урока';
$string['ruletypeviewlessoncontentdesc'] = 'Когда пользователь просматривает содержимое страницы в активности «Урок».';
$string['ruletypeviewproducecontent'] = 'Создать контент';
$string['ruletypeviewproducecontentdesc'] = 'Когда пользователь создаёт любой тип контента в широком смысле.';
$string['searchandselectcourse'] = 'Найти и выбрать курс';
$string['searchandselectmodule'] = 'Найти и выбрать активность или ресурс';
$string['selectcourse'] = 'Выбрать курс';
$string['send'] = 'Отправить';
$string['setpoints'] = 'Установить баллы';
$string['settingsoutdatedxppnotice'] = 'Если вы видите эти настройки, установлена устаревшая версия XP+. Обратитесь к администратору для обновления.';
$string['shortcode:xpbadge'] = 'Значок текущего уровня пользователя.';
$string['shortcode:xpladder'] = 'Показать часть таблицы лидеров.';
$string['shortcode:xpladder_help'] = '
По умолчанию отображается часть рейтинга вокруг текущего пользователя.

```
[xpladder]
```

Чтобы показать топ-10 студентов вместо соседей пользователя, добавьте параметр `top`. Можно указать количество, например `top=20`.

```
[xpladder top]
[xpladder top=15]
```

Ссылка на полный рейтинг появляется автоматически под таблицей. Чтобы скрыть её, добавьте аргумент `hidelink`.

```
[xpladder hidelink]
```

По умолчанию таблица не включает колонку прогресса. Если в настройках рейтинга она выбрана, используйте аргумент `withprogress`, чтобы показать её.

```
[xpladder withprogress]
```

Если курс использует группы, плагин автоматически выберет подходящую таблицу лидеров.
';
$string['shortcode:xpiflevel'] = 'Показывать содержимое, если уровень пользователя совпадает.';
$string['shortcode:xpiflevel_help'] = '
Смотрите примеры ниже для правильного форматирования шорткода. Если уровень задан точно, содержимое отображается независимо от других правил.
Правила _больше_ и _меньше чем_ должны выполняться одновременно, иначе содержимое может не появиться вовсе.
Преподаватели и пользователи с правами редактирования всегда видят всё.

```
[xpiflevel 1 3 5]
Отображается, если уровень пользователя равен 1, 3 или 5.
[/xpiflevel]

[xpiflevel >3]
Отображается, если уровень пользователя больше 3.
[/xpiflevel]

[xpiflevel >=3]
Отображается, если уровень пользователя больше или равен 3.
[/xpiflevel]

[xpiflevel >=10 <20 30]
Отображается, если уровень пользователя больше или равен 10 И меньше 20,
ИЛИ равен 30.
[/xpiflevel]

[xpiflevel <=10 >=20]
Никогда не отображается, так как уровень не может быть одновременно ≤10 и ≥20.
[/xpiflevel]
```

Эти шорткоды НЕЛЬЗЯ вкладывать друг в друга.
';
$string['shortcode:xplevelname'] = 'Показать название уровня.';
$string['shortcode:xplevelname_help'] = '
По умолчанию тег показывает название уровня текущего пользователя.
Можно использовать аргумент `level`, чтобы показать название конкретного уровня.

```
[xplevelname]
[xplevelname level=5]
```

Если уровень с указанным номером не существует, ничего не отображается.
';
$string['shortcode:xppoints'] = 'Показать количество баллов в формате XP.';
$string['shortcode:xppoints_help'] = '
По умолчанию отображает количество баллов текущего пользователя. Можно указать произвольное число, чтобы заменить значение.

Оформление зависит от того, показываются ли реальные баллы пользователя или произвольное значение. Аргумент `plain` убирает оформление.

```
[xppoints]
[xppoints 500]
[xppoints 123 plain]
```
';
$string['shortcode:xpprogressbar'] = 'Показать полосу прогресса пользователя.';
$string['shortcodeinactiveleaderboarddisabled'] = 'Рейтинг отключён — шорткод неактивен. Можно включить рейтинг в его настройках.';
$string['shortcodexpteamladderembedintro'] = 'С помощью следующего шорткода таблицу лидеров можно встроить в любое место на этом сайте. Дополнительные параметры и информацию можно найти в [документации](https://docs.levelup.plus/xp/docs/leaderboard-embed).';
$string['shortcodexpladderembedintro'] = 'С помощью следующего шорткода таблицу лидеров можно встроить в любое место на этом сайте. Дополнительные параметры и информацию можно найти в [документации](https://docs.levelup.plus/xp/docs/leaderboard-embed).';
$string['showcustomassistant'] = 'Показывать пользовательского помощника';
$string['showcustomassistant_desc'] = 'Показывать пользователям инструкции по доступу к пользовательскому ИИ-помощнику, который настроен и управляется вне Level Up XP.';
$string['signedinasxony'] = 'Вход выполнен как {$a->user} на сайте {$a->site}';
$string['sitewide'] = 'На весь сайт';
$string['sitewideonly'] = 'Только на уровне сайта';
$string['somefeaturesrequireotherplugins'] = 'Некоторые функции требуют установки дополнительных плагинов.';
$string['someoneelse'] = 'Кто-то другой';
$string['somethinghappened'] = 'Что-то произошло';
$string['source'] = 'Источник';
$string['sources'] = 'Источники';
$string['sourceurl'] = 'URL источника';
$string['sourceremoveconfirm'] = 'Убрать этот курс из выбранных? Баллы, полученные в нём, больше не будут учитываться здесь в общих результатах участников.';
$string['sourceremoveallconfirm'] = 'Убрать все курсы из выбранных? Баллы всех участников в этом контексте станут равны нулю.';
$string['taskadminnotices'] = 'Админ-уведомления';
$string['taskcollectionloggerpurge'] = 'Очистить журнал коллекции';
$string['taskstateprovisioner'] = 'Создание записей пользователей';
$string['taskusagereport'] = 'Отчёт об использовании';
$string['teamleaderboard'] = 'Командный рейтинг';
$string['teamleaderboard_help'] = 'Командный рейтинг показывает места команд по сумме баллов участников.  
Команды могут формироваться из групп курса или когорт. Есть параметры для компенсации разных размеров команд.  
[Подробнее](https://docs.levelup.plus/xp/docs/how-to/setup-team-leaderboard/team-leaderboard?ref=blockxp_help)';
$string['teamleaderboardintro'] = 'Командный рейтинг — это список команд по сумме баллов их участников.';
$string['teams'] = 'Команды';
$string['timeframe'] = 'Период времени';
$string['timesallowed'] = 'Разрешённое количество раз';
$string['tokenurl'] = 'URL токена';
$string['total'] = 'Всего';
$string['tool'] = 'Инструмент';
$string['thankyou'] = 'Спасибо!';
$string['timebetweensameactions'] = 'Интервал между одинаковыми действиями';
$string['timebetweensameactions_help'] = 'Минимальное время между повтором одинакового действия (например повторное чтение того же поста). Если пусто или 0 — не ограничивается.';
$string['timeformaxactions'] = 'Интервал для макс. действий';
$string['timeformaxactions_help'] = 'Интервал (в секундах), в течение которого нельзя превысить заданное количество действий.';
$string['tinytimenow'] = 'сейчас';
$string['tinytimeseconds'] = '{$a}с';
$string['tinytimeminutes'] = '{$a}м';
$string['tinytimehours'] = '{$a}ч';
$string['tinytimedays'] = '{$a}д';
$string['tinytimeweeks'] = '{$a}н';
$string['tinytimewithinayearformat'] = '%b %e';
$string['tinytimeolderyearformat'] = '%b %Y';
$string['tryme'] = 'Попробовать';
$string['type'] = 'Тип';
$string['unavailablebecause'] = 'Недоступно по следующей причине:';
$string['unknown'] = 'Неизвестно';
$string['unlimitedrepeats'] = 'Неограниченные повторы';
$string['unlockfeaturewithxpplus'] = 'Для этой функции требуется XP+. <a href="{$a}">Подробнее</a>';
$string['unavailable'] = 'Недоступно';
$string['undo'] = 'Отменить';
$string['unstableversioninstalledinfo'] = 'Эта версия Level Up XP (block_xp) ещё в разработке и считается нестабильной. Используйте официальные релизы.';
$string['upgradingplugins'] = 'Обновление плагинов';
$string['upgradetoaddmore'] = 'Достигнут лимит.';
$string['unstableversioninstalled'] = 'Установлена нестабильная версия';
$string['userladderparticipation'] = 'Участие в рейтинге';
$string['userladderparticipation_help'] = 'Определяет, участвует ли пользователь в рейтинге. Не влияет на командный рейтинг.';
$string['userladderparticipationlocked'] = 'Заблокировать участие до';
$string['userladderparticipationlocked_help'] = 'Дата, после которой пользователь сможет изменить статус участия.';
$string['valid'] = 'Действительно';
$string['value'] = 'Значение';
$string['visitpagetoeditdefaultactionrules'] = 'Правила действий теперь являются рекомендуемым способом настройки правил. Перейдите на [эту страницу]({$a}), чтобы изменить их значения по умолчанию.';
$string['visualsintro'] = 'Настройте внешний вид уровней и обозначение баллов.';
$string['wherearexpused'] = 'Как учитываются баллы?';
$string['wherearexpused_desc'] = 'В режиме «По курсам» пользователи получают баллы и повышают уровень отдельно в каждом курсе. У каждого курса свой рейтинг. В режиме «На всём сайте» баллы, полученные на сайте, суммируются в единое количество баллов и единый уровень для каждого пользователя; действует общий рейтинг. [Подробнее](https://docs.levelup.plus/xp/docs/getting-started/per-course-or-site)';
$string['updateandpreview'] = 'Обновить и просмотреть';
$string['urlaccessdeprecated'] = 'Доступ по этому URL устарел, обновите ссылки.';
$string['usagereport'] = 'Отправлять отчёт об использовании';
$string['usagereport_desc'] = 'Периодически отправлять анонимную статистику разработчикам плагина. Она поможет улучшить продукт. Включает URL Moodle, версию и сводные данные (число пользователей, настройки, используемые правила и т.д.).';
$string['usealgo'] = 'Использовать алгоритм';
$string['usecustomlevelbadges'] = 'Использовать собственные значки уровней';
$string['usecustomlevelbadges_help'] = 'Если выбрано «Да», необходимо загрузить изображение для каждого уровня.';
$string['usedefaultlimits'] = 'Использовать лимиты по умолчанию';
$string['unknownactivitya'] = 'Неизвестная активность ({$a})';
$string['unknownbadgea'] = 'Неизвестный значок ({$a})';
$string['unknownconditiona'] = 'Неизвестное условие ({$a})';
$string['unknowneventa'] = 'Неизвестное событие ({$a})';
$string['unknowntypea'] = 'Неизвестный тип ({$a})';
$string['unknownsectiona'] = 'Неизвестный раздел ({$a})';
$string['viewas'] = 'Просмотреть как';
$string['viewlogs'] = 'Просмотреть журнал';
$string['when'] = 'Когда';
$string['whoops'] = 'Упс!';
$string['wewillreplyat'] = 'Мы ответим на: _{$a}_.';
$string['xp:addinstance'] = 'Добавить новый блок';
$string['xp:earnxp'] = 'Получение баллов';
$string['xp:manage'] = 'Управление всеми аспектами XP';
$string['xp:myaddinstance'] = 'Добавить блок на мою панель';
$string['xp:view'] = 'Просматривать блок и его страницы';
$string['xp:viewlogs'] = 'Просматривать журналы';
$string['xp:viewreport'] = 'Просматривать отчёт';
$string['xpplusrequired'] = 'Требуется XP+';
$string['xpgaindisabled'] = 'Начисление баллов отключено';
$string['xppremiumrequired'] = 'Требуется XP+ Premium';
$string['xppremiumrequiredforfeature'] = 'Для этой функции требуется XP+ Premium.';
$string['youleveledupexcl'] = 'Вы повысили уровень!';
$string['youreachedlevel'] = 'Вы достигли уровня:';
$string['youreachedlevela'] = 'Вы достигли уровня {$a}!';
$string['yourmessage'] = 'Ваше сообщение';
$string['yourownrules'] = 'Ваши собственные правила';
$string['addrulesformhelp'] = 'Последний столбец определяет количество очков опыта, начисляемых при выполнении критерия.';
$string['changelevelformhelp'] = 'Если вы измените количество уровней, пользовательские значки уровней временно отключатся, чтобы избежать уровней без значков. После изменения количества уровней перейдите на страницу «Внешний вид» и снова включите пользовательские значки после сохранения этой формы.';
$string['enablelogging'] = 'Включить журналирование';
$string['levelswillbereset'] = 'Внимание! Сохранение этой формы пересчитает уровни для всех пользователей!';
$string['viewtheladder'] = 'Посмотреть таблицу лидеров';
$string['xp'] = 'Очки опыта';
$string['xprequired'] = 'Требуется XP';
$string['promocontactintro'] = 'Свяжитесь с нами, чтобы узнать больше. Мы не кусаемся и отвечаем быстро!';
$string['promocontactus'] = 'Связаться с нами';
$string['promoemailusat'] = 'Напишите нам по адресу _levelup@branchup.tech_.';
$string['promoerrorsendingemail'] = 'Упс! Не удалось отправить сообщение… Пожалуйста, напишите напрямую на: {$a}. Спасибо!';
$string['promoifpreferemailusat'] = 'Псс! Если хотите, напишите напрямую на _{$a}_.';
$string['promoyourmessagewassent'] = 'Спасибо, ваше сообщение отправлено. Мы скоро с вами свяжемся.';
$string['rulesformhelp'] = '<p>Этот плагин использует систему событий Moodle для начисления баллов за действия студентов. Вы можете использовать форму ниже, чтобы добавить собственные правила или просмотреть правила по умолчанию.</p>
<p>Рекомендуется открыть <a href="{$a->log}">журнал</a> плагина, чтобы определить, какие события срабатывают при действиях студентов, и ознакомиться с <a href="{$a->list}">списком всех событий</a> и <a href="{$a->doc}">документацией для разработчиков</a>.</p>
<p>Плагин всегда игнорирует:
<ul>
    <li>Действия администраторов, гостей и незарегистрированных пользователей.</li>
    <li>Действия пользователей без права <em>block/xp:earnxp</em>.</li>
    <li>Повторяющиеся действия за короткий промежуток времени (чтобы предотвратить накрутку).</li>
    <li>Анонимные события, например в анонимной обратной связи.</li>
    <li>События с образовательным уровнем, отличным от <em>Participating</em>.</li>
</ul>
</p>';
$string['for1day'] = 'На 1 день';
$string['for1month'] = 'На 1 месяц';
$string['for1week'] = 'На 1 неделю';
$string['for3days'] = 'На 3 дня';
$string['xptogo'] = 'Осталось [[{$a}]]';
$string['basexp'] = 'Базовое значение алгоритма';
$string['coursevisuals'] = 'Внешний вид курса';
$string['levelbadgesformhelp'] = 'Назовите файлы в формате [level].[расширение файла], например 1.png, 2.jpg и т. д. Рекомендуемый размер — 100 × 100 пикселей.';
$string['levelcount'] = 'Количество уровней';
$string['leveldesc'] = 'Описание уровня';
$string['leveldesc_help'] = 'Краткое описание уровня, отображаемое на информационной странице рядом с самим уровнем. Вы можете использовать его для описания награды за достижение уровня, дать инструкции по продвижению к нему или оформить описание в игровом стиле (например: _Только самые храбрые души достигали этого уровня_).';
$string['levelname'] = 'Название уровня';
$string['levelname_help'] = 'Краткое название, которое отображается вместо стандартного _Уровень №1_, _Уровень №2_ и т. д. Если вы называете несколько уровней, рекомендуется назвать все.';
$string['privacy:metadata:xp:lvl'] = 'Уровень пользователя';
$string['usingalgo'] = 'Используется алгоритм';
$string['valuessaved'] = 'Значения успешно сохранены.';
$string['forthewholesite'] = 'Для всего сайта';
$string['addinstructions'] = 'Добавить дополнительную информацию';
$string['courserules'] = 'Правила курса';
$string['defaultrulesformhelp'] = 'Это правила по умолчанию, предоставляемые плагином. Они автоматически начисляют базовые баллы и игнорируют некоторые повторяющиеся события. Ваши собственные правила имеют приоритет над ними.';
$string['editinstructions'] = 'Редактировать информацию';
$string['grid'] = 'Сетка';
$string['list'] = 'Список';
$string['navrules'] = 'Правила';
$string['outofsyncexcessive'] = 'Сильная рассинхронизация';
$string['outofsyncexcessiveinfo'] = 'Версия XP+ значительно старее XP, что может вызвать непредсказуемые ошибки. В будущем XP+ автоматически отключится.';
$string['resetcourserulestodefaults'] = 'Сбросить правила курса к значениям по умолчанию';
$string['resetlevelstodefaults'] = 'Сбросить уровни к значениям по умолчанию';
$string['resetvisualstodefaults'] = 'Сбросить внешний вид к значениям по умолчанию';
$string['questreleasenotice'] = 'Поднимите геймификацию на новый уровень с **Level Up Quest** 🥳. Превратите свои курсы в **увлекательные приключения** с **стратегиями повторного вовлечения** и **праздничными наградами** 🤯! Ознакомьтесь с [сайтом Quest]({$a->questurl}) и нашим [анонсом запуска]({$a->questblogurl}). 👈';
$string['likenotice'] = 'Нравится Level Up XP? Поставьте его в список любимых плагинов на <a href="{$a->moodleorg}" target="_blank">Moodle.org</a>.';
$string['promocheatguard'] = 'Эта защита от накрутки не рассчитана на длинные интервалы времени. Обновитесь до <em>Level Up XP+</em>, чтобы разблокировать больше функций и длительные интервалы. <a href="{$a->url}">Подробнее</a>.';
$string['promorulesdidyouknow'] = 'Знаете ли вы, что с <em>Level Up XP+</em> можно начислять баллы за <em>завершение курсов</em>, <em>активностей</em> и даже <em>по оценкам</em>? <a href="{$a->url}">Узнайте больше</a>.';
