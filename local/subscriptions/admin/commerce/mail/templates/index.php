<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php');
$title = get_string('commerce_mail_templates_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-mail-library-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$category = optional_param('category', '', PARAM_ALPHANUMEXT);
$status = optional_param('status', '', PARAM_ALPHANUMEXT);
if (!in_array($category, CommerceMailLibrary::categories(), true)) { $category = ''; }
if (!in_array($status, CommerceMailLibrary::statuses(), true)) { $status = ''; }

$library = new CommerceMailLibraryRepository($DB);
$native = $library->all($category, $status);
$legacyrepository = new CommerceMailTemplateRepository($DB);
$transactionalbridge = CommerceTransactionalMailStudioBridge::create($DB);
$legacycustom = [];
foreach ($legacyrepository->get_all() as $record) {
    $legacycustom[(string)$record->mailtype][(string)$record->language] = $record;
}

$categorylabel = static fn(string $value): string => get_string('commerce_mail_library_category_' . $value, 'local_subscriptions');
$statusbadge = static function(string $value): string {
    $class = match ($value) {
        CommerceMailLibrary::STATUS_ACTIVE => 'is-active',
        CommerceMailLibrary::STATUS_ARCHIVED => 'is-archived',
        default => 'is-draft',
    };
    return html_writer::span(
        get_string('commerce_mail_library_status_' . $value, 'local_subscriptions'),
        'commerce-mail-library-status ' . $class
    );
};
$languagepills = static function(array $languages): string {
    $out = '';
    foreach (CommerceMailLibrary::LANGUAGES as $language) {
        $out .= html_writer::span(
            strtoupper($language),
            'commerce-mail-library-language ' . (in_array($language, $languages, true) ? 'is-ready' : 'is-missing')
        );
    }
    return $out;
};

$toolbar = html_writer::div(
    html_writer::div(
        html_writer::tag('strong', get_string('commerce_mail_library_heading', 'local_subscriptions'))
        . html_writer::div(get_string('commerce_mail_library_help', 'local_subscriptions'), 'commerce-mail-library-toolbar-help'),
        'commerce-mail-library-toolbar-copy'
    )
    . html_writer::div(
        html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/mail/templates/import.php'),
            html_writer::tag('i', '', ['class' => 'fa fa-upload', 'aria-hidden' => 'true']) . ' ' . get_string('commerce_mail_library_import', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary']
        )
        . html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
                $category !== '' ? ['category' => $category] : []
            ),
            html_writer::tag('i', '', ['class' => 'fa fa-plus', 'aria-hidden' => 'true']) . ' ' . get_string('commerce_mail_library_new', 'local_subscriptions'),
            ['class' => 'btn btn-primary']
        ),
        'commerce-mail-library-toolbar-actions'
    ),
    'commerce-mail-library-toolbar'
);

$filter = html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false), 'class' => 'commerce-mail-library-filter']);
$filter .= html_writer::select(
    ['' => get_string('all')] + array_combine(
        CommerceMailLibrary::categories(),
        array_map($categorylabel, CommerceMailLibrary::categories())
    ),
    'category', $category, false, ['class' => 'form-select', 'aria-label' => get_string('commerce_mail_library_category', 'local_subscriptions')]
);
$statusoptions = ['' => get_string('all')];
foreach (CommerceMailLibrary::statuses() as $item) {
    $statusoptions[$item] = get_string('commerce_mail_library_status_' . $item, 'local_subscriptions');
}
$filter .= html_writer::select($statusoptions, 'status', $status, false, ['class' => 'form-select', 'aria-label' => get_string('status')]);
$filter .= html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-outline-primary']);
$filter .= html_writer::link($pageurl, get_string('reset'), ['class' => 'btn btn-link']);
$filter .= html_writer::end_tag('form');

