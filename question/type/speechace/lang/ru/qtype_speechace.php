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
 * Strings for component 'qtype_speechace', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['speechace'] = 'SpeechAce';
$string['pluginname'] = 'SpeechAce';
$string['pluginname_help'] = 'В ответ на вопрос участник произносит текст. SpeechAce автоматически выставляет оценку в зависимости от качества произношения.';
$string['pluginname_link'] = 'question/type/speechace';
$string['pluginnameadding'] = 'Добавление вопроса с оценкой SpeechAce';
$string['pluginnameediting'] = 'Редактирование вопроса с оценкой SpeechAce';
$string['pluginnamesummary'] = 'Позволяет ответить голосом на английский текст. SpeechAce автоматически оценивает речь в зависимости от её близости к произношению и акценту американского английского стандарта.';
$string['scoringinfo'] = 'Текст ответа';
$string['scoringinfo_help'] = 'Укажите слово, предложение или абзац, который студент должен произнести. Вы можете включить необходимую пунктуацию. 
Кроме того, вы можете добавить образец аудио, записав собственный голос или автоматически сгенерировав аудио нажатием кнопки обновления. Образец аудио нужно будет перегенерировать при любом изменении текста.';

$string['dialect_default'] = 'Диалект по умолчанию';
$string['dialect'] = 'Диалект';
$string['dialect_description'] = 'Диалект по умолчанию для оценки SpeechAce и образца аудио в новых вопросах.';
$string['dialect_us_english'] = 'Американский английский';
$string['dialect_gb_english'] = 'Британский английский';
$string['dialect_fr_french'] = 'Французский';
$string['dialectinfomissing'] = 'Информация о диалекте отсутствует';
$string['dialectinfoinvalid'] = 'Недопустимое значение диалекта';
$string['dialect_default_temp'] = 'Временный диалект';
$string['yesoption'] = 'Да';
$string['nooption'] = 'Нет';
$string['numericscore'] = 'Числовая оценка';
$string['numericscore_default'] = 'Да';
$string['numericscore_description'] = 'По умолчанию отображается процентная оценка';
$string['numericscore_descritptivename'] = 'Показать процентную оценку';
$string['scoremessages'] = 'Сообщения об оценке';
$string['scoremessages_description'] = 'Настройка текста, отображаемого после оценки';
$string['scoremessages_defaultinfo'] = 'Нажмите "Сбросить сообщения" для восстановления стандартных значений';
$string['scoremessages_default_textOne'] = 'Отлично! Вы, случайно, не носитель языка?';
$string['scoremessages_default_textTwo'] = 'Неплохо.';
$string['scoremessages_default_textThree'] = 'Звучит не очень. Попробуйте ещё раз.';
$string['showanswer'] = 'Показать текст ответа';
$string['showanswer_always'] = 'Всегда';
$string['showanswer_result'] = 'Когда отображается оценка SpeechAce';
$string['showresult'] = 'Показать оценку SpeechAce';
$string['showresult_immediately'] = 'Сразу после записи';
$string['showresult_review'] = 'Во время просмотра викторины';
$string['showexpertaudio'] = 'Показать образец аудио';
$string['showexpertaudio_always'] = 'Всегда';
$string['showexpertaudio_result'] = 'Когда отображается оценка SpeechAce';
$string['showexpertaudio_missing'] = 'Образец аудио отсутствует';
$string['showexpertaudio_infoinvalid'] = 'Недопустимое значение образца';
$string['pleaserecordaudio'] = 'Пожалуйста, запишите своё аудио.';
$string['productkey'] = 'Ключ продукта';
$string['productkey_description'] = 'Ключ продукта SpeechAce для доступа к веб-сервису SpeechAce.';
$string['productkeyempty'] = 'Ключ продукта не может быть пустым.';
$string['productkeyinvalid'] = 'Недопустимый ключ продукта.';
$string['scoringinfomissing'] = 'Отсутствуют данные для компьютерной оценки';
$string['scoringinfotextmissing'] = 'Пожалуйста, укажите текст для оценки';
$string['scoringinfotextunknownerror'] = 'Не удалось проверить текст для оценки';
$string['scoringinfotextoutofvocab'] = 'Некоторые слова в тексте не допускаются: {$a->detail_message}';
$string['scoringinfotexttoolong'] = 'Текст слишком длинный. Убедитесь, что он содержит менее {$a->detail_message} символов.';
$string['scoringinfosourcetypeinvalid'] = 'Неверный выбор. Пожалуйста, выберите "Использовать своё аудио" или "Использовать аудио SpeechAce"';
$string['scoringinfomoodlekeygenericerror'] = 'Не удалось проверить ваше аудио. Попробуйте снова сохранить, записать другое аудио или использовать аудио SpeechAce.';
$string['scoringinfomoodlekeydetailmessage'] = 'Ошибка при проверке аудио: {$a->detail_message}. Попробуйте снова сохранить, записать другое аудио или использовать аудио SpeechAce.';
$string['error_productkey_missing'] = 'Ключ продукта SpeechAce не установлен. Если вы администратор, укажите его в Панель администратора -> Плагины -> Типы вопросов -> SpeechAce';
$string['error_productkey_invalid'] = 'Ключ продукта SpeechAce недействителен. Если вы администратор, укажите корректный ключ в Панель администратора -> Плагины -> Типы вопросов -> SpeechAce';
$string['error_productkey_expired'] = 'Срок действия ключа продукта SpeechAce истёк. Если вы администратор, свяжитесь с SpeechAce для продления';
$string['error_http_api_call'] = 'SpeechAce не удалось получить доступ к удалённому сервису. Если вы администратор, свяжитесь с SpeechAce для получения помощи';
$string['error_no_speech'] = 'Речь не обнаружена';

