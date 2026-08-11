<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

final class CommerceShowroomBlockDefaultsCatalog {
    public const LANGUAGES = ['fr', 'en', 'ru'];

    public static function for_block(string $showroomkey, string $blocktype): array {
        if ($showroomkey !== 'third-group-verbs') {
            return [];
        }

        $language = self::language(current_language());
        $current = self::blocks($language)[$blocktype] ?? [];
        if ($current === []) {
            return [];
        }

        $schema = CommerceShowroomBlockEditorRegistry::schema($blocktype);
        $translations = [];
        foreach (self::LANGUAGES as $lang) {
            $translated = self::blocks($lang)[$blocktype] ?? [];
            foreach ($schema['fields'] as $field) {
                if (empty($field['translatable'])) {
                    continue;
                }
                $name = (string)$field['name'];
                if (array_key_exists($name, $translated)) {
                    $translations[$lang][$name] = $translated[$name];
                }
            }
        }

        $current['translations'] = $translations;
        return $current;
    }

    public static function for_showroom(string $showroomkey): array {
        if ($showroomkey !== 'third-group-verbs') {
            return [];
        }

        $result = [];
        foreach (array_keys(self::blocks('fr')) as $type) {
            $result[$type] = self::for_block($showroomkey, $type);
        }
        return $result;
    }

    /** @return array<string,string> */
    private static function premium_ascent_defaults(string $lang): array {
        $defaults = [
            'ru' => [
                'eyebrow' => 'СОВЕРШИТЕ СВОЁ ВОСХОЖДЕНИЕ',
                'title' => 'Восхождение к вершине французского языка',
                'text' => 'Не просто упражнения, а настоящее путешествие с целью. 30 этапов, новые вершины знаний и награды за каждый шаг',
                'cards' => "01|Карта восхождения|30 этапов ведут вас от самых нужных глаголов к более сложным. Проходите их по порядку и следите, как приближаетесь к вершине.|fa-solid fa-map-location-dot|0\n02|Разнообразная практика|Более 10 типов заданий помогают тренировать формы по-разному: выбирать, соединять, собирать, писать, слушать и вспоминать самостоятельно.|fa-solid fa-compass|0\n03|Слушаем и произносим|Каждый глагол озвучен носителем. Вы не только запоминаете формы, но и сразу привыкаете к их правильному звучанию.|fa-solid fa-headphones|0\n04|Проверяем и закрепляем|Финальные задания помогают проверить, насколько уверенно формы глаголов уже держатся в памяти, прежде чем двигаться дальше.|fa-solid fa-link|0\n05|Вершина Монблана|Финиш! Все 180 глаголов покорены.\nТо, что в начале маршрута казалось сложным и непредсказуемым, постепенно стало привычным. Теперь формы не приходится искать — они сами приходят в нужный момент.|fa-solid fa-flag|1",
                'summaryitems' => "30 этапов|продуманный маршрут|fa-solid fa-mountain-sun\n180 глаголов|все 3-й группы|fa-solid fa-book-open\n4000+ заданий|для уверенного результата|fa-solid fa-pen\nНаграды на всём пути|мотивация и удовольствие|fa-solid fa-trophy",
            ],
            'fr' => [
                'eyebrow' => 'RÉALISEZ VOTRE ASCENSION',
                'title' => 'L’ascension vers le sommet du français',
                'text' => 'Pas de simples exercices, mais un véritable voyage avec un objectif. 30 étapes, de nouveaux sommets de connaissances et des récompenses à chaque pas.',
                'cards' => "01|La carte de l’ascension|30 étapes vous conduisent des verbes les plus essentiels aux plus complexes. Suivez-les dans l’ordre et regardez-vous vous rapprocher du sommet.|fa-solid fa-map-location-dot|0\n02|Une pratique variée|Plus de 10 types d’exercices permettent de travailler les formes sous différents angles : choisir, associer, reconstruire, écrire, écouter et retrouver de mémoire.|fa-solid fa-compass|0\n03|Écouter et prononcer|Chaque verbe est prononcé par un locuteur natif. Vous mémorisez les formes tout en vous habituant immédiatement à leur prononciation correcte.|fa-solid fa-headphones|0\n04|Vérifier et consolider|Les exercices finaux permettent de vérifier à quel point les formes des verbes sont déjà bien ancrées en mémoire avant de poursuivre l’ascension.|fa-solid fa-link|0\n05|Le sommet du Mont-Blanc|Arrivée ! Les 180 verbes sont maîtrisés.\nCe qui paraissait complexe et imprévisible au début du parcours devient progressivement naturel. Vous n’avez plus besoin de chercher les formes : elles viennent au bon moment.|fa-solid fa-flag|1",
                'summaryitems' => "30 étapes|un parcours pensé avec soin|fa-solid fa-mountain-sun\n180 verbes|tous les verbes du 3e groupe|fa-solid fa-book-open\n4000+ exercices|pour un résultat solide|fa-solid fa-pen\nDes récompenses tout au long du parcours|motivation et plaisir|fa-solid fa-trophy",
            ],
            'en' => [
                'eyebrow' => 'MAKE YOUR ASCENT',
                'title' => 'The ascent to the summit of French',
                'text' => 'Not just exercises, but a real journey with a goal. 30 stages, new peaks of knowledge and rewards at every step.',
                'cards' => "01|Your ascent map|30 stages take you from the most essential verbs to more complex ones. Complete them in order and watch yourself get closer to the summit.|fa-solid fa-map-location-dot|0\n02|Varied practice|More than 10 exercise formats train the forms from different angles: choosing, matching, rebuilding, writing, listening and recalling them independently.|fa-solid fa-compass|0\n03|Listen and pronounce|Every verb is voiced by a native speaker. You memorise the forms while immediately getting used to their correct pronunciation.|fa-solid fa-headphones|0\n04|Check and reinforce|Final exercises help you check how confidently the verb forms are already anchored in memory before you move on.|fa-solid fa-link|0\n05|The Mont Blanc summit|Finish! All 180 verbs have been conquered.\nWhat seemed difficult and unpredictable at the start gradually becomes familiar. You no longer have to search for the forms — they come to you at the right moment.|fa-solid fa-flag|1",
                'summaryitems' => "30 stages|a carefully designed route|fa-solid fa-mountain-sun\n180 verbs|the entire third group|fa-solid fa-book-open\n4000+ exercises|for confident results|fa-solid fa-pen\nRewards all along the way|motivation and enjoyment|fa-solid fa-trophy",
            ],
        ];

        $lang = array_key_exists($lang, $defaults) ? $lang : 'fr';
        return $defaults[$lang];
    }

