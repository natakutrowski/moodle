<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Applies published CMS block configuration to the historical Showroom presenter data. */
final class CommerceShowroomBlockConfigurationPresenter {
    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function apply(array $data, CommerceShowroomRuntimeBlockSet $blocks): array {
        if (!$blocks->is_managed()) {
            return $data;
        }

        $data = $this->apply_layout($data, 'hero', $blocks->config('hero'));
        $data = $this->apply_hero($data, $blocks->config('hero'));
        $data = $this->apply_layout($data, 'stats', $blocks->config('stats'));
        $data = $this->apply_stats($data, $blocks->config('stats'));

        $data = $this->apply_layout($data, 'anchornav', $blocks->config('anchor_nav'), 'white');
        $data = $this->apply_anchor_nav($data, $blocks->config('anchor_nav'));
        $data = $this->apply_layout($data, 'adventureoverview', $blocks->config('adventure_overview'), 'white');
        $data = $this->apply_adventure_overview($data, $blocks->config('adventure_overview'));
        $data = $this->apply_layout($data, 'communityclan', $blocks->config('community_clan'), 'white');
        $data = $this->apply_community_clan($data, $blocks->config('community_clan'));
        $data = $this->apply_layout($data, 'tutorchat', $blocks->config('tutor_chat'), 'white');
        $data = $this->apply_tutor_chat($data, $blocks->config('tutor_chat'));
        $data = $this->apply_layout($data, 'curriculumtabs', $blocks->config('curriculum_tabs'), 'white');
        $data = $this->apply_curriculum_tabs($data, $blocks->config('curriculum_tabs'));
        $data = $this->apply_layout($data, 'journeymap', $blocks->config('journey_map'), 'white');
        $data = $this->apply_journey_map($data, $blocks->config('journey_map'));
        $data = $this->apply_layout($data, 'preparationflow', $blocks->config('preparation_flow'), 'white');
        $data = $this->apply_preparation_flow($data, $blocks->config('preparation_flow'));

        $data = $this->apply_layout($data, 'problem', $blocks->config('problem'), 'soft');
        $data = $this->apply_problem($data, $blocks->config('problem'));
        $data = $this->apply_layout($data, 'probleminteractive', $blocks->config('problem_interactive'), 'white');
        $data = $this->apply_problem_interactive($data, $blocks->config('problem_interactive'));
        $data = $this->apply_layout($data, 'learningmethod', $blocks->config('learning_method'));
        $data = $this->apply_learning_method($data, $blocks->config('learning_method'));

        $data = $this->apply_layout($data, 'video', $blocks->config('video'));
        $data = $this->apply_video($data, $blocks->config('video'));

        $data = $this->apply_layout($data, 'contenthighlights', $blocks->config('content_highlights'));
        $data = $this->apply_content_highlights($data, $blocks->config('content_highlights'));

        $data = $this->apply_layout($data, 'audienceprofiles', $blocks->config('audience_profiles'));
        $data = $this->apply_audience_profiles($data, $blocks->config('audience_profiles'));

        $data = $this->apply_layout($data, 'heartpuzzle', $blocks->config('heart_puzzle'), 'campuspink');
        $data = $this->apply_heart_puzzle($data, $blocks->config('heart_puzzle'));
        $data = $this->apply_layout($data, 'beforeafter', $blocks->config('before_after'), 'white');
        $data = $this->apply_before_after($data, $blocks->config('before_after'));

        $data = $this->apply_layout($data, 'ascent', $blocks->config('ascent'));
        $data = $this->apply_ascent($data, $blocks->config('ascent'));
        $data = $this->apply_layout($data, 'stagemethod', $blocks->config('stage_method'));
        $data = $this->apply_stage_method($data, $blocks->config('stage_method'));

        $data = $this->apply_layout(
            $data,
            'exerciseexplorer',
            $blocks->config('exercise_explorer'),
            'white'
        );
        $data = $this->apply_exercises($data, $blocks->config('exercise_explorer'));

        $data = $this->apply_layout($data, 'offers', $blocks->config('offers'));
        $data = $this->apply_offers($data, $blocks->config('offers'));
        $data = $this->apply_layout($data, 'comparison', $blocks->config('comparison'));
        $data = $this->apply_comparison($data, $blocks->config('comparison'));

        $data = $this->apply_layout($data, 'memorymethod', $blocks->config('memory_method'));
        $data = $this->apply_memory_method($data, $blocks->config('memory_method'));
        $data = $this->apply_layout($data, 'trust', $blocks->config('trust'));
        $data = $this->apply_trust($data, $blocks->config('trust'));

        $data = $this->apply_layout($data, 'testimonials', $blocks->config('testimonials'));
        $data = $this->apply_testimonials($data, $blocks->config('testimonials'));
        $data = $this->apply_layout($data, 'bonus', $blocks->config('bonus'));
        $data = $this->apply_bonus($data, $blocks->config('bonus'));

        $data = $this->apply_layout($data, 'faq', $blocks->config('faq'));
        $data = $this->apply_faq($data, $blocks->config('faq'));
        $data = $this->apply_layout($data, 'support', $blocks->config('support'));
        $data = $this->apply_support($data, $blocks->config('support'));

        // The A1 FAQ contact uses Verbs' Support visual contract without
        // replacing the pedagogical Support block already present on the page.
        $data = $this->apply_layout($data, 'faqcontact', $blocks->config('faq_contact'), 'campuspink');
        $data = $this->apply_faq_contact($data, $blocks->config('faq_contact'));

        $data = $this->apply_layout($data, 'verbscards', $blocks->config('verbs_cards'), 'white');
        $data = $this->apply_verbs_cards($data, $blocks->config('verbs_cards'));

        $data = $this->apply_layout($data, 'finalcta', $blocks->config('final_cta'));
        return $this->apply_final_cta($data, $blocks->config('final_cta'));
    }

