<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

/** Accessible two-state switch for subscription plans. */
final class CommercePlanStatusToggleRenderer {
    public static function render(int $planid, bool $active, moodle_url $returnurl): string {
        $action = new moodle_url(subscription_config::commerce_plan_toggle_page());
        $label = $active ? get_string('active') : get_string('inactive');
        $stateclass = $active ? ' is-active' : ' is-inactive';

        $content = html_writer::span('', 'commerce-toggle-track', [
            'aria-hidden' => 'true',
        ]) . html_writer::span($label, 'commerce-toggle-label');

        $form = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $action->out(false),
            'class' => 'commerce-toggle-form',
        ]);
        $form .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $form .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $planid,
        ]);
        $form .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'returnurl',
            'value' => $returnurl->out_as_local_url(false),
        ]);
        $form .= html_writer::tag('button', $content, [
            'type' => 'submit',
            'class' => 'commerce-toggle' . $stateclass,
            'role' => 'switch',
            'aria-checked' => $active ? 'true' : 'false',
            'title' => get_string('commerce_plan_toggle_help', 'local_subscriptions'),
        ]);
        $form .= html_writer::end_tag('form');

        return $form;
    }
}
