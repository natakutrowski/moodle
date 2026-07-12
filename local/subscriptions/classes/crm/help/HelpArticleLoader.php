<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;
use core_text;

final class HelpArticleLoader {

    private const SUPPORTED_LANGUAGES = [
        'fr',
        'en',
        'ru',
    ];

    public function load(
        HelpArticle $article,
        ?string $language = null
    ): string {
        $language = $this->normalize_language(
            $language ?? current_language()
        );

        foreach ($this->language_candidates($language) as $candidate) {
            $filepath = $this->resolve_file(
                $candidate,
                $article->contentfile
            );

            if ($filepath !== null) {
                $content = file_get_contents($filepath);

                if ($content === false) {
                    throw new \moodle_exception(
                        'crm_help_article_read_error',
                        'local_subscriptions'
                    );
                }

                return $content;
            }
        }

        throw new \moodle_exception(
            'crm_help_article_content_missing',
            'local_subscriptions',
            '',
            $article->id
        );
    }

    public function render(
        HelpArticle $article,
        ?string $language = null
    ): string {
        $markdown = $this->load($article, $language);

        return format_text(
            $markdown,
            FORMAT_MARKDOWN,
            [
                'context' => \context_system::instance(),
                'filter' => false,
                'overflowdiv' => false,
            ]
        );
    }

    private function normalize_language(string $language): string {
        $language = clean_param(
            core_text::strtolower(trim($language)),
            PARAM_LANG
        );

        $base = preg_split('/[_-]/', $language)[0] ?? '';

        if (in_array($base, self::SUPPORTED_LANGUAGES, true)) {
            return $base;
        }

        return 'en';
    }

    private function language_candidates(string $language): array {
        return array_values(array_unique([
            $language,
            'en',
            'fr',
        ]));
    }

    private function resolve_file(
        string $language,
        string $filename
    ): ?string {
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            return null;
        }

        if (!preg_match(
            '/\A[a-z0-9][a-z0-9_-]*\.md\z/',
            $filename
        )) {
            throw new \coding_exception(
                'Invalid CRM help article filename: ' . $filename
            );
        }

        $basedir = realpath(
            subscription_config::help_content_dir()
        );

        if ($basedir === false) {
            throw new \moodle_exception(
                'crm_help_content_directory_missing',
                'local_subscriptions'
            );
        }

        $filepath = $basedir .
            DIRECTORY_SEPARATOR .
            $language .
            DIRECTORY_SEPARATOR .
            $filename;

        if (!is_file($filepath) || !is_readable($filepath)) {
            return null;
        }

        $realpath = realpath($filepath);

        if (
            $realpath === false ||
            !str_starts_with(
                $realpath,
                $basedir . DIRECTORY_SEPARATOR
            )
        ) {
            throw new \coding_exception(
                'CRM help article path is outside the help directory.'
            );
        }

        return $realpath;
    }
}