    /** @return array<string,string> */
    private static function learning_method_defaults(string $lang): array {
        $defaults = [
            'ru' => [
                'eyebrow' => 'ПОЧЕМУ РАБОТАЕТ ТРЕНАЖЁР?',
                'title' => 'Навык появляется тогда, когда вы перестаёте о нём думать',
                'subtitle' => 'Учиться водить машину сначала сложно.',
                'subtitleaccent' => 'С глаголами — точно так же.',
                'intro' => "В начале вы думаете о каждом действии.\nСо временем всё становится автоматическим.\nИменно так формируется любой навык.",
                'stage1title' => 'В начале',
                'stage1items' => "Куда смотреть\nКакую передачу включить\nКогда нажать сцепление\nКак тронуться\nЧто делать дальше",
                'stage1footer' => 'Много мыслей и ошибок — это нормально.',
                'stage2title' => 'Практика',
                'stage2items' => "Вы ошибаетесь|fa-solid fa-xmark\nПробуете снова|fa-solid fa-rotate-right\nПовторяете|fa-solid fa-repeat\nСтановитесь увереннее|fa-solid fa-check",
                'stage2footer' => 'Повторение превращает знания в навык.',
                'stage3title' => 'Автоматизм',
                'stage3items' => "Вы больше не думаете о каждом движении\nВсё получается само собой\nВы просто едете и наслаждаетесь дорогой",
                'stage3footer' => 'Вы водите уверенно и свободно.',
                'summarytitle' => 'С французскими глаголами происходит точно так же!',
                'summaryitems' => "Сначала вы думаете о каждой форме.|fa-solid fa-brain\nПотом многократно повторяете её.|fa-solid fa-arrows-rotate\nИ в какой-то момент начинаете говорить свободно и уверенно.|fa-solid fa-rocket",
            ],
            'fr' => [
                'eyebrow' => 'POURQUOI L’ENTRAÎNEUR FONCTIONNE-T-IL ?',
                'title' => 'Une compétence apparaît quand vous cessez d’y penser',
                'subtitle' => 'Au début, apprendre à conduire est difficile.',
                'subtitleaccent' => 'Avec les verbes, c’est exactement pareil.',
                'intro' => "Au début, vous pensez à chaque geste.\nAvec le temps, tout devient automatique.\nC’est ainsi que se construit n’importe quelle compétence.",
                'stage1title' => 'Au début',
                'stage1items' => "Où regarder ?\nQuelle vitesse passer ?\nQuand appuyer sur l’embrayage ?\nComment démarrer ?\nQue faire ensuite ?",
                'stage1footer' => 'Beaucoup de pensées et d’erreurs — c’est normal.',
                'stage2title' => 'La pratique',
                'stage2items' => "Vous vous trompez|fa-solid fa-xmark\nVous recommencez|fa-solid fa-rotate-right\nVous répétez|fa-solid fa-repeat\nVous gagnez en assurance|fa-solid fa-check",
                'stage2footer' => 'La répétition transforme les connaissances en compétence.',
                'stage3title' => 'L’automatisme',
                'stage3items' => "Vous ne pensez plus à chaque mouvement\nTout se fait naturellement\nVous conduisez simplement et profitez de la route",
                'stage3footer' => 'Vous conduisez avec assurance et liberté.',
                'summarytitle' => 'Avec les verbes français, c’est exactement la même chose !',
                'summaryitems' => "D’abord, vous réfléchissez à chaque forme.|fa-solid fa-brain\nPuis vous la répétez encore et encore.|fa-solid fa-arrows-rotate\nEt un jour, vous commencez à parler naturellement et avec assurance.|fa-solid fa-rocket",
            ],
            'en' => [
                'eyebrow' => 'WHY DOES THE TRAINER WORK?',
                'title' => 'A skill appears when you stop thinking about it',
                'subtitle' => 'Learning to drive is difficult at first.',
                'subtitleaccent' => 'Verbs work exactly the same way.',
                'intro' => "At first, you think about every action.\nWith time, everything becomes automatic.\nThat is how any skill is built.",
                'stage1title' => 'At first',
                'stage1items' => "Where should I look?\nWhich gear should I use?\nWhen should I press the clutch?\nHow do I start moving?\nWhat should I do next?",
                'stage1footer' => 'Lots of thoughts and mistakes — that is normal.',
                'stage2title' => 'Practice',
                'stage2items' => "You make mistakes|fa-solid fa-xmark\nYou try again|fa-solid fa-rotate-right\nYou repeat|fa-solid fa-repeat\nYou become more confident|fa-solid fa-check",
                'stage2footer' => 'Repetition turns knowledge into a skill.',
                'stage3title' => 'Automatic recall',
                'stage3items' => "You no longer think about every movement\nEverything happens naturally\nYou simply drive and enjoy the road",
                'stage3footer' => 'You drive confidently and freely.',
                'summarytitle' => 'The same thing happens with French verbs!',
                'summaryitems' => "First, you think about each form.|fa-solid fa-brain\nThen you repeat it again and again.|fa-solid fa-arrows-rotate\nAnd at some point, you begin to speak naturally and confidently.|fa-solid fa-rocket",
            ],
        ];
        $lang = array_key_exists($lang, $defaults) ? $lang : 'fr';
        return $defaults[$lang];
    }

