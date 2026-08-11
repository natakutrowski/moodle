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

        $data = $this->apply_layout($data, 'verbscards', $blocks->config('verbs_cards'), 'white');
        $data = $this->apply_verbs_cards($data, $blocks->config('verbs_cards'));

        $data = $this->apply_layout($data, 'finalcta', $blocks->config('final_cta'));
        return $this->apply_final_cta($data, $blocks->config('final_cta'));
    }

    private function apply_hero(array $data, array $config): array {
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
        $data['showherogustave'] = !array_key_exists('showgustave', $config) || !empty($config['showgustave']);

        $background = $this->text($config, 'backgroundurl', '');
        $data['hasherobackground'] = $background !== '';
        $data['herobackgroundurl'] = $background;

        $desktopimage = $this->text($config, 'desktopimageurl', '');
        $data['hasherodesktopimage'] = $desktopimage !== '';
        $data['herodesktopimageurl'] = $desktopimage;

        $mobileimage = $this->text($config, 'mobileimageurl', '');
        $data['hasheromobileimage'] = $mobileimage !== '';
        $data['heromobileimageurl'] = $mobileimage;

        $data['herovariant'] = $this->option(
            $config,
            'herovariant',
            ['standard', 'cover', 'split', 'minimal'],
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
        foreach (array_slice($items, 0, 4) as $index => $item) {
            $number = $index + 1;
            $data['herostat' . $number . 'value'] = $item[0];
            $data['herostat' . $number . 'label'] = $item[1];
            $data['herostat' . $number . 'icon'] = $item[2] ?? '';
        }
        $data['statsanimate'] = !array_key_exists('animate', $config) || !empty($config['animate']);
        return $data;
    }

    private function apply_video(array $data, array $config): array {
        $data['videotitle'] = $this->text($config, 'title', $data['videotitle'] ?? '');
        $data['videodescription'] = $this->text($config, 'text', $data['videodescription'] ?? '');
        $poster = $this->text($config, 'posterurl', '');
        $data['hasvideoposter'] = $poster !== '';
        $data['videoposterurl'] = $poster;
        $data['videourl'] = $this->text($config, 'videourl', '');
        $data['hasvideourl'] = $data['videourl'] !== '';
        $data['videoplaylabel'] = get_string('commerce_showroom_video_play', 'local_subscriptions');
        $data['videopauselabel'] = get_string('commerce_showroom_video_pause', 'local_subscriptions');
        $data['videoreplaylabel'] = get_string('commerce_showroom_video_replay', 'local_subscriptions');
        $data['videomodal'] = !array_key_exists('modal', $config) || !empty($config['modal']);
        return $data;
    }


    private function apply_problem_interactive(array $data, array $config): array {
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
        return $data;
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
        $data['contenteyebrow'] = $this->text($config, 'eyebrow', $data['contenteyebrow'] ?? '');
        $data['contenttitle'] = $this->text($config, 'title', $data['contenttitle'] ?? '');
        $data['contentdescription'] = $this->text($config, 'text', $data['contentdescription'] ?? '');
        $items = $this->editorial_items($this->text($config, 'items', ''));
        if ($items !== []) {
            $data['contentstats'] = $items;
        }
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
        $titleprefix = $this->text($config, 'title', $data['offersheading'] ?? '');
        $titlehighlight = $this->text($config, 'titlehighlight', '');
        $titlesuffix = $this->text($config, 'titlesuffix', '');
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
        $data['offersdescription'] = $this->text(
            $config,
            'text',
            $data['offersdescription'] ?? ''
        );
        $showsavings = !array_key_exists('showsavings', $config) || !empty($config['showsavings']);
        $showbadges = !array_key_exists('showbadges', $config) || !empty($config['showbadges']);
        $featuredsku = strtoupper($this->text($config, 'featuredsku', ''));
        $featuredrole = $this->text($config, 'featuredrole', 'bundle');
        $order = $this->roles((string)($config['order'] ?? 'pdf,bundle,course'));
        $offers = is_array($data['offers'] ?? null) ? $data['offers'] : [];
        foreach ($offers as &$offer) {
            $role = (string)($offer['role'] ?? '');
            if (in_array($role, ['pdf', 'course', 'bundle'], true)) {
                $rolelabel = $this->text($config, $role . 'rolelabel', '');
                $title = $this->text($config, $role . 'title', '');
                $description = $this->text($config, $role . 'description', '');
                $features = $this->line_items($this->text($config, $role . 'features', ''));

                if ($rolelabel !== '') {
                    $offer['rolelabel'] = $rolelabel;
                }
                if ($title !== '') {
                    $offer['name'] = $title;
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
                if ($role === 'bundle') {
                    $featuredlabel = $this->text($config, 'bundlefeaturedlabel', '');
                    if ($featuredlabel !== '') {
                        $offer['featuredlabel'] = $featuredlabel;
                    }

                    $subtitle = $this->text($config, 'bundlesubtitle', '');
                    $offer['subtitle'] = $subtitle;
                    $offer['hassubtitle'] = $subtitle !== '';

                    // In the J16 editorial hierarchy the Bundle subtitle is the
                    // commercial promise. When it is explicitly configured and
                    // no complementary description is supplied, do not fall back
                    // to the catalogue description underneath it.
                    if ($subtitle !== '' && $description === '') {
                        $offer['shortdescription'] = '';
                    }
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
        $data['offerscardvariant'] = $this->option($config, 'cardvariant', ['classic', 'premium', 'minimal', 'horizontal'], 'classic');
        $data['offerscolumns'] = $this->option($config, 'columns', ['2', '3', '4'], '3');

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

        $rows = $this->comparison_rows_from_config(
            $this->text($config, 'rows', '')
        );
        if ($rows !== []) {
            $data['comparisonrows'] = $rows;
        }

        $order = $this->roles((string)($config['order'] ?? 'pdf,bundle,course'));
        $data['comparisonoffers'] = $this->reorder(
            is_array($data['comparisonoffers'] ?? null) ? $data['comparisonoffers'] : [],
            $order
        );

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
        $data['comparisonswipehint'] = get_string(
            'commerce_showroom_comparison_swipe_hint',
            'local_subscriptions'
        );

        return $data;
    }

    /**
     * One row per line: label|pdf|bundle|course.
     *
     * Cells accept:
     * - ✓ for included;
     * - — or - for not included;
     * - any other value as editorial text.
     *
     * @return array<int,array<string,mixed>>
     */
    private function comparison_rows_from_config(string $value): array {
        $rows = [];

        foreach (preg_split('/\\R/u', trim($value)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = array_map('trim', explode('|', $line, 4));
            if (count($columns) !== 4 || $columns[0] === '') {
                continue;
            }

            $rows[] = [
                'label' => $columns[0],
                'pdf' => $this->comparison_config_cell($columns[1]),
                'bundle' => $this->comparison_config_cell($columns[2]),
                'course' => $this->comparison_config_cell($columns[3]),
            ];
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
        $data['faqeyebrow'] = $this->text(
            $config,
            'eyebrow',
            $data['faqeyebrow'] ?? ''
        );
        $data['faqheading'] = $this->text(
            $config,
            'title',
            $data['faqheading'] ?? ''
        );
        $data['faqdescription'] = $this->text(
            $config,
            'text',
            $data['faqdescription'] ?? ''
        );
        $rows = $this->pipe_rows($this->text($config, 'items', ''), 2);
        if ($rows !== []) {
            $faqs = [];
            foreach ($rows as $index => $row) {
                $faqs[] = [
                    'id' => 'showroom-faq-cms-' . ($index + 1),
                    'question' => $row[0],
                    'answer' => $row[1],
                ];
            }
            $data['faqs'] = $faqs;
        }
        $data['faqsingleopen'] = !array_key_exists('singleopen', $config) || !empty($config['singleopen']);
        return $data;
    }

    private function apply_support(array $data, array $config): array {
        $data['supporttitle'] = $this->text($config, 'title', $data['supporttitle'] ?? '');
        $data['supporttext'] = $this->text($config, 'text', $data['supporttext'] ?? '');
        $data['supportcta'] = $this->text($config, 'buttonlabel', $data['supportcta'] ?? '');

        $data['supporttelegramurl'] = trim((string)($config['telegramurl'] ?? ''));
        $data['hassupporttelegram'] = $data['supporttelegramurl'] !== '';
        $data['supportwhatsappurl'] = trim((string)($config['whatsappurl'] ?? ''));
        $data['hassupportwhatsapp'] = $data['supportwhatsappurl'] !== '';

        return $data;
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
        $data['finaleyebrow'] = $this->text($config, 'eyebrow', $data['finaleyebrow'] ?? '');
        $data['finaltitle'] = $this->text($config, 'title', $data['finaltitle'] ?? '');
        $data['finaltext'] = $this->text($config, 'text', $data['finaltext'] ?? '');
        $data['finalcta'] = $this->text($config, 'buttonlabel', $data['finalcta'] ?? '');
        $data['finalactionurl'] = $this->text($config, 'buttontarget', $data['finalactionurl'] ?? '');
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
            ['default', 'white', 'light', 'soft', 'campuspink', 'dark', 'gradient', 'custom', 'image'],
            $backgroundfallback
        );
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
            . ' commerce-showroom-spacing--' . $spacing
            . ' commerce-showroom-animation--' . $animation;
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

    /** @return string[] */
    private function roles(string $source): array {
        $roles = [];
        foreach (explode(',', strtolower($source)) as $role) {
            $role = trim($role);
            if (in_array($role, ['pdf', 'bundle', 'course'], true)) {
                $roles[$role] = $role;
            }
        }
        foreach (['pdf', 'bundle', 'course'] as $role) {
            $roles[$role] ??= $role;
        }
        return array_values($roles);
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