$string['scorecomment_good'] = "Хорошо";
$string['scorecomment_stressmore'] = "Сильнее ударение";
$string['scorecomment_stressless'] = "Меньше ударения";
$string['scorecomment_missing'] = "Пропущено";
$string['scorecomment_silent'] = "Беззвучно";

$string['moodlejs_NoAudioError'] = "Ваш браузер не поддерживает аудио полностью.";
$string['moodlejs_NoAudioError_custom'] = "Пожалуйста, используйте последнюю версию Google Chrome или Mozilla Firefox.";
$string['moodlejs_NoRecordingError'] = "Ваш браузер не поддерживает запись звука.";
$string['moodlejs_NoRecordingError_custom'] = "Пожалуйста, используйте последнюю версию Google Chrome или Mozilla Firefox.";
$string['moodlejs_AssertionError'] = "Ошибка утверждения:";
$string['moodlejs_NoAnswerError'] = "Не удалось услышать, что вы сказали.";
$string['moodlejs_NoAnswerError_custom'] = "Пожалуйста, проверьте свой микрофон или попробуйте говорить громче.";
$string['moodlejs_WrongAnswerError'] = "Ответ неверный.";
$string['moodlejs_WrongAnswerError_custom'] = "Пожалуйста, попробуйте ещё раз.";
$string['moodlejs_InvalidResponseData'] = "Ответ недействителен.";
$string['moodlejs_InvalidResponseData_custom'] = "Пожалуйста, перезагрузите страницу и попробуйте снова.";
$string['moodlejs_MicrophoneDeniedError'] = "Невозможно использовать ваш микрофон.";
$string['moodlejs_MicrophoneDeniedError_custom'] = "Нам нужно использовать ваш микрофон для оценки вашего произношения.";
$string['moodlejs_MicrophoneNotConnectedError'] = "Микрофон не найден.";
$string['moodlejs_MicrophoneNotConnectedError_custom'] = "Пожалуйста, проверьте ваш микрофон и попробуйте снова.";
$string['moodlejs_TTSError'] = "Невозможно получить речь из текста.";
$string['moodlejs_TTSError_custom'] = "Пожалуйста, попробуйте снова.";
$string['moodlejs_FlashPendingError'] = "Инициализация Flash.";
$string['moodlejs_FlashPendingError_custom'] = "Пожалуйста, подождите несколько секунд и попробуйте снова.";
$string['moodlejs_ScoringError'] = "Невозможно оценить речь.";
$string['moodlejs_ScoringError_custom'] = "Пожалуйста, попробуйте снова.";
$string['moodlejs_ScoringFormatError'] = "Неизвестные результаты оценки.";
$string['moodlejs_ScoringFormatError_custom'] = "Пожалуйста, обновите страницу и попробуйте снова. Если это не помогает, свяжитесь с SpeechAce.";
$string['moodlejs_UnknownPageError'] = "Неизвестная страница.";
$string['moodlejs_UnknownPageError_custom'] = "Пожалуйста, проверьте URL и исправьте ошибки. Если он выглядит правильно, свяжитесь с SpeechAce.";
$string['moodlejs_UnauthorizedPageError'] = "Несанкционированная страница.";
$string['moodlejs_UnauthorizedPageError_custom'] = "Пожалуйста, перезапустите страницу.";
$string['moodlejs_SignInError'] = "Ошибка входа.";
$string['moodlejs_MissingAnswerError'] = "Ответ отсутствует";
$string['moodlejs_MissingAnswerError_custom'] = "Пожалуйста, ответьте на вопрос. Если вы уже ответили, свяжитесь с SpeechAce.";
$string['moodlejs_UnknownQuestionTypeError'] = "Неизвестный тип вопроса.";
$string['moodlejs_UnknownQuestionTypeError_custom'] = "Произошла внутренняя ошибка. Пожалуйста, свяжитесь с SpeechAce.";
$string['moodlejs_QuizNotFoundError_1'] = "Страница (";
$string['moodlejs_QuizNotFoundError_2'] = ") не найдена.";
$string['moodlejs_QuizNotFoundError_custom'] = "Пожалуйста, проверьте URL и исправьте ошибки. Если он выглядит правильно, свяжитесь с SpeechAce.";
$string['moodlejs_QuestionNotFoundError_1'] = "Вопрос (";
$string['moodlejs_QuestionNotFoundError_2'] = ") не найден.";
$string['moodlejs_QuestionNotFoundError_custom'] = "Пожалуйста, проверьте URL и исправьте ошибки. Если он выглядит правильно, свяжитесь с SpeechAce.";
$string['moodlejs_UnknownWordsError'] = "Словарный запас SpeechAce исчерпан.";
$string['moodlejs_UnknownWordsError_custom'] = "Эти слова неизвестны SpeechAce: ";
$string['moodlejs_TextTooLongError'] = "Текст слишком длинный.";
$string['moodlejs_TextTooLongError_custom'] = "Количество символов не должно превышать ";
$string['moodlejs_TextSpeechValidationError'] = "Невозможно подтвердить текст.";
$string['moodlejs_TextSpeechValidationError_custom_1'] = "Пожалуйста, попробуйте снова. Если проблема сохраняется, свяжитесь с SpeechAce. (Код ошибки: ";
$string['moodlejs_TextSpeechValidationError_custom_2'] = "Пожалуйста, попробуйте снова. Если проблема сохраняется, свяжитесь с SpeechAce.";
$string['moodlejs_CourseNotFoundError_1'] = "Страница (";
$string['moodlejs_CourseNotFoundError_2'] = ") не найдена.";
$string['moodlejs_CourseNotFoundError_custom'] = "Пожалуйста, проверьте URL и исправьте ошибки. Если он выглядит правильно, свяжитесь с SpeechAce.";
$string['moodlejs_AjaxError'] = "Пожалуйста, попробуйте снова.";
$string['moodlejs_CourseAttemptNotFoundError_1'] = "Попытка прохождения курса (";
$string['moodlejs_CourseAttemptNotFoundError_2'] = ") не найдена.";
$string['moodlejs_CourseAttemptNotFoundError_custom'] = "Пожалуйста, попробуйте снова. Если проблема сохраняется, свяжитесь с SpeechAce.";
$string['moodlejs_NotImplementedError'] = "Не реализовано.";
$string['moodlejs_extractErrorMessages_short'] = "Упс! Что-то пошло не так.";
$string['moodlejs_extractErrorMessages_detailed'] = "Пожалуйста, попробуйте снова.";
$string['moodlejs_SoundLike'] = "Похоже на ";
$string['moodlejs_PlayStopExampleAudio'] = "Воспроизвести или остановить пример аудио";
$string['moodlejs_StopAudio'] = "Остановить воспроизведение аудио";
$string['moodlejs_StartPlayingYourAudio'] = "Начать воспроизведение вашего аудио";
$string['moodlejs_StopPlayingYourAudio'] = "Остановить воспроизведение вашего аудио";
$string['moodlejs_StartPlayingExampleAudio'] = "Начать воспроизведение примерного аудио";
$string['moodlejs_StopPlayingExampleAudio'] = "Остановить воспроизведение примерного аудио";
$string['moodlejs_HideScoreDetail'] = "Скрыть детали оценки: ";
$string['moodlejs_ShowScoreDetail'] = "Показать детали оценки: ";
$string['moodlejs_Select'] = "Выбрать";
$string['moodlejs_Syllable'] = "Слог";
$string['moodlejs_Phone'] = "Звук";
$string['moodlejs_Score'] = "Оценка";
$string['moodlejs_StartRecordingAudio'] = "Начать запись аудио";
$string['moodlejs_StopRecordingAudio'] = "Остановить запись аудио";
$string['moodlejs_HideInfo'] = "Скрыть информацию";
$string['moodlejs_ShowInfo'] = "Показать информацию";
$string['moodlejs_UnableStartRecording'] = "Невозможно начать запись: ";
$string['moodlejs_UnableStopRecording'] = "Невозможно остановить запись: ";
$string['moodlejs_UnableUnmarshalBlob'] = "Невозможно разобрать файл записи: ";
$string['moodlejs_UnableStartPlayback'] = "Невозможно начать воспроизведение: ";
$string['moodlejs_UnableStopPlayback'] = "Невозможно остановить воспроизведение: ";
$string['moodlejs_FlashError_1'] = "Ваша версия Flash устарела. Пожалуйста, перейдите по ссылке https://helpx.adobe.com/ru/flash-player.html для обновления";
$string['moodlejs_FlashError_2'] = "Flash не поддерживается на мобильных устройствах или планшетах.";
$string['moodlejs_FlashError_3'] = "Ваш браузер поддерживает Flash, но он отключен. Пожалуйста, включите его.";
$string['moodlejs_FlashError_4'] = "Flash недоступен. Пожалуйста, перейдите по ссылке https://helpx.adobe.com/ru/flash-player.html для установки";
$string['moodlejs_TapRedWords'] = "Нажмите на слова, выделенные красным, чтобы узнать, как улучшить.";
$string['moodlejs_AnswerSaved'] = "Ответ сохранён";
$string['moodlejs_Say'] = "Скажите: ";
$string['moodlejs_Review'] = "Пересмотреть";
$string['moodlejs_FetchingAudio'] = "Загрузка аудио: ";
$string['moodlejs_AudioSaved'] = "Аудио сохранено";
$string['moodlejs_UnableSaveAudio'] = "Не удалось сохранить аудио.";
$string['moodlejs_TryAgain'] = "Пожалуйста, попробуйте снова.";
$string['moodlejs_RecordYourAudio'] = "Запишите своё аудио";
$string['moodlejs_UseSpeechAceAudio'] = "Использовать аудио SpeechAce для текста ответа";
$string['moodlejs_PlaceHolder'] = "Введите текст для оценки здесь...";
$string['moodlejs_MessageScoreGreaterThan'] = "Сообщение, когда оценка больше или равна ";
$string['moodlejs_MessageButLessThan'] = "но меньше чем ";
$string['moodlejs_MessageScoreLessThan'] = "Сообщение, когда оценка меньше чем ";
$string['moodlejs_MessageReset'] = "Сбросить сообщения";
