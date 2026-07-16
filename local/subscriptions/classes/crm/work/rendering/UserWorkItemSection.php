<?php

namespace local_subscriptions\crm\work\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;
use local_subscriptions\subscription_config;
use moodle_url;

final class UserWorkItemSection {

    public static function render(int $userid): string {
        if (!AdminSecurity::can(Capabilities::VIEW_WORK_ITEMS)) {
            return '';
        }

        $summary = (new WorkItemReadRepository())->get_user_summary($userid);

        $cards = [
            get_string('crm_work_total', 'local_subscriptions') => $summary->totalcount,
            get_string('crm_work_active', 'local_subscriptions') => $summary->activecount,
            get_string('crm_work_urgent', 'local_subscriptions') => $summary->urgentcount,
            get_string('crm_work_overdue', 'local_subscriptions') => $summary->overduecount,
        ];

        $out = html_writer::start_div('row g-2 mb-3');
        foreach ($cards as $label => $value) {
            $out .= html_writer::div(
                html_writer::div((string)$value, 'h4 mb-1') .
                html_writer::div($label, 'small text-muted'),
                'col-6 col-lg-3 card card-body'
            );
        }
        $out .= html_writer::end_div();

        foreach ($summary->recent as $item) {
            $out .= html_writer::link(
                new moodle_url(subscription_config::admin_work_item_view_page(), ['id' => $item->id]),
                s($item->reference) . ' — ' . format_string($item->title),
                ['class' => 'd-block mb-2']
            );
        }

        $out .= html_writer::link(
            new moodle_url(subscription_config::admin_work_items_page(), ['targetuserid' => $userid]),
            get_string('crm_work_open_user_items', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary btn-sm mt-2']
        );

        if (AdminSecurity::can(Capabilities::MANAGE_WORK_ITEMS)) {
            $out .= ' ' . html_writer::link(
                new moodle_url(subscription_config::admin_work_item_create_page(), [
                    'targetuserid' => $userid,
                    'source' => 'user_360',
                ]),
                get_string('crm_work_create_for_user', 'local_subscriptions'),
                ['class' => 'btn btn-primary btn-sm mt-2']
            );
        }

        return $out;
    }
}