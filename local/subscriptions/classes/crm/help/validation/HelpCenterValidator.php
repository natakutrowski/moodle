<?php

namespace local_subscriptions\crm\help\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\HelpArticle;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\help\HelpRegistry;
use local_subscriptions\crm\help\guides\HelpGuide;
use local_subscriptions\crm\help\guides\HelpGuideRegistry;
use local_subscriptions\crm\help\onboarding\HelpOnboardingRegistry;
use local_subscriptions\crm\help\onboarding\HelpOnboardingStep;
use moodle_url;

final class HelpCenterValidator {

    private const LANGUAGES = [
        'fr',
        'en',
        'ru',
    ];

    public function __construct(
        private readonly HelpRegistry $registry =
            new HelpRegistry(),

        private readonly HelpGuideRegistry $guideregistry =
            new HelpGuideRegistry(),

        private readonly HelpOnboardingRegistry $onboardingregistry =
            new HelpOnboardingRegistry()
    ) {
    }

    public function validate(): HelpValidationResult {
        $result = new HelpValidationResult();

        $this->validate_help_directory($result);
        $this->validate_language_files($result);
        $this->validate_categories($result);
        $this->validate_articles($result);
        $this->validate_guides($result);
        $this->validate_onboarding($result);

        return $result;
    }

    private function validate_help_directory(
        HelpValidationResult $result
    ): void {
        $directory = subscription_config::help_content_dir();

        if (!is_dir($directory)) {
            $result->add_error(
                'Help content directory does not exist: ' .
                $directory
            );

            return;
        }

        if (!is_readable($directory)) {
            $result->add_error(
                'Help content directory is not readable: ' .
                $directory
            );

            return;
        }

        $result->add_success(
            'Help content directory is available.'
        );

        foreach (self::LANGUAGES as $language) {
            $languagedirectory = $directory . $language;

            if (!is_dir($languagedirectory)) {
                $result->add_error(
                    'Missing help language directory: ' .
                    $languagedirectory
                );

                continue;
            }

            if (!is_readable($languagedirectory)) {
                $result->add_error(
                    'Help language directory is not readable: ' .
                    $languagedirectory
                );

                continue;
            }

            $result->add_success(
                'Help language directory is available: ' .
                $language
            );
        }
    }

    private function validate_language_files(
        HelpValidationResult $result
    ): void {
        $languagestrings = [];

        foreach (self::LANGUAGES as $language) {
            $strings =
                $this->load_language_strings(
                    $language
                );

            if ($strings === null) {
                $result->add_error(
                    'Unable to load language file: ' .
                    $language
                );

                continue;
            }

            $languagestrings[$language] =
                $strings;

            $result->add_success(
                sprintf(
                    '%d language strings loaded for %s.',
                    count($strings),
                    $language
                )
            );
        }

        if (
            !isset(
                $languagestrings['en']
            )
        ) {
            return;
        }

        $referencekeys = array_keys(
            $languagestrings['en']
        );

        sort($referencekeys);

        foreach (
            ['fr', 'ru']
            as $language
        ) {
            if (
                !isset(
                    $languagestrings[$language]
                )
            ) {
                continue;
            }

            $translatedkeys = array_keys(
                $languagestrings[$language]
            );

            $missing = array_values(
                array_diff(
                    $referencekeys,
                    $translatedkeys
                )
            );

            $extra = array_values(
                array_diff(
                    $translatedkeys,
                    $referencekeys
                )
            );

            sort($missing);
            sort($extra);

            foreach ($missing as $key) {
                $result->add_error(
                    sprintf(
                        'Missing language string in %s: %s',
                        $language,
                        $key
                    )
                );
            }

            foreach ($extra as $key) {
                $result->add_warning(
                    sprintf(
                        'Language string exists in %s but not in en: %s',
                        $language,
                        $key
                    )
                );
            }

            if (!$missing && !$extra) {
                $result->add_success(
                    sprintf(
                        'Language file %s matches the English key set.',
                        $language
                    )
                );
            }
        }
    }

