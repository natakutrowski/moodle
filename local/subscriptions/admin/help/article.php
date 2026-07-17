<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\HelpRegistry;
use local_subscriptions\crm\help\HelpArticleLoader;

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

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($article->title);
$PAGE->set_heading(
    get_string(
        'crm_help_title',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);

$PAGE->add_body_class(
    'local-subscriptions-help-page'
);

$PAGE->add_body_class(
    'local-subscriptions-help-article-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

$content = (new HelpArticleLoader())->render(
    $article
);

echo $OUTPUT->header();

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

echo html_writer::link(
    new moodle_url(
        subscription_config::admin_help_page()
    ),
    '← ' . get_string(
        'crm_help_home',
        'local_subscriptions'
    ),
    [
        'class' => 'crm-help-sidebar-back',
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

        if ($categoryarticle->id === $article->id) {
            $classes .= ' active';
        }

        echo html_writer::link(
            new moodle_url(
                subscription_config::admin_help_article_page(),
                [
                    'id' => $categoryarticle->id,
                ]
            ),
            s($categoryarticle->title),
            [
                'class' => $classes,
            ]
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

echo html_writer::tag(
    'h1',
    s($article->title),
    [
        'class' => 'crm-help-article-title',
    ]
);

echo html_writer::div(
    s($article->summary),
    'crm-help-article-summary'
);

echo html_writer::div(
    $content,
    'crm-help-markdown'
);

echo html_writer::end_tag('article');
echo html_writer::end_div();

echo $OUTPUT->footer();