$cards = '';
foreach ($native as $record) {
    $contents = $library->contents((int)$record->id);
    $languages = array_keys($contents);
    $editurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', ['id' => (int)$record->id]);
    $exporturl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/export.php', ['source' => 'native', 'id' => (int)$record->id]);
    $duplicateurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_action.php', [
        'action' => 'duplicate', 'id' => (int)$record->id, 'sesskey' => sesskey(),
    ]);
    $archiveurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_action.php', [
        'action' => 'archive', 'id' => (int)$record->id, 'sesskey' => sesskey(),
    ]);
    $deleteurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_action.php', [
        'action' => 'delete', 'id' => (int)$record->id, 'sesskey' => sesskey(),
    ]);
    $cards .= html_writer::tag('article',
        html_writer::div(
            html_writer::span(
                $categorylabel((string)$record->category),
                'commerce-mail-library-category'
            )
            . html_writer::span(
                get_string(
                    'commerce_mail_library_builder_badge',
                    'local_subscriptions',
                    (int)$record->builderversion
                ),
                'commerce-mail-library-builder-badge'
            )
            . $statusbadge((string)$record->status),
            'commerce-mail-library-card-eyebrow'
        )
        . html_writer::tag('h3', s((string)$record->name), ['class' => 'commerce-mail-library-card-title'])
        . html_writer::div($languagepills($languages), 'commerce-mail-library-languages')
        . html_writer::div(
            get_string('commerce_mail_library_modified', 'local_subscriptions', userdate((int)$record->timemodified, get_string('strftimedatetimeshort', 'langconfig'))),
            'commerce-mail-library-card-meta'
        )
        . html_writer::div(
            html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary'])
            . html_writer::link($exporturl, get_string('commerce_mail_library_export', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary'])
            . html_writer::link($duplicateurl, get_string('duplicate'), ['class' => 'btn btn-sm btn-outline-secondary'])
            . ((string)$record->status !== CommerceMailLibrary::STATUS_ARCHIVED
                ? html_writer::link($archiveurl, get_string('commerce_mail_library_archive', 'local_subscriptions'), [
                    'class' => 'btn btn-sm btn-outline-secondary',
                    'data-confirmation' => 'modal',
                    'data-confirmation-title-str' => json_encode(['confirm', 'core']),
                    'data-confirmation-content-str' => json_encode(['commerce_mail_library_archive_confirm', 'local_subscriptions']),
                    'data-confirmation-yes-button-str' => json_encode(['yes', 'core']),
                    'data-confirmation-destination' => $archiveurl->out(false),
                ])
                : html_writer::link(
                    $deleteurl,
                    get_string('commerce_mail_library_delete', 'local_subscriptions'),
                    [
                        'class' => 'btn btn-sm btn-outline-danger',
                        'data-confirmation' => 'modal',
                        'data-confirmation-title-str' => json_encode(['confirm', 'core']),
                        'data-confirmation-content-str' => json_encode([
                            'commerce_mail_library_delete_confirm',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-yes-button-str' => json_encode([
                            'commerce_mail_library_delete',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-destination' => $deleteurl->out(false),
                    ]
                )), 
            'commerce-mail-library-card-actions'
        ),
        ['class' => 'commerce-mail-library-card']
    );
}

$legacycards = '';
if ($category === '' || $category === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) {
    foreach (CommerceTransactionalMailStudioBridge::supported_types() as $mailtype) {
        $customlangs = array_keys($legacycustom[$mailtype] ?? []);
        $runtimetemplate = $transactionalbridge->template($mailtype);
        $exporturl = $runtimetemplate
            ? new moodle_url('/local/subscriptions/admin/commerce/mail/templates/export.php', [
                'source' => 'native',
                'id' => (int)$runtimetemplate->id,
            ])
            : new moodle_url('/local/subscriptions/admin/commerce/mail/templates/export.php', [
                'source' => 'transactional',
                'mailtype' => $mailtype,
            ]);
        $firstedit = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/edit.php', ['mailtype' => $mailtype, 'language' => 'fr']);
        $runtimeedit = $runtimetemplate
            ? new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', ['id' => (int)$runtimetemplate->id])
            : null;
        $migrateurl = new moodle_url('/local/subscriptions/admin/commerce/mail/templates/transactional_migrate.php', [
            'mailtype' => $mailtype,
            'sesskey' => sesskey(),
        ]);
        $runtimebadge = $runtimetemplate
            ? html_writer::span(
                get_string('commerce_mail_transactional_runtime_mailstudio', 'local_subscriptions'),
                'commerce-mail-library-status is-active'
            )
            : html_writer::span(
                get_string('commerce_mail_transactional_runtime_legacy', 'local_subscriptions'),
                'commerce-mail-library-status is-runtime'
            );
        $legacycards .= html_writer::tag('article',
            html_writer::div(
                html_writer::span($categorylabel(CommerceMailLibrary::CATEGORY_TRANSACTIONAL), 'commerce-mail-library-category')
                . $runtimebadge,
                'commerce-mail-library-card-eyebrow'
            )
            . html_writer::tag('h3', s(CommerceMailAdminPresentation::type_label($mailtype)), ['class' => 'commerce-mail-library-card-title'])
            . html_writer::div($languagepills(CommerceMailLibrary::LANGUAGES), 'commerce-mail-library-languages')
            . html_writer::div(
                get_string('commerce_mail_library_transactional_bridge_help', 'local_subscriptions', count($customlangs)),
                'commerce-mail-library-card-meta'
            )
            . html_writer::div(
                ($runtimetemplate
                    ? html_writer::link(
                        $runtimeedit,
                        get_string('commerce_mail_transactional_edit_mailstudio', 'local_subscriptions'),
                        ['class' => 'btn btn-sm btn-outline-primary']
                    )
                    : html_writer::link(
                        $migrateurl,
                        get_string('commerce_mail_transactional_migrate', 'local_subscriptions'),
                        ['class' => 'btn btn-sm btn-primary']
                    ))
                . (!$runtimetemplate
                    ? html_writer::link(
                        $firstedit,
                        get_string('commerce_mail_transactional_edit_legacy', 'local_subscriptions'),
                        ['class' => 'btn btn-sm btn-outline-secondary']
                    )
                    : '')
                . html_writer::link(
                    $exporturl,
                    get_string('commerce_mail_library_export', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                ),
                'commerce-mail-library-card-actions'
            ),
            ['class' => 'commerce-mail-library-card is-runtime']
        );
    }
}

$content = $toolbar . $filter;
if (($category === '' || $category === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) && $status === '') {
    $content .= html_writer::div(
        html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/mail/templates/transactional_migrate_all.php',
                ['sesskey' => sesskey()]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-magic me-1',
                'aria-hidden' => 'true',
            ]) . get_string(
                'commerce_mail_transactional_migrate_all',
                'local_subscriptions'
            ),
            [
                'class' => 'btn btn-sm btn-outline-primary',
                'onclick' => "return confirm('" . addslashes(
                    get_string(
                        'commerce_mail_transactional_migrate_all_confirm',
                        'local_subscriptions'
                    )
                ) . "');",
            ]
        ),
        'commerce-mail-transactional-bulk-action'
    );
}
if ($cards !== '') {
    $content .= html_writer::tag('h2', get_string('commerce_mail_library_native_title', 'local_subscriptions'), ['class' => 'h6 commerce-mail-library-section-title']);
    $content .= html_writer::div($cards, 'commerce-mail-library-grid');
} else if ($status !== '' || ($category !== '' && $category !== CommerceMailLibrary::CATEGORY_TRANSACTIONAL)) {
    $content .= html_writer::div(get_string('commerce_mail_library_empty', 'local_subscriptions'), 'commerce-mail-library-empty');
}
if ($legacycards !== '' && $status === '') {
    $content .= html_writer::tag('h2', get_string('commerce_mail_library_runtime_title', 'local_subscriptions'), ['class' => 'h6 commerce-mail-library-section-title']);
    $content .= html_writer::div(get_string('commerce_mail_library_runtime_help', 'local_subscriptions'), 'commerce-mail-library-section-help');
    $content .= html_writer::div($legacycards, 'commerce-mail-library-grid');
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_mail_admin_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_mail_library_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo CommerceMailSectionNavigationRenderer::render(CommerceMailSectionNavigationRenderer::TEMPLATES);
echo $content;
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
