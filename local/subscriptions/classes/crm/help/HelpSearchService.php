<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

use core_text;

final class HelpSearchService {

    public function __construct(
        private readonly HelpRegistry $registry = new HelpRegistry(),
        private readonly HelpArticleLoader $loader = new HelpArticleLoader()
    ) {
    }

    public function search(string $query): array {
        $query = core_text::strtolower(trim($query));

        if ($query === '') {
            return $this->registry->articles();
        }

        $results = [];

        foreach ($this->registry->articles() as $article) {
            try {
                $content = $this->loader->load($article);
            } catch (\Throwable $e) {
                $content = '';
            }

            $haystack = implode(' ', [
                $article->title,
                $article->summary,
                strip_tags($content),
                implode(' ', $article->keywords),
            ]);

            $haystack = core_text::strtolower($haystack);

            if (str_contains($haystack, $query)) {
                $results[] = $article;
            }
        }

        return $results;
    }
}