    private function load_language_strings(
        string $language
    ): ?array {
        if (
            !in_array(
                $language,
                self::LANGUAGES,
                true
            )
        ) {
            return null;
        }

        $filepath =
            subscription_config::plugin_dir() .
            'lang/' .
            $language .
            '/local_subscriptions.php';

        if (
            !is_file($filepath) ||
            !is_readable($filepath)
        ) {
            return null;
        }

        $loader =
            static function(
                string $languagefile
            ): array {
                $string = [];

                include($languagefile);

                return $string;
            };

        return $loader($filepath);
    }

    private function validate_categories(
        HelpValidationResult $result
    ): void {
        $categories = $this->registry->categories();

        if (!$categories) {
            $result->add_error(
                'The help registry contains no category.'
            );

            return;
        }

        $ids = [];

        foreach ($categories as $category) {
            if ($category->id === '') {
                $result->add_error(
                    'A help category has an empty identifier.'
                );

                continue;
            }

            if (isset($ids[$category->id])) {
                $result->add_error(
                    'Duplicate help category identifier: ' .
                    $category->id
                );
            }

            $ids[$category->id] = true;

            if ($category->title === '') {
                $result->add_error(
                    'Help category has an empty title: ' .
                    $category->id
                );
            }

            if ($category->description === '') {
                $result->add_warning(
                    'Help category has no description: ' .
                    $category->id
                );
            }
        }

        $result->add_success(
            count($categories) .
            ' help categories validated.'
        );
    }

    private function validate_articles(
        HelpValidationResult $result
    ): void {
        $articles = $this->registry->articles();

        if (!$articles) {
            $result->add_error(
                'The help registry contains no article.'
            );

            return;
        }

        $ids = [];

        foreach ($articles as $article) {
            $this->validate_article(
                $article,
                $ids,
                $result
            );
        }

        $this->validate_unregistered_markdown_files(
            $articles,
            $result
        );

        $result->add_success(
            count($articles) .
            ' help articles validated.'
        );
    }

    private function validate_article(
        HelpArticle $article,
        array &$ids,
        HelpValidationResult $result
    ): void {
        if ($article->id === '') {
            $result->add_error(
                'A help article has an empty identifier.'
            );

            return;
        }

        if (isset($ids[$article->id])) {
            $result->add_error(
                'Duplicate help article identifier: ' .
                $article->id
            );
        }

        $ids[$article->id] = true;

        if (
            $this->registry->get_category(
                $article->categoryid
            ) === null
        ) {
            $result->add_error(
                'Article "' .
                $article->id .
                '" references unknown category "' .
                $article->categoryid .
                '".'
            );
        }

        if ($article->title === '') {
            $result->add_error(
                'Article has an empty title: ' .
                $article->id
            );
        }

        if ($article->summary === '') {
            $result->add_warning(
                'Article has no summary: ' .
                $article->id
            );
        }

        if (
            preg_match(
                '/\A[a-z0-9][a-z0-9_-]*\.md\z/',
                $article->contentfile
            ) !== 1
        ) {
            $result->add_error(
                'Article "' .
                $article->id .
                '" has an invalid content filename: ' .
                $article->contentfile
            );
        }

        foreach ($article->contexts as $context) {
            if (
                !in_array(
                    $context,
                    HelpContext::allowed(),
                    true
                )
            ) {
                $result->add_error(
                    'Article "' .
                    $article->id .
                    '" references invalid context "' .
                    $context .
                    '".'
                );
            }
        }

        if (!$article->contexts) {
            $result->add_warning(
                'Article has no contextual assignment: ' .
                $article->id
            );
        }

        if (!$article->keywords) {
            $result->add_warning(
                'Article has no search keyword: ' .
                $article->id
            );
        }

        foreach (self::LANGUAGES as $language) {
            $filepath =
                subscription_config::help_content_dir() .
                $language .
                DIRECTORY_SEPARATOR .
                $article->contentfile;

            if (!is_file($filepath)) {
                $result->add_error(
                    'Missing Markdown file for article "' .
                    $article->id .
                    '" in language "' .
                    $language .
                    '": ' .
                    $filepath
                );

                continue;
            }

            if (!is_readable($filepath)) {
                $result->add_error(
                    'Unreadable Markdown file: ' .
                    $filepath
                );

                continue;
            }

            $content = file_get_contents($filepath);

            if ($content === false) {
                $result->add_error(
                    'Unable to read Markdown file: ' .
                    $filepath
                );

                continue;
            }

            if (trim($content) === '') {
                $result->add_error(
                    'Empty Markdown file: ' .
                    $filepath
                );

                continue;
            }

            if (
                preg_match(
                    '/^\s*#\s+\S+/m',
                    $content
                ) !== 1
            ) {
                $result->add_warning(
                    'Markdown file has no level-one heading: ' .
                    $filepath
                );
            }

            $this->validate_markdown_links(
                $article,
                $language,
                $content,
                $result
            );
            
        }
    }