    private static function problem_defaults(string $lang): array {
        $defaults = [
            'ru' => [
                'eyebrow' => 'ПОЧЕМУ ТАК ПРОИСХОДИТ?',
                'title' => 'Почему глаголы 3 группы так',
                'titleaccent' => 'сложно запомнить?',
                'text' => "Мы спросили более 28 000 человек, какая тема во французском языке кажется им самой сложной.\nОдним из самых частых ответов стали глаголы третьей группы.",
                'items' => "Нет общего правила|У каждого глагола свои формы и свои особенности.|fa-solid fa-shuffle\nРазные основы и окончания|Формы меняются в зависимости от подлежащего и времени.|fa-solid fa-code-branch\nМало повторений|Без постоянной практики формы не переходят в долговременную память.|fa-solid fa-repeat\nОдной теории недостаточно|Знать форму — ещё не значит суметь быстро использовать её в речи.|fa-solid fa-book-open-reader",
                'solutiontitle' => 'Хорошая новость:',
                'solutiontitleaccent' => 'все эти проблемы можно решить.',
                'solutiontext' => 'Именно поэтому мы создали',
                'solutiontextaccent' => 'интерактивный тренажер',
                'solutiontextmiddle' => ', который ',
                'solutiontextaccent2' => 'помогает довести глаголы до автоматизма.',
            ],
            'fr' => [
                'eyebrow' => 'POURQUOI EST-CE SI DIFFICILE ?',
                'title' => 'Pourquoi les verbes du 3e groupe sont-ils',
                'titleaccent' => 'si difficiles à mémoriser ?',
                'text' => "Nous avons demandé à plus de 28 000 personnes quel sujet de français leur semblait le plus difficile.\nLes verbes du troisième groupe ont été l’une des réponses les plus fréquentes.",
                'items' => "Pas de règle unique|Chaque verbe a ses propres formes et ses propres particularités.|fa-solid fa-shuffle\nDes radicaux et des terminaisons différents|Les formes changent selon le sujet et le temps.|fa-solid fa-code-branch\nPas assez de répétitions|Sans pratique régulière, les formes ne passent pas dans la mémoire à long terme.|fa-solid fa-repeat\nLa théorie seule ne suffit pas|Connaître une forme ne signifie pas encore pouvoir l’utiliser rapidement à l’oral.|fa-solid fa-book-open-reader",
                'solutiontitle' => 'Bonne nouvelle :',
                'solutiontitleaccent' => 'tous ces problèmes peuvent être résolus.',
                'solutiontext' => 'C’est précisément pour cela que nous avons créé un',
                'solutiontextaccent' => 'entraîneur interactif',
                'solutiontextmiddle' => ' qui ',
                'solutiontextaccent2' => 'aide à automatiser l’usage des verbes.',
            ],
            'en' => [
                'eyebrow' => 'WHY DOES THIS HAPPEN?',
                'title' => 'Why are third-group verbs',
                'titleaccent' => 'so difficult to remember?',
                'text' => "We asked more than 28,000 people which topic in French they found the most difficult.\nThird-group verbs were among the most frequent answers.",
                'items' => "No single rule|Each verb has its own forms and its own particularities.|fa-solid fa-shuffle\nDifferent stems and endings|Forms change depending on the subject and the tense.|fa-solid fa-code-branch\nNot enough repetition|Without regular practice, the forms do not move into long-term memory.|fa-solid fa-repeat\nTheory alone is not enough|Knowing a form does not yet mean being able to use it quickly in speech.|fa-solid fa-book-open-reader",
                'solutiontitle' => 'Good news:',
                'solutiontitleaccent' => 'all of these problems can be solved.',
                'solutiontext' => 'That is exactly why we created an',
                'solutiontextaccent' => 'interactive trainer',
                'solutiontextmiddle' => ' that ',
                'solutiontextaccent2' => 'helps make verb use automatic.',
            ],
        ];

        $lang = array_key_exists($lang, $defaults) ? $lang : 'fr';
        return $defaults[$lang];
    }

