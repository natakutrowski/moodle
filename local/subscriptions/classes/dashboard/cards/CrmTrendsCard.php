<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\intelligence\history\CrmScoreHistoryRepository;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use moodle_url;

final class CrmTrendsCard implements DashboardCard {

    public static function render(): string {

        if (!Capabilities::can_view_users()) {
            return '';
        }

        global $DB;

        $history = new CrmScoreHistoryRepository();

        $records = $DB->get_records_sql("
            SELECT s.*
            FROM {local_subscriptions_crm_score} s
            JOIN (
                    SELECT userid, MAX(id) AS maxid
                    FROM {local_subscriptions_crm_score}
                GROUP BY userid
            ) latest ON latest.userid = s.userid AND latest.maxid = s.id
        ORDER BY s.globalscore DESC, s.riskscore DESC
        ", [], 0, 5);

        $out = html_writer::tag('h3', '📈 ' . get_string('crm_trends_title', 'local_subscriptions'), [
            'class' => 'h4 mb-3',
        ]);

        if (empty($records)) {
            $out .= html_writer::div(get_string('crm_trends_empty', 'local_subscriptions'), 'text-muted');
            return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
        }

        foreach ($records as $record) {
            $user = $DB->get_record('user', ['id' => $record->userid], '*', IGNORE_MISSING);
            if (!$user) {
                continue;
            }

            $url = new moodle_url(subscription_config::admin_user_view_page(), ['id' => $user->id]);

            $out .= html_writer::div(
                html_writer::link($url, s(fullname($user)), ['class' => 'fw-bold']) .
                html_writer::div(
                    get_string('crm_intelligence_global_score', 'local_subscriptions') . ': ' . (int)$record->globalscore . '/100',
                    'small text-muted'
                ),
                'border rounded p-2 mb-2'
            );
        }

        return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
    }
}