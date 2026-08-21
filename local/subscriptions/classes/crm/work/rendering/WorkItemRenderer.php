<?php

namespace local_subscriptions\crm\work\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemStatus;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\dto\WorkItemListResult;
use local_subscriptions\subscription_config;
use moodle_url;

final class WorkItemRenderer {

    public static function render_list(WorkItemListResult $result): string {
        $out = html_writer::start_tag('section', [
            'class' => 'crm-work-items',
            'aria-label' => get_string('crm_work_region_label', 'local_subscriptions'),
        ]);

        $out .= self::filters($result);
        $out .= html_writer::div(
            get_string('crm_work_result_count', 'local_subscriptions', $result->total),
            'crm-work-result-summary mb-3',
            ['role' => 'status', 'aria-live' => 'polite']
        );

        if (!$result->has_results()) {
            $out .= html_writer::div(
                get_string('crm_work_empty', 'local_subscriptions'),
                'alert alert-light border'
            );
            return $out . html_writer::end_tag('section');
        }

        $out .= html_writer::start_div('crm-work-list', ['role' => 'list']);
        foreach ($result->items as $item) {
            $out .= self::card($item);
        }
        $out .= html_writer::end_div();

        $baseurl = new moodle_url(
            subscription_config::admin_work_items_page(),
            $result->criteria->url_params(false)
        );

        $out .= $GLOBALS['OUTPUT']->paging_bar(
            $result->total,
            $result->criteria->page,
            $result->criteria->perpage,
            $baseurl
        );

        return $out . html_writer::end_tag('section');
    }