    private static function interactive_problem_defaults(string $lang): array {
        $all = [
            'ru' => [
                'eyebrow' => 'ЗНАКОМО?',
                'title' => 'Вы знаете, что хотите сказать, но нужная форма глагола третьей группы',
                'titleaccent' => 'исчезает из памяти',
                'text' => 'Вы перебираете варианты, а разговор продолжается без вас.',
                'choiceslabel' => 'В голове крутятся варианты:',
                'choices' => "Je prend\nJe prenne\nJe prends\nJe prende\nJe prens",
                'draghint' => 'Перетащите вариант к знаку вопроса или нажмите на него.',
                'taphint' => 'Нажмите на вариант, чтобы проверить его.',
                'correctanswer' => 'Je prends',
                'targetlabel' => 'Какую форму выбрать?',
                'consequences' => "В нужный момент нужная форма просто не приходит в голову.\nВместо того чтобы говорить, вы начинаете мысленно перебирать окончания.\nИз-за пауз и сомнений хочется заменить глагол на более простой.\nПостепенно появляется ощущение, что у вас плохая память или нет способностей к языкам.",
                'successfeedback' => 'Верно!', 'errorfeedback' => 'Попробуйте ещё раз',
                'solutiontitle' => 'Хорошая новость:',
                'solutiontitleaccent' => 'вам не нужно иметь идеальную память',
                'solutiontext' => 'Это отсутствие автоматизма. И именно',
                'solutiontextaccent' => 'это решает наш тренажёр',
            ],
            'fr' => [
                'eyebrow' => 'ÇA VOUS PARLE ?',
                'title' => 'Vous savez ce que vous voulez dire, mais la forme du verbe du 3e groupe dont vous avez besoin',
                'titleaccent' => 'disparaît de votre mémoire',
                'text' => 'Vous passez les possibilités en revue pendant que la conversation continue sans vous.',
                'choiceslabel' => 'Les possibilités tournent dans votre tête :',
                'choices' => "Je prend\nJe prenne\nJe prends\nJe prende\nJe prens",
                'draghint' => 'Faites glisser une forme vers le point d’interrogation, ou cliquez dessus.',
                'taphint' => 'Touchez une forme pour la tester.',
                'correctanswer' => 'Je prends',
                'targetlabel' => 'Quelle forme choisir ?',
                'consequences' => "Au moment où vous en avez besoin, la bonne forme ne vous vient tout simplement pas.\nAu lieu de parler, vous commencez à passer mentalement les terminaisons en revue.\nÀ force d’hésitations et de pauses, vous avez envie de remplacer le verbe par un plus simple.\nPeu à peu, vous finissez par penser que vous avez une mauvaise mémoire ou que vous n’êtes pas doué pour les langues.",
                'successfeedback' => 'Exact !', 'errorfeedback' => 'Essayez encore',
                'solutiontitle' => 'Bonne nouvelle :',
                'solutiontitleaccent' => 'vous n’avez pas besoin d’avoir une mémoire parfaite',
                'solutiontext' => 'C’est l’absence d’automatisme. Et c’est précisément',
                'solutiontextaccent' => 'ce que résout notre entraîneur',
            ],
            'en' => [
                'eyebrow' => 'SOUND FAMILIAR?',
                'title' => 'You know what you want to say, but the third-group verb form you need',
                'titleaccent' => 'slips your mind',
                'text' => 'You run through the possibilities while the conversation carries on without you.',
                'choiceslabel' => 'The options keep spinning in your head:',
                'choices' => "Je prend\nJe prenne\nJe prends\nJe prende\nJe prens",
                'draghint' => 'Drag a form to the question mark, or click it.',
                'taphint' => 'Tap a form to test it.',
                'correctanswer' => 'Je prends',
                'targetlabel' => 'Which form should you choose?',
                'consequences' => "When you need it, the right form simply does not come to mind.\nInstead of speaking, you start mentally running through the endings.\nThe pauses and doubts make you want to replace the verb with an easier one.\nGradually, you start feeling that you have a bad memory or simply are not good at languages.",
                'successfeedback' => 'Correct!', 'errorfeedback' => 'Try again',
                'solutiontitle' => 'Good news:',
                'solutiontitleaccent' => 'you do not need a perfect memory',
                'solutiontext' => 'This is a lack of automatic recall. And this is exactly',
                'solutiontextaccent' => 'what our trainer solves',
            ],
        ];
        return $all[$lang] ?? $all['fr'];
    }

