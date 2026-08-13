<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\identity;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityGraphService;
use moodle_url;

/** Renders known email history and review-only potential identities. */
final class User360IdentityGraphRenderer {
    public static function render(int $userid): string {
        global $DB;
        $graph = (new CommerceCustomerIdentityGraphService($DB))->for_user($userid);
        return self::render_graph($graph);
    }

    public static function render_email(string $email): string {
        global $DB;
        $graph = (new CommerceCustomerIdentityGraphService($DB))->for_email($email);
        return self::render_graph($graph);
    }

    private static function render_graph(array $graph): string {
        $out = html_writer::tag('h3', get_string('user360_identity_graph_title', 'local_subscriptions'), ['class' => 'h5']);
        $out .= html_writer::div(get_string('user360_identity_graph_help', 'local_subscriptions'), 'small text-muted mb-3');

        $items = '';
        foreach ($graph['emails'] as $entry) {
            $badge = $entry['current']
                ? html_writer::span(get_string('user360_identity_current', 'local_subscriptions'), 'badge bg-success ms-2')
                : html_writer::span(get_string('user360_identity_historical', 'local_subscriptions'), 'badge bg-light text-dark border ms-2');
            $sources = [];
            foreach ($entry['evidence'] as $evidence) {
                $sources[] = get_string('user360_identity_source_' . $evidence['source'], 'local_subscriptions') . ' #' . (int)$evidence['id'];
            }
            $items .= html_writer::tag('li',
                html_writer::tag('strong', s($entry['email'])) . $badge
                . html_writer::div(s(implode(' · ', array_unique($sources))), 'small text-muted'),
                ['class' => 'list-group-item']
            );
        }
        if ($items !== '') {
            $out .= html_writer::tag('ul', $items, ['class' => 'list-group mb-3']);
        }

        if ($graph['potential'] !== []) {
            $out .= html_writer::tag('h4', get_string('user360_identity_potential_title', 'local_subscriptions'), ['class' => 'h6 mt-3']);
            $out .= html_writer::div(get_string('user360_identity_potential_help', 'local_subscriptions'), 'alert alert-warning py-2 small');
            foreach ($graph['potential'] as $candidate) {
                $label = trim($candidate['name']) !== '' ? $candidate['name'] : ('#' . $candidate['userid']);
                $meta = $candidate['email'] . ' · #' . $candidate['userid'] . ' · score ' . $candidate['score'];
                $out .= html_writer::div(
                    html_writer::link(new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $candidate['userid']]), s($label))
                    . html_writer::div(s($meta), 'small text-muted'),
                    'border rounded p-2 mb-2'
                );
            }
        }
        return html_writer::div($out, 'crm-user360-identity-graph');
    }
}
