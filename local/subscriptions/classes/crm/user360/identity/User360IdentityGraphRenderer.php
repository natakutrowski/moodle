<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\identity;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityGraphService;
use moodle_url;

/**
 * Renders known e-mail history and review-only potential identities.
 *
 * N11.7A keeps identity evidence explainable without exposing a long,
 * stressful stream of individual Commerce / Legacy source rows.
 */
final class User360IdentityGraphRenderer {

    public static function render(int $userid): string {
        global $DB;

        $graph = (new CommerceCustomerIdentityGraphService($DB))
            ->for_user($userid);

        return self::render_graph($graph);
    }

    public static function render_email(string $email): string {
        global $DB;

        $graph = (new CommerceCustomerIdentityGraphService($DB))
            ->for_email($email);

        return self::render_graph($graph);
    }

    private static function render_graph(array $graph): string {
        $out = html_writer::tag(
            'h3',
            get_string(
                'user360_identity_graph_title',
                'local_subscriptions'
            ),
            ['class' => 'h5']
        );

        $out .= html_writer::div(
            get_string(
                'user360_identity_graph_help',
                'local_subscriptions'
            ),
            'small text-muted mb-3'
        );

        $items = '';

        foreach ($graph['emails'] as $entry) {
            $badge = !empty($entry['current'])
                ? html_writer::span(
                    get_string(
                        'user360_identity_current',
                        'local_subscriptions'
                    ),
                    'badge bg-success'
                )
                : html_writer::span(
                    get_string(
                        'user360_identity_historical',
                        'local_subscriptions'
                    ),
                    'badge bg-light text-dark border'
                );

            $evidence = is_array($entry['evidence'] ?? null)
                ? $entry['evidence']
                : [];

            $items .= html_writer::tag(
                'li',
                html_writer::div(
                    html_writer::div(
                        html_writer::tag(
                            'strong',
                            s((string)$entry['email']),
                            [
                                'class' =>
                                    'crm-user360-identity-email-address',
                            ]
                        )
                        . $badge,
                        'crm-user360-identity-email-heading'
                    )
                    . self::evidence_summary($evidence)
                    . self::evidence_details($evidence),
                    'crm-user360-identity-email-card'
                ),
                [
                    'class' =>
                        'list-group-item crm-user360-identity-email-item',
                ]
            );
        }

        if ($items !== '') {
            $out .= html_writer::tag(
                'ul',
                $items,
                ['class' => 'list-group mb-3 crm-user360-identity-email-list']
            );
        }

        if ($graph['potential'] !== []) {
            $candidates = '';

            foreach ($graph['potential'] as $candidate) {
                $label = trim((string)$candidate['name']) !== ''
                    ? (string)$candidate['name']
                    : ('#' . (int)$candidate['userid']);

                $confidence = self::confidence_label(
                    (int)($candidate['score'] ?? 0)
                );

                $candidates .= html_writer::div(
                    html_writer::div(
                        html_writer::link(
                            new moodle_url(
                                '/local/subscriptions/admin/users/view.php',
                                ['id' => (int)$candidate['userid']]
                            ),
                            s($label),
                            [
                                'class' =>
                                    'crm-user360-identity-candidate-name',
                            ]
                        )
                        . html_writer::div(
                            s((string)$candidate['email'])
                            . ' · #'
                            . (int)$candidate['userid'],
                            'crm-user360-identity-candidate-meta'
                        ),
                        'crm-user360-identity-candidate-copy'
                    )
                    . html_writer::div(
                        html_writer::span(
                            s($confidence),
                            'crm-user360-identity-confidence'
                        )
                        . html_writer::link(
                            new moodle_url(
                                '/local/subscriptions/admin/users/merge.php',
                                [
                                    'userid' => (int)$candidate['userid'],
                                ]
                            ),
                            html_writer::tag('i', '', [
                                'class' => 'fa fa-random',
                                'aria-hidden' => 'true',
                            ])
                            . html_writer::span(
                                get_string(
                                    'crm_user360_n117b_merge',
                                    'local_subscriptions'
                                )
                            ),
                            [
                                'class' =>
                                    'btn btn-sm btn-outline-secondary '
                                    . 'crm-user360-identity-merge-link',
                            ]
                        ),
                        'crm-user360-identity-candidate-actions'
                    ),
                    'crm-user360-identity-candidate'
                );
            }

        }

        $identitycolumn = html_writer::div(
            $out,
            'crm-user360-identity-column crm-user360-identity-column-main'
        );

        $potentialcolumn = '';

        if ($graph['potential'] !== []) {
            $potentialcolumn = html_writer::div(
                html_writer::tag(
                    'h3',
                    get_string(
                        'crm_user360_n117b_potential_column_title',
                        'local_subscriptions'
                    ),
                    ['class' => 'h5']
                )
                . html_writer::div(
                    get_string(
                        'user360_identity_potential_help',
                        'local_subscriptions'
                    ),
                    'small text-muted mb-3'
                )
                . html_writer::tag(
                    'details',
                    html_writer::tag(
                        'summary',
                        html_writer::span(
                            get_string(
                                'user360_identity_potential_title',
                                'local_subscriptions'
                            )
                            . ' '
                            . html_writer::span(
                                (string)count($graph['potential']),
                                'crm-user360-identity-potential-count'
                            ),
                            'crm-user360-identity-potential-summary-copy'
                        )
                        . html_writer::tag('i', '', [
                            'class' =>
                                'fa fa-chevron-down '
                                . 'crm-user360-identity-potential-chevron',
                            'aria-hidden' => 'true',
                        ]),
                        [
                            'class' =>
                                'crm-user360-identity-potential-summary',
                        ]
                    )
                    . html_writer::div(
                        html_writer::div(
                            html_writer::tag('i', '', [
                                'class' => 'fa fa-exclamation-triangle',
                                'aria-hidden' => 'true',
                            ])
                            . html_writer::span(
                                get_string(
                                    'user360_identity_potential_help',
                                    'local_subscriptions'
                                )
                            ),
                            'crm-user360-identity-potential-warning'
                        )
                        . html_writer::div(
                            $candidates,
                            'crm-user360-identity-candidates'
                        ),
                        'crm-user360-identity-potential-body'
                    ),
                    [
                        'class' =>
                            'crm-user360-identity-potential',
                        'open' => 'open',
                    ]
                ),
                'crm-user360-identity-column '
                    . 'crm-user360-identity-column-potential'
            );
        }

        return html_writer::div(
            $identitycolumn . $potentialcolumn,
            'crm-user360-identity-graph crm-user360-identity-grid'
        );
    }