    /** @return array<string,mixed> */
    private static function stage_method_defaults(string $lang): array {
        $all = [
            'ru' => [
                'eyebrow' => 'ВОСХОЖДЕНИЕ ИЗ 30 ЭТАПОВ',
                'title' => 'Как проходит каждый этап?',
                'text' => 'На каждом этапе вас ждут 6 новых глаголов и полный цикл тренировки — от первого знакомства до финального квиза и заслуженного привала.',
                'items' => "Знакомимся с глаголами|Слушаем произношение, узнаём значение и запоминаем 6 новых глаголов.|fa-solid fa-ear-listen\nОсваиваем настоящее время|Изучаем спряжение каждого глагола и тренируем формы в разных форматах: слушаем, выбираем, соединяем, собираем и пишем сами.|fa-solid fa-arrows-rotate\nИзучаем participe passé|Запоминаем формы participe passé всех 6 глаголов и закрепляем их в упражнениях.|fa-solid fa-layer-group\nОсваиваем основу futur simple|Изучаем основы будущего времени и тренируемся быстро вспоминать их без подсказок.|fa-solid fa-forward\nПроверяем себя|Проходим финальный квиз по всем 6 глаголам и проверяем, насколько уверенно усвоен этап.|fa-solid fa-list-check\nОтправляемся на привал|Получаем награду, немного отдыхаем с Гюставом, узнаём что-нибудь интересное о Франции и Монблане — и готовимся к следующей части восхождения.|fa-solid fa-mug-hot",
            ],
            'fr' => [
                'eyebrow' => 'UNE ASCENSION EN 30 ÉTAPES',
                'title' => 'Comment se déroule chaque étape ?',
                'text' => 'À chaque étape, 6 nouveaux verbes vous attendent ainsi qu’un cycle d’entraînement complet — de la première découverte au quiz final et à une halte bien méritée.',
                'items' => "Découvrir les verbes|Écoutez leur prononciation, découvrez leur sens et mémorisez 6 nouveaux verbes.|fa-solid fa-ear-listen\nMaîtriser le présent|Étudiez la conjugaison de chaque verbe et entraînez les formes de différentes façons : écouter, choisir, associer, reconstruire et écrire vous-même.|fa-solid fa-arrows-rotate\nÉtudier le participe passé|Mémorisez le participe passé des 6 verbes et consolidez-le grâce aux exercices.|fa-solid fa-layer-group\nMaîtriser le radical du futur simple|Étudiez les radicaux du futur et entraînez-vous à les retrouver rapidement sans aide.|fa-solid fa-forward\nSe tester|Passez le quiz final sur les 6 verbes et vérifiez à quel point l’étape est solidement acquise.|fa-solid fa-list-check\nFaire une halte|Recevez votre récompense, reposez-vous un peu avec Gustave, découvrez quelque chose d’intéressant sur la France et le Mont-Blanc — puis préparez-vous pour la suite de l’ascension.|fa-solid fa-mug-hot",
            ],
            'en' => [
                'eyebrow' => 'A 30-STAGE ASCENT',
                'title' => 'What happens at each stage?',
                'text' => 'At every stage, 6 new verbs await you along with a complete training cycle — from the first introduction to the final quiz and a well-earned rest stop.',
                'items' => "Meet the verbs|Listen to their pronunciation, learn their meaning and memorise 6 new verbs.|fa-solid fa-ear-listen\nMaster the present tense|Study the conjugation of each verb and practise the forms in different ways: listening, choosing, matching, rebuilding and writing them yourself.|fa-solid fa-arrows-rotate\nLearn the past participle|Memorise the past participle of all 6 verbs and reinforce it through exercises.|fa-solid fa-layer-group\nMaster the future-simple stem|Learn the future stems and practise recalling them quickly without hints.|fa-solid fa-forward\nTest yourself|Take the final quiz on all 6 verbs and check how confidently you have mastered the stage.|fa-solid fa-list-check\nTake a rest stop|Earn your reward, relax for a moment with Gustave, discover something interesting about France and Mont Blanc — then get ready for the next part of the ascent.|fa-solid fa-mug-hot",
            ],
        ];

        $lang = array_key_exists($lang, $all) ? $lang : 'fr';
        return $all[$lang] + [
            'backgroundurl' => '',
            'backgroundopacity' => 34,
            'backgroundblur' => 0,
        ];
    }

