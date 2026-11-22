<?php
// Shared secured CRM shell for the editorial Reader workshop.
declare(strict_types=1);
require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\commerce\education\admin\CommerceEducationNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\library\ReaderEditorialService;

$context = AdminSecurity::require(Capabilities::MANAGE_PROMOTIONS);
ReaderEditorialService::assert_ready();

function cfr_d21_url(string $page = 'index.php', array $args = []): moodle_url {
    return new moodle_url('/local/subscriptions/admin/commerce/education/library/' . $page, $args);
}
/** D2.7: single CRM-wide navigation for all editorial screens. Read-only renderer. */
function cfr_d27_navigation(): void {
    $page = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $sections = [
        'home' => ['index.php', 'cfr_d27_home'],
        'stories' => ['stories.php', 'cfr_d27_stories'],
        'calendars' => ['calendars.php', 'cfr_d27_calendars'],
        'anthologies' => ['anthologies.php', 'cfr_d24_anthologies'],
        'commerce' => ['publications.php', 'cfr_d243_publications'],
        'transfers' => ['bundles.php', 'cfr_d27_transfers'],
    ];
    $groups = [
        'index.php' => 'home', 'collection.php' => 'home',
        'stories.php' => 'stories', 'book.php' => 'stories', 'pages.php' => 'stories',
        'media.php' => 'stories', 'preview.php' => 'stories', 'story_flipbook.php' => 'stories',
        'audio_studio.php' => 'stories', 'layout_studio.php' => 'stories', 'layout_pdf.php' => 'stories',
        'calendars.php' => 'calendars', 'calendar.php' => 'calendars', 'production.php' => 'calendars',
        'anthologies.php' => 'anthologies', 'anthology.php' => 'anthologies',
        'anthology_preview.php' => 'anthologies', 'anthology_revision.php' => 'anthologies',
        'anthology_flipbook.php' => 'anthologies', 'anthology_revision_media.php' => 'anthologies',
        'publications.php' => 'commerce', 'publication.php' => 'commerce', 'qa_access.php' => 'commerce',
        'transfers.php' => 'transfers', 'bundles.php' => 'transfers',
        'story_export.php' => 'transfers', 'bundle_export.php' => 'transfers',
    ];
    $current = $groups[$page] ?? 'home';
    $links = '';
    foreach ($sections as $key => [$target, $string]) {
        $active = $key === $current;
        $attrs = ['class' => 'cfr-d27-nav__link' . ($active ? ' is-active' : '')];
        if ($active) { $attrs['aria-current'] = 'page'; }
        $links .= html_writer::link(cfr_d21_url($target),
            get_string($string, 'local_subscriptions'), $attrs);
    }
    echo html_writer::tag('nav', $links, ['class' => 'cfr-d27-nav',
        'aria-label' => get_string('cfr_d27_navlabel', 'local_subscriptions')]);
}

function cfr_d21_start(string $title, string $subtitle, array $trail = [], ?string $actions = null): void {
    global $PAGE, $OUTPUT, $context;
    CrmPageConfigurator::configure($PAGE, $context, cfr_d21_url(basename($_SERVER['SCRIPT_NAME'])),
        $title, 'cfr-d21-reader-page');
    $PAGE->requires->css(cfr_d21_url('style.css'));
    echo $OUTPUT->header();
    echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
    $items = [
        ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
        ['label' => get_string('crm_commerce_nav_education', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php')],
        ['label' => get_string('cfr_d21_nav', 'local_subscriptions'), 'url' => $trail ? cfr_d21_url() : null],
    ];
    foreach ($trail as $item) {
        $items[] = $item;
    }
    echo CrmBreadcrumbRenderer::render($items);
    echo CrmPageHeader::render($title, $subtitle, HelpContext::COMMERCE, $actions);
    echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);
    echo CommerceEducationNavigationRenderer::render(CommerceEducationNavigationRenderer::LIBRARY);
    echo html_writer::start_div('cfr-reader-editor');
    cfr_d27_navigation();
}
function cfr_d21_end(): void {
    global $OUTPUT;
    echo html_writer::end_div();
    echo CrmWorkspaceRenderer::end();
    echo $OUTPUT->footer();
}
function cfr_d21_field(string $name, string $label, string $value, bool $readonly = false,
    string $type = 'text', string $help = '', bool $required = true): void {
    echo '<div class="cfr-reader-editor__field"><label for="cfr-d21-' . s($name) . '">' . s($label) . '</label>';
    echo '<input class="form-control" id="cfr-d21-' . s($name) . '" name="' . s($name) . '" type="' . s($type)
        . '" value="' . s($value) . '"' . ($readonly ? ' readonly' : ($required ? ' required' : '')) . '>';
    if ($help !== '') { echo '<small>' . s($help) . '</small>'; }
    echo '</div>';
}