    private function apply_hero(array $data, array $config): array {
        $a1lavender = $this->option($config, 'herovariant',
            ['standard', 'cover', 'split', 'minimal', 'a1_lavender'], 'standard') === 'a1_lavender'
            && ($data['showroomkey'] ?? '') === 'a1-course';
        $data['heroislavendera1'] = $a1lavender;
        $data['heroexpeditionlabel'] = $this->text(
            $config,
            'expeditionlabel',
            $data['heroexpeditionlabel'] ?? ''
        );
        $data['herostagelabel'] = $this->text(
            $config,
            'stagelabel',
            $data['herostagelabel'] ?? ''
        );
        $data['eyebrow'] = $this->text($config, 'eyebrow', $data['eyebrow'] ?? '');
        $data['title'] = $this->text($config, 'title', $data['title'] ?? '');
        $data['herotitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['hasherotitleaccent'] = $data['herotitleaccent'] !== '';
        $data['herotitlesuffix'] = $this->text($config, 'titlesuffix', '');
        $data['hasherotitlesuffix'] = $data['herotitlesuffix'] !== '';
        $data['herostartlabel'] = $this->text($config, 'startlabel', '');
        $data['hasherostartlabel'] = $data['herostartlabel'] !== '';

        $description = $this->text($config, 'text', $data['description'] ?? '');
        $data['description'] = $description;
        $lines = preg_split('/\R/u', $description, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $data['herodescriptionlines'] = array_map(
            static fn(string $line): array => ['text' => trim($line)],
            $lines
        );

        $data['herocta'] = $this->text($config, 'primarylabel', $data['herocta'] ?? '');
        $data['heroactionurl'] = $this->text($config, 'primarytarget', $data['heroactionurl'] ?? '');
        $data['herosecondarycta'] = $this->text($config, 'secondarylabel', $data['herosecondarycta'] ?? '');
        $data['herosecondarycta'] = match ($data['herosecondarycta']) {
            'Посмотреть, как это работает' => 'Посмотреть видео о тренажёре',
            'Voir comment ça fonctionne' => 'Découvrir l’entraîneur en vidéo',
            'See how it works' => 'Watch the trainer video',
            default => $data['herosecondarycta'],
        };
        $data['herosecondarytarget'] = $this->text($config, 'secondarytarget', '#showroom-video');
        $data['showherosecondarycta'] = (!array_key_exists('showvideobutton', $config) || !empty($config['showvideobutton']))
            && $data['herosecondarycta'] !== '';
        $data['showherogustave'] = !array_key_exists('showgustave', $config) || !empty($config['showgustave']);

        // E8.1 — default lavender artwork only for the opt-in A1 hero.
        // A Builder-selected backgroundurl always takes precedence.
        $background = $this->text($config, 'backgroundurl', '');
        if ($data['heroislavendera1'] && $background === '') {
            global $CFG;
            $background = rtrim($CFG->wwwroot, '/')
                . '/local/subscriptions/pix/showroom/a1_hero_lavender.webp';
        }
        $data['hasherobackground'] = $background !== '';
        $data['herobackgroundurl'] = $background;

        $desktopimage = $this->text($config, 'desktopimageurl', '');
        $data['hasherodesktopimage'] = $desktopimage !== '';
        $data['herodesktopimageurl'] = $desktopimage;

        $mobileimage = $this->text($config, 'mobileimageurl', '');
        if ($data['heroislavendera1'] && $mobileimage === '') {
            global $CFG;
            $mobileimage = rtrim($CFG->wwwroot, '/')
                . '/local/subscriptions/pix/showroom/a1_hero_mobile_radio.webp';
        }
        $data['hasheromobileimage'] = $mobileimage !== '';
        $data['heromobileimageurl'] = $mobileimage;

        $data['herovariant'] = $this->option(
            $config,
            'herovariant',
            ['standard', 'cover', 'split', 'minimal', 'a1_lavender'],
            'standard'
        );
        $data['heromediaposition'] = $this->option(
            $config,
            'mediaposition',
            ['right', 'left'],
            'right'
        );
        return $data;
    }

    private function apply_stats(array $data, array $config): array {
        $items = $this->pipe_rows($this->text($config, 'items', ''), 3);
        foreach (array_slice($items, 0, 5) as $index => $item) {
            $number = $index + 1;
            $data['herostat' . $number . 'value'] = $this->dynamic_text($data, $item[0]);
            $data['herostat' . $number . 'label'] = $this->dynamic_text($data, $item[1]);
            $data['herostat' . $number . 'icon'] = $item[2] ?? '';
        }
        $data['hasherostat5'] = trim((string)($data['herostat5value'] ?? '')) !== ''
            || trim((string)($data['herostat5label'] ?? '')) !== '';
        // Only the A1 lavender layout consumes the icons. Legacy values and
        // labels remain untouched for the Articles and Verbs Showrooms.
        $data['heroa1statitems'] = [];
        if (!empty($data['heroislavendera1'])) {
            foreach (array_slice($items, 0, 5) as $item) {
                $icon = trim((string)($item[2] ?? ''));
                if (preg_match('/^fa-(?:solid|regular) fa-[a-z0-9-]+$/', $icon) !== 1) {
                    $icon = 'fa-solid fa-circle-info';
                }
                $data['heroa1statitems'][] = [
                    'value' => $this->dynamic_text($data, $item[0]),
                    'label' => $this->dynamic_text($data, $item[1]),
                    'icon' => $icon,
                ];
            }
        }
        $data['statslayout'] = $this->option(
            $config,
            'layout',
            ['bar', 'grid_2x2_left'],
            'bar'
        );
        $data['statsanimate'] = !array_key_exists('animate', $config) || !empty($config['animate']);
        return $data;
    }

    private function apply_video(array $data, array $config): array {
        $presentation = $this->option(
            $config,
            'presentation',
            ['standard', 'article_investigation', 'a1_showcase'],
            'standard'
        );
        $data['videopresentation'] = $presentation;
        $data['videoisinvestigation'] = $presentation === 'article_investigation';
        $data['videoisa1showcase'] = $presentation === 'a1_showcase';
        $data['videovariantclass'] = 'commerce-showroom-video--' . str_replace('_', '-', $presentation);
        $data['videoeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['hasvideoeyebrow'] = $data['videoeyebrow'] !== '';
        $data['videobadgeicon'] = $this->text(
            $config,
            'badgeicon',
            $data['videoisa1showcase'] ? 'fa-solid fa-circle-play' : 'fa-solid fa-gears'
        );
        $data['videotitle'] = $this->dynamic_text($data, $this->text($config, 'title', $data['videotitle'] ?? ''));
        $data['videotitleaccent'] = $this->dynamic_text($data, $this->text($config, 'titleaccent', ''));
        $data['hasvideotitleaccent'] = $data['videotitleaccent'] !== '';
        $data['videodescription'] = $this->dynamic_text($data, $this->text($config, 'text', $data['videodescription'] ?? ''));
        $data['videotextaccent'] = $this->dynamic_text($data, $this->text($config, 'textaccent', ''));
        $data['hasvideotextaccent'] = $data['videotextaccent'] !== '';
        $data['videochecklisttitle'] = $this->dynamic_text($data, $this->text($config, 'checklisttitle', ''));
        $data['videochecklistitems'] = array_slice(
            $this->line_items($this->dynamic_text($data, $this->text($config, 'checklistitems', ''))),
            0,
            8
        );
        $data['hasvideochecklist'] = $data['videochecklistitems'] !== [];
        $data['videoposterlabel'] = $this->dynamic_text($data, $this->text($config, 'posterlabel', ''));
        $data['hasvideoposterlabel'] = $data['videoposterlabel'] !== '';
        $data['videonote'] = $this->dynamic_text($data, $this->text($config, 'note', ''));
        $data['hasvideonote'] = $data['videonote'] !== '';

        $steps = [];
        foreach (array_slice($this->pipe_rows($this->text($config, 'steps', ''), 3), 0, 4) as $index => $parts) {
            $steps[] = [
                'number' => $parts[0],
                'title' => $parts[1],
                'text' => $parts[2],
                'icon' => $parts[3] ?? 'fa-solid fa-circle',
                'hasarrow' => $index < 3,
            ];
        }
        $data['videosteps'] = $steps;
        $data['hasvideosteps'] = $steps !== [];

        $poster = $this->text($config, 'posterurl', '');
        $data['hasvideoposter'] = $poster !== '';
        $data['videoposterurl'] = $poster;
        $data['videourl'] = $this->text($config, 'videourl', '');
        $data['hasvideourl'] = $data['videourl'] !== '';
        $data['videoplaylabel'] = get_string('commerce_showroom_video_play', 'local_subscriptions');
        $data['videopauselabel'] = get_string('commerce_showroom_video_pause', 'local_subscriptions');
        $data['videoreplaylabel'] = get_string('commerce_showroom_video_replay', 'local_subscriptions');
        $data['videoplaceholder'] = trim((string)($data['videoplaceholder'] ?? '')) !== ''
            ? (string)$data['videoplaceholder']
            : get_string('commerce_showroom_video_placeholder', 'local_subscriptions');
        $data['videomodal'] = !array_key_exists('modal', $config) || !empty($config['modal']);
        return $data;
    }


    private function apply_problem_interactive(array $data, array $config): array {
        $presentation = $this->text($config, 'presentation', 'interactive');
        if (!in_array($presentation, ['interactive', 'static_ppt', 'four_columns_ppt'], true)) {
            $presentation = 'interactive';
        }
        $data['interactiveproblempresentation'] = $presentation;
        $data['interactiveproblemispresentation'] = $presentation === 'static_ppt';
        $data['interactiveproblemisfourcolumnsppt'] = $presentation === 'four_columns_ppt';
        $data['interactiveproblemisinteractive'] = $presentation === 'interactive';
        $data['interactiveproblemvariantclass'] = 'commerce-showroom-interactive-problem--' . $presentation;
        $data['interactiveproblemeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['interactiveproblemtitle'] = $this->text($config, 'title', '');
        $data['interactiveproblemtitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['interactiveproblemtext'] = $this->text($config, 'text', '');
        $data['interactiveproblemchoiceslabel'] = $this->text($config, 'choiceslabel', '');
        $data['interactiveproblemdraghint'] = $this->text($config, 'draghint', '');
        $data['interactiveproblemtaphint'] = $this->text($config, 'taphint', '');
        $data['interactiveproblemtargetlabel'] = $this->text($config, 'targetlabel', '');
        $data['interactiveproblemsuccessfeedback'] = $this->text($config, 'successfeedback', '');
        $data['interactiveproblemerrorfeedback'] = $this->text($config, 'errorfeedback', '');
        $data['interactiveproblemsolutiontitle'] = $this->text($config, 'solutiontitle', '');
        $data['interactiveproblemsolutiontitleaccent'] = $this->text($config, 'solutiontitleaccent', '');
        $data['interactiveproblemsolutiontext'] = $this->text($config, 'solutiontext', '');
        $data['interactiveproblemsolutiontextaccent'] = $this->text($config, 'solutiontextaccent', '');
        $correct = $this->text($config, 'correctanswer', '');
        $data['interactiveproblemcorrectanswer'] = $correct;
        $choices = preg_split('/\R/u', $this->text($config, 'choices', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $data['interactiveproblemchoices'] = array_map(static function(string $choice) use ($correct): array {
            $choice = trim($choice);
            return ['text' => $choice, 'iscorrect' => $choice === $correct ? '1' : '0'];
        }, $choices);
        $consequences = preg_split('/\R/u', $this->text($config, 'consequences', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $data['interactiveproblemconsequences'] = array_map(static fn(string $text): array => ['text' => trim($text)], $consequences);

        $data['interactiveproblemcol1title'] = $this->text($config, 'col1title', '');
        $data['interactiveproblemcol1items'] = $this->line_items($this->text($config, 'col1items', ''));
        $data['interactiveproblemcol1footer'] = $this->text($config, 'col1footer', '');
        $data['interactiveproblemcol2title'] = $this->text($config, 'col2title', '');
        $data['interactiveproblemcol2centertext'] = $this->text($config, 'col2centertext', '?');
        $data['interactiveproblemcol2subtext'] = $this->text($config, 'col2subtext', '');
        $data['interactiveproblemcol3title'] = $this->text($config, 'col3title', '');
        $data['interactiveproblemcol3items'] = $this->line_items($this->text($config, 'col3items', ''));
        $data['interactiveproblemcol4title'] = $this->text($config, 'col4title', '');
        $data['interactiveproblemcol4items'] = [];
        $defaulticons = [
            'fa-regular fa-comment-dots',
            'fa-solid fa-shuffle',
            'fa-regular fa-eye-slash',
        ];
        foreach ($this->pipe_rows($this->text($config, 'col4items', ''), 2) as $index => $row) {
            $icon = trim((string)($row[2] ?? ''));
            if ($icon === '') {
                $icon = $defaulticons[$index] ?? 'fa-solid fa-circle-info';
            }
            $data['interactiveproblemcol4items'][] = [
                'title' => trim((string)($row[0] ?? '')),
                'text' => trim((string)($row[1] ?? '')),
                'icon' => $icon,
            ];
        }
        return $data;
    }


    private function apply_anchor_nav(array $data, array $config): array {
        $items = [];
        foreach ($this->pipe_rows($this->text($config, 'items', ''), 2) as $row) {
            $target = trim((string)($row[1] ?? ''));
            if ($target === '') {
                continue;
            }
            if (!str_starts_with($target, '#')) {
                $target = '#' . ltrim($target, '#');
            }
            if (preg_match('/^#[A-Za-z][A-Za-z0-9_:\-.]*$/', $target) !== 1) {
                continue;
            }
            $items[] = ['label' => trim((string)$row[0]), 'target' => $target];
        }
        $data['anchornavitems'] = $items;
        $data['hasanchornavitems'] = $items !== [];
        $data['anchornavsticky'] = !array_key_exists('sticky', $config) || !empty($config['sticky']);
        $data['anchornavafterhero'] = $data['anchornavsticky']
            && ($data['showroomkey'] ?? '') === 'a1-course'
            && $this->option($config, 'presentation', ['standard', 'a1_after_hero'], 'standard') === 'a1_after_hero';
        $data['anchornavclass'] = $data['anchornavsticky'] ? 'is-sticky' : '';
        if ($data['anchornavafterhero']) {
            $data['anchornavclass'] .= ' commerce-showroom-anchor-nav--a1-after-hero';
        }
        $data['anchornavctalabel'] = $this->text($config, 'ctalabel', '');
        $data['anchornavctatarget'] = trim((string)($config['ctatarget'] ?? ''));
        $data['hasanchornavcta'] = $data['anchornavctalabel'] !== '' && $data['anchornavctatarget'] !== '';
        return $data;
    }

    private function apply_adventure_overview(array $data, array $config): array {
        $data['adventureeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['adventuretitle'] = $this->dynamic_text($data, $this->text($config, 'title', ''));
        $data['adventuretitleaccent'] = $this->dynamic_text($data, $this->text($config, 'titleaccent', ''));
        $data['adventuretext'] = $this->dynamic_text($data, $this->text($config, 'text', ''));
        $data['adventureitems'] = array_slice($this->dynamic_editorial_items($data, $this->text($config, 'items', '')), 0, 8);
        $data['adventureleftitems'] = array_slice($data['adventureitems'], 0, 4);
        $data['adventurerightitems'] = array_slice($data['adventureitems'], 4, 4);
        $data['adventurecenterimageurl'] = trim((string)($config['centerimageurl'] ?? ''));
        $data['hasadventurecenterimage'] = $data['adventurecenterimageurl'] !== '';
        $data['adventurecenterlabel'] = $this->text($config, 'centerlabel', '');
        $data['adventurenote'] = $this->text($config, 'note', '');
        $data['hasadventurenote'] = $data['adventurenote'] !== '';
        return $data;
    }

    private function apply_community_clan(array $data, array $config): array {
        $data['claneyebrow'] = $this->text($config, 'eyebrow', '');
        $data['clanbadgeicon'] = trim((string)($config['badgeicon'] ?? 'fa-solid fa-people-group'));
        $data['clantitle'] = $this->dynamic_text($data, $this->text($config, 'title', ''));
        $data['clantitleaccent'] = $this->dynamic_text($data, $this->text($config, 'titleaccent', ''));
        $data['clantext'] = $this->dynamic_text($data, $this->text($config, 'text', ''));
        $data['clanitems'] = array_slice($this->dynamic_editorial_items($data, $this->text($config, 'items', '')), 0, 4);
        $data['clanimageurl'] = trim((string)($config['imageurl'] ?? ''));
        $data['hasclanimage'] = $data['clanimageurl'] !== '';
        $data['clanimagenote'] = $this->text($config, 'imagenote', '');
        $data['hasclanimagenote'] = $data['clanimagenote'] !== '';
        $data['clanbuttonlabel'] = $this->text($config, 'buttonlabel', '');
        $data['clanbuttontarget'] = trim((string)($config['buttontarget'] ?? ''));
        $data['hasclanbutton'] = $data['clanbuttonlabel'] !== '' && $data['clanbuttontarget'] !== '';
        return $data;
    }

    private function apply_tutor_chat(array $data, array $config): array {
        $data['tutoreyebrow'] = $this->text($config, 'eyebrow', '');
        $data['tutortitle'] = $this->dynamic_text($data, $this->text($config, 'title', ''));
        $data['tutortitleaccent'] = $this->dynamic_text($data, $this->text($config, 'titleaccent', ''));
        $data['tutortext'] = $this->dynamic_text($data, $this->text($config, 'text', ''));
        $data['tutorfeatures'] = array_slice($this->dynamic_editorial_items($data, $this->text($config, 'features', '')), 0, 4);
        $data['tutorimageurl'] = trim((string)($config['imageurl'] ?? ''));
        $data['hastutorimage'] = $data['tutorimageurl'] !== '';
        $data['tutorconversationtitle'] = $this->text($config, 'conversationtitle', '');
        $data['hastutorconversationtitle'] = $data['tutorconversationtitle'] !== '';
        $data['tutorsidenote'] = $this->text($config, 'sidenote', '');
        $data['hastutorsidenote'] = $data['tutorsidenote'] !== '';
        $messages = [];
        foreach (preg_split('/\R/u', $this->text($config, 'messages', '')) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = array_map('trim', explode('|', $line));
            $messagetext = trim((string)($row[1] ?? ''));
            if ($messagetext === '') {
                continue;
            }
            $side = strtolower(trim((string)($row[2] ?? 'tutor')));
            if (!in_array($side, ['tutor', 'student'], true)) {
                $side = 'tutor';
            }
            $author = trim((string)($row[0] ?? ''));
            $time = trim((string)($row[3] ?? ''));
            $messages[] = [
                'author' => $author,
                'text' => $messagetext,
                'side' => $side,
                'time' => $time,
                'hasauthor' => $author !== '',
                'hastime' => $time !== '',
                'istutor' => $side === 'tutor',
                'isstudent' => $side === 'student',
            ];
        }
        $data['tutormessages'] = array_slice($messages, 0, 8);
        $data['hastutormessages'] = $data['tutormessages'] !== [];
        $data['tutorbuttonlabel'] = $this->text($config, 'buttonlabel', '');
        $data['tutorbuttontarget'] = trim((string)($config['buttontarget'] ?? ''));
        $data['hastutorbutton'] = $data['tutorbuttonlabel'] !== '' && $data['tutorbuttontarget'] !== '';
        return $data;
    }

    private function apply_curriculum_tabs(array $data, array $config): array {
        $data['curriculumeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['curriculumbadgeicon'] = $this->text(
            $config,
            'badgeicon',
            'fa-solid fa-list-check'
        );
        $data['curriculumtitle'] = $this->text($config, 'title', '');
        $data['curriculumtitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['curriculumtext'] = $this->text($config, 'text', '');
        $data['curriculumtab1label'] = $this->text($config, 'tab1label', 'Programme');
        $data['curriculumtab2label'] = $this->text($config, 'tab2label', 'Grammaire');
        $data['curriculumdefaulttab'] = $this->option($config, 'defaulttab', ['topics', 'grammar'], 'topics');
        $data['curriculumtopicsgroups'] = $this->curriculum_groups($this->text($config, 'topics', ''));
        $data['curriculumgrammargroups'] = $this->curriculum_groups($this->text($config, 'grammar', ''));
        $data['hascurriculumtopics'] = $data['curriculumtopicsgroups'] !== [];
        $data['hascurriculumgrammar'] = $data['curriculumgrammargroups'] !== [];
        if ($data['curriculumdefaulttab'] === 'grammar' && !$data['hascurriculumgrammar']) {
            $data['curriculumdefaulttab'] = 'topics';
        }
        $data['curriculumtopicsactive'] = $data['curriculumdefaulttab'] === 'topics';
        $data['curriculumgrammaractive'] = $data['curriculumdefaulttab'] === 'grammar';
        return $data;
    }

    private function apply_journey_map(array $data, array $config): array {
        $data['journeymapinteractive'] = false;
        if (($config['presentation'] ?? '') === 'interactive_a1') {
            $data = $this->apply_interactive_journey_map($data, $config);
        }
        $data['journeymapeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['journeymaptitle'] = $this->dynamic_text($data, $this->text($config, 'title', ''));
        $data['journeymaptitleaccent'] = $this->dynamic_text($data, $this->text($config, 'titleaccent', ''));
        $data['journeymaptext'] = $this->dynamic_text($data, $this->text($config, 'text', ''));
        $data['journeymapimageurl'] = trim((string)($config['imageurl'] ?? ''));
        $data['hasjourneymapimage'] = $data['journeymapimageurl'] !== '';
        $stops = [];
        foreach ($this->pipe_rows($this->text($config, 'stops', ''), 2) as $row) {
            $place = trim((string)($row[0] ?? ''));
            if ($place === '') {
                continue;
            }
            $region = trim((string)($row[1] ?? ''));
            $stops[] = [
                'place' => $place,
                'region' => $region,
                'hasregion' => $region !== '',
                'hasarrow' => true,
            ];
        }
        $stops = array_slice($stops, 0, 12);
        if ($stops !== []) {
            $stops[array_key_last($stops)]['hasarrow'] = false;
        }
        $data['journeymapstops'] = $stops;
        $data['hasjourneymapstops'] = $stops !== [];
        $data['journeymapitems'] = array_slice($this->dynamic_editorial_items($data, $this->text($config, 'items', '')), 0, 6);
        $data['journeymapnote'] = $this->text($config, 'note', '');
        $data['hasjourneymapnote'] = $data['journeymapnote'] !== '';
        return $data;
    }

    /**
     * Interactive A1 journey map.
     * Geographic lat/lon remain the source of truth, but optional editor-defined
     * percentage positions and overlay labels may be provided to fit a custom map.
     */
    private function apply_interactive_journey_map(array $data, array $config): array {
        global $CFG;

        $data['journeymapinteractive'] = true;
        $data['journeymapcounttitle'] = $this->text($config, 'counttitle', '30 étapes');
        $data['journeymapcountsubtitle'] = $this->text($config, 'countsubtitle', 'CampusFR · A1');
        $data['journeymaphint'] = $this->text($config, 'hint', 'Choisissez un point');
        $data['journeymapswipehint'] = $this->text($config, 'mobileswipelabel', 'Faites glisser la carte');
        $data['journeymapadventurelabel'] = $this->text($config, 'adventurelabel', 'AVENTURE');
        $data['journeymapcloselabel'] = $this->text($config, 'closelabel', 'Fermer');
        $data['journeymapbackground'] = rtrim($CFG->wwwroot, '/')
            . '/local/subscriptions/pix/journey_map/paca_corse_georeferenced.png';
        $photobase = rtrim($CFG->wwwroot, '/') . '/local/subscriptions/pix/journey_map/photos/';

        $labeldefaults = [
            'provence' => ['key' => 'provence', 'text' => 'PROVENCE', 'x' => '19.0', 'y' => '20.5', 'rotation' => '0'],
            'cotedazur' => ['key' => 'cotedazur', 'text' => 'CÔTE D’AZUR', 'x' => '66.0', 'y' => '31.5', 'rotation' => '-16'],
            'corse' => ['key' => 'corse', 'text' => 'CORSE', 'x' => '86.0', 'y' => '58.5', 'rotation' => '0'],
        ];
        $labels = [];
        foreach ($this->pipe_rows($this->text($config, 'maplabels', ''), 5) as $row) {
            [$key, $textlabel, $x, $y, $rotation] = array_slice($row, 0, 5);
            $key = trim(mb_strtolower($key));
            if ($key === '' || !is_numeric($x) || !is_numeric($y) || !is_numeric($rotation)) {
                continue;
            }
            $labels[$key] = [
                'key' => preg_replace('/[^a-z0-9_-]+/', '', $key),
                'text' => trim($textlabel),
                'x' => number_format((float)$x, 4, '.', ''),
                'y' => number_format((float)$y, 4, '.', ''),
                'rotation' => number_format((float)$rotation, 2, '.', ''),
            ];
        }
        foreach ($labeldefaults as $key => $label) {
            if (!isset($labels[$key])) {
                $labels[$key] = $label;
            }
        }
        $data['journeymaplabels'] = array_values($labels);

        $positionoverrides = [];
        foreach ($this->pipe_rows($this->text($config, 'pointpositions', ''), 3) as $row) {
            [$id, $rawx, $rawy] = array_slice($row, 0, 3);
            $id = trim($id);
            if (preg_match('/^(0[1-9]|[12][0-9]|30)$/', $id) !== 1 || !is_numeric($rawx) || !is_numeric($rawy)) {
                continue;
            }
            $x = (float)$rawx;
            $y = (float)$rawy;
            if ($x < 0 || $x > 100 || $y < 0 || $y > 100) {
                continue;
            }
            $positionoverrides[$id] = ['x' => $x, 'y' => $y];
        }

        // Default geographic projection for the legacy map image.
        [$west, $east, $south, $north] = [3.55, 9.95, 40.95, 45.40];
        $captions = [];
        foreach ($this->pipe_rows($this->text($config, 'captions', ''), 2) as $row) {
            $id = trim($row[0]);
            if (preg_match('/^(0[1-9]|[12][0-9]|30)$/', $id) === 1) {
                $captions[$id] = trim(implode('|', array_slice($row, 1)));
            }
        }
        $points = [];
        $seen = [];
        foreach ($this->pipe_rows($this->text($config, 'geopoints', ''), 5) as $row) {
            [$id, $place, $rawlat, $rawlon, $filename] = array_slice($row, 0, 5);
            if (preg_match('/^(0[1-9]|[12][0-9]|30)$/', $id) !== 1
                || isset($seen[$id])
                || !is_numeric($rawlat) || !is_numeric($rawlon)
                || preg_match('/^CampusFR_A1_(0[1-9]|[12][0-9]|30)\.jpg$/', $filename) !== 1
                || $filename !== 'CampusFR_A1_' . $id . '.jpg') {
                continue;
            }
            $lat = (float)$rawlat;
            $lon = (float)$rawlon;
            if ($lat < $south || $lat > $north || $lon < $west || $lon > $east) {
                continue;
            }
            $seen[$id] = true;
            $points[] = [
                'number' => $id,
                'place' => $place,
                'caption' => $captions[$id] ?? '',
                'imageurl' => $photobase . $filename,
                'x' => number_format($positionoverrides[$id]['x'] ?? (100 * ($lon - $west) / ($east - $west)), 4, '.', ''),
                'y' => number_format($positionoverrides[$id]['y'] ?? (100 * ($north - $lat) / ($north - $south)), 4, '.', ''),
                'latitude' => number_format($lat, 5, '.', ''),
                'longitude' => number_format($lon, 5, '.', ''),
            ];
        }
        usort($points, static fn(array $a, array $b): int => strcmp($a['number'], $b['number']));
        $data['journeymapgeopoints'] = $points;
        $data['hasjourneymapgeopoints'] = $points !== [];
        return $data;
    }

    private function apply_preparation_flow(array $data, array $config): array {
        // The Lovable A0 presentation is opt-in. Other Showrooms keep the existing cards.
        $presentation = $this->option($config, 'presentation', ['standard', 'lovable_a0'], 'standard');
        $data['preparationislovablea0'] = $presentation === 'lovable_a0';
        $data['preparationflowvariantclass'] = $data['preparationislovablea0']
            ? 'commerce-showroom-preparation-flow--lovable-a0' : '';
        $data['preparationeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['preparationtitle'] = $this->dynamic_text($data, $this->text($config, 'title', ''));
        $data['preparationtitleaccent'] = $this->dynamic_text($data, $this->text($config, 'titleaccent', ''));
        $data['preparationtitlesuffix'] = $this->dynamic_text($data, $this->text($config, 'titlesuffix', ''));
        $data['haspreparationtitlesuffix'] = $data['preparationtitlesuffix'] !== '';
        $data['preparationtext'] = $this->dynamic_text($data, $this->text($config, 'text', ''));
        $data['preparationannotation'] = $this->text($config, 'annotation', '');
        $data['haspreparationannotation'] = $data['preparationannotation'] !== '';

        if ($data['preparationislovablea0']) {
            // A0 is deliberately four steps with no description, matching Lovable.
            // The pipe parser requires two nonempty fields (title|icon).
            $items = [];
            foreach (array_slice($this->pipe_rows($this->text($config, 'steps', ''), 2), 0, 4) as $row) {
                $icon = trim($row[1]);
                if (preg_match('/^fa-(?:solid|regular) fa-[a-z0-9-]+$/', $icon) !== 1) {
                    $icon = 'fa-solid fa-circle';
                }
                $items[] = [
                    'title' => $this->dynamic_text($data, trim($row[0])),
                    'text' => '',
                    'icon' => $icon,
                ];
            }
        } else {
            $items = array_slice($this->dynamic_editorial_items($data, $this->text($config, 'items', '')), 0, 6);
        }
        foreach ($items as $index => &$item) {
            $item['hasarrow'] = $index < count($items) - 1;
        }
        unset($item);
        $data['preparationitems'] = $items;
        $data['preparationimageurl'] = trim((string)($config['imageurl'] ?? ''));
        $data['haspreparationimage'] = $data['preparationimageurl'] !== '';
        // The original note remains supported but is omitted when not configured.
        $data['preparationnote'] = $this->text($config, 'note', '');
        $data['haspreparationnote'] = $data['preparationnote'] !== '';
        return $data;
    }

    /** @return array<int,array<string,mixed>> */
    private function curriculum_groups(string $raw): array {
        $groups = [];
        $indexes = [];
        foreach ($this->pipe_rows($raw, 4) as $row) {
            $group = trim((string)($row[0] ?? ''));
            if ($group === '') {
                continue;
            }
            if (!array_key_exists($group, $indexes)) {
                $indexes[$group] = count($groups);
                $groups[] = [
                    'title' => $group,
                    'icon' => trim((string)($row[1] ?? 'fa-solid fa-book-open')),
                    'items' => [],
                ];
            }
            $number = trim((string)($row[2] ?? ''));
            $groups[$indexes[$group]]['items'][] = [
                'number' => $number,
                'hasnumber' => $number !== '',
                'title' => trim((string)($row[3] ?? '')),
            ];
        }
        return $groups;
    }

    private function apply_problem(array $data, array $config): array {
        $data['problemeyebrow'] = $this->text($config, 'eyebrow', $data['problemeyebrow'] ?? '');
        $data['problemtitle'] = $this->text($config, 'title', $data['problemtitle'] ?? '');
        $data['problemtitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['problemdescription'] = $this->text($config, 'text', $data['problemdescription'] ?? '');
        $descriptionlines = preg_split('/\R/u', $data['problemdescription'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $data['problemdescriptionlines'] = array_map(
            static fn(string $line): array => ['text' => trim($line)],
            $descriptionlines
        );
        $data['problemsolutiontitle'] = $this->text($config, 'solutiontitle', '');
        $data['problemsolutiontitleaccent'] = $this->text($config, 'solutiontitleaccent', '');
        $data['problemsolutiontext'] = $this->text($config, 'solutiontext', '');
        $data['problemsolutiontextaccent'] = $this->text($config, 'solutiontextaccent', '');
        $data['problemsolutiontextmiddle'] = $this->text($config, 'solutiontextmiddle', '');
        $data['problemsolutiontextaccent2'] = $this->text($config, 'solutiontextaccent2', '');
        $data['hasproblemsolution'] = $data['problemsolutiontitle'] !== ''
            || $data['problemsolutiontitleaccent'] !== ''
            || $data['problemsolutiontext'] !== ''
            || $data['problemsolutiontextaccent'] !== ''
            || $data['problemsolutiontextmiddle'] !== ''
            || $data['problemsolutiontextaccent2'] !== '';

        $items = $this->editorial_items($this->text($config, 'items', ''));
        if ($items !== []) {
            $data['problems'] = $items;
        }
        return $data;
    }

    private function apply_learning_method(array $data, array $config): array {
        $data['methodeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['methodtitle'] = $this->text($config, 'title', $data['methodtitle'] ?? '');
        $data['methodsubtitle'] = $this->text($config, 'subtitle', '');
        $data['methodsubtitleaccent'] = $this->text($config, 'subtitleaccent', '');
        $data['methodintrolines'] = $this->line_items(
            $this->text($config, 'intro', $this->text($config, 'text', ''))
        );

        $data['methodstage1title'] = $this->text($config, 'stage1title', '');
        $data['methodstage1items'] = $this->line_items($this->text($config, 'stage1items', ''));
        $data['methodstage1footer'] = $this->text($config, 'stage1footer', '');

        $data['methodstage2title'] = $this->text($config, 'stage2title', '');
        $data['methodstage2items'] = [];
        foreach ($this->pipe_rows($this->text($config, 'stage2items', ''), 2) as $row) {
            $data['methodstage2items'][] = ['text' => $row[0], 'icon' => $row[1]];
        }
        $data['methodstage2footer'] = $this->text($config, 'stage2footer', '');

        $data['methodstage3title'] = $this->text($config, 'stage3title', '');
        $data['methodstage3items'] = $this->line_items($this->text($config, 'stage3items', ''));
        $data['methodstage3footer'] = $this->text($config, 'stage3footer', '');

        $data['methodsummarytitle'] = $this->text($config, 'summarytitle', '');
        $summaryrows = $this->pipe_rows($this->text($config, 'summaryitems', ''), 2);
        $data['methodsummaryitems'] = [];
        foreach ($summaryrows as $index => $row) {
            $data['methodsummaryitems'][] = [
                'text' => $row[0],
                'icon' => $row[1],
                'hasarrow' => $index < count($summaryrows) - 1,
            ];
        }
        return $data;
    }

    private function apply_content_highlights(array $data, array $config): array {
        $presentation = $this->option(
            $config,
            'presentation',
            ['standard', 'article_meaning', 'audience_profiles'],
            'standard'
        );
        $data['contenthighlightpresentation'] = $presentation;
        $data['contenthighlightisarticlemeaning'] = $presentation === 'article_meaning';
        $data['contenthighlightisaudienceprofiles'] = $presentation === 'audience_profiles';
        $data['contenthighlightisstandard'] = $presentation === 'standard';
        $data['contenthighlightvariantclass'] = 'commerce-showroom-content-highlights--' . $presentation;
        $data['contenteyebrow'] = $this->text($config, 'eyebrow', $data['contenteyebrow'] ?? '');
        $data['contentbadgeicon'] = $this->text($config, 'badgeicon', '');
        $data['hascontentbadgeicon'] = $data['contentbadgeicon'] !== '';
        $data['contentsidenote'] = $this->text($config, 'sidenote', '');
        $data['hascontentsidenote'] = $data['contentsidenote'] !== '';
        $data['contenttitle'] = $this->text($config, 'title', $data['contenttitle'] ?? '');
        $data['contenttitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['contentdescription'] = $this->text($config, 'text', $data['contentdescription'] ?? '');
        $data['contenthighlightcolumns'] = $this->option(
            $config,
            'columns',
            ['2', '3', '4', '6'],
            '6'
        );

        $items = [];
        foreach ($this->pipe_rows($this->text($config, 'items', ''), 3) as $index => $row) {
            $title = $this->dynamic_text($data, trim((string)($row[0] ?? '')));
            $itemtext = $this->dynamic_text($data, trim((string)($row[1] ?? '')));
            if ($title === '' && $itemtext === '') {
                continue;
            }
            $imageurl = trim((string)($config['image' . ($index + 1)] ?? ''));
            $example = trim((string)($row[3] ?? ''));
            $items[] = [
                'title' => $title,
                'text' => $itemtext,
                'icon' => trim((string)($row[2] ?? 'fa-solid fa-circle')),
                'example' => $example,
                'hasexample' => $example !== '',
                'imageurl' => $imageurl,
                'hasimage' => $imageurl !== '',
            ];
        }
        if ($items !== []) {
            $data['contentstats'] = $items;
        }

        $learningcards = [];
        foreach ($this->pipe_rows($this->text($config, 'learningcards', ''), 5) as $index => $row) {
            if ($index >= 3) {
                break;
            }
            $article = trim((string)($row[0] ?? ''));
            $noun = trim((string)($row[1] ?? ''));
            $example = trim((string)($row[3] ?? ''));
            $imageurl = trim((string)($config['image' . ($index + 1)] ?? ''));
            $highlight = $this->split_example_highlight($example, $article, $noun);
            $learningcards[] = [
                'article' => $article,
                'noun' => $noun,
                'meaning' => trim((string)($row[2] ?? '')),
                'example' => $example,
                'exampleprefix' => $highlight['prefix'],
                'examplehighlight' => $highlight['highlight'],
                'examplesuffix' => $highlight['suffix'],
                'hasexamplehighlight' => $highlight['highlight'] !== '',
                'translation' => trim((string)($row[4] ?? '')),
                'imageurl' => $imageurl,
                'hasimage' => $imageurl !== '',
            ];
        }
        $data['contentlearningcards'] = $learningcards;
        $data['hascontentlearningcards'] = $learningcards !== [];
        $data['contentsidetitle'] = $this->text($config, 'sidetitle', '');
        $data['contentsideintro'] = $this->text($config, 'sideintro', '');
        $data['contentsidefooter'] = $this->text($config, 'sidefooter', '');
        $data['contentsidefooteraccent'] = $this->text($config, 'sidefooteraccent', '');
        $data['contentsideitems'] = [];
        foreach ($this->pipe_rows($this->text($config, 'sideitems', ''), 2) as $row) {
            $data['contentsideitems'][] = [
                'text' => trim((string)($row[0] ?? '')),
                'icon' => trim((string)($row[1] ?? 'fa-solid fa-check')),
            ];
        }
        $data['contentsummarytext'] = $this->text($config, 'summarytext', '');
        $data['contentsummaryaccent'] = $this->text($config, 'summaryaccent', '');
        $data['hascontentsummary'] = $data['contentsummarytext'] !== '' || $data['contentsummaryaccent'] !== '';
        return $data;
    }

    private function apply_audience_profiles(array $data, array $config): array {
        $data['audienceeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['audiencebadgeicon'] = $this->text($config, 'badgeicon', 'fa-solid fa-users');
        $data['audiencesidenote'] = $this->text($config, 'sidenote', '');
        $data['hasaudiencesidenote'] = $data['audiencesidenote'] !== '';
        $data['audiencetitle'] = $this->text($config, 'title', '');
        $data['audiencetitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['audiencetitlesuffix'] = $this->text($config, 'titlesuffix', '');
        $data['audiencedescription'] = $this->text($config, 'text', '');
        $data['audiencestats'] = [];

        $items = $this->editorial_items($this->text($config, 'items', ''));
        foreach (array_slice($items, 0, 4) as $index => $item) {
            $imageurl = trim((string)($config['image' . ($index + 1)] ?? ''));
            $item['imageurl'] = $imageurl;
            $item['hasimage'] = $imageurl !== '';
            $data['audiencestats'][] = $item;
        }

        return $data;
    }

    private function apply_heart_puzzle(array $data, array $config): array {
        $data['heartpuzzleeyebrow'] = $this->text($config, 'eyebrow', '');
        $data['heartpuzzletitle'] = $this->text($config, 'title', '');
        $data['heartpuzzletitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['heartpuzzletext'] = $this->text($config, 'text', '');
        $data['heartpuzzlecenterlabel'] = $this->text($config, 'centerlabel', '');
        $data['heartpuzzlefootertitle'] = $this->text($config, 'footertitle', '');
        $data['heartpuzzlefootertext'] = $this->text($config, 'footertext', '');
        $data['heartpuzzlefooteraccent'] = $this->text($config, 'footeraccent', '');

        $centerimage = trim((string)($config['centerimageurl'] ?? ''));
        $data['heartpuzzlecenterimageurl'] = $centerimage;
        $data['hasheartpuzzlecenterimage'] = $centerimage !== '';

        $pieces = [];
        foreach ($this->pipe_rows($this->text($config, 'pieces', ''), 4) as $index => $row) {
            if ($index >= 5) {
                break;
            }
            $number = trim((string)($row[0] ?? str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)));
            $slot = $index + 1;
            $piece = [
                'number' => $number,
                'title' => trim((string)($row[1] ?? '')),
                'text' => trim((string)($row[2] ?? '')),
                'example' => trim((string)($row[3] ?? '')),
                'hasexample' => trim((string)($row[3] ?? '')) !== '',
                'icon' => trim((string)($row[4] ?? 'fa-solid fa-puzzle-piece')),
                'slot' => $slot,
                'cardclass' => 'commerce-showroom-heart-puzzle__card--' . $slot,
                'arrowclass' => 'commerce-showroom-heart-puzzle__arrow--' . $slot,
            ];
            $pieces[] = $piece;
            $data['heartpuzzlepiece' . $slot . 'number'] = $number;
            $data['heartpuzzlepiece' . $slot . 'title'] = $piece['title'];
        }

        for ($slot = count($pieces) + 1; $slot <= 5; $slot++) {
            $data['heartpuzzlepiece' . $slot . 'number'] = str_pad((string)$slot, 2, '0', STR_PAD_LEFT);
            $data['heartpuzzlepiece' . $slot . 'title'] = '';
        }

        $data['heartpuzzlepieces'] = $pieces;
        $data['hasheartpuzzlefooter'] = $data['heartpuzzlefootertitle'] !== ''
            || $data['heartpuzzlefootertext'] !== ''
            || $data['heartpuzzlefooteraccent'] !== '';
        return $data;
    }

    private function apply_before_after(array $data, array $config): array {
        $presentation = trim((string)($config['presentation'] ?? 'standard'));
        if (!in_array($presentation, ['standard', 'article_outcome'], true)) {
            $presentation = 'standard';
        }
        $data['beforeafterpresentation'] = $presentation;
        $data['beforeafterpresentationclass'] = 'commerce-showroom-before-after--' . $presentation;
        $data['beforeafterisarticleoutcome'] = $presentation === 'article_outcome';

        $data['beforeaftereyebrow'] = $this->text($config, 'eyebrow', '');
        $data['beforeaftertitle'] = $this->text($config, 'title', '');
        $data['beforeaftertitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['beforeaftertext'] = $this->text($config, 'text', '');
        $data['beforelabel'] = $this->text($config, 'beforelabel', '');
        $data['beforetitle'] = $this->text($config, 'beforetitle', '');
        $data['beforenote'] = $this->text($config, 'beforenote', '');
        $data['hasbeforenote'] = $data['beforenote'] !== '';
        $data['afterlabel'] = $this->text($config, 'afterlabel', '');
        $data['aftertitle'] = $this->text($config, 'aftertitle', '');
        $data['beforeitems'] = $this->editorial_items($this->text($config, 'beforeitems', ''));
        $data['afteritems'] = $this->editorial_items($this->text($config, 'afteritems', ''));

        $image = trim((string)($config['imageurl'] ?? ''));
        $data['beforeafterimageurl'] = $image;
        $data['hasbeforeafterimage'] = $image !== '';
        $data['beforeaftermedianote'] = $this->text($config, 'medianote', '');
        $data['hasbeforeaftermedianote'] = $data['beforeaftermedianote'] !== '';

        $data['beforeaftersummarytitle'] = $this->text($config, 'summarytitle', '');
        $data['beforeaftersummarytext'] = $this->text($config, 'summarytext', '');
        $data['beforeaftersummaryaccent'] = $this->text($config, 'summaryaccent', '');
        $data['hasbeforeaftersummary'] = $data['beforeaftersummarytitle'] !== ''
            || $data['beforeaftersummarytext'] !== ''
            || $data['beforeaftersummaryaccent'] !== '';
        return $data;
    }

    private function apply_ascent(array $data, array $config): array {
        $data['ascenteyebrow'] = $this->text($config, 'eyebrow', $data['ascenteyebrow'] ?? '');
        $data['ascenttitle'] = $this->text($config, 'title', $data['ascenttitle'] ?? '');
        $data['ascentdescription'] = $this->text($config, 'text', $data['ascentdescription'] ?? '');
        $data['ascentgradientstart'] = $this->hex_color(
            (string)($config['gradientstart'] ?? ''),
            '#ff7bbb'
        );
        $data['ascentgradientend'] = $this->hex_color(
            (string)($config['gradientend'] ?? ''),
            '#6f2dbd'
        );

        // J16M8: optional photographic background, independently enabled per viewport.
        // The historical CSS/SVG mountain background remains the fallback.
        $background = $this->text($config, 'backgroundurl', '');
        $data['hasascentbackground'] = $background !== '';
        $data['ascentbackgroundurl'] = $background;
        $data['ascentbackgrounddesktop'] = $background !== '' && !empty($config['backgrounddesktop']);
        $data['ascentbackgroundmobile'] = $background !== '' && !empty($config['backgroundmobile']);
        $data['ascentbackgroundopacity'] = $this->bounded_number(
            $config['backgroundopacity'] ?? 100,
            0,
            100,
            100
        );
        $data['ascentbackgroundblur'] = $this->bounded_number(
            $config['backgroundblur'] ?? 0,
            0,
            24,
            0
        );

        $rawcards = $this->text($config, 'cards', '');
        $cardlines = preg_split('/\R/u', $rawcards, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $cards = [];
        foreach ($cardlines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 5) {
                $cards[] = array_slice($parts, 0, 5);
                continue;
            }

            // A continuation line belongs to the previous card description.
            // This keeps multiline editorial copy (notably card 05) safe.
            if ($cards !== []) {
                $last = count($cards) - 1;
                $cards[$last][2] = trim($cards[$last][2] . "\n" . trim($line));
            }
        }

        $data['ascentcards'] = [];
        foreach ($cards as $index => $row) {
            $text = trim((string)($row[2] ?? ''));
            $lines = preg_split('/\R/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $data['ascentcards'][] = [
                'number' => trim((string)($row[0] ?? str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT))),
                'title' => trim((string)($row[1] ?? '')),
                'textlines' => array_map(
                    static fn(string $line): array => ['text' => trim($line)],
                    $lines
                ),
                'icon' => trim((string)($row[3] ?? 'fa-solid fa-mountain')),
                'summit' => !empty($row[4]) && (string)$row[4] !== '0',
                'imageurl' => $this->text($config, 'cardimage' . ($index + 1), ''),
                'hasimage' => $this->text($config, 'cardimage' . ($index + 1), '') !== '',
            ];
        }

        if ($data['ascentcards'] === []) {
            $rows = $this->pipe_rows($this->text($config, 'checkpoints', ''), 6);
            foreach ($rows as $index => $row) {
                $data['ascentcards'][] = [
                    'number' => str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT),
                    'title' => trim((string)($row[2] ?? '')),
                    'textlines' => [['text' => trim((string)($row[3] ?? ''))]],
                    'icon' => trim((string)($row[4] ?? 'fa-solid fa-mountain')),
                    'summit' => !empty($row[5]) && (string)$row[5] !== '0',
                    'imageurl' => $this->text($config, 'cardimage' . ($index + 1), ''),
                    'hasimage' => $this->text($config, 'cardimage' . ($index + 1), '') !== '',
                ];
            }
        }

        $summaryrows = $this->pipe_rows($this->text($config, 'summaryitems', ''), 3);
        $data['ascentsummaryitems'] = [];
        foreach ($summaryrows as $row) {
            $data['ascentsummaryitems'][] = [
                'title' => trim((string)($row[0] ?? '')),
                'text' => trim((string)($row[1] ?? '')),
                'icon' => trim((string)($row[2] ?? 'fa-solid fa-circle-check')),
            ];
        }

        return $data;
    }

    private function apply_stage_method(array $data, array $config): array {
        $data['journeyeyebrow'] = $this->text($config, 'eyebrow', $data['ascenteyebrow'] ?? '');
        $data['journeytitle'] = $this->text($config, 'title', $data['journeytitle'] ?? '');
        $data['journeytitlehighlight'] = $this->text($config, 'titlehighlight', '');
        $data['hasjourneytitlehighlight'] = $data['journeytitlehighlight'] !== '';
        $data['journeydescription'] = $this->text($config, 'text', $data['journeydescription'] ?? '');

        // J16O8: Journey items are intentionally title|text.
        // Do not require the historical third icon column.
        $items = $this->editorial_items(
            $this->text($config, 'items', ''),
            true,
            2
        );
        if ($items !== []) {
            $last = count($items) - 1;
            foreach ($items as $index => &$item) {
                $item['isreststop'] = $index === $last;
            }
            unset($item);
            $data['journeysteps'] = $items;
        }

        $background = $this->text($config, 'backgroundurl', '');
        $data['hasjourneybackground'] = $background !== '';
        $data['journeybackgroundurl'] = $background;
        $data['journeybackgroundopacity'] = $this->bounded_number(
            $config['backgroundopacity'] ?? 100,
            0,
            100,
            100
        );
        $data['journeybackgroundblur'] = $this->bounded_number(
            $config['backgroundblur'] ?? 0,
            0,
            24,
            0
        );

        return $data;
    }

    private function apply_exercises(array $data, array $config): array {
        $data['exerciseeyebrow'] = $this->text($config, 'eyebrow', $data['exerciseeyebrow'] ?? '');
        $data['exercisetitle'] = $this->text($config, 'title', $data['exercisetitle'] ?? '');
        $data['exercisedescription'] = $this->text($config, 'text', $data['exercisedescription'] ?? '');
        $data['showexercisepreview'] = !array_key_exists('previewenabled', $config) || !empty($config['previewenabled']);
        return $data;
    }

    private function apply_offers(array $data, array $config): array {
        $presentation = $this->option($config, 'presentation', ['standard', 'single_course', 'alf_support'], 'standard');
        $data['offerspresentation'] = $presentation;
        $data['offerspresentationclass'] = match ($presentation) {
            'alf_support' => 'commerce-showroom-offers--alf-support',
            'single_course' => 'commerce-showroom-offers--single-course',
            default => 'commerce-showroom-offers--standard',
        };
        $data['offersisalfSupport'] = $presentation === 'alf_support';
        $data['offersissinglecourse'] = $presentation === 'single_course';
        $data['offersbadgeicon'] = $this->text(
            $config,
            'badgeicon',
            $data['offersbadgeicon'] ?? 'fa-solid fa-bag-shopping'
        );
        $data['offersfootertext'] = $this->dynamic_text($data, $this->text($config, 'footertext', ''));
        $data['offersfooteraccent'] = $this->dynamic_text($data, $this->text($config, 'footeraccent', ''));
        $data['hasoffersfooter'] = $data['offersfootertext'] !== '' || $data['offersfooteraccent'] !== '';

        $titleprefix = $this->dynamic_text($data, $this->text($config, 'title', $data['offersheading'] ?? ''));
        $titlehighlight = $this->dynamic_text($data, $this->text($config, 'titlehighlight', ''));
        $titlesuffix = $this->dynamic_text($data, $this->text($config, 'titlesuffix', ''));
        $data['offersbadge'] = $this->text(
            $config,
            'badge',
            get_string('commerce_showroom_offers_badge', 'local_subscriptions')
        );
        $data['offerstitleprefix'] = $titleprefix;
        $data['offerstitlehighlight'] = $titlehighlight;
        $data['hasofferstitlehighlight'] = $titlehighlight !== '';
        $data['offertitlesuffix'] = $titlesuffix;
        $data['hasoffertitlesuffix'] = $titlesuffix !== '';
        $data['offersheading'] = trim($titleprefix . ' ' . $titlehighlight . $titlesuffix);
        $data['offersdescription'] = $this->dynamic_text(
            $data,
            $this->text(
                $config,
                'text',
                $data['offersdescription'] ?? ''
            )
        );

        $offers = is_array($data['offers'] ?? null) ? $data['offers'] : [];
        $available = $this->item_roles($offers);
        if ($presentation === 'single_course' && $offers !== []) {
            $defaultsingle = in_array('course', $available, true)
                ? 'course'
                : (string)($available[0] ?? '');
            $singlerole = $this->normalise_role(
                $this->text($config, 'singleofferrole', $defaultsingle)
            ) ?? $defaultsingle;
            $offers = array_values(array_filter(
                $offers,
                fn(array $offer): bool => ($this->normalise_role((string)($offer['role'] ?? '')) ?? '') === $singlerole
            ));
            $available = $this->item_roles($offers);
        }
        $defaultorder = $this->default_role_order($available);
        $order = $this->roles((string)($config['order'] ?? $defaultorder), $available);
        $showsavings = !array_key_exists('showsavings', $config) || !empty($config['showsavings']);
        $showbadges = !array_key_exists('showbadges', $config) || !empty($config['showbadges']);
        $featuredsku = strtoupper($this->text($config, 'featuredsku', ''));
        $defaultfeatured = in_array('bundle', $available, true)
            ? 'bundle'
            : (string)($available[0] ?? '');
        $featuredrole = $this->normalise_role(
            $this->text($config, 'featuredrole', $defaultfeatured)
        ) ?? $defaultfeatured;

        foreach ($offers as &$offer) {
            $role = $this->normalise_role((string)($offer['role'] ?? '')) ?? '';

            // Articles / Alf compact offer presentation: keep the course card's
            // feature list, while Cours + Don and Don seul rely on their full
            // editorial copy. These flags are consumed by offer.mustache and
            // must coexist with the per-role image overrides introduced in C4.19.
            $offer['isalfSupportOffer'] = $presentation === 'alf_support';
            $offer['suppressfeatures'] = $presentation === 'alf_support'
                && in_array($role, ['course_support', 'support'], true);

            if ($role !== '') {
                $rolelabel = $this->dynamic_text($data, $this->text($config, $role . 'rolelabel', ''));
                $imageurl = trim((string)($config[$role . 'imageurl'] ?? ''));
                $title = $this->dynamic_text($data, $this->text($config, $role . 'title', ''));
                $subtitle = $this->dynamic_text($data, $this->text($config, $role . 'subtitle', ''));
                $description = $this->dynamic_text($data, $this->text($config, $role . 'description', ''));
                $features = $this->line_items($this->dynamic_text(
                    $data,
                    $this->text($config, $role . 'features', '')
                ));
                $featuredlabel = $this->dynamic_text($data, $this->text($config, $role . 'featuredlabel', ''));
                $buylabel = $this->dynamic_text($data, $this->text($config, $role . 'buylabel', ''));

                if ($rolelabel !== '') {
                    $offer['rolelabel'] = $rolelabel;
                }
                if ($title !== '') {
                    $offer['name'] = $title;
                }
                if ($imageurl !== '') {
                    // The Showroom offer is a commercial composition, not always
                    // a standalone catalogue product (for example Cours + Don).
                    // A role-level image therefore overrides the product media
                    // only inside this offers block while preserving the normal
                    // catalogue fallback when no override is configured.
                    $offer['hascover'] = true;
                    $offer['coverurl'] = $imageurl;
                    $offer['coverresponsive'] = false;
                    $offer['coversrcset'] = '';
                    $offer['coveralt'] = (string)($offer['name'] ?? '');
                }
                if ($description !== '') {
                    // Builder descriptions are plain editorial text. Escape
                    // them before they reach the historical triple-brace slot.
                    $offer['shortdescription'] = s($description);
                }
                if ($features !== []) {
                    $offer['features'] = $features;
                    $offer['hasfeatures'] = true;
                }
                if ($featuredlabel !== '') {
                    $offer['featuredlabel'] = $featuredlabel;
                }
                if ($buylabel !== '') {
                    $offer['buylabel'] = $buylabel;
                }

                $offer['subtitle'] = $subtitle;
                $offer['hassubtitle'] = $subtitle !== '';

                // A configured subtitle can become the commercial promise for
                // any offer role. Avoid repeating the catalogue description
                // underneath it unless the editor explicitly supplied one.
                if ($subtitle !== '' && $description === '') {
                    $offer['shortdescription'] = '';
                }

                $offer['hasdescription'] = trim(strip_tags((string)($offer['shortdescription'] ?? ''))) !== '';
            }

            if (!$showsavings) {
                $offer['haspromotion'] = false;
                $offer['hascompareprice'] = false;
            }
            if (!$showbadges) {
                $offer['isfeatured'] = false;
            } else {
                $skumatches = $featuredsku !== ''
                    && strtoupper((string)($offer['sku'] ?? '')) === $featuredsku;

                $offer['isfeatured'] = $skumatches
                    || ($featuredsku === '' && $role === $featuredrole)
                    || (
                        $role === $featuredrole
                        && !$this->has_offer_sku($offers, $featuredsku)
                    );
            }
        }
        unset($offer);

        $data['offers'] = $this->reorder($offers, $order);
        $data['comparisonoffers'] = $this->reorder(
            is_array($data['comparisonoffers'] ?? null) ? $data['comparisonoffers'] : [],
            $order
        );
        $data['offerscardvariant'] = $this->option(
            $config,
            'cardvariant',
            ['classic', 'premium', 'minimal', 'horizontal'],
            'classic'
        );
        $data['offerscolumns'] = $presentation === 'single_course'
            ? '1'
            : $this->option($config, 'columns', ['2', '3', '4'], '3');

        $mobilepresentation = $this->option($config, 'mobilepresentation', ['stack', 'slider'], 'stack');
        $data['offersmobilepresentation'] = $mobilepresentation;
        $data['offersmobilepresentationclass'] = 'commerce-showroom-offers--mobile-' . $mobilepresentation;
        $data['offersmobileslider'] = $mobilepresentation === 'slider';
        $data['offersmobilesliderhint'] = get_string('commerce_showroom_offers_slider_hint', 'local_subscriptions');

        $cardstopspacing = $this->option(
            $config,
            'cardstopspacing',
            ['compact', 'normal', 'airy', 'veryairy'],
            'airy'
        );
        $data['offerscardstopspacing'] = $cardstopspacing;
        $data['offerscardstopspacingclass'] = 'commerce-showroom-offers--cards-spacing-' . $cardstopspacing;
        return $data;
    }

    private function apply_comparison(array $data, array $config): array {
        $data['comparisoneyebrow'] = $this->text(
            $config,
            'eyebrow',
            $data['comparisoneyebrow'] ?? ''
        );
        $data['comparisontitle'] = $this->text(
            $config,
            'title',
            $data['comparisontitle'] ?? ''
        );
        $data['comparisondescription'] = $this->text(
            $config,
            'text',
            $data['comparisondescription'] ?? ''
        );
        $data['comparisonfeaturelabel'] = $this->text(
            $config,
            'featurelabel',
            $data['comparisonfeaturelabel'] ?? ''
        );

        $comparisonoffers = is_array($data['comparisonoffers'] ?? null)
            ? $data['comparisonoffers']
            : [];
        $available = $this->item_roles($comparisonoffers);
        $defaultorder = $this->default_role_order($available);
        $order = $this->roles((string)($config['order'] ?? $defaultorder), $available);

        $rows = $this->comparison_rows_from_config(
            $this->text($config, 'rows', ''),
            $order
        );
        if ($rows !== []) {
            $data['comparisonrows'] = $rows;
        }

        $data['comparisonoffers'] = $this->reorder($comparisonoffers, $order);

        $featuredbroles = [];
        foreach ($data['comparisonoffers'] as $offer) {
            if (!empty($offer['featured'])) {
                $featuredbroles[(string)($offer['role'] ?? '')] = true;
            }
        }
        $comparisonrows = is_array($data['comparisonrows'] ?? null)
            ? $data['comparisonrows']
            : [];
        foreach ($comparisonrows as &$row) {
            $cells = [];
            foreach ($order as $role) {
                $value = is_array($row[$role] ?? null) ? $row[$role] : [];
                $cells[] = [
                    'role' => $role,
                    'featured' => !empty($featuredbroles[$role]),
                    'value' => $value,
                ];
            }
            $row['cells'] = $cells;
        }
        unset($row);
        $data['comparisonrows'] = $comparisonrows;
        $data['comparisoncolumncount'] = count($order);

        $mobileoffers = [];
        foreach ($data['comparisonoffers'] as $index => $offer) {
            $role = (string)($offer['role'] ?? '');
            $offer['mobileindex'] = $index;
            $offer['mobilelabel'] = (string)($offer['name'] ?? '');
            $offer['mobileprice'] = (string)($offer['priceformatted'] ?? '');
            $offer['mobilerows'] = [];
            foreach ($data['comparisonrows'] ?? [] as $row) {
                $offer['mobilerows'][] = [
                    'value' => is_array($row[$role] ?? null) ? $row[$role] : [],
                ];
            }
            $mobileoffers[] = $offer;
        }
        $data['comparisonmobileoffers'] = $mobileoffers;
        $data['comparisonmobilecount'] = count($mobileoffers);
        $data['comparisonswipehint'] = get_string(
            'commerce_showroom_comparison_swipe_hint',
            'local_subscriptions'
        );

        return $data;
    }

    /**
     * One row per line: label|role-1|role-2|...
     *
     * Cells accept:
     * - ✓ for included;
     * - — or - for not included;
     * - any other value as editorial text.
     *
     * @param string[] $roles
     * @return array<int,array<string,mixed>>
     */
    private function comparison_rows_from_config(
        string $value,
        array $roles = ['pdf', 'bundle', 'course']
    ): array {
        $roles = $this->roles(implode(',', $roles), $roles);
        if ($roles === []) {
            return [];
        }

        $rows = [];
        $expected = count($roles) + 1;
        foreach (preg_split('/\R/u', trim($value)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = array_map('trim', explode('|', $line));
            if (count($columns) !== $expected || $columns[0] === '') {
                continue;
            }

            $row = ['label' => $columns[0]];
            foreach ($roles as $index => $role) {
                $row[$role] = $this->comparison_config_cell($columns[$index + 1]);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return array<string,mixed> */
    private function comparison_config_cell(string $value): array {
        $value = trim($value);
        $included = in_array($value, ['✓', '✔', 'yes', 'true', '1'], true);
        $notincluded = in_array($value, ['—', '–', '-', 'no', 'false', '0'], true);
        $text = (!$included && !$notincluded) ? $value : '';

        return [
            'included' => $included,
            'notincluded' => $notincluded,
            'text' => $text,
            'hastext' => $text !== '',
        ];
    }

    private function apply_testimonials(array $data, array $config): array {
        $data['testimonialseyebrow'] = $this->text(
            $config,
            'eyebrow',
            $data['testimonialseyebrow'] ?? ''
        );
        $data['testimonialstitle'] = $this->text(
            $config,
            'title',
            $data['testimonialstitle'] ?? ''
        );

        $rows = $this->pipe_rows($this->text($config, 'items', ''), 2);
        $testimonials = [];
        foreach ($rows as $row) {
            $quote = trim((string)($row[0] ?? ''));
            $author = trim((string)($row[1] ?? ''));
            if ($quote === '') {
                continue;
            }

            $testimonials[] = [
                'quote' => $quote,
                'author' => $author,
            ];
        }

        if ($testimonials !== []) {
            $data['testimonials'] = $testimonials;
        }

        $data['hastestimonials'] = !empty($data['testimonials']);
        return $data;
    }

    private function apply_memory_method(array $data, array $config): array {
        $data['whyeyebrow'] = $this->text($config, 'eyebrow', $data['whyeyebrow'] ?? '');
        $data['whytitle'] = $this->text($config, 'title', $data['whytitle'] ?? '');
        $data['whydescription'] = $this->text($config, 'text', $data['whydescription'] ?? '');
        $items = $this->editorial_items($this->text($config, 'items', ''));
        if ($items !== []) {
            $data['whyitems'] = $items;
        }
        return $data;
    }

    private function apply_trust(array $data, array $config): array {
        $items = $this->editorial_items($this->text($config, 'items', ''));
        if ($items !== []) {
            $data['trustitems'] = $items;
        }
        return $data;
    }

    private function apply_bonus(array $data, array $config): array {
        $data['bonuseyebrow'] = $this->text($config, 'eyebrow', 'Bonus');
        $data['bonusheading'] = $this->text($config, 'title', $data['bonusheading'] ?? '');
        $data['bonustext'] = $this->text($config, 'text', $data['bonustext'] ?? '');
        $data['bonuscta'] = $this->text($config, 'buttonlabel', $data['bonuscta'] ?? '');
        $data['bonusactionurl'] = $this->text($config, 'buttontarget', $data['shopurl'] ?? '');
        return $data;
    }

    private function apply_faq(array $data, array $config): array {
        $presentation = trim((string)($config['presentation'] ?? 'standard'));
        if (!in_array($presentation, ['standard', 'article_course', 'a1_travel'], true)) {
            $presentation = 'standard';
        }
        $data['faqpresentation'] = $presentation;
        $data['faqisarticlecourse'] = $presentation === 'article_course';
        $data['faqistravela1'] = $presentation === 'a1_travel';
        $data['faqpresentationclass'] = match ($presentation) {
            'article_course' => 'commerce-showroom-faq--article-course',
            'a1_travel' => 'commerce-showroom-faq--a1-travel',
            default => '',
        };

        $data['faqeyebrow'] = $this->text($config, 'eyebrow', $data['faqeyebrow'] ?? '');
        $data['faqheading'] = $this->text($config, 'title', $data['faqheading'] ?? '');
        $data['faqtitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['faqtitlesuffix'] = $this->text($config, 'titlesuffix', '');
        $data['hasfaqtitlesuffix'] = $data['faqtitlesuffix'] !== '';
        $data['faqdescription'] = $this->text(
            $config,
            'text',
            $data['faqistravela1'] ? '' : ($data['faqdescription'] ?? '')
        );

        // Existing pipe-separated FAQ remains the default for all legacy Showrooms.
        $rows = $this->pipe_rows($this->text($config, 'items', ''), 2);
        if ($rows !== []) {
            $faqs = [];
            foreach ($rows as $index => $row) {
                $icon = trim((string)($row[2] ?? ''));
                if ($icon === '') {
                    $icon = 'fa-solid fa-circle-question';
                }
                $faqs[] = [
                    'id' => 'showroom-faq-cms-' . ($index + 1),
                    'question' => trim((string)($row[0] ?? '')),
                    'answer' => trim((string)($row[1] ?? '')),
                    'icon' => $icon,
                    'answerparagraphs' => [['text' => trim((string)($row[1] ?? ''))]],
                ];
            }
            $data['faqs'] = $faqs;
        }

        if ($data['faqistravela1']) {
            // Only this opt-in variant accepts structured answers with genuine
            // paragraph breaks. Never interpret HTML from editable CMS content.
            $encoded = $this->text($config, 'entriesjson', '');
            $entries = json_decode($encoded, true);
            if (is_array($entries) && array_is_list($entries)) {
                $faqs = [];
                foreach (array_slice($entries, 0, 12) as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $question = trim((string)($entry['question'] ?? ''));
                    $answer = trim((string)($entry['answer'] ?? ''));
                    if ($question === '' || $answer === '') {
                        continue;
                    }
                    $paragraphs = [];
                    foreach (preg_split('/\R[ \t]*\R/u', $answer) ?: [] as $paragraph) {
                        $paragraph = trim($paragraph);
                        if ($paragraph !== '') {
                            $paragraphs[] = ['text' => $paragraph];
                        }
                    }
                    if ($paragraphs === []) {
                        continue;
                    }
                    $icon = trim((string)($entry['icon'] ?? ''));
                    if (preg_match('/^fa-(?:solid|regular) fa-[a-z0-9-]+$/', $icon) !== 1) {
                        $icon = 'fa-solid fa-circle-question';
                    }
                    $faqs[] = [
                        'id' => 'showroom-faq-cms-' . (count($faqs) + 1),
                        'question' => $question,
                        'answer' => $answer,
                        'answerparagraphs' => $paragraphs,
                        'icon' => $icon,
                    ];
                }
                if ($faqs !== []) {
                    $data['faqs'] = $faqs;
                }
            }
        }

        $data['faqsingleopen'] = !array_key_exists('singleopen', $config) || !empty($config['singleopen']);

        $data['faqsupporttitle'] = $this->text($config, 'supporttitle', '');
        $data['faqsupporttext'] = $this->text($config, 'supporttext', '');
        $data['faqsupportbuttonlabel'] = $this->text($config, 'supportbuttonlabel', '');
        $data['faqsupportbuttontarget'] = trim((string)($config['supportbuttontarget'] ?? ''));
        if ($data['faqistravela1'] && preg_match('/^#[a-z][a-z0-9_-]*$/i', $data['faqsupportbuttontarget']) !== 1) {
            // The contact button must target an existing Showroom section.
            $data['faqsupportbuttontarget'] = '';
        }
        $data['hasfaqsupportbutton'] = $data['faqsupportbuttonlabel'] !== '' && $data['faqsupportbuttontarget'] !== '';
        $data['faqsupportleftimageurl'] = trim((string)($config['supportleftimageurl'] ?? ''));
        $data['hasfaqsupportleftimage'] = $data['faqsupportleftimageurl'] !== '';
        $data['faqsupportrightimageurl'] = trim((string)($config['supportrightimageurl'] ?? ''));
        $data['hasfaqsupportrightimage'] = $data['faqsupportrightimageurl'] !== '';
        // Historical Articles FAQ intentionally uses the separate Support block.
        $data['hasfaqsupport'] = !$data['faqisarticlecourse']
            && ($data['faqsupporttitle'] !== '' || $data['faqsupporttext'] !== '' || $data['hasfaqsupportbutton']);
        $data['faqfooternote'] = $this->text($config, 'footernote', '');
        $data['hasfaqfooternote'] = $data['faqfooternote'] !== '';
        return $data;
    }

    private function apply_support(array $data, array $config): array {
        $data['supportpresentation'] = $this->option(
            $config,
            'presentation',
            ['standard', 'teacher_help'],
            'standard'
        );
        $data['issupportteacherstyle'] = $data['supportpresentation'] === 'teacher_help';

        if ($data['issupportteacherstyle']) {
            $data['supporteyebrow'] = $this->text($config, 'eyebrow', '');
            $data['supportnote'] = $this->text($config, 'note', '');
            $data['hassupportnote'] = $data['supportnote'] !== '';
            $data['supporttitle'] = $this->dynamic_text(
                $data,
                $this->text($config, 'title', $data['supporttitle'] ?? '')
            );
            $data['supporttitleaccent'] = $this->dynamic_text(
                $data,
                $this->text($config, 'titleaccent', '')
            );
            $data['hassupporttitleaccent'] = $data['supporttitleaccent'] !== '';
            $data['supporttext'] = $this->dynamic_text(
                $data,
                $this->text($config, 'text', $data['supporttext'] ?? '')
            );
            $data['supportitems'] = array_slice(
                $this->dynamic_editorial_items($data, $this->text($config, 'items', '')),
                0,
                4
            );
            $data['supportleftitems'] = array_slice($data['supportitems'], 0, 2);
            $data['supportrightitems'] = array_slice($data['supportitems'], 2, 2);
            $data['supportconversationtitle'] = $this->text($config, 'conversationtitle', '');
            $data['hassupportconversationtitle'] = $data['supportconversationtitle'] !== '';
            $data['supportsidenote'] = $this->text($config, 'sidenote', '');
            $data['hassupportsidenote'] = $data['supportsidenote'] !== '';

            $messages = [];
            foreach (preg_split('/\R/u', $this->text($config, 'messages', '')) ?: [] as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $row = array_map('trim', explode('|', $line));
                $messagetext = trim((string)($row[1] ?? ''));
                if ($messagetext === '') {
                    continue;
                }
                $side = strtolower(trim((string)($row[2] ?? 'tutor')));
                if (!in_array($side, ['tutor', 'student'], true)) {
                    $side = 'tutor';
                }
                $author = trim((string)($row[0] ?? ''));
                $time = trim((string)($row[3] ?? ''));
                $messages[] = [
                    'author' => $author,
                    'text' => $messagetext,
                    'side' => $side,
                    'time' => $time,
                    'hasauthor' => $author !== '',
                    'hastime' => $time !== '',
                    'istutor' => $side === 'tutor',
                    'isstudent' => $side === 'student',
                ];
            }
            $data['supportmessages'] = array_slice($messages, 0, 6);
            $data['hassupportmessages'] = $data['supportmessages'] !== [];
        } else {
            // Preserve the historical Support contract byte-for-byte in behaviour.
            $data['supporttitle'] = $this->text($config, 'title', $data['supporttitle'] ?? '');
            $data['supporttext'] = $this->text($config, 'text', $data['supporttext'] ?? '');
        }

        $data['supportcta'] = $this->text($config, 'buttonlabel', $data['supportcta'] ?? '');
        $data['supporttelegramurl'] = trim((string)($config['telegramurl'] ?? ''));
        $data['hassupporttelegram'] = $data['supporttelegramurl'] !== '';
        $data['supportwhatsappurl'] = trim((string)($config['whatsappurl'] ?? ''));
        $data['hassupportwhatsapp'] = $data['supportwhatsappurl'] !== '';

        return $data;
    }


    /** Dedicated Support-like contact beneath A1 FAQ, without mutating Support. */
    private function apply_faq_contact(array $data, array $config): array {
        $data['faqcontacttitle'] = $this->text($config, 'title', '');
        $data['faqcontacttext'] = $this->text($config, 'text', '');
        $data['faqcontactbuttonlabel'] = $this->text($config, 'buttonlabel', '');

        // Keep the native CampusFR Support route unless an explicit HTTP(S)
        // destination is configured (never trust arbitrary JavaScript URLs).
        $url = trim((string)($config['buttonurl'] ?? ''));
        $data['faqcontactbuttonurl'] = $this->safe_faq_contact_url($url)
            ? $url : (string)($data['supporturl'] ?? '#showroom-support');
        $data['faqcontacttelegramurl'] = trim((string)($config['telegramurl'] ?? ''));
        $data['faqcontactwhatsappurl'] = trim((string)($config['whatsappurl'] ?? ''));
        if (!$this->safe_faq_contact_url($data['faqcontacttelegramurl'])) {
            $data['faqcontacttelegramurl'] = '';
        }
        if (!$this->safe_faq_contact_url($data['faqcontactwhatsappurl'])) {
            $data['faqcontactwhatsappurl'] = '';
        }
        $data['hasfaqcontacttelegram'] = $data['faqcontacttelegramurl'] !== '';
        $data['hasfaqcontactwhatsapp'] = $data['faqcontactwhatsappurl'] !== '';
        $data['hasfaqcontactemail'] = trim((string)($data['supportemail'] ?? '')) !== '';
        $data['hasfaqcontactbutton'] = $data['faqcontactbuttonlabel'] !== '';
        return $data;
    }

    private function safe_faq_contact_url(string $url): bool {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true);
    }

    private function apply_verbs_cards(array $data, array $config): array {
        $data['verbscardsimageurl'] = $this->text($config, 'imageurl', '');
        $data['hasverbscardsimage'] = $data['verbscardsimageurl'] !== '';
        $data['verbscardseyebrow'] = $this->text($config, 'eyebrow', '');
        $data['verbscardstitle'] = $this->text($config, 'title', '');
        $data['verbscardstitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['hasverbscardstitleaccent'] = $data['verbscardstitleaccent'] !== '';
        $data['verbscardstext'] = $this->text($config, 'text', '');
        $data['verbscardsbuttonlabel'] = $this->text($config, 'buttonlabel', '');
        $data['verbscardsbuttontarget'] = $this->text($config, 'buttontarget', '#');

        $data['verbscardsitems'] = $this->verbs_cards_items(
            $this->text($config, 'items', '')
        );

        return $data;
    }

    /**
     * Parses Verbs Cards benefits.
     *
     * One physical line = one benefit:
     * title|text|fa-icon
     *
     * Literal \\n inside title/text is converted to a visual line break.
     *
     * @return array<int,array{title:string,text:string,icon:string}>
     */
    private function verbs_cards_items(string $raw): array {
        $items = [];

        foreach ($this->pipe_rows($raw, 3) as $row) {
            $title = str_replace('\\n', "\n", trim((string)($row[0] ?? '')));
            $text = str_replace('\\n', "\n", trim((string)($row[1] ?? '')));
            $icon = trim((string)($row[2] ?? ''));

            if ($title === '' || $text === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'text' => $text,
                'icon' => $icon !== '' ? $icon : 'fa-regular fa-star',
            ];
        }

        return array_slice($items, 0, 3);
    }

    private function apply_final_cta(array $data, array $config): array {
        $presentation = $this->option(
            $config,
            'presentation',
            ['standard', 'article_heart'],
            'standard'
        );

        $data['finalpresentation'] = $presentation;
        $data['finalpresentationclass'] = 'commerce-showroom-final--' . $presentation;
        $data['isfinalctaarticle'] = $presentation === 'article_heart';

        // C4.15 — article_heart is deliberately quieter by default while the Builder
        // can explicitly restore any editorial layer. Standard Final CTA keeps its
        // historical behaviour when the new controls are left on Auto.
        $titlesize = $this->option($config, 'titlesize', ['auto', 'normal', 'compact'], 'auto');
        if ($titlesize === 'auto') {
            $titlesize = $data['isfinalctaarticle'] ? 'compact' : 'normal';
        }
        $data['finaltitlesizeclass'] = 'commerce-showroom-final__title--' . $titlesize;

        $badgedisplay = $this->option($config, 'badgedisplay', ['auto', 'show', 'hide'], 'auto');
        $textdisplay = $this->option($config, 'textdisplay', ['auto', 'show', 'hide'], 'auto');
        $perksdisplay = $this->option($config, 'perksdisplay', ['auto', 'show', 'hide'], 'auto');
        $handnotedisplay = $this->option($config, 'handnotedisplay', ['auto', 'show', 'hide'], 'auto');

        $data['showfinalbadge'] = $badgedisplay === 'show'
            || ($badgedisplay === 'auto' && !$data['isfinalctaarticle']);
        $data['showfinaltext'] = $textdisplay === 'show'
            || ($textdisplay === 'auto' && !$data['isfinalctaarticle']);
        $data['showfinalperks'] = $perksdisplay === 'show'
            || ($perksdisplay === 'auto' && !$data['isfinalctaarticle']);
        $data['showfinalhandnote'] = $handnotedisplay === 'show'
            || ($handnotedisplay === 'auto' && !$data['isfinalctaarticle']);

        $data['finaleyebrow'] = $this->text($config, 'eyebrow', $data['finaleyebrow'] ?? '');
        $data['finalbadgeicon'] = $this->text(
            $config,
            'badgeicon',
            $presentation === 'article_heart' ? 'fa-solid fa-binoculars' : 'fa-solid fa-person-hiking'
        );
        $data['finaltitle'] = $this->text($config, 'title', $data['finaltitle'] ?? '');
        $data['finaltitleaccent'] = $this->text($config, 'titleaccent', '');
        $data['hasfinaltitleaccent'] = $data['finaltitleaccent'] !== '';
        $data['finaltext'] = $this->text($config, 'text', $data['finaltext'] ?? '');
        $data['finalhandnote'] = $this->text($config, 'handnote', '');
        $data['hasfinalhandnote'] = $data['showfinalhandnote'] && $data['finalhandnote'] !== '';
        $data['finalcta'] = $this->text($config, 'buttonlabel', $data['finalcta'] ?? '');
        $data['finalactionurl'] = $this->text($config, 'buttontarget', $data['finalactionurl'] ?? '');
        $data['finalsecondarylabel'] = $this->text($config, 'secondarylabel', '');
        $data['finalsecondarytarget'] = $this->text($config, 'secondarytarget', '');
        $data['hasfinalsecondarycta'] = $data['finalsecondarylabel'] !== ''
            && $data['finalsecondarytarget'] !== '';
        $data['finalperks'] = $this->final_perks($this->text($config, 'perkitems', ''));
        $data['showfinalbadge'] = $data['showfinalbadge'] && $data['finaleyebrow'] !== '';
        $data['showfinaltext'] = $data['showfinaltext'] && $data['finaltext'] !== '';
        $data['hasfinalperks'] = $data['showfinalperks'] && !empty($data['finalperks']);
        $data['finalstyle'] = $this->option(
            $config,
            'style',
            ['primary', 'secondary', 'dark'],
            'primary'
        );

        $background = $this->text($config, 'backgroundurl', '');
        $data['hasfinalbackground'] = $background !== '';
        $data['finalbackgroundurl'] = $background;
        $data['finalbackgroundopacity'] = $this->bounded_number(
            $config['backgroundopacity'] ?? 100,
            0,
            100,
            100
        );
        $data['finalbackgroundblur'] = $this->bounded_number(
            $config['backgroundblur'] ?? 0,
            0,
            24,
            0
        );

        // J16P6 — legal footer display switches. Missing legacy keys default to ON.
        foreach ([
            'name',
            'address',
            'legal',
            'email',
            'phone',
            'website',
            'taxnotice',
            'footer',
            'privacy',
            'terms',
            'offer',
        ] as $legalfield) {
            $key = 'legalshow' . $legalfield;
            $data['finallegalshow' . $legalfield] =
                !array_key_exists($key, $config) || !empty($config[$key]);
        }

        return $data;
    }

    /** @return array<int,array{title:string,icon:string}> */
    private function final_perks(string $raw): array {
        $rows = $this->pipe_rows($raw, 2);
        $items = [];
        foreach ($rows as $row) {
            $title = trim((string)($row[0] ?? ''));
            $icon = trim((string)($row[1] ?? ''));
            if ($title === '' || $icon === '') {
                continue;
            }
            $items[] = [
                'title' => $title,
                'icon' => $icon,
            ];
        }

        return array_slice($items, 0, 4);
    }

    /** @return array{prefix:string,highlight:string,suffix:string} */
    private function split_example_highlight(string $example, string $article, string $noun): array {
        $needle = trim($article . ' ' . $noun);
        if ($example === '' || $needle === '') {
            return ['prefix' => '', 'highlight' => '', 'suffix' => ''];
        }

        if (function_exists('mb_stripos') && function_exists('mb_substr')) {
            $position = mb_stripos($example, $needle, 0, 'UTF-8');
            if ($position !== false) {
                $length = mb_strlen($needle, 'UTF-8');
                return [
                    'prefix' => mb_substr($example, 0, $position, 'UTF-8'),
                    'highlight' => mb_substr($example, $position, $length, 'UTF-8'),
                    'suffix' => mb_substr($example, $position + $length, null, 'UTF-8'),
                ];
            }
        } else {
            $position = stripos($example, $needle);
            if ($position !== false) {
                return [
                    'prefix' => substr($example, 0, $position),
                    'highlight' => substr($example, $position, strlen($needle)),
                    'suffix' => substr($example, $position + strlen($needle)),
                ];
            }
        }

        return ['prefix' => '', 'highlight' => '', 'suffix' => ''];
    }


    private function dynamic_text(array $data, string $value): string {
        $tokens = [
            '{promotion_name}' => (string)($data['pedagogicalpromotionname'] ?? ''),
            '{promotion_start}' => (string)($data['pedagogicalpromotionstartlabel'] ?? ''),
            '{sales_close}' => (string)($data['pedagogicalsalescloselabel'] ?? ($data['pedagogicalpromotionsalescloselabel'] ?? '')),
            '{group_size}' => isset($data['pedagogicalgroupsize']) ? (string)$data['pedagogicalgroupsize'] : '',
            '{remaining_places}' => isset($data['pedagogicalremaining']) ? (string)$data['pedagogicalremaining'] : '',
            '{capacity}' => isset($data['pedagogicalpromotioncapacity']) ? (string)$data['pedagogicalpromotioncapacity'] : '',
        ];
        return strtr($value, $tokens);
    }

    /** @return array<int,array{title:string,text:string,icon:string}> */
    private function dynamic_editorial_items(array $data, string $raw): array {
        $items = $this->editorial_items($raw);
        foreach ($items as &$item) {
            $item['title'] = $this->dynamic_text($data, (string)$item['title']);
            $item['text'] = $this->dynamic_text($data, (string)$item['text']);
        }
        unset($item);
        return $items;
    }

    private function editorial_items(
        string $raw,
        bool $numbered = false,
        int $minimum = 3
    ): array {
        $rows = $this->pipe_rows($raw, $minimum);
        $items = [];
        foreach ($rows as $index => $row) {
            $title = trim((string)($row[0] ?? ''));
            $text = trim((string)($row[1] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            $item = [
                'title' => $title,
                'text' => $text,
                'icon' => trim((string)($row[2] ?? 'fa-solid fa-circle')),
            ];
            if ($numbered) {
                $item['number'] = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
            }
            $items[] = $item;
        }
        return $items;
    }

    private function apply_layout(
        array $data,
        string $key,
        array $config,
        string $backgroundfallback = 'default'
    ): array {
        $width = $this->option($config, 'sectionwidth', ['contained', 'wide', 'full'], 'contained');
        $background = $this->option(
            $config,
            'sectionbackground',
            ['default', 'white', 'light', 'soft', 'campuspink', 'dark', 'dark2', 'gradient', 'custom', 'image'],
            $backgroundfallback
        );
        $textcolor = $this->option(
            $config,
            'sectiontextcolor',
            ['auto', 'brand', 'white'],
            'auto'
        );
        $resolvedtextcolor = $textcolor;
        if ($resolvedtextcolor === 'auto') {
            $resolvedtextcolor = in_array($background, ['dark', 'dark2', 'gradient'], true)
                ? 'white'
                : 'brand';
        }

        $backgroundcolor = strtolower(trim((string)($config['sectionbackgroundcolor'] ?? '#fff0f7')));
        if (preg_match('/^#[0-9a-f]{6}$/', $backgroundcolor) !== 1) {
            $backgroundcolor = '#fff0f7';
        }
        $backgroundimage = trim((string)($config['sectionbackgroundimageurl'] ?? ''));
        $backgroundopacity = $this->bounded_number(
            $config['sectionbackgroundopacity'] ?? 100,
            0,
            100,
            100
        );
        $backgroundblur = $this->bounded_number(
            $config['sectionbackgroundblur'] ?? 0,
            0,
            24,
            0
        );
        $spacing = $this->option($config, 'sectionspacing', ['compact', 'normal', 'large'], 'normal');
        $animation = $this->option($config, 'sectionanimation', ['none', 'fade', 'rise'], 'none');
        $data[$key . 'layoutclass'] = 'commerce-showroom-layout--' . $width
            . ' commerce-showroom-background--' . $background
            . ' commerce-showroom-text--' . $resolvedtextcolor
            . ' commerce-showroom-spacing--' . $spacing
            . ' commerce-showroom-animation--' . $animation;
        $data[$key . 'layouttextcolor'] = $textcolor;
        $data[$key . 'layoutresolvedtextcolor'] = $resolvedtextcolor;
        $data[$key . 'layoutbackgroundcolor'] = $backgroundcolor;
        $data[$key . 'layoutbackgroundimageurl'] = $backgroundimage;
        $data['has' . $key . 'layoutbackgroundimage'] = $background === 'image' && $backgroundimage !== '';
        $data[$key . 'layoutbackgroundopacity'] = $backgroundopacity;
        $data[$key . 'layoutbackgroundblur'] = $backgroundblur;
        return $data;
    }

    private function option(array $config, string $key, array $allowed, string $fallback): string {
        $value = (string)($config[$key] ?? '');
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
     * @param array<int,array<string,mixed>> $offers
     */
    private function has_offer_sku(array $offers, string $sku): bool {
        if ($sku === '') {
            return false;
        }

        foreach ($offers as $offer) {
            if (strtoupper((string)($offer['sku'] ?? '')) === $sku) {
                return true;
            }
        }

        return false;
    }

    private function bounded_number(
        mixed $value,
        float $min,
        float $max,
        float $fallback
    ): float {
        if (!is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (float)$value));
    }

    private function hex_color(string $value, string $fallback): string {
        $value = trim($value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1
            ? strtolower($value)
            : $fallback;
    }

    private function text(array $config, string $key, string $fallback): string {
        $language = strtolower(substr(current_language(), 0, 2));
        if (!in_array($language, CommerceShowroomBlockDefaultsCatalog::LANGUAGES, true)) {
            $language = 'fr';
        }

        $translations = is_array($config['translations'] ?? null)
            ? $config['translations']
            : [];

        foreach ([
            $translations[$language][$key] ?? null,
            $translations['fr'][$key] ?? null,
            $config[$key] ?? null,
        ] as $candidate) {
            if ($candidate === null) {
                continue;
            }
            $value = trim((string)$candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    /** @return array<int,array<int,string>> */
    private function pipe_rows(string $source, int $minimum): array {
        $rows = [];
        foreach (preg_split('/\\R/u', $source) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= $minimum && $parts[0] !== '' && $parts[1] !== '') {
                $rows[] = $parts;
            }
        }
        return $rows;
    }

    /** @return array<int,array{text:string}> */
    private function line_items(string $source): array {
        $items = [];
        foreach (preg_split('/\R/u', $source) ?: [] as $line) {
            $line = trim($line);
            $line = preg_replace('/^(?:✅|✓|✔)\s*/u', '', $line) ?? $line;
            if ($line !== '') {
                $items[] = ['text' => $line];
            }
        }
        return $items;
    }

    /** @param string[] $available @return string[] */
    private function roles(string $source, array $available = []): array {
        $allowed = [];
        foreach ($available as $rawrole) {
            $role = $this->normalise_role((string)$rawrole);
            if ($role !== null) {
                $allowed[$role] = $role;
            }
        }

        $roles = [];
        foreach (explode(',', strtolower($source)) as $rawrole) {
            $role = $this->normalise_role($rawrole);
            if ($role === null || ($allowed !== [] && !isset($allowed[$role]))) {
                continue;
            }
            $roles[$role] = $role;
        }
        foreach ($allowed as $role) {
            $roles[$role] ??= $role;
        }
        return array_values($roles);
    }

    /** @param array<int,array<string,mixed>> $items @return string[] */
    private function item_roles(array $items): array {
        $roles = [];
        foreach ($items as $item) {
            $role = $this->normalise_role((string)($item['role'] ?? ''));
            if ($role !== null) {
                $roles[$role] = $role;
            }
        }
        return array_values($roles);
    }

    /** @param string[] $roles */
    private function default_role_order(array $roles): string {
        if (
            count($roles) === 3
            && count(array_intersect($roles, ['pdf', 'bundle', 'course'])) === 3
        ) {
            return 'pdf,bundle,course';
        }
        return implode(',', $roles);
    }

    private function normalise_role(string $role): ?string {
        $role = strtolower(trim($role));
        if ($role === '' || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $role)) {
            return null;
        }
        return $role;
    }

    /** @param array<int,array<string,mixed>> $items @param string[] $roles */
    private function reorder(array $items, array $roles): array {
        $rank = array_flip($roles);
        usort($items, static function(array $left, array $right) use ($rank): int {
            return ($rank[(string)($left['role'] ?? '')] ?? 99)
                <=> ($rank[(string)($right['role'] ?? '')] ?? 99);
        });
        return $items;
    }
}
