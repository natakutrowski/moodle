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

        $icon = $certified ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation';
        $title = $certified
            ? get_string('commerce_mail_health_certified', 'local_subscriptions')
            : get_string('commerce_mail_health_attention', 'local_subscriptions');
        $class = $certified ? 'is-certified' : 'has-attention';

        $metrics = [
            self::metric(get_string('commerce_mail_health_ok', 'local_subscriptions'), $ok, 'is-ok'),
            self::metric(get_string('commerce_mail_health_warnings', 'local_subscriptions'), $warnings, 'is-warning'),
            self::metric(get_string('commerce_mail_health_errors', 'local_subscriptions'), $errors, 'is-error'),
        ];

        $release = html_writer::span(
            s(CommerceMailReleaseManifest::STATUS . ' · ' . CommerceMailReleaseManifest::LIFECYCLE),
            'commerce-mail-health__release'
        );
        $heading = html_writer::div(
            html_writer::tag('i', '', ['class' => $icon, 'aria-hidden' => 'true']) .
            html_writer::div(
                html_writer::div(
                    html_writer::tag('strong', s($title), ['class' => 'commerce-mail-health__title']) . $release,
                    'commerce-mail-health__title-row'
                ) .
                html_writer::div(
                    get_string('commerce_mail_health_readonly', 'local_subscriptions'),
                    'commerce-mail-health__subtitle'
                )
            ),
            'commerce-mail-health__heading'
        );

        return html_writer::div(
            $heading . html_writer::div(implode('', $metrics), 'commerce-mail-health__metrics'),
            'commerce-mail-health ' . $class
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
