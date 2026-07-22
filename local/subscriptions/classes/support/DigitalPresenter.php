<?php

namespace local_subscriptions\support;

defined('MOODLE_INTERNAL') || die();

use html_writer;

final class DigitalPresenter {

    public static function render_status_badge(?string $status): string {
        $status = strtoupper(trim((string)$status));

        $classes = [
            'PAID' => 'crm-commerce-status crm-commerce-status--success badge bg-success',
            'COMPLETED' => 'crm-commerce-status crm-commerce-status--success badge bg-success',
            'FAILED' => 'crm-commerce-status crm-commerce-status--danger badge bg-danger',
            'CANCELED' => 'crm-commerce-status crm-commerce-status--neutral badge bg-secondary',
            'CANCELLED' => 'crm-commerce-status crm-commerce-status--neutral badge bg-secondary',
            'PENDING' => 'crm-commerce-status crm-commerce-status--warning badge bg-warning text-dark',
            'CREATED' => 'crm-commerce-status crm-commerce-status--info badge bg-info text-dark',
            'EXPIRED' => 'crm-commerce-status crm-commerce-status--neutral badge bg-dark',
            'ERROR' => 'crm-commerce-status crm-commerce-status--danger badge bg-danger',
        ];

        $class = $classes[$status] ?? 'crm-commerce-status crm-commerce-status--neutral badge bg-light text-dark border';

        return html_writer::span($status ?: '-', $class);
    }

    public static function render_provider_icon(string $provider): string {
        global $CFG;

        $provider = strtolower(trim($provider));

        $files = [
            'stripe' => 'stripe.png',
            'alfa' => 'alfa.png',
        ];

        if (empty($files[$provider])) {
            return s($provider ?: '-');
        }

        $path = $CFG->dirroot . '/local/subscriptions/pix/email/' . $files[$provider];

        if (!file_exists($path)) {
            return s($provider);
        }

        return html_writer::empty_tag('img', [
            'src' => $CFG->wwwroot . '/local/subscriptions/pix/email/' . $files[$provider],
            'alt' => s($provider),
            'title' => s($provider),
            'style' => 'height:28px;width:auto;',
        ]);
    }

}