    /** @return array<string,mixed> */
    private static function hero_defaults(string $lang): array {
        $all = [
            'ru' => [
                'expeditionlabel' => 'Эскпедиция на Монблан',
                'stagelabel' => 'Тренажёр глаголов 3-й группы',
                'eyebrow' => '',
                'title' => 'Больше не нужно бояться',
                'titleaccent' => 'глаголов 3-й группы',
                'text' => "Перестаньте вспоминать спряжения.\nНачните просто говорить по-французски.",
                'primarylabel' => 'Начать восхождение',
                'primarytarget' => '#showroom-offers',
                'secondarylabel' => 'Посмотреть видео о тренажёре',
                'secondarytarget' => '#showroom-video',
            ],
            'fr' => [
                'expeditionlabel' => 'EXPÉDITION MONT-BLANC',
                'stagelabel' => 'ENTRAÎNEUR DE VERBES DU 3e GROUPE',
                'eyebrow' => '',
                'title' => 'N’ayez plus peur',
                'titleaccent' => 'des verbes du 3e groupe',
                'text' => "Arrêtez de chercher les conjugaisons dans votre mémoire.\nCommencez simplement à parler français.",
                'primarylabel' => 'Commencer l’ascension',
                'primarytarget' => '#showroom-offers',
                'secondarylabel' => 'Découvrir l’entraîneur en vidéo',
                'secondarytarget' => '#showroom-video',
            ],
            'en' => [
                'expeditionlabel' => 'MONT BLANC EXPEDITION',
                'stagelabel' => 'THIRD-GROUP VERB TRAINER',
                'eyebrow' => '',
                'title' => 'Stop being afraid',
                'titleaccent' => 'of third-group verbs',
                'text' => "Stop trying to recall conjugations.\nStart simply speaking French.",
                'primarylabel' => 'Start the ascent',
                'primarytarget' => '#showroom-offers',
                'secondarylabel' => 'Watch the trainer video',
                'secondarytarget' => '#showroom-video',
            ],
        ];

        $lang = array_key_exists($lang, $all) ? $lang : 'fr';
        return $all[$lang] + [
            'backgroundurl' => '',
            'desktopimageurl' => '',
            'mobileimageurl' => '',
            'herovariant' => 'standard',
            'mediaposition' => 'right',
            'showgustave' => false,
            'showstats' => true,
        ];
    }

    /** @return array<string,mixed> */
    private static function hero_stats_defaults(string $lang): array {
        $items = [
            'ru' => [
                '180|глаголов 3-й группы|fa-solid fa-book-open',
                '30|этапов восхождения|fa-solid fa-mountain-sun',
                '4000+|интерактивных заданий|fa-solid fa-pen',
                '10+|форматов практики|fa-regular fa-star',
            ],
            'fr' => [
                '180|verbes du 3e groupe|fa-solid fa-book-open',
                '30|étapes d’ascension|fa-solid fa-mountain-sun',
                '4000+|exercices interactifs|fa-solid fa-pen',
                '10+|formats de pratique|fa-regular fa-star',
            ],
            'en' => [
                '180|third-group verbs|fa-solid fa-book-open',
                '30|ascent stages|fa-solid fa-mountain-sun',
                '4000+|interactive exercises|fa-solid fa-pen',
                '10+|practice formats|fa-regular fa-star',
            ],
        ];

        $lang = array_key_exists($lang, $items) ? $lang : 'fr';
        return [
            'title' => '',
            'items' => implode("\n", $items[$lang]),
            'animate' => true,
        ];
    }

