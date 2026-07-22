<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\HelpRegistry;
use local_subscriptions\crm\help\HelpArticleLoader;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

$articleid = required_param(
    'id',
    PARAM_ALPHANUMEXT
);

$registry = new HelpRegistry();
$article = $registry->get_article($articleid);

if ($article === null) {
    throw new moodle_exception(
        'crm_help_article_not_found',
        'local_subscriptions'
    );
}

$category = $registry->get_category(
    $article->categoryid
);

$url = new moodle_url(
    subscription_config::admin_help_article_page(),
    [
        'id' => $article->id,
    ]
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $article->title,
    [
        'local-subscriptions-help-page',
        'local-subscriptions-help-article-page',
    ]
);

$content = (new HelpArticleLoader())->render(
    $article
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::HELP,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_help_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_help_page()
                ),
        ],
        [
            'label' =>
                $article->title,

            'url' =>
                null,
        ],
    ]
);

echo CrmPageHeader::render(
    $article->title,
    $article->summary,
    HelpContext::HELP_CENTER
);

echo html_writer::start_div(
    'crm-help-article-layout'
);

echo html_writer::start_tag(
    'aside',
    [
        'class' => 'crm-help-article-sidebar',
        'aria-label' => get_string(
            'crm_help_article_navigation',
            'local_subscriptions'
        ),
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            admin_help_page()
    ),
    get_string(
        'crm_help_home',
        'local_subscriptions'
    ),
    [
        'crm-help-sidebar-back',
    ]
);

if ($category !== null) {
    echo html_writer::link(
        new moodle_url(
            subscription_config::admin_help_page(),
            [
                'category' => $category->id,
            ]
        ),
        $category->icon . ' ' . s($category->title),
        [
            'class' => 'crm-help-sidebar-category',
        ]
    );

    echo html_writer::start_div(
        'crm-help-sidebar-articles'
    );

    foreach (
        $registry->articles_by_category($category->id)
        as $categoryarticle
    ) {
        $classes = 'crm-help-sidebar-article';

        $isactive =
            $categoryarticle->id === $article->id;

        if ($isactive) {
            $classes .= ' active';
        }

        $linkattributes = [
            'class' => $classes,
        ];

        if ($isactive) {
            $linkattributes['aria-current'] = 'page';
        }

        echo html_writer::link(
            new moodle_url(
                subscription_config::admin_help_article_page(),
                [
                    'id' => $categoryarticle->id,
                ]
            ),
            s($categoryarticle->title),
            $linkattributes
        );
    }

    echo html_writer::end_div();
}

echo html_writer::end_tag('aside');

echo html_writer::start_tag(
    'article',
    [
        'class' => 'crm-help-article',
    ]
);

if ($category !== null) {
    echo html_writer::div(
        $category->icon . ' ' . s($category->title),
        'crm-help-article-category'
    );
}

echo html_writer::div(
    $content,
    'crm-help-markdown'
);

echo html_writer::end_tag('article');
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();