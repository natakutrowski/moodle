<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;

final class HelpRenderer {

    public static function render_home(
        HelpRegistry $registry,
        array $articles,
        string $query = '',
        string $categoryid = ''
    ): string {
        $out = html_writer::start_div('crm-help-center');

        $out .= self::render_hero($query);

        if ($query !== '') {
            $out .= self::render_search_results(
                $articles,
                $query
            );
        } else if ($categoryid !== '') {
            $category = $registry->get_category($categoryid);

            if ($category !== null) {
                $out .= self::render_category(
                    $category,
                    $articles
                );
            }
        } else {
            $out .= self::render_categories($registry);
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_hero(string $query): string {
        $out = html_writer::start_div('crm-help-hero');

        $out .= html_writer::div('💡', 'crm-help-hero-icon');

        $out .= html_writer::tag(
            'h2',
            get_string('crm_help_title', 'local_subscriptions'),
            ['class' => 'crm-help-hero-title']
        );

        $out .= html_writer::div(
            get_string('crm_help_subtitle', 'local_subscriptions'),
            'crm-help-hero-subtitle'
        );

        $out .= html_writer::start_tag('form', [
            'method' => 'get',
            'action' => new moodle_url(subscription_config::admin_help_page()),
            'class' => 'crm-help-search',
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'search',
            'name' => 'q',
            'value' => $query,
            'class' => 'form-control crm-help-search-input',
            'placeholder' => get_string(
                'crm_help_search_placeholder',
                'local_subscriptions'
            ),
        ]);

        $out .= html_writer::tag(
            'button',
            get_string('search'),
            [
                'type' => 'submit',
                'class' => 'btn btn-primary crm-help-search-button',
            ]
        );

        $out .= html_writer::end_tag('form');
        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_categories(
        HelpRegistry $registry
    ): string {
        $out = html_writer::start_div(
            'crm-help-category-grid'
        );

        foreach ($registry->categories() as $category) {
            $articles = $registry->articles_by_category(
                $category->id
            );

            $content =
                html_writer::div(
                    $category->icon,
                    'crm-help-category-icon'
                ) .
                html_writer::tag(
                    'h3',
                    s($category->title),
                    [
                        'class' => 'crm-help-category-title',
                    ]
                ) .
                html_writer::div(
                    s($category->description),
                    'crm-help-category-description'
                ) .
                html_writer::div(
                    get_string(
                        'crm_help_article_count',
                        'local_subscriptions',
                        count($articles)
                    ),
                    'crm-help-category-count'
                );

            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::admin_help_page(),
                    [
                        'category' => $category->id,
                    ]
                ),
                $content,
                [
                    'class' => 'crm-help-category-card',
                ]
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_category(
        HelpCategory $category,
        array $articles
    ): string {
        $out = html_writer::start_div(
            'crm-help-category-view'
        );

        $out .= html_writer::link(
            new moodle_url(
                subscription_config::admin_help_page()
            ),
            '← ' . get_string(
                'crm_help_all_categories',
                'local_subscriptions'
            ),
            [
                'class' => 'crm-help-back-link',
            ]
        );

        $out .= html_writer::start_div(
            'crm-help-category-view-header'
        );

        $out .= html_writer::div(
            $category->icon,
            'crm-help-category-view-icon'
        );

        $out .= html_writer::start_div();

        $out .= html_writer::tag(
            'h2',
            s($category->title),
            [
                'class' => 'crm-help-category-view-title',
            ]
        );

        $out .= html_writer::div(
            s($category->description),
            'crm-help-category-view-description'
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        if (!$articles) {
            $out .= html_writer::div(
                get_string(
                    'crm_help_category_empty',
                    'local_subscriptions'
                ),
                'crm-help-empty'
            );

            $out .= html_writer::end_div();

            return $out;
        }

        $out .= html_writer::start_div(
            'crm-help-article-list'
        );

        foreach ($articles as $article) {
            $out .= self::render_article_card($article);
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_article_card(
        HelpArticle $article
    ): string {
        $url = new moodle_url(
            subscription_config::admin_help_article_page(),
            [
                'id' => $article->id,
            ]
        );

        return html_writer::link(
            $url,
            html_writer::tag(
                'h3',
                s($article->title),
                [
                    'class' => 'crm-help-result-title',
                ]
            ) .
            html_writer::div(
                s($article->summary),
                'crm-help-result-summary'
            ) .
            html_writer::span(
                get_string(
                    'crm_help_read_article',
                    'local_subscriptions'
                ) . ' →',
                'crm-help-result-link'
            ),
            [
                'class' => 'crm-help-result-card',
            ]
        );
    }

    private static function render_search_results(
        array $articles,
        string $query
    ): string {
        $out = html_writer::start_div('crm-help-results');

        $out .= html_writer::tag(
            'h3',
            get_string(
                'crm_help_search_results',
                'local_subscriptions',
                s($query)
            ),
            ['class' => 'h5 mb-3']
        );

        if (!$articles) {
            $out .= html_writer::div(
                get_string(
                    'crm_help_no_results',
                    'local_subscriptions'
                ),
                'crm-help-empty'
            );

            $out .= html_writer::end_div();

            return $out;
        }

        foreach ($articles as $article) {
            $out .= self::render_article_card($article);
        }

        $out .= html_writer::end_div();

        return $out;
    }

    public static function render_guides(
        array $guides
    ): string {
        if (!$guides) {
            return '';
        }

        $out = html_writer::start_div(
            'crm-help-guides-section'
        );

        $out .= html_writer::tag(
            'h2',
            get_string(
                'crm_help_guides_title',
                'local_subscriptions'
            ),
            [
                'class' => 'crm-help-section-title',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'crm_help_guides_description',
                'local_subscriptions'
            ),
            'crm-help-section-description'
        );

        $out .= \local_subscriptions\crm\help\guides\HelpGuideRenderer::render_cards(
            $guides
        );

        $out .= html_writer::end_div();

        return $out;
    }
}