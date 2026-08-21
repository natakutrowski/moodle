<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * N11.5A — Advanced CRM shell for User360.
 *
 * Advanced data is split into explicit sub-screens so the support-first
 * dashboard stays readable and expert tools are available on demand.
 */
final class User360AdvancedRenderer {

    private const TAB_RELATION = 'relation';
    private const TAB_COMMERCE = 'commerce';
    private const TAB_IDENTITIES = 'identities';
    private const TAB_TIMELINE = 'timeline';

    public static function render(\stdClass $profile): string {
        $tabs = self::tabs($profile);
        if ($tabs === []) {
            return '';
        }

        $requested = optional_param(
            'advancedtab',
            '',
            PARAM_ALPHA
        );

        $active = isset($tabs[$requested])
            ? $requested
            : array_key_first($tabs);

        $navigation = '';
        foreach ($tabs as $key => $tab) {
            $classes = 'crm-user360-n115a-nav-item';
            if ($key === $active) {
                $classes .= ' is-active';
            }

            $navigation .= html_writer::link(
                self::tab_url($key),
                html_writer::tag('i', '', [
                    'class' => $tab['icon'],
                    'aria-hidden' => 'true',
                ])
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        s($tab['label']),
                        ['class' => 'crm-user360-n115a-nav-label']
                    )
                    . html_writer::span(
                        s($tab['description']),
                        'crm-user360-n115a-nav-description'
                    ),
                    'crm-user360-n115a-nav-copy'
                ),
                [
                    'class' => $classes,
                    'aria-current' =>
                        $key === $active
                            ? 'page'
                            : null,
                ]
            );
        }

        $content = self::content(
            $active,
            $profile
        );

        return html_writer::tag(
            'section',
            html_writer::div(
                html_writer::div(
                    html_writer::span(
                        get_string(
                            'crm_user360_n115a_eyebrow',
                            'local_subscriptions'
                        ),
                        'crm-user360-n115a-eyebrow'
                    )
                    . html_writer::tag(
                        'h2',
                        get_string(
                            'crm_user360_n115a_title',
                            'local_subscriptions'
                        ),
                        [
                            'id' => 'crm-user360-advanced-title',
                            'class' => 'crm-user360-n115a-title',
                        ]
                    )
                    . html_writer::div(
                        get_string(
                            'crm_user360_n115a_intro',
                            'local_subscriptions'
                        ),
                        'crm-user360-n115a-intro'
                    ),
                    'crm-user360-n115a-heading-copy'
                ),
                'crm-user360-n115a-heading'
            )
            . html_writer::tag(
                'nav',
                $navigation,
                [
                    'class' => 'crm-user360-n115a-nav',
                    'aria-label' => get_string(
                        'crm_user360_n115a_navigation',
                        'local_subscriptions'
                    ),
                ]
            )
            . html_writer::div(
                html_writer::div(
                    html_writer::tag(
                        'h3',
                        s($tabs[$active]['label']),
                        ['class' => 'crm-user360-n115a-pane-title']
                    )
                    . html_writer::div(
                        s($tabs[$active]['longdescription']),
                        'crm-user360-n115a-pane-intro'
                    ),
                    'crm-user360-n115a-pane-heading'
                )
                . html_writer::div(
                    $content,
                    'crm-user360-n115a-pane-body'
                ),
                'crm-user360-n115a-pane is-' . $active
            ),
            [
                'id' => 'crm-user360-advanced',
                'class' => 'crm-user360-n115a-advanced',
                'aria-labelledby' => 'crm-user360-advanced-title',
            ]
        );
    }

    private static function tabs(\stdClass $profile): array {
        $tabs = [];

        if (empty($profile->iscommerceguest)) {
            $tabs[self::TAB_RELATION] = [
                'label' => get_string(
                    'crm_user360_n115a_relation',
                    'local_subscriptions'
                ),
                'description' => get_string(
                    'crm_user360_n115a_relation_short',
                    'local_subscriptions'
                ),
                'longdescription' => get_string(
                    'crm_user360_n115a_relation_help',
                    'local_subscriptions'
                ),
                'icon' => 'fa fa-users',
            ];
        }

        $tabs[self::TAB_COMMERCE] = [
            'label' => get_string(
                'crm_user360_n115a_commerce',
                'local_subscriptions'
            ),
            'description' => get_string(
                'crm_user360_n115a_commerce_short',
                'local_subscriptions'
            ),
            'longdescription' => get_string(
                'crm_user360_n115a_commerce_help',
                'local_subscriptions'
            ),
            'icon' => 'fa fa-shopping-bag',
        ];

        $tabs[self::TAB_IDENTITIES] = [
            'label' => get_string(
                'crm_user360_n115a_identities',
                'local_subscriptions'
            ),
            'description' => get_string(
                'crm_user360_n115a_identities_short',
                'local_subscriptions'
            ),
            'longdescription' => get_string(
                'crm_user360_n115a_identities_help',
                'local_subscriptions'
            ),
            'icon' => 'fa fa-id-card-o',
        ];

        $tabs[self::TAB_TIMELINE] = [
            'label' => get_string(
                'crm_user360_n115a_timeline',
                'local_subscriptions'
            ),
            'description' => get_string(
                'crm_user360_n115a_timeline_short',
                'local_subscriptions'
            ),
            'longdescription' => get_string(
                'crm_user360_n115a_timeline_help',
                'local_subscriptions'
            ),
            'icon' => 'fa fa-clock-o',
        ];

        return $tabs;
    }

    private static function content(
        string $active,
        \stdClass $profile
    ): string {
        return match ($active) {
            self::TAB_RELATION =>
                User360RelationRenderer::render($profile),

            self::TAB_COMMERCE =>
                User360CommerceAccessRenderer::render($profile),

            self::TAB_IDENTITIES =>
                User360IdentitiesRenderer::render($profile),

            self::TAB_TIMELINE =>
                User360TimelineRenderer::render($profile),

            default => '',
        };
    }

    private static function tab_url(
        string $tab
    ): moodle_url {
        global $PAGE;

        $url = new moodle_url(
            $PAGE->url
        );

        $url->param(
            'advancedtab',
            $tab
        );

        $url->set_anchor(
            'crm-user360-advanced'
        );

        return $url;
    }
}
