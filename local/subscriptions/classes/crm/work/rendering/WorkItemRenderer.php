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

    public static function render_detail(\stdClass $item, array $teams, array $assignees): string {
        $out = html_writer::start_tag('article', [
            'class' => 'crm-work-detail',
            'aria-labelledby' => 'crm-work-title-' . (int)$item->id,
        ]);

        $badges = self::badge_status((string)$item->status) . ' ' .
            self::badge_priority((string)$item->priority) . ' ' .
            html_writer::span(
                WorkItemPresentation::type_label((string)$item->type),
                'badge bg-light text-dark border'
            );

        $out .= html_writer::div(
            html_writer::tag('div', s($item->reference), ['class' => 'text-muted small']) .
            html_writer::tag('h2', format_string($item->title), [
                'id' => 'crm-work-title-' . (int)$item->id,
                'class' => 'h3 mb-2',
            ]) .
            html_writer::div($badges),
            'card card-body mb-3'
        );

        $out .= html_writer::div(
            format_text((string)$item->description, FORMAT_PLAIN),
            'card card-body mb-3 crm-work-description'
        );

        if (AdminSecurity::can(Capabilities::MANAGE_WORK_ITEMS)) {
            $out .= self::management_forms($item, $teams, $assignees);
        }

        $out .= self::children($item->children ?? []);
        $out .= self::comments($item);
        $out .= self::links($item->links ?? []);
        $out .= self::history($item->history ?? []);

        return $out . html_writer::end_tag('article');
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

        $meta = [];
        if (!empty($item->teamname)) {
            $meta[] = s($item->teamname);
        }
        if (!empty($item->assigneduserid)) {
            $meta[] = get_string('crm_work_assigned_user', 'local_subscriptions');
        }
        if (!empty($item->dueat)) {
            $meta[] = get_string('crm_work_due', 'local_subscriptions') . ': ' .
                AdminFormatter::datetime((int)$item->dueat);
        }

        return html_writer::div(
            html_writer::div(
                html_writer::div(
                    html_writer::tag('div', s($item->reference), ['class' => 'small text-muted']) .
                    html_writer::link($url, format_string($item->title), ['class' => 'h5 d-block mb-2']) .
                    html_writer::div(implode(' · ', $meta), 'small text-muted'),
                    'crm-work-card-main'
                ) .
                html_writer::div(
                    self::badge_status((string)$item->status) . ' ' .
                    self::badge_priority((string)$item->priority),
                    'crm-work-card-badges'
                ),
                'd-flex justify-content-between gap-3'
            ),
            'card card-body mb-2 crm-work-card',
            ['role' => 'listitem']
        );
    }

    private static function management_forms(\stdClass $item, array $teams, array $assignees): string {
        $action = subscription_config::admin_work_item_action_page();
        $common = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => (int)$item->id]) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

        $status = html_writer::start_tag('form', ['method' => 'post', 'action' => $action, 'class' => 'crm-work-action-form']);
        $status .= $common;
        $status .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'status']);
        $status .= html_writer::select(self::status_options(), 'status', $item->status, false, ['class' => 'custom-select']);
        $status .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('savechanges')]);
        $status .= html_writer::end_tag('form');

        $priority = html_writer::start_tag('form', ['method' => 'post', 'action' => $action, 'class' => 'crm-work-action-form']);
        $priority .= $common;
        $priority .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'priority']);
        $priority .= html_writer::select(self::priority_options(), 'priority', $item->priority, false, ['class' => 'custom-select']);
        $priority .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('savechanges')]);
        $priority .= html_writer::end_tag('form');

        $teamoptions = [0 => get_string('none')];
        foreach ($teams as $team) {
            $teamoptions[(int)$team->id] = format_string($team->name);
        }
        $useroptions = [0 => get_string('none')];
        foreach ($assignees as $user) {
            $useroptions[(int)$user->id] = fullname($user);
        }

        $assignment = html_writer::start_tag('form', ['method' => 'post', 'action' => $action, 'class' => 'crm-work-action-form']);
        $assignment .= $common;
        $assignment .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'assign']);
        $assignment .= html_writer::select($teamoptions, 'assignedteamid', (int)($item->assignedteamid ?? 0), false, ['class' => 'custom-select']);
        $assignment .= html_writer::select($useroptions, 'assigneduserid', (int)($item->assigneduserid ?? 0), false, ['class' => 'custom-select']);
        $assignment .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('savechanges')]);
        $assignment .= html_writer::end_tag('form');

        return html_writer::div($status . $priority . $assignment, 'card card-body mb-3 crm-work-management');
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
            'card card-body mb-3'
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
        return html_writer::div($out, 'card card-body mb-3');
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
        return html_writer::div($out, 'card card-body mb-3');
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
            $out .= html_writer::div(
                html_writer::span(s($entry->action), 'font-weight-bold') . ' · ' .
                s($actor) . ' · ' . AdminFormatter::datetime((int)$entry->timecreated),
                'small border-bottom py-2'
            );
        }
        return html_writer::div($out, 'card card-body mb-3');
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