<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/** CRM-only accessible, audit-friendly presentation of manual promotion bonuses. */
final class CommercePedagogicalXpBonusHistoryRenderer {
    public static function render(\stdClass $record, \moodle_url $actionurl,
        string $targettype, int $targetid, bool $cancancel = true): string {
        $cancelled = $record->cancelledat !== null;
        $actor = static function(int $userid): string {
            if ($userid <= 0) {
                return '';
            }
            return \html_writer::link(
                new \moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $userid]),
                'Admin #' . $userid,
                ['class' => 'commerce-ped-xp-actor',
                    'title' => get_string('commerce_education_xp_user360_link', 'local_subscriptions')]
            );
        };
        $text = \html_writer::span(CommercePedagogicalPointsPresenter::promotion((int)$record->points, true),
            'commerce-ped-xp-amount') . ' · ' . s((string)$record->reason)
            . ' · ' . userdate((int)$record->timecreated)
            . ' · ' . $actor((int)$record->actorid);
        if ($cancelled) {
            $text .= \html_writer::div(
                get_string('commerce_education_xp_manual_cancelled', 'local_subscriptions') . ' · '
                . userdate((int)$record->cancelledat) . ' · '
                . $actor((int)$record->cancelledby),
                'commerce-ped-xp-cancelled-info'
            );
        }
        $result = \html_writer::start_div('commerce-ped-xp-history-entry' . ($cancelled ? ' is-cancelled' : ''));
        if (!$cancelled && $cancancel) {
            $result .= \html_writer::start_tag('form', [
                'method' => 'post', 'action' => $actionurl->out(false),
                'class' => 'commerce-ped-xp-cancel-form',
                'onsubmit' => 'return confirm(' . json_encode(
                    get_string('commerce_education_xp_manual_cancel_confirm', 'local_subscriptions'),
                    JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG
                ) . ');',
            ]);
            foreach ([
                'sesskey' => sesskey(), 'action' => 'xpcancel',
                ($targettype === CommercePedagogicalXpAdjustmentService::USER ? 'userid' : 'groupid') => $targetid,
                'adjustmentid' => (int)$record->id,
            ] as $name => $value) {
                $result .= \html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => $name, 'value' => $value,
                ]);
            }
            $result .= \html_writer::tag('button',
                \html_writer::tag('i', '', ['class' => 'fa fa-trash-o', 'aria-hidden' => 'true']),
                [
                    'type' => 'submit', 'class' => 'commerce-ped-xp-cancel-btn',
                    'title' => get_string('commerce_education_xp_manual_cancel', 'local_subscriptions'),
                    'aria-label' => get_string('commerce_education_xp_manual_cancel', 'local_subscriptions'),
                ]);
            $result .= \html_writer::end_tag('form');
        } else if ($cancelled) {
            $result .= \html_writer::span(
                \html_writer::tag('i', '', ['class' => 'fa fa-ban', 'aria-hidden' => 'true']),
                'commerce-ped-xp-cancel-icon',
                ['title' => get_string('commerce_education_xp_manual_cancelled', 'local_subscriptions')]
            );
        }
        return $result . \html_writer::div($text, 'commerce-ped-xp-history-copy')
            . \html_writer::end_div();
    }
}
