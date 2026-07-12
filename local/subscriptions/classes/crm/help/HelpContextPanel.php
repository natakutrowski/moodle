<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\guides\HelpGuide;
use local_subscriptions\crm\help\guides\HelpGuideRegistry;

final class HelpContextPanel {

    public static function render(
        string $context,
        ?HelpRegistry $registry = null,
        ?HelpGuideRegistry $guideregistry = null
    ): string {
        $context = HelpContext::normalize($context);

        $registry ??= new HelpRegistry();
        $guideregistry ??= new HelpGuideRegistry();

        $articles = $registry->articles_by_context(
            $context
        );

        $articles = array_slice(
            $articles,
            0,
            3
        );

        if (!$articles && $context !== HelpContext::GENERAL) {
            $articles = $registry->articles_by_context(
                HelpContext::GENERAL
            );
        }

        $guides = $guideregistry->guides_by_context(
            $context
        );

        $guides = array_slice(
            $guides,
            0,
            2
        );

        return self::render_panel(
            $context,
            $articles,
            $guides
        );
    }

    public static function render_current_page(
        ?HelpRegistry $registry = null,
        ?HelpGuideRegistry $guideregistry = null
    ): string {
        return self::render(
            HelpContextResolver::current(),
            $registry,
            $guideregistry
        );
    }

    private static function render_panel(
        string $context,
        array $articles,
        array $guides
    ): string {
        $out = html_writer::start_tag(
            'details',
            [
                'class' => 'crm-context-help',
                'data-help-context' => $context,
            ]
        );

        $out .= html_writer::start_tag(
            'summary',
            [
                'class' => 'crm-context-help-trigger',
            ]
        );

        $out .= html_writer::span(
            '?',
            'crm-context-help-trigger-icon'
        );

        $out .= html_writer::span(
            get_string(
                'crm_context_help_trigger',
                'local_subscriptions'
            ),
            'crm-context-help-trigger-label'
        );

        $out .= html_writer::end_tag('summary');

        $out .= html_writer::start_div(
            'crm-context-help-panel'
        );

        $out .= html_writer::start_div(
            'crm-context-help-header'
        );

        $out .= html_writer::start_div();

        $out .= html_writer::tag(
            'h3',
            get_string(
                'crm_context_help_title',
                'local_subscriptions'
            ),
            [
                'class' => 'crm-context-help-title',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'crm_context_help_description',
                'local_subscriptions'
            ),
            'crm-context-help-description'
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        if (!$articles && !$guides) {
            $out .= html_writer::div(
                get_string(
                    'crm_context_help_empty',
                    'local_subscriptions'
                ),
                'crm-context-help-empty'
            );
        } else {
            if ($articles) {
                $out .= html_writer::div(
                    get_string(
                        'crm_context_help_articles_title',
                        'local_subscriptions'
                    ),
                    'crm-context-help-section-title'
                );

                $out .= html_writer::start_div(
                    'crm-context-help-articles'
                );

                foreach ($articles as $article) {
                    $out .= self::render_article(
                        $article
                    );
                }

                $out .= html_writer::end_div();
            }

            if ($guides) {
                $out .= html_writer::div(
                    get_string(
                        'crm_context_help_guides_title',
                        'local_subscriptions'
                    ),
                    'crm-context-help-section-title crm-context-help-guides-heading'
                );

                $out .= html_writer::start_div(
                    'crm-context-help-guides'
                );

                foreach ($guides as $guide) {
                    $out .= self::render_guide(
                        $guide
                    );
                }

                $out .= html_writer::end_div();
            }
        }

        $out .= html_writer::link(
            new moodle_url(
                subscription_config::admin_help_page()
            ),
            get_string(
                'crm_context_help_open_center',
                'local_subscriptions'
            ) . ' →',
            [
                'class' => 'crm-context-help-center-link',
            ]
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('details');

        return $out;
    }

    private static function render_guide(
        HelpGuide $guide
    ): string {
        $url = new moodle_url(
            subscription_config::admin_help_guide_page(),
            [
                'id' => $guide->id,
            ]
        );

        return html_writer::link(
            $url,
            html_writer::div(
                $guide->icon,
                'crm-context-help-guide-icon'
            ) .
            html_writer::start_div(
                'crm-context-help-guide-content'
            ) .
            html_writer::tag(
                'h4',
                s($guide->title),
                [
                    'class' =>
                        'crm-context-help-guide-title',
                ]
            ) .
            html_writer::div(
                s($guide->description),
                'crm-context-help-guide-description'
            ) .
            html_writer::end_div(),
            [
                'class' => 'crm-context-help-guide',
            ]
        );
    }

    private static function render_article(
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
                'h4',
                s($article->title),
                [
                    'class' =>
                        'crm-context-help-article-title',
                ]
            ) .
            html_writer::div(
                s($article->summary),
                'crm-context-help-article-summary'
            ),
            [
                'class' =>
                    'crm-context-help-article',
            ]
        );
    }
}