    private function validate_markdown_links(
        HelpArticle $article,
        string $language,
        string $content,
        HelpValidationResult $result
    ): void {
        $matches = [];

        preg_match_all(
            '/\[[^\]]+\]\(([^)]+)\)/',
            $content,
            $matches
        );

        foreach (
            $matches[1] ?? []
            as $target
        ) {
            $target = trim(
                (string)$target
            );

            if (
                $target === '' ||
                str_starts_with($target, '#') ||
                preg_match(
                    '/\A(?:https?:|mailto:|tel:)/i',
                    $target
                ) === 1
            ) {
                continue;
            }

            $targetpath = parse_url(
                $target,
                PHP_URL_PATH
            );

            if (
                !is_string($targetpath) ||
                $targetpath === ''
            ) {
                continue;
            }

            if (
                str_ends_with(
                    $targetpath,
                    '.md'
                )
            ) {
                $filename =
                    basename($targetpath);

                $filepath =
                    subscription_config::
                        help_content_dir() .
                    $language .
                    DIRECTORY_SEPARATOR .
                    $filename;

                if (!is_file($filepath)) {
                    $result->add_error(
                        sprintf(
                            'Article "%s" in %s references missing Markdown file "%s".',
                            $article->id,
                            $language,
                            $target
                        )
                    );
                }

                continue;
            }

            if (
                str_starts_with(
                    $targetpath,
                    '/local/subscriptions/'
                )
            ) {
                $this->validate_internal_url(
                    new moodle_url(
                        $targetpath
                    ),
                    sprintf(
                        'Article %s (%s)',
                        $article->id,
                        $language
                    ),
                    $result
                );
            }
        }
    }
    private function validate_unregistered_markdown_files(
        array $articles,
        HelpValidationResult $result
    ): void {
        $registered = [];

        foreach ($articles as $article) {
            $registered[$article->contentfile] = true;
        }

        foreach (self::LANGUAGES as $language) {
            $directory =
                subscription_config::help_content_dir() .
                $language;

            if (!is_dir($directory)) {
                continue;
            }

            $files = glob(
                $directory .
                DIRECTORY_SEPARATOR .
                '*.md'
            );

            if ($files === false) {
                continue;
            }

            foreach ($files as $filepath) {
                $filename = basename($filepath);

                if (!isset($registered[$filename])) {
                    $result->add_warning(
                        'Unregistered Markdown file: ' .
                        $filepath
                    );
                }
            }
        }
    }

    private function validate_guides(
        HelpValidationResult $result
    ): void {
        $guides = $this->guideregistry->guides();

        if (!$guides) {
            $result->add_warning(
                'The Help Center contains no practical guide.'
            );

            return;
        }

        $guideids = [];

        foreach ($guides as $guide) {
            $this->validate_guide(
                $guide,
                $guideids,
                $result
            );
        }

        $result->add_success(
            count($guides) .
            ' practical guides validated.'
        );
    }

