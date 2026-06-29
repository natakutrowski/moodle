<?php

namespace local_subscriptions\support;

defined('MOODLE_INTERNAL') || die();

use html_writer;

final class DigitalPresenter {

    public static function render_status_badge(?string $status): string {
        $status = strtoupper(trim((string)$status));

        $classes = [
            'PAID' => 'badge bg-success',
            'COMPLETED' => 'badge bg-success',
            'FAILED' => 'badge bg-danger',
            'CANCELED' => 'badge bg-secondary',
            'CANCELLED' => 'badge bg-secondary',
            'PENDING' => 'badge bg-warning text-dark',
            'CREATED' => 'badge bg-info text-dark',
            'EXPIRED' => 'badge bg-dark',
            'ERROR' => 'badge bg-danger',
        ];

        $class = $classes[$status] ?? 'badge bg-light text-dark border';

        return html_writer::span($status ?: '-', $class);
    }
}