    private static function blocks(string $lang): array {
        return [
            'hero' => self::hero_defaults($lang),
            'stats' => self::hero_stats_defaults($lang),
            'video' => [
                'title' => self::s('commerce_showroom_video_title', $lang),
                'text' => self::s('commerce_showroom_video_description', $lang),
                'posterurl' => '',
                'videourl' => '',
                'ratio' => '16:9',
                'modal' => true,
            ],
            'problem' => self::problem_defaults($lang),
            'problem_interactive' => self::interactive_problem_defaults($lang),
            'learning_method' => self::learning_method_defaults($lang),
            'content_highlights' => [
                'eyebrow' => self::s('commerce_showroom_content_eyebrow', $lang),
                'title' => self::s('commerce_showroom_content_title', $lang),
                'text' => self::s('commerce_showroom_content_description', $lang),
                'items' => self::numbered(
                    'commerce_showroom_stat_',
                    6,
                    [
                        'fa-solid fa-person-hiking',
                        'fa-solid fa-list-check',
                        'fa-solid fa-headphones',
                        'fa-solid fa-rotate',
                        'fa-solid fa-award',
                        'fa-solid fa-mountain-sun',
                    ],
                    $lang
                ),
            ],
            'ascent' => self::premium_ascent_defaults($lang),
            'stage_method' => self::stage_method_defaults($lang),
            'exercise_explorer' => [
                'eyebrow' => self::s('commerce_showroom_exercises_eyebrow', $lang),
                'title' => self::s('commerce_showroom_exercises_title', $lang),
                'text' => self::s('commerce_showroom_exercises_description', $lang),
                'previewenabled' => true,
            ],
            'offers' => [
                'badge' => self::s('commerce_showroom_offers_badge', $lang, 'Tarifs'),
                'title' => self::s('commerce_showroom_offers_title_prefix', $lang, 'Comment commencera votre'),
                'titlehighlight' => self::s('commerce_showroom_offers_title_highlight', $lang, 'ascension'),
                'titlesuffix' => self::s('commerce_showroom_offers_title_suffix', $lang, ' ?'),
                'text' => self::s('commerce_showroom_offers_subtitle', $lang),
                'skus' => "DIGITAL.VERBES-3E-GROUPE\nBUNDLEA1VERBES\nSUB.PLAN.30",
                'featuredrole' => 'bundle',
                'featuredsku' => '',
                'showsavings' => true,
                'showbadges' => true,
                'order' => 'pdf,bundle,course',
                'mobilepresentation' => 'stack',
                'cardstopspacing' => 'airy',
                'bundlefeaturedlabel' => match ($lang) {
                    'ru' => 'самый популярный',
                    'en' => 'most popular',
                    default => 'le plus populaire',
                },
                'bundlerolelabel' => match ($lang) {
                    'ru' => 'полное восхождение',
                    'en' => 'complete ascent',
                    default => 'ascension complète',
                },
            ],
            'comparison' => [
                'title' => self::s('commerce_showroom_comparison_title', $lang),
                'text' => self::s('commerce_showroom_comparison_description', $lang),
                'order' => 'pdf,bundle,course',
            ],
            'memory_method' => [
                'eyebrow' => self::s('commerce_showroom_why_eyebrow', $lang),
                'title' => self::s('commerce_showroom_why_title', $lang),
                'text' => self::s('commerce_showroom_why_description', $lang),
                'items' => self::numbered(
                    'commerce_showroom_why_',
                    5,
                    [
                        'fa-solid fa-repeat',
                        'fa-solid fa-comments',
                        'fa-solid fa-headphones',
                        'fa-solid fa-gamepad',
                        'fa-solid fa-brain',
                    ],
                    $lang
                ),
            ],
            'trust' => [
                'items' => self::numbered(
                    'commerce_showroom_trust_',
                    4,
                    [
                        'fa-solid fa-shield-halved',
                        'fa-solid fa-bolt',
                        'fa-solid fa-infinity',
                        'fa-solid fa-headset',
                    ],
                    $lang
                ),
            ],
            'testimonials' => [
                'eyebrow' => self::s('commerce_showroom_testimonials_eyebrow', $lang),
                'title' => self::s('commerce_showroom_testimonials_title', $lang),
                'items' => '',
            ],
            'faq' => [
                'eyebrow' => self::s('commerce_showroom_faq_eyebrow', $lang),
                'title' => self::s('commerce_showroom_faq_heading', $lang, 'Questions fréquentes'),
                'text' => self::s('commerce_showroom_faq_description', $lang),
                'items' => self::faq($lang),
                'singleopen' => true,
            ],
            'support' => [
                'title' => self::s('commerce_showroom_support_title', $lang, 'Besoin d’aide ?'),
                'text' => self::s('commerce_showroom_support_description', $lang),
                'buttonlabel' => self::s('commerce_showroom_support_cta', $lang, 'Contacter le support'),
                'telegramurl' => '',
                'whatsappurl' => '',
            ],
            'bonus' => [
                'eyebrow' => 'Bonus',
                'title' => self::s('commerce_showroom_bonus_heading', $lang),
                'text' => self::s('commerce_showroom_bonus_text', $lang),
                'buttonlabel' => self::s('commerce_showroom_bonus_cta', $lang),
                'buttontarget' => '/boutique',
            ],
            'verbs_cards' => [
                'imageurl' => '',
                'eyebrow' => '',
                'title' => '',
                'titleaccent' => '',
                'text' => '',
                'items' => '',
                'buttonlabel' => '',
                'buttontarget' => '#',
            ],
            'final_cta' => [
                'eyebrow' => self::s('commerce_showroom_final_eyebrow', $lang),
                'title' => self::s('commerce_showroom_final_title', $lang),
                'text' => self::s('commerce_showroom_final_text', $lang),
                'buttonlabel' => self::s('commerce_showroom_final_cta', $lang, 'Choisir ma formule'),
                'buttontarget' => '#showroom-offers',
                'style' => 'primary',
            ],
        ];
    }