    public static function render_detail(
        \stdClass $item,
        array $teams,
        array $assignees
    ): string {
        $out = html_writer::start_tag(
            'article',
            [
                'class' => 'crm-work-detail',
                'aria-labelledby' =>
                    'crm-work-title-' . (int)$item->id,
            ]
        );

        $badges =
            self::badge_status((string)$item->status)
            . self::badge_priority((string)$item->priority)
            . html_writer::span(
                WorkItemPresentation::type_label(
                    (string)$item->type
                ),
                'badge bg-light text-dark border'
            );

        $assignee = self::assignee_name($item);

        $meta = [
            self::detail_metric(
                get_string(
                    'crm_work_due',
                    'local_subscriptions'
                ),
                !empty($item->dueat)
                    ? AdminFormatter::datetime(
                        (int)$item->dueat
                    )
                    : get_string(
                        'crm_work_no_due_n127a',
                        'local_subscriptions'
                    )
            ),
            self::detail_metric(
                get_string(
                    'crm_work_team',
                    'local_subscriptions'
                ),
                !empty($item->teamname)
                    ? format_string($item->teamname)
                    : get_string(
                        'crm_work_unassigned_n127a',
                        'local_subscriptions'
                    )
            ),
            self::detail_metric(
                get_string(
                    'crm_work_assignee_n127a',
                    'local_subscriptions'
                ),
                $assignee !== ''
                    ? $assignee
                    : get_string(
                        'crm_work_unassigned_n127a',
                        'local_subscriptions'
                    )
            ),
        ];

        $out .= html_writer::div(
            html_writer::div(
                html_writer::span(
                    s($item->reference),
                    'crm-work-detail-reference'
                )
                . html_writer::tag(
                    'h2',
                    format_string($item->title),
                    [
                        'id' =>
                            'crm-work-title-' . (int)$item->id,
                        'class' =>
                            'crm-work-detail-title',
                    ]
                )
                . html_writer::div(
                    $badges,
                    'crm-work-detail-badges'
                ),
                'crm-work-detail-heading'
            )
            . html_writer::div(
                implode('', $meta),
                'crm-work-detail-metrics'
            ),
            'crm-work-detail-summary'
        );

        $description = html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'crm_work_description_n127a',
                    'local_subscriptions'
                ),
                ['class' => 'crm-work-panel-title']
            )
            . html_writer::div(
                format_text(
                    (string)$item->description,
                    FORMAT_PLAIN
                ),
                'crm-work-description'
            ),
            'crm-work-panel'
        );

        $left = $description
            . self::comments($item)
            . self::children($item->children ?? [])
            . self::links($item->links ?? []);

        $right = '';

        if (
            AdminSecurity::can(
                Capabilities::MANAGE_WORK_ITEMS
            )
        ) {
            $right .= self::management_forms(
                $item,
                $teams,
                $assignees
            );
        }

        $right .= self::history(
            $item->history ?? []
        );

        $out .= html_writer::div(
            html_writer::div(
                $left,
                'crm-work-detail-primary'
            )
            . html_writer::div(
                $right,
                'crm-work-detail-sidebar'
            ),
            'crm-work-detail-grid'
        );

        return $out
            . html_writer::end_tag('article');
    }

    private static function detail_metric(
        string $label,
        string $value
    ): string {
        return html_writer::div(
            html_writer::span(
                s($label),
                'crm-work-detail-metric-label'
            )
            . html_writer::span(
                s($value),
                'crm-work-detail-metric-value'
            ),
            'crm-work-detail-metric'
        );
    }

    private static function assignee_name(
        \stdClass $item
    ): string {
        if (empty($item->assigneduserid)) {
            return '';
        }

        $user = (object)[
            'firstname' =>
                (string)($item->assigneefirstname ?? ''),
            'lastname' =>
                (string)($item->assigneelastname ?? ''),
            'firstnamephonetic' =>
                (string)(
                    $item->assigneefirstnamephonetic ?? ''
                ),
            'lastnamephonetic' =>
                (string)(
                    $item->assigneelastnamephonetic ?? ''
                ),
            'middlename' =>
                (string)($item->assigneemiddlename ?? ''),
            'alternatename' =>
                (string)($item->assigneealternatename ?? ''),
        ];

        return fullname($user);
    }


    private static function filters(WorkItemListResult $result): string {
        $c = $result->criteria;
        $out = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => subscription_config::admin_work_items_page(),
            'class' => 'card card-body mb-3 crm-work-filters',
        ]);
        $out .= html_writer::start_div('row g-2 align-items-end');

        $out .= self::text_input('q', get_string('search'), $c->query, 'col-12 col-lg-3');
        $out .= self::select_input('status', get_string('crm_work_status', 'local_subscriptions'),
            ['' => get_string('all')] + self::status_options(), $c->status, 'col-6 col-lg-2');
        $out .= self::select_input('priority', get_string('crm_work_priority', 'local_subscriptions'),
            ['' => get_string('all')] + self::priority_options(), $c->priority, 'col-6 col-lg-2');
        $out .= self::select_input('type', get_string('crm_work_type', 'local_subscriptions'),
            ['' => get_string('all')] + self::type_options(), $c->type, 'col-6 col-lg-2');

        $teamoptions = ['' => get_string('all')];
        foreach ($result->teams as $team) {
            $teamoptions[(string)$team->id] = format_string($team->name);
        }
        $out .= self::select_input('assignedteamid', get_string('crm_work_team', 'local_subscriptions'),
            $teamoptions, (string)$c->assignedteamid, 'col-6 col-lg-2');

        $out .= html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'checkbox', 'name' => 'mineonly', 'value' => '1',
                'id' => 'id_work_mine', 'checked' => $c->mineonly ? 'checked' : null,
            ]) . ' ' . html_writer::label(
                get_string('crm_work_filter_mine', 'local_subscriptions'),
                'id_work_mine'
            ) . '<br>' .
            html_writer::empty_tag('input', [
                'type' => 'checkbox', 'name' => 'unassignedonly', 'value' => '1',
                'id' => 'id_work_unassigned', 'checked' => $c->unassignedonly ? 'checked' : null,
            ]) . ' ' . html_writer::label(
                get_string('crm_work_filter_unassigned', 'local_subscriptions'),
                'id_work_unassigned'
            ) . '<br>' .
            html_writer::empty_tag('input', [
                'type' => 'checkbox', 'name' => 'overdueonly', 'value' => '1',
                'id' => 'id_work_overdue', 'checked' => $c->overdueonly ? 'checked' : null,
            ]) . ' ' . html_writer::label(
                get_string('crm_work_filter_overdue', 'local_subscriptions'),
                'id_work_overdue'
            ),
            'col-12 col-lg-3 small'
        );

        $out .= html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'submit', 'class' => 'btn btn-primary',
                'value' => get_string('filter'),
            ]),
            'col-12 col-lg-auto'
        );

        $out .= html_writer::end_div();
        return $out . html_writer::end_tag('form');
    }

    private static function card(\stdClass $item): string {
        $url = new moodle_url(
            subscription_config::admin_work_item_view_page(),
            ['id' => (int)$item->id]
        );

        $assignee = self::assignee_name($item);

        $due = !empty($item->dueat)
            ? AdminFormatter::datetime((int)$item->dueat)
            : get_string(
                'crm_work_no_due_n127a',
                'local_subscriptions'
            );

        $assignment = trim(
            implode(
                ' · ',
                array_filter([
                    !empty($item->teamname)
                        ? format_string($item->teamname)
                        : '',
                    $assignee,
                ])
            )
        );

        if ($assignment === '') {
            $assignment = get_string(
                'crm_work_unassigned_n127a',
                'local_subscriptions'
            );
        }

        $main = html_writer::div(
            html_writer::div(
                html_writer::link(
                    $url,
                    s($item->reference),
                    [
                        'class' =>
                            'crm-work-card-reference',
                    ]
                )
                . html_writer::link(
                    $url,
                    format_string($item->title),
                    [
                        'class' =>
                            'crm-work-card-title',
                    ]
                ),
                'crm-work-card-identity'
            )
            . html_writer::div(
                html_writer::span(
                    WorkItemPresentation::type_label(
                        (string)$item->type
                    ),
                    'badge bg-light text-dark border'
                )
                . html_writer::span(
                    s($assignment),
                    'crm-work-card-assignment'
                ),
                'crm-work-card-context'
            ),
            'crm-work-card-main'
        );

        $timing = html_writer::div(
            html_writer::span(
                get_string(
                    'crm_work_due',
                    'local_subscriptions'
                ),
                'crm-work-card-meta-label'
            )
            . html_writer::span(
                s($due),
                'crm-work-card-meta-value'
            ),
            'crm-work-card-timing'
        );

        $status = html_writer::div(
            self::badge_status(
                (string)$item->status
            )
            . self::badge_priority(
                (string)$item->priority
            ),
            'crm-work-card-badges'
        );

        $action = html_writer::link(
            $url,
            get_string(
                'crm_work_open_n127a',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-outline-primary btn-sm',
            ]
        );

        return html_writer::div(
            $main
            . $timing
            . $status
            . html_writer::div(
                $action,
                'crm-work-card-action'
            ),
            'crm-work-card',
            ['role' => 'listitem']
        );
    }


    private static function management_forms(
        \stdClass $item,
        array $teams,
        array $assignees
    ): string {
        $action =
            subscription_config::
                admin_work_item_action_page();

        $common =
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'id',
                    'value' => (int)$item->id,
                ]
            )
            . html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'sesskey',
                    'value' => sesskey(),
                ]
            );

        $status = self::management_field(
            get_string(
                'crm_work_status',
                'local_subscriptions'
            ),
            $action,
            $common,
            'status',
            html_writer::select(
                self::status_options(),
                'status',
                $item->status,
                false,
                ['class' => 'custom-select']
            )
        );

        $priority = self::management_field(
            get_string(
                'crm_work_priority',
                'local_subscriptions'
            ),
            $action,
            $common,
            'priority',
            html_writer::select(
                self::priority_options(),
                'priority',
                $item->priority,
                false,
                ['class' => 'custom-select']
            )
        );

        $teamoptions = [
            0 => get_string('none'),
        ];

        foreach ($teams as $team) {
            $teamoptions[(int)$team->id] =
                format_string($team->name);
        }

        $useroptions = [
            0 => get_string('none'),
        ];

        foreach ($assignees as $user) {
            $useroptions[(int)$user->id] =
                fullname($user);
        }

        $assignment = html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => $action,
                'class' =>
                    'crm-work-management-section',
            ]
        );

        $assignment .= $common;

        $assignment .=
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'action',
                    'value' => 'assign',
                ]
            );

        $assignment .= html_writer::tag(
            'h3',
            get_string(
                'crm_work_assignment_n127a',
                'local_subscriptions'
            ),
            ['class' => 'crm-work-management-title']
        );

        $assignment .= html_writer::label(
            get_string(
                'crm_work_team',
                'local_subscriptions'
            ),
            'id_work_assigned_team',
            false,
            ['class' => 'form-label']
        );

        $assignment .= html_writer::select(
            $teamoptions,
            'assignedteamid',
            (int)($item->assignedteamid ?? 0),
            false,
            [
                'id' => 'id_work_assigned_team',
                'class' => 'custom-select',
            ]
        );

        $assignment .= html_writer::label(
            get_string(
                'crm_work_assignee_n127a',
                'local_subscriptions'
            ),
            'id_work_assigned_user',
            false,
            ['class' => 'form-label mt-2']
        );

        $assignment .= html_writer::select(
            $useroptions,
            'assigneduserid',
            (int)($item->assigneduserid ?? 0),
            false,
            [
                'id' => 'id_work_assigned_user',
                'class' => 'custom-select',
            ]
        );

        $assignment .= html_writer::empty_tag(
            'input',
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-outline-primary btn-sm mt-3',
                'value' => get_string('savechanges'),
            ]
        );

        $assignment .= html_writer::end_tag('form');

        return html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'crm_work_management_n127a',
                    'local_subscriptions'
                ),
                ['class' => 'crm-work-panel-title']
            )
            . $status
            . $priority
            . $assignment,
            'crm-work-management-panel'
        );
    }

    private static function management_field(
        string $label,
        string $action,
        string $common,
        string $field,
        string $control
    ): string {
        $form = html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => $action,
                'class' =>
                    'crm-work-management-section',
            ]
        );

        $form .= $common;

        $form .= html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'action',
                'value' => $field,
            ]
        );

        $form .= html_writer::tag(
            'h3',
            s($label),
            ['class' => 'crm-work-management-title']
        );

        $form .= $control;

        $form .= html_writer::empty_tag(
            'input',
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-outline-primary btn-sm mt-2',
                'value' => get_string('savechanges'),
            ]
        );

        $form .= html_writer::end_tag('form');

        return $form;
    }


    private static function comments(\stdClass $item): string {
        $out = html_writer::tag(
            'h3',
            get_string(
                'crm_work_comments',
                'local_subscriptions'
            ),
            ['class' => 'h5']
        );

        /** @var \stdClass $workcomment */
        foreach (($item->comments ?? []) as $workcomment) {
            $author = (object)[
                'firstname' =>
                    (string)($workcomment->firstname ?? ''),
                'lastname' =>
                    (string)($workcomment->lastname ?? ''),
                'firstnamephonetic' =>
                    (string)($workcomment->firstnamephonetic ?? ''),
                'lastnamephonetic' =>
                    (string)($workcomment->lastnamephonetic ?? ''),
                'middlename' =>
                    (string)($workcomment->middlename ?? ''),
                'alternatename' =>
                    (string)($workcomment->alternatename ?? ''),
            ];

            $out .= html_writer::div(
                html_writer::div(
                    fullname($author),
                    'font-weight-bold'
                ) .
                html_writer::div(
                    AdminFormatter::datetime(
                        (int)($workcomment->timecreated ?? 0)
                    ),
                    'small text-muted mb-2'
                ) .
                format_text(
                    (string)($workcomment->body ?? ''),
                    FORMAT_PLAIN
                ),
                'border rounded p-3 mb-2'
            );
        }

        if (
            AdminSecurity::can(
                Capabilities::MANAGE_WORK_ITEMS
            )
        ) {
            $out .= html_writer::start_tag(
                'form',
                [
                    'method' => 'post',
                    'action' =>
                        subscription_config::
                            admin_work_item_action_page(),
                    'class' =>
                        'crm-work-action-form mt-3',
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'id',
                    'value' => (int)$item->id,
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'action',
                    'value' => 'comment',
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'sesskey',
                    'value' => sesskey(),
                ]
            );

            $out .= html_writer::tag(
                'textarea',
                '',
                [
                    'name' => 'body',
                    'rows' => 4,
                    'class' => 'form-control mb-2',
                    'required' => 'required',
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                    'value' => get_string(
                        'crm_work_add_comment',
                        'local_subscriptions'
                    ),
                ]
            );

            $out .= html_writer::end_tag('form');
        }

        return html_writer::div(
            $out,
            'crm-work-panel crm-work-comments-panel'
        );
    }

    private static function children(array $children): string {
        if (!$children) {
            return '';
        }
        $out = html_writer::tag('h3', get_string('crm_work_subtasks', 'local_subscriptions'), ['class' => 'h5']);
        foreach ($children as $child) {
            $out .= html_writer::link(
                new moodle_url(subscription_config::admin_work_item_view_page(), ['id' => $child->id]),
                s($child->reference) . ' — ' . format_string($child->title),
                ['class' => 'd-block mb-1']
            );
        }
        return html_writer::div($out, 'crm-work-panel');
    }

    private static function links(array $links): string {
        if (!$links) {
            return '';
        }
        $out = html_writer::tag('h3', get_string('crm_work_links', 'local_subscriptions'), ['class' => 'h5']);
        foreach ($links as $link) {
            $out .= html_writer::div(
                s($link->objecttype) . ' #' . (int)$link->objectid . ' · ' . s($link->relation),
                'small mb-1'
            );
        }
        return html_writer::div($out, 'crm-work-panel');
    }

    private static function history(array $history): string {
        if (!$history) {
            return '';
        }
        $out = html_writer::tag('h3', get_string('crm_work_history', 'local_subscriptions'), ['class' => 'h5']);
        foreach ($history as $entry) {
            if (!empty($entry->actorid)) {
                $actoruser = (object)[
                    'firstname' =>
                        (string)($entry->firstname ?? ''),
                    'lastname' =>
                        (string)($entry->lastname ?? ''),
                    'firstnamephonetic' =>
                        (string)($entry->firstnamephonetic ?? ''),
                    'lastnamephonetic' =>
                        (string)($entry->lastnamephonetic ?? ''),
                    'middlename' =>
                        (string)($entry->middlename ?? ''),
                    'alternatename' =>
                        (string)($entry->alternatename ?? ''),
                ];

                $actor = fullname($actoruser);
            } else {
                $actor = get_string('system');
            }
            $actionlabel = self::history_action_label(
                (string)$entry->action
            );

            $details = self::history_change_details(
                $entry
            );

            $out .= html_writer::div(
                html_writer::span(
                    s($actionlabel),
                    'font-weight-bold'
                )
                . (
                    $details !== ''
                        ? html_writer::span(
                            ' · ' . s($details),
                            'text-muted'
                        )
                        : ''
                )
                . ' · '
                . s($actor)
                . ' · '
                . AdminFormatter::datetime(
                    (int)$entry->timecreated
                ),
                'small border-bottom py-2'
            );
        }
        return html_writer::div($out, 'crm-work-panel crm-work-history-panel');
    }

    private static function history_action_label(
        string $action
    ): string {
        $key = match ($action) {
            'created' =>
                'crm_work_history_created_n127a1',
            'status_changed' =>
                'crm_work_history_status_changed_n127a1',
            'priority_changed' =>
                'crm_work_history_priority_changed_n127a1',
            'assignment_changed' =>
                'crm_work_history_assignment_changed_n127a1',
            'comment_added' =>
                'crm_work_history_comment_added_n127a1',
            'link_added' =>
                'crm_work_history_link_added_n127a1',
            default => '',
        };

        if ($key === '') {
            return str_replace(
                '_',
                ' ',
                $action
            );
        }

        return get_string(
            $key,
            'local_subscriptions'
        );
    }

    private static function history_change_details(
        \stdClass $entry
    ): string {
        $action = (string)($entry->action ?? '');

        if (
            !in_array(
                $action,
                [
                    'status_changed',
                    'priority_changed',
                ],
                true
            )
        ) {
            return '';
        }

        $old = trim(
            (string)($entry->oldvalue ?? '')
        );
        $new = trim(
            (string)($entry->newvalue ?? '')
        );

        if (
            $old === ''
            && $new === ''
        ) {
            return '';
        }

        $label = static function(
            string $value,
            string $kind
        ): string {
            if ($value === '') {
                return '—';
            }

            if ($kind === 'status') {
                return WorkItemPresentation::status_label(
                    $value
                );
            }

            return WorkItemPresentation::priority_label(
                $value
            );
        };

        $kind =
            $action === 'status_changed'
                ? 'status'
                : 'priority';

        return $label($old, $kind)
            . ' → '
            . $label($new, $kind);
    }

    private static function badge_status(string $status): string {
        return html_writer::span(
            WorkItemPresentation::status_label($status),
            'badge ' . WorkItemPresentation::status_class($status)
        );
    }

    private static function badge_priority(string $priority): string {
        return html_writer::span(
            WorkItemPresentation::priority_label($priority),
            'badge ' . WorkItemPresentation::priority_class($priority)
        );
    }

    public static function status_options(): array {
        $options = [];
        foreach (WorkItemStatus::all() as $status) {
            $options[$status] = WorkItemPresentation::status_label($status);
        }
        return $options;
    }

    public static function priority_options(): array {
        $options = [];
        foreach (WorkItemPriority::all() as $priority) {
            $options[$priority] = WorkItemPresentation::priority_label($priority);
        }
        return $options;
    }

    public static function type_options(): array {
        $options = [];
        foreach (WorkItemType::all() as $type) {
            $options[$type] = WorkItemPresentation::type_label($type);
        }
        return $options;
    }

    private static function text_input(string $name, string $label, string $value, string $class): string {
        return html_writer::div(
            html_writer::label($label, 'id_' . $name, false, ['class' => 'form-label']) .
            html_writer::empty_tag('input', [
                'type' => 'text', 'name' => $name, 'id' => 'id_' . $name,
                'value' => $value, 'class' => 'form-control',
            ]),
            $class
        );
    }

    private static function select_input(string $name, string $label, array $options, string $value, string $class): string {
        return html_writer::div(
            html_writer::label($label, 'id_' . $name, false, ['class' => 'form-label']) .
            html_writer::select($options, $name, $value, false, ['id' => 'id_' . $name, 'class' => 'custom-select']),
            $class
        );
    }
}