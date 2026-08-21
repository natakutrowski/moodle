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
                get_string(
                    'crm_help_diag_msg_content_dir_missing_n1210c',
                    'local_subscriptions',
                    $directory
                )
            );

            return;
        }

        if (!is_readable($directory)) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_content_dir_unreadable_n1210c',
                    'local_subscriptions',
                    $directory
                )
            );

            return;
        }

        $result->add_success(
            get_string(
                'crm_help_diag_msg_content_dir_ok_n1210c',
                'local_subscriptions'
            )
        );

        foreach (self::LANGUAGES as $language) {
            $languagedirectory = $directory . $language;

            if (!is_dir($languagedirectory)) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_language_dir_missing_n1210c',
                        'local_subscriptions',
                        $languagedirectory
                    )
                );

                continue;
            }

            if (!is_readable($languagedirectory)) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_language_dir_unreadable_n1210c',
                        'local_subscriptions',
                        $languagedirectory
                    )
                );

                continue;
            }

            $result->add_success(
                get_string(
                    'crm_help_diag_msg_language_dir_ok_n1210c',
                    'local_subscriptions',
                    $language
                )
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
                    get_string(
                        'crm_help_diag_msg_language_file_unloadable_n1210c',
                        'local_subscriptions',
                        $language
                    )
                );

                continue;
            }

            $languagestrings[$language] =
                $strings;

            $result->add_success(
                get_string(
                    'crm_help_diag_msg_language_strings_loaded_n1210c',
                    'local_subscriptions',
                    (object)[
                        'count' => count($strings),
                        'language' => $language,
                    ]
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
                    get_string(
                        'crm_help_diag_msg_language_key_missing_n1210c',
                        'local_subscriptions',
                        (object)[
                            'language' => $language,
                            'key' => $key,
                        ]
                    )
                );
            }

            foreach ($extra as $key) {
                $result->add_warning(
                    get_string(
                        'crm_help_diag_msg_language_key_extra_n1210c',
                        'local_subscriptions',
                        (object)[
                            'language' => $language,
                            'key' => $key,
                        ]
                    )
                );
            }

            if (!$missing && !$extra) {
                $result->add_success(
                    get_string(
                        'crm_help_diag_msg_language_keys_match_n1210c',
                        'local_subscriptions',
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
                get_string(
                    'crm_help_diag_msg_no_category_n1210c',
                    'local_subscriptions'
                )
            );

            return;
        }

        $ids = [];

        foreach ($categories as $category) {
            if ($category->id === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_category_empty_id_n1210c',
                        'local_subscriptions'
                    )
                );

                continue;
            }

            if (isset($ids[$category->id])) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_category_duplicate_id_n1210c',
                        'local_subscriptions',
                        $category->id
                    )
                );
            }

            $ids[$category->id] = true;

            if ($category->title === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_category_empty_title_n1210c',
                        'local_subscriptions',
                        $category->id
                    )
                );
            }

            if ($category->description === '') {
                $result->add_warning(
                    get_string(
                        'crm_help_diag_msg_category_no_description_n1210c',
                        'local_subscriptions',
                        $category->id
                    )
                );
            }
        }

        $result->add_success(
            get_string(
                'crm_help_diag_msg_categories_validated_n1210c',
                'local_subscriptions',
                count($categories)
            )
        );
    }

    private function validate_articles(
        HelpValidationResult $result
    ): void {
        $articles = $this->registry->articles();

        if (!$articles) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_no_article_n1210c',
                    'local_subscriptions'
                )
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
            get_string(
                'crm_help_diag_msg_articles_validated_n1210c',
                'local_subscriptions',
                count($articles)
            )
        );
    }

    private function validate_article(
        HelpArticle $article,
        array &$ids,
        HelpValidationResult $result
    ): void {
        if ($article->id === '') {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_article_empty_id_n1210c',
                    'local_subscriptions'
                )
            );

            return;
        }

        if (isset($ids[$article->id])) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_article_duplicate_id_n1210c',
                    'local_subscriptions',
                    $article->id
                )
            );
        }

        $ids[$article->id] = true;

        if (
            $this->registry->get_category(
                $article->categoryid
            ) === null
        ) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_article_unknown_category_n1210c',
                    'local_subscriptions',
                    (object)[
                        'article' => $article->id,
                        'category' => $article->categoryid,
                    ]
                )
            );
        }

        if ($article->title === '') {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_article_empty_title_n1210c',
                    'local_subscriptions',
                    $article->id
                )
            );
        }

        if ($article->summary === '') {
            $result->add_warning(
                get_string(
                    'crm_help_diag_msg_article_no_summary_n1210c',
                    'local_subscriptions',
                    $article->id
                )
            );
        }

        if (
            preg_match(
                '/\A[a-z0-9][a-z0-9_-]*\.md\z/',
                $article->contentfile
            ) !== 1
        ) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_article_invalid_filename_n1210c',
                    'local_subscriptions',
                    (object)[
                        'article' => $article->id,
                        'file' => $article->contentfile,
                    ]
                )
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
                    get_string(
                        'crm_help_diag_msg_article_invalid_context_n1210c',
                        'local_subscriptions',
                        (object)[
                            'article' => $article->id,
                            'context' => $context,
                        ]
                    )
                );
            }
        }

        if (!$article->contexts) {
            $result->add_warning(
                get_string(
                    'crm_help_diag_msg_article_no_context_n1210c',
                    'local_subscriptions',
                    $article->id
                )
            );
        }

        if (!$article->keywords) {
            $result->add_warning(
                get_string(
                    'crm_help_diag_msg_article_no_keyword_n1210c',
                    'local_subscriptions',
                    $article->id
                )
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
                    get_string(
                        'crm_help_diag_msg_markdown_missing_n1210c',
                        'local_subscriptions',
                        (object)[
                            'article' => $article->id,
                            'language' => $language,
                            'file' => $filepath,
                        ]
                    )
                );

                continue;
            }

            if (!is_readable($filepath)) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_markdown_unreadable_n1210c',
                        'local_subscriptions',
                        $filepath
                    )
                );

                continue;
            }

            $content = file_get_contents($filepath);

            if ($content === false) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_markdown_read_failed_n1210c',
                        'local_subscriptions',
                        $filepath
                    )
                );

                continue;
            }

            if (trim($content) === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_markdown_empty_n1210c',
                        'local_subscriptions',
                        $filepath
                    )
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
                    get_string(
                        'crm_help_diag_msg_markdown_no_h1_n1210c',
                        'local_subscriptions',
                        $filepath
                    )
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
                        get_string(
                            'crm_help_diag_msg_markdown_link_missing_n1210c',
                            'local_subscriptions',
                            (object)[
                                'article' => $article->id,
                                'language' => $language,
                                'target' => $target,
                            ]
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
                    get_string(
                        'crm_help_diag_source_article_n1210c',
                        'local_subscriptions',
                        (object)[
                            'article' => $article->id,
                            'language' => $language,
                        ]
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
                        get_string(
                            'crm_help_diag_msg_markdown_unregistered_n1210c',
                            'local_subscriptions',
                            $filepath
                        )
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
                get_string(
                    'crm_help_diag_msg_no_guide_n1210c',
                    'local_subscriptions'
                )
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
            get_string(
                'crm_help_diag_msg_guides_validated_n1210c',
                'local_subscriptions',
                count($guides)
            )
        );
    }

    private function validate_guide(
        HelpGuide $guide,
        array &$guideids,
        HelpValidationResult $result
    ): void {
        if ($guide->id === '') {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_guide_empty_id_n1210c',
                    'local_subscriptions'
                )
            );

            return;
        }

        if (isset($guideids[$guide->id])) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_guide_duplicate_id_n1210c',
                    'local_subscriptions',
                    $guide->id
                )
            );
        }

        $guideids[$guide->id] = true;

        if ($guide->title === '') {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_guide_empty_title_n1210c',
                    'local_subscriptions',
                    $guide->id
                )
            );
        }

        if ($guide->description === '') {
            $result->add_warning(
                get_string(
                    'crm_help_diag_msg_guide_no_description_n1210c',
                    'local_subscriptions',
                    $guide->id
                )
            );
        }

        if (!$guide->steps) {
            $result->add_error(
                get_string(
                    'crm_help_diag_msg_guide_no_step_n1210c',
                    'local_subscriptions',
                    $guide->id
                )
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
                    get_string(
                        'crm_help_diag_msg_guide_invalid_context_n1210c',
                        'local_subscriptions',
                        (object)[
                            'guide' => $guide->id,
                            'context' => $context,
                        ]
                    )
                );
            }
        }

        $stepids = [];

        foreach ($guide->steps as $step) {
            if ($step->id === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_guide_step_empty_id_n1210c',
                        'local_subscriptions',
                        $guide->id
                    )
                );

                continue;
            }

            if (isset($stepids[$step->id])) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_guide_step_duplicate_n1210c',
                        'local_subscriptions',
                        (object)[
                            'guide' => $guide->id,
                            'step' => $step->id,
                        ]
                    )
                );
            }

            $stepids[$step->id] = true;

            if ($step->title === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_guide_step_empty_title_n1210c',
                        'local_subscriptions',
                        (object)[
                            'guide' => $guide->id,
                            'step' => $step->id,
                        ]
                    )
                );
            }

            if (
                ($step->url === null) !==
                ($step->actionlabel === null)
            ) {
                $result->add_warning(
                    get_string(
                        'crm_help_diag_msg_guide_step_incomplete_action_n1210c',
                        'local_subscriptions',
                        (object)[
                            'guide' => $guide->id,
                            'step' => $step->id,
                        ]
                    )
                );
            }

            if ($step->url !== null) {
                $this->validate_internal_url(
                    $step->url,
                    get_string(
                        'crm_help_diag_source_guide_step_n1210c',
                        'local_subscriptions',
                        (object)[
                            'guide' => $guide->id,
                            'step' => $step->id,
                        ]
                    ),
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
                get_string(
                    'crm_help_diag_msg_no_onboarding_n1210c',
                    'local_subscriptions'
                )
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
                    get_string(
                        'crm_help_diag_msg_onboarding_invalid_object_n1210c',
                        'local_subscriptions'
                    )
                );

                continue;
            }

            if ($step->id === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_onboarding_empty_id_n1210c',
                        'local_subscriptions'
                    )
                );

                continue;
            }

            if (isset($ids[$step->id])) {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_onboarding_duplicate_id_n1210c',
                        'local_subscriptions',
                        $step->id
                    )
                );
            }

            $ids[$step->id] = true;

            if ($step->title === '') {
                $result->add_error(
                    get_string(
                        'crm_help_diag_msg_onboarding_empty_title_n1210c',
                        'local_subscriptions',
                        $step->id
                    )
                );
            }

            if ($step->description === '') {
                $result->add_warning(
                    get_string(
                        'crm_help_diag_msg_onboarding_no_description_n1210c',
                        'local_subscriptions',
                        $step->id
                    )
                );
            }

            $this->validate_internal_url(
                $step->url,
                get_string(
                    'crm_help_diag_source_onboarding_step_n1210c',
                    'local_subscriptions',
                    $step->id
                ),
                $result
            );
        }

        $result->add_success(
            get_string(
                'crm_help_diag_msg_onboarding_validated_n1210c',
                'local_subscriptions',
                count($steps)
            )
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
                get_string(
                    'crm_help_diag_msg_missing_page_n1210c',
                    'local_subscriptions',
                    (object)[
                        'source' => $source,
                        'path' => $path,
                    ]
                )
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
                    get_string(
                        'crm_help_diag_msg_unknown_help_article_n1210c',
                        'local_subscriptions',
                        (object)[
                            'source' => $source,
                            'article' =>
                                $articleid !== ''
                                    ? $articleid
                                    : get_string(
                                        'crm_help_diag_empty_value_n1210c',
                                        'local_subscriptions'
                                    ),
                        ]
                    )
                );
            }
        }
    }

}