    private static function numbered(
        string $prefix,
        int $count,
        array $icons,
        string $lang
    ): string {
        $rows = [];
        for ($index = 1; $index <= $count; $index++) {
            $rows[] = implode('|', [
                str_replace('|', '—', self::s($prefix . $index . '_title', $lang)),
                str_replace('|', '—', self::s($prefix . $index . '_text', $lang)),
                (string)($icons[$index - 1] ?? 'fa-solid fa-circle'),
            ]);
        }
        return implode("\n", $rows);
    }

    private static function ascent(string $lang): string {
        $altitudes = ['1 035 m', '1 900 m', '2 650 m', '3 400 m', '4 808 m'];
        $stages = ['1–6', '7–12', '13–18', '19–24', '25–30'];
        $icons = [
            'fa-solid fa-house-chimney',
            'fa-solid fa-tree',
            'fa-solid fa-mountain',
            'fa-solid fa-snowflake',
            'fa-solid fa-flag-checkered',
        ];
        $rows = [];
        for ($index = 1; $index <= 5; $index++) {
            $rows[] = implode('|', [
                $altitudes[$index - 1],
                self::s('commerce_showroom_ascent_stages', $lang, 'Étapes {$a}'),
                self::s('commerce_showroom_ascent_' . $index . '_title', $lang),
                self::s('commerce_showroom_ascent_' . $index . '_text', $lang),
                $icons[$index - 1],
                $index === 5 ? '1' : '0',
            ]);
            $rows[array_key_last($rows)] = str_replace('{$a}', $stages[$index - 1], $rows[array_key_last($rows)]);
        }
        return implode("\n", $rows);
    }

    private static function faq(string $lang): string {
        $rows = [];
        for ($i = 1; $i <= 9; $i++) {
            $q = self::s('commerce_showroom_faq_' . $i . '_q', $lang);
            $a = self::s('commerce_showroom_faq_' . $i . '_a', $lang);
            if ($q !== '' && $a !== '') {
                $rows[] = str_replace('|', '—', $q) . '|'
                    . str_replace(["\r", "\n", "|"], [' ', ' ', '—'], $a);
            }
        }
        return implode("\n", $rows);
    }

    private static function language(string $lang): string {
        $lang = strtolower(substr($lang, 0, 2));
        return in_array($lang, self::LANGUAGES, true) ? $lang : 'fr';
    }

    private static function s(string $key, string $lang, string $fallback = ''): string {
        $manager = get_string_manager();
        if (!$manager->string_exists($key, 'local_subscriptions')) {
            return $fallback;
        }
        return (string)$manager->get_string(
            $key,
            'local_subscriptions',
            null,
            self::language($lang)
        );
    }
}