    private function validate_guide(
        HelpGuide $guide,
        array &$guideids,
        HelpValidationResult $result
    ): void {
        if ($guide->id === '') {
            $result->add_error(
                'A practical guide has an empty identifier.'
            );

            return;
        }

        if (isset($guideids[$guide->id])) {
            $result->add_error(
                'Duplicate practical guide identifier: ' .
                $guide->id
            );
        }

        $guideids[$guide->id] = true;

        if ($guide->title === '') {
            $result->add_error(
                'Guide has an empty title: ' .
                $guide->id
            );
        }

        if ($guide->description === '') {
            $result->add_warning(
                'Guide has no description: ' .
                $guide->id
            );
        }

        if (!$guide->steps) {
            $result->add_error(
                'Guide has no step: ' .
                $guide->id
            );

            return;
        }

        foreach ($guide->contexts as $context) {
            if (
                !in_array(
                    $context,
                    HelpContext::allowed(),
                    true
                )
            ) {
                $result->add_error(
                    'Guide "' .
                    $guide->id .
                    '" references invalid context "' .
                    $context .
                    '".'
                );
            }
        }

        $stepids = [];

        foreach ($guide->steps as $step) {
            if ($step->id === '') {
                $result->add_error(
                    'Guide "' .
                    $guide->id .
                    '" contains a step with no identifier.'
                );

                continue;
            }

            if (isset($stepids[$step->id])) {
                $result->add_error(
                    'Guide "' .
                    $guide->id .
                    '" contains duplicate step "' .
                    $step->id .
                    '".'
                );
            }

            $stepids[$step->id] = true;

            if ($step->title === '') {
                $result->add_error(
                    'Guide step has an empty title: ' .
                    $guide->id .
                    '/' .
                    $step->id
                );
            }

            if (
                ($step->url === null) !==
                ($step->actionlabel === null)
            ) {
                $result->add_warning(
                    'Guide step has an incomplete action: ' .
                    $guide->id .
                    '/' .
                    $step->id
                );
            }

            if ($step->url !== null) {
                $this->validate_internal_url(
                    $step->url,
                    'Guide step ' .
                        $guide->id .
                        '/' .
                        $step->id,
                    $result
                );
            }
        }
    }

    private function validate_onboarding(
        HelpValidationResult $result
    ): void {
        $steps =
            $this->onboardingregistry
                ->steps();

        if (!$steps) {
            $result->add_warning(
                'The Help Center contains no onboarding step.'
            );

            return;
        }

        $ids = [];

        foreach ($steps as $step) {
            if (
                !$step instanceof
                HelpOnboardingStep
            ) {
                $result->add_error(
                    'Invalid onboarding step object.'
                );

                continue;
            }

            if ($step->id === '') {
                $result->add_error(
                    'An onboarding step has an empty identifier.'
                );

                continue;
            }

            if (isset($ids[$step->id])) {
                $result->add_error(
                    'Duplicate onboarding step identifier: ' .
                    $step->id
                );
            }

            $ids[$step->id] = true;

            if ($step->title === '') {
                $result->add_error(
                    'Onboarding step has an empty title: ' .
                    $step->id
                );
            }

            if ($step->description === '') {
                $result->add_warning(
                    'Onboarding step has no description: ' .
                    $step->id
                );
            }

            $this->validate_internal_url(
                $step->url,
                'Onboarding step ' .
                    $step->id,
                $result
            );
        }

        $result->add_success(
            count($steps) .
            ' onboarding steps validated.'
        );
    }

    private function validate_internal_url(
        moodle_url $url,
        string $source,
        HelpValidationResult $result
    ): void {
        global $CFG;

        $path = $url->get_path();

        if (
            $path === '' ||
            !str_starts_with(
                $path,
                '/local/subscriptions/'
            )
        ) {
            return;
        }

        $relativepath = ltrim(
            $path,
            '/'
        );

        $filepath =
            $CFG->dirroot .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativepath
            );

        if (!is_file($filepath)) {
            $result->add_error(
                $source .
                ' references a missing page: ' .
                $path
            );

            return;
        }

        $articlepath =
            new moodle_url(
                subscription_config::
                    admin_help_article_page()
            );

        if (
            $path ===
            $articlepath->get_path()
        ) {
            $params = $url->params();

            $articleid = clean_param(
                (string)(
                    $params['id']
                    ?? ''
                ),
                PARAM_ALPHANUMEXT
            );

            if (
                $articleid === '' ||
                $this->registry
                    ->get_article(
                        $articleid
                    ) === null
            ) {
                $result->add_error(
                    $source .
                    ' references an unknown Help Center article: ' .
                    (
                        $articleid !== ''
                            ? $articleid
                            : '[empty]'
                    )
                );
            }
        }
    }

}