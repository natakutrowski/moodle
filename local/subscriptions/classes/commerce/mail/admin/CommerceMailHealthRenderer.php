<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\admin;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\mail\certification\CommerceMailCertificationFinding;
use local_subscriptions\commerce\mail\certification\CommerceMailCertificationReport;
use local_subscriptions\commerce\mail\certification\CommerceMailReleaseManifest;

/** Compact CRM health banner based on the read-only mail engine certification report. */
final class CommerceMailHealthRenderer {

    public static function render(CommerceMailCertificationReport $report): string {
        $ok = $report->count(CommerceMailCertificationFinding::OK);
        $warnings = $report->count(CommerceMailCertificationFinding::WARNING);
        $errors = $report->count(CommerceMailCertificationFinding::ERROR);
        $certified = $report->is_certified();

        $icon = $certified ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle';
        $title = $certified
            ? get_string('commerce_mail_health_operational', 'local_subscriptions')
            : get_string('commerce_mail_health_attention', 'local_subscriptions');
        $class = $certified ? 'is-certified' : 'has-attention';

        $metrics = [
            self::metric(get_string('commerce_mail_health_ok', 'local_subscriptions'), $ok, 'is-ok'),
            self::metric(get_string('commerce_mail_health_warnings', 'local_subscriptions'), $warnings, 'is-warning'),
            self::metric(get_string('commerce_mail_health_errors', 'local_subscriptions'), $errors, 'is-error'),
        ];

        $release = CommerceMailReleaseManifest::STATUS . ' · ' . CommerceMailReleaseManifest::LIFECYCLE;
        $diagnostic = html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                get_string('commerce_mail_health_diagnostic', 'local_subscriptions')
                    . html_writer::tag('i', '', ['class' => 'fa fa-chevron-down', 'aria-hidden' => 'true']),
                ['class' => 'commerce-mail-health__diagnostic-toggle']
            )
            . html_writer::div(
                html_writer::div(s($release), 'commerce-mail-health__diagnostic-release')
                . html_writer::div(get_string('commerce_mail_health_readonly', 'local_subscriptions'), 'commerce-mail-health__diagnostic-text'),
                'commerce-mail-health__diagnostic-panel'
            ),
            ['class' => 'commerce-mail-health__diagnostic']
        );

        $heading = html_writer::div(
            html_writer::tag('i', '', ['class' => $icon, 'aria-hidden' => 'true'])
            . html_writer::div(
                html_writer::tag('strong', s($title), ['class' => 'commerce-mail-health__title'])
                . html_writer::div(get_string('commerce_mail_health_operational_subtitle', 'local_subscriptions'), 'commerce-mail-health__subtitle')
            ),
            'commerce-mail-health__heading'
        );

        return html_writer::div(
            $heading
            . html_writer::div(implode('', $metrics), 'commerce-mail-health__metrics')
            . $diagnostic,
            'commerce-mail-health ' . $class
        );
    }

    /** Compact status used alongside the e-mail workspace tabs. */
    public static function render_compact(CommerceMailCertificationReport $report): string {
        $warnings = $report->count(CommerceMailCertificationFinding::WARNING);
        $errors = $report->count(CommerceMailCertificationFinding::ERROR);
        $certified = $report->is_certified();
        $label = $certified
            ? get_string('commerce_mail_health_operational', 'local_subscriptions')
            : get_string('commerce_mail_health_attention', 'local_subscriptions');
        $detail = $certified
            ? get_string('commerce_mail_health_compact_ok', 'local_subscriptions')
            : get_string('commerce_mail_health_compact_issues', 'local_subscriptions', (object)['warnings' => $warnings, 'errors' => $errors]);

        return html_writer::div(
            html_writer::span('', 'commerce-mail-health-compact__dot ' . ($certified ? 'is-ok' : 'has-attention'))
            . html_writer::span(s($label), 'commerce-mail-health-compact__label')
            . html_writer::span(s($detail), 'commerce-mail-health-compact__detail')
            . html_writer::link(
                new \moodle_url('/local/subscriptions/admin/commerce/mail/index.php', ['diagnostic' => 1]),
                get_string('commerce_mail_health_diagnostic', 'local_subscriptions'),
                ['class' => 'commerce-mail-health-compact__diagnostic']
            ),
            'commerce-mail-health-compact'
        );
    }

    private static function metric(string $label, int $value, string $class): string {
        return html_writer::div(
            html_writer::span((string)$value, 'commerce-mail-health__metric-value') .
            html_writer::span(s($label), 'commerce-mail-health__metric-label'),
            'commerce-mail-health__metric ' . $class
        );
    }
}