    /**
     * Render grouped, customer-friendly identity evidence.
     *
     * Example:
     * [Moodle account #113] [10 Commerce orders] [6 Legacy purchases]
     */
    private static function evidence_summary(array $evidence): string {
        $groups = self::group_evidence($evidence);

        if ($groups === []) {
            return '';
        }

        $items = '';

        foreach ($groups as $source => $ids) {
            $items .= html_writer::span(
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' =>
                            self::source_icon($source),
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    s(
                        self::source_summary_label(
                            $source,
                            $ids
                        )
                    )
                ),
                'crm-user360-identity-source-chip '
                    . 'is-' . self::source_tone($source)
            );
        }

        return html_writer::div(
            $items,
            'crm-user360-identity-source-summary'
        );
    }

    /**
     * Keep individual technical IDs available, but collapsed by default.
     */
    private static function evidence_details(array $evidence): string {
        if (count($evidence) <= 1) {
            return '';
        }

        $groups = self::group_evidence($evidence);
        $rows = '';

        foreach ($groups as $source => $ids) {
            $idlabels = array_map(
                static fn(int $id): string => '#' . $id,
                $ids
            );

            $rows .= html_writer::div(
                html_writer::span(
                    s(self::source_name($source)),
                    'crm-user360-identity-source-detail-name'
                )
                . html_writer::span(
                    s(implode(', ', $idlabels)),
                    'crm-user360-identity-source-detail-ids'
                ),
                'crm-user360-identity-source-detail-row'
            );
        }

        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                get_string(
                    'crm_user360_n117a_identity_source_details',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'crm-user360-identity-source-details-summary',
                ]
            )
            . html_writer::div(
                $rows,
                'crm-user360-identity-source-details-body'
            ),
            [
                'class' =>
                    'crm-user360-identity-source-details',
            ]
        );
    }

    /**
     * @return array<string,array<int,int>>
     */
    private static function group_evidence(array $evidence): array {
        $groups = [];

        foreach ($evidence as $item) {
            if (!is_array($item)) {
                continue;
            }

            $source = trim(
                (string)($item['source'] ?? '')
            );

            if ($source === '') {
                continue;
            }

            $id = (int)($item['id'] ?? 0);

            $groups[$source] ??= [];

            if (
                $id > 0
                && !in_array($id, $groups[$source], true)
            ) {
                $groups[$source][] = $id;
            }
        }

        return $groups;
    }

    private static function source_summary_label(
        string $source,
        array $ids
    ): string {
        $count = count($ids);

        if (
            in_array(
                $source,
                ['moodle_current', 'external_current'],
                true
            )
        ) {
            if ($count === 1) {
                return self::source_name($source)
                    . ' #' . reset($ids);
            }

            return self::source_name($source);
        }

        $key = match ($source) {
            'commerce_purchase' =>
                'crm_user360_n117a_commerce_orders',
            'legacy_digital' =>
                'crm_user360_n117a_legacy_purchases',
            'personal_offer' =>
                'crm_user360_n117a_personal_offers',
            'merged_account',
            'merge_identity_history' =>
                'crm_user360_n117a_merge_sources',
            default => '',
        };

        if ($key !== '') {
            return get_string(
                $key,
                'local_subscriptions',
                $count
            );
        }

        return self::source_name($source)
            . ($count > 0 ? ' · ' . $count : '');
    }

    private static function source_name(string $source): string {
        $key = 'user360_identity_source_' . $source;

        return get_string_manager()->string_exists(
            $key,
            'local_subscriptions'
        )
            ? get_string(
                $key,
                'local_subscriptions'
            )
            : ucfirst(
                str_replace('_', ' ', $source)
            );
    }

    private static function source_icon(string $source): string {
        return match ($source) {
            'moodle_current' =>
                'fa fa-graduation-cap',
            'external_current' =>
                'fa fa-id-card-o',
            'commerce_purchase' =>
                'fa fa-shopping-cart',
            'legacy_digital' =>
                'fa fa-file-text-o',
            'personal_offer' =>
                'fa fa-tag',
            'merged_account',
            'merge_identity_history' =>
                'fa fa-random',
            default =>
                'fa fa-circle-o',
        };
    }

    private static function source_tone(string $source): string {
        return match ($source) {
            'moodle_current' =>
                'moodle',
            'external_current' =>
                'external',
            'commerce_purchase' =>
                'commerce',
            'legacy_digital' =>
                'legacy',
            'personal_offer' =>
                'offer',
            'merged_account',
            'merge_identity_history' =>
                'merge',
            default =>
                'neutral',
        };
    }

    private static function confidence_label(int $score): string {
        if ($score >= 90) {
            return get_string(
                'crm_user360_n113e_match_high',
                'local_subscriptions'
            );
        }

        if ($score >= 70) {
            return get_string(
                'crm_user360_n113e_match_medium',
                'local_subscriptions'
            );
        }

        return get_string(
            'crm_user360_n113e_match_low',
            'local_subscriptions'
        );
    }
}
