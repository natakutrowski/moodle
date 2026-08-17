<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
require_capability(
    'local/subscriptions:manage_showrooms',
    context_system::instance()
);

$id = required_param('id', PARAM_INT);
$restore = optional_param('restore', 0, PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = optional_param('perpage', 25, PARAM_INT);
if (!in_array($perpage, [25, 50, 100], true)) {
    $perpage = 25;
}

$context = context_system::instance();
$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/showrooms/history.php',
    ['id' => $id]
);
$pagetitle = get_string(
    'commerce_showroom_history',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-showroom-history-page'
);

$repository = new CommerceShowroomCmsRepository($DB);
$showroom = $repository->get($id);
if ($showroom === null) {
    throw new moodle_exception('invalidrecord');
}

$service = new CommerceShowroomPublicationService(
    $DB,
    $repository
);

if ($restore > 0 && confirm_sesskey()) {
    $service->restore(
        $id,
        $restore,
        (int)$USER->id
    );
    redirect(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/edit.php',
            ['id' => $id]
        ),
        get_string(
            'commerce_showroom_revision_restored',
            'local_subscriptions'
        )
    );
}

$revisions = array_values($service->revisions($id));
$total = count($revisions);
$maxpage = max(0, (int)ceil($total / $perpage) - 1);
$page = min($page, $maxpage);
$pagedrevisions = array_slice(
    $revisions,
    $page * $perpage,
    $perpage
);

$showroomname = format_string((string)$showroom->name);
$editurl = new moodle_url(
    '/local/subscriptions/admin/commerce/showrooms/edit.php',
    ['id' => $id]
);
$listurl = new moodle_url(
    '/local/subscriptions/admin/commerce/showrooms/index.php'
);

$statuslabel = CommerceShowroomStatus::label(
    (string)$showroom->status
);
$statusclass = CommerceShowroomStatus::badge_class(
    (string)$showroom->status
);

$latestrevision = $revisions[0] ?? null;
$latestdate = $latestrevision
    ? userdate(
        (int)$latestrevision->timecreated,
        get_string(
            'strftimedatetimeshort',
            'langconfig'
        )
    )
    : '—';

$actionlabel = static function(string $action): string {
    return match ($action) {
        'publish' => get_string(
            'commerce_showroom_publish',
            'local_subscriptions'
        ),
        'submit_review' => get_string(
            'commerce_showroom_submit_review',
            'local_subscriptions'
        ),
        'return_draft' => get_string(
            'commerce_showroom_return_draft',
            'local_subscriptions'
        ),
        'restore' => get_string(
            'commerce_showroom_restore_revision',
            'local_subscriptions'
        ),
        default => str_replace('_', ' ', $action),
    };
};

$actionbadge = static function(string $action) use (
    $actionlabel
): string {
    $class = match ($action) {
        'publish' => 'bg-success',
        'submit_review' => 'bg-primary',
        'return_draft' => 'bg-secondary',
        'restore' => 'bg-warning text-dark',
        default => 'bg-light text-dark border',
    };

    return html_writer::span(
        s($actionlabel($action)),
        'badge rounded-pill ' . $class
        . ' crm-showroom-history-action-badge'
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        'url' => $listurl,
    ],
    [
        'label' => $showroomname,
        'url' => $editurl,
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    get_string(
        'commerce_showroom_n92_history_title',
        'local_subscriptions',
        $showroomname
    ),
    get_string(
        'commerce_showroom_n92_history_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SHOWROOMS,
    $context
);

echo html_writer::start_tag('nav', ['class' => 'commerce-showroom-subnav mb-4', 'aria-label' => 'Navigation du showroom']);
foreach ([
    ['Informations', 'edit.php', 'fa-circle-info'],
    ['Référencement', 'seo.php', 'fa-magnifying-glass'],
    ['Builder', 'builder.php', 'fa-layer-group'],
    [get_string('commerce_showroom_history', 'local_subscriptions'), 'history.php', 'fa-clock-rotate-left'],
] as [$label, $file, $icon]) {
    echo html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/' . $file, ['id' => $id]),
        '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i> ' . s($label),
        ['class' => 'commerce-showroom-subnav__item' . ($file === 'history.php' ? ' is-active' : '')]
    );
}
echo html_writer::end_tag('nav');

echo html_writer::start_div(
    'crm-showroom-history-summary'
);

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            get_string(
                'commerce_showroom_n92_current_status',
                'local_subscriptions'
            ),
            'crm-showroom-history-summary-label'
        )
        . html_writer::span(
            s($statuslabel),
            'badge rounded-pill bg-' . $statusclass
            . ' crm-showroom-history-status-badge'
        ),
        'crm-showroom-history-summary-value'
    ),
    'crm-showroom-history-summary-card'
);

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            get_string(
                'commerce_showroom_n92_revision_count',
                'local_subscriptions'
            ),
            'crm-showroom-history-summary-label'
        )
        . html_writer::tag(
            'strong',
            (string)$total,
            ['class' => 'crm-showroom-history-summary-number']
        ),
        'crm-showroom-history-summary-value'
    ),
    'crm-showroom-history-summary-card'
);

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            get_string(
                'commerce_showroom_n92_latest_revision',
                'local_subscriptions'
            ),
            'crm-showroom-history-summary-label'
        )
        . html_writer::tag(
            'strong',
            $latestrevision
                ? '#' . (int)$latestrevision->revisionno
                : '—',
            ['class' => 'crm-showroom-history-summary-number']
        )
        . html_writer::span(
            s($latestdate),
            'crm-showroom-history-summary-meta'
        ),
        'crm-showroom-history-summary-value'
    ),
    'crm-showroom-history-summary-card is-highlighted'
);

echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag(
        'i',
        '',
        [
            'class' => 'fa fa-info-circle me-2',
            'aria-hidden' => 'true',
        ]
    )
    . get_string(
        'commerce_showroom_n92_restore_help',
        'local_subscriptions'
    ),
    'crm-showroom-history-help'
);

if ($pagedrevisions === []) {
    echo html_writer::div(
        get_string(
            'commerce_showroom_no_revisions',
            'local_subscriptions'
        ),
        'crm-showroom-history-empty'
    );
} else {
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle '
        . 'crm-showroom-history-table';
    $table->head = [
        get_string(
            'commerce_showroom_revision',
            'local_subscriptions'
        ),
        get_string(
            'commerce_showroom_revision_action',
            'local_subscriptions'
        ),
        get_string('date'),
        get_string('user'),
        get_string(
            'commerce_showroom_revision_note',
            'local_subscriptions'
        ),
        html_writer::span(
            get_string('actions'),
            'crm-showroom-history-actions-heading'
        ),
    ];

    foreach ($pagedrevisions as $index => $revision) {
        $user = $revision->usercreated
            ? core_user::get_user(
                (int)$revision->usercreated
            )
            : null;

        $restoreurl = new moodle_url(
            $PAGE->url,
            [
                'restore' => (int)$revision->id,
                'sesskey' => sesskey(),
            ]
        );

        $islatest =
            $page === 0
            && $index === 0;

        $revisioncell = html_writer::tag(
            'strong',
            '#' . (int)$revision->revisionno
        );
        if ($islatest) {
            $revisioncell .= html_writer::span(
                get_string(
                    'commerce_showroom_n92_latest_badge',
                    'local_subscriptions'
                ),
                'badge rounded-pill bg-success-subtle '
                . 'text-success ms-2 crm-showroom-history-latest-badge'
            );
        }

        $note = trim((string)$revision->note);

        $restorebutton = html_writer::link(
            $restoreurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-undo me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_showroom_restore_revision',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-primary '
                    . 'crm-showroom-history-restore',
                'onclick' => 'return confirm('
                    . json_encode(
                        get_string(
                            'commerce_showroom_n92_restore_confirm',
                            'local_subscriptions',
                            '#' . (int)$revision->revisionno
                        )
                    )
                    . ');',
            ]
        );

        $table->data[] = [
            $revisioncell,
            $actionbadge((string)$revision->action),
            html_writer::span(
                userdate(
                    (int)$revision->timecreated,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                ),
                'crm-showroom-history-date'
            ),
            $user
                ? html_writer::span(
                    s(fullname($user)),
                    'crm-showroom-history-user'
                )
                : '—',
            $note !== ''
                ? html_writer::span(
                    s($note),
                    'crm-showroom-history-note'
                )
                : html_writer::span(
                    '—',
                    'text-muted'
                ),
            $restorebutton,
        ];
    }

    echo html_writer::table($table);

    if ($total > $perpage) {
        echo $OUTPUT->paging_bar(
            $total,
            $page,
            $perpage,
            new moodle_url(
                $pageurl,
                ['perpage' => $perpage]
            ),
            'page'
        );
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
