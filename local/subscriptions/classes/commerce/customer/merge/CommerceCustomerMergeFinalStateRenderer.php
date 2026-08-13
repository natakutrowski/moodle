<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Renders an explicit, non-destructive preview of the state produced by a merge.
 *
 * The main view intentionally contains only admin-decision information. Raw transfer
 * counts and before/after identifiers remain available in the technical disclosure.
 */
final class CommerceCustomerMergeFinalStateRenderer {
    /**
     * @param callable(object):string $fullname
     * @param array<string,string> $learningresolutions
     */
    public static function render(
        CommerceCustomerMergePlan $plan,
        int $preferredidentityuserid,
        CommerceCustomerLearningMergeService $learningservice,
        CommerceCustomerLegacyConsolidationService $legacyservice,
        callable $fullname,
        array $learningresolutions = []
    ): string {
        $target = $plan->target_profile();
        $preferred = $target;
        foreach ($plan->profiles as $profile) {
            if ($profile->userid() === $preferredidentityuserid) {
                $preferred = $profile;
                break;
            }
        }

        $identitytransfer = $preferred->userid() !== $target->userid();
        $finalemail = $identitytransfer ? (string)$preferred->user->email : (string)$target->user->email;
        $finalusername = $identitytransfer ? (string)$preferred->user->username : (string)$target->user->username;

        $learningbyuser = [];
        $legacybyuser = [];
        $learningtotal = 0;
        $legacytotal = 0;
        foreach ($plan->source_profiles() as $source) {
            $learningbyuser[$source->userid()] = $learningservice->preview($source->userid());
            $legacybyuser[$source->userid()] = $legacyservice->preview($source->userid());
            $learningtotal += array_sum($learningbyuser[$source->userid()]);
            $legacytotal += array_sum($legacybyuser[$source->userid()]);
        }

        $commerce = [
            'purchases' => $target->purchases,
            'grants' => $target->grants,
            'digital' => $target->digitalaccesses,
            'guests' => $target->guestsessions,
        ];
        foreach ($plan->source_profiles() as $source) {
            $commerce['purchases'] += $source->purchases;
            $commerce['grants'] += $source->grants;
            $commerce['digital'] += $source->digitalaccesses;
            $commerce['guests'] += $source->guestsessions;
        }

        $out = '';
        $out .= html_writer::start_tag('section', ['class' => 'm13d-final-state']);
        $out .= html_writer::start_div('m13d-final-state__header');
        $out .= html_writer::div(
            html_writer::span('✓', 'm13d-final-state__check') .
            html_writer::tag('h3', get_string('commerce_identity_merge_final_state_title', 'local_subscriptions')),
            'm13d-final-state__title'
        );
        $out .= html_writer::div(
            get_string('commerce_identity_merge_final_state_help', 'local_subscriptions'),
            'm13d-final-state__help'
        );
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('m13d-final-grid');
        $out .= self::retained_card(
            $target,
            $preferred,
            $identitytransfer,
            $finalemail,
            $finalusername,
            $commerce,
            $learningtotal,
            $fullname
        );
        $out .= self::absorbed_card($plan, $target, $preferred, $identitytransfer, $fullname);
        $out .= html_writer::end_div();

        $out .= html_writer::div(
            s(get_string('commerce_identity_merge_final_sentence', 'local_subscriptions', (object)[
                'email' => $finalemail,
                'userid' => $target->userid(),
                'courses' => $target->enrolledcourses,
                'activities' => $target->completedactivities,
                'grades' => $target->gradecount,
                'purchases' => $commerce['purchases'],
                'sources' => count($plan->source_profiles()),
            ])),
            'm13d-final-summary'
        );

        $out .= self::technical_details(
            $plan,
            $target,
            $preferred,
            $identitytransfer,
            $finalemail,
            $finalusername,
            $learningbyuser,
            $legacybyuser,
            $learningtotal,
            $legacytotal,
            $learningresolutions,
            CommerceCustomerMergeTechnicalDetailService::create()->build($plan),
            $fullname
        );
        $out .= html_writer::end_tag('section');
        return $out;
    }

    /** @param callable(object):string $fullname */
    private static function retained_card(
        CommerceCustomerMergeAccountProfile $target,
        CommerceCustomerMergeAccountProfile $preferred,
        bool $identitytransfer,
        string $finalemail,
        string $finalusername,
        array $commerce,
        int $learningtotal,
        callable $fullname
    ): string {
        $out = html_writer::start_tag('article', ['class' => 'm13d-account-card m13d-account-card--retained']);
        $out .= html_writer::div(
            html_writer::span('✓', 'm13d-account-card__icon') .
            html_writer::div(
                html_writer::tag('strong', get_string('commerce_identity_merge_final_retained_title', 'local_subscriptions')) .
                html_writer::div(get_string('commerce_identity_merge_final_retained_help', 'local_subscriptions'), 'small'),
                'm13d-account-card__heading-copy'
            ),
            'm13d-account-card__heading'
        );
        $out .= html_writer::div(
            html_writer::tag('strong', '#' . $target->userid() . ' — ' . s($fullname($target->user))) .
            html_writer::span(get_string('commerce_identity_merge_final_active_badge', 'local_subscriptions'), 'm13d-badge m13d-badge--success'),
            'm13d-account-card__identity'
        );

        $out .= html_writer::start_div('m13d-retained-columns');
        $out .= html_writer::start_div('m13d-info-panel');
        $out .= html_writer::tag('h4', get_string('commerce_identity_merge_final_identity_title', 'local_subscriptions'));
        $out .= self::row(get_string('commerce_identity_merge_final_moodle_id', 'local_subscriptions'), '#' . $target->userid(), 'm13d-value--strong');
        $out .= self::row(get_string('commerce_identity_merge_final_name', 'local_subscriptions'), $fullname($target->user));
        $out .= self::identity_row(
            get_string('commerce_identity_merge_final_email', 'local_subscriptions'),
            (string)$target->user->email,
            $finalemail,
            $identitytransfer ? $preferred->userid() : null
        );
        $out .= self::identity_row(
            get_string('commerce_identity_merge_final_username', 'local_subscriptions'),
            (string)$target->user->username,
            $finalusername,
            $identitytransfer ? $preferred->userid() : null
        );
        $out .= self::row(
            get_string('commerce_identity_merge_final_status', 'local_subscriptions'),
            get_string('commerce_identity_merge_final_status_value', 'local_subscriptions')
        );
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('m13d-info-panel');
        $out .= html_writer::tag('h4', get_string('commerce_identity_merge_final_learning_title', 'local_subscriptions'));
        $out .= self::metric(get_string('commerce_identity_merge_final_courses', 'local_subscriptions'), (string)$target->enrolledcourses);
        $out .= self::metric(get_string('commerce_identity_merge_final_completed_courses', 'local_subscriptions'), (string)$target->completedcourses);
        $out .= self::metric(get_string('commerce_identity_merge_final_activities', 'local_subscriptions'), (string)$target->completedactivities);
        $out .= self::metric(
            get_string('commerce_identity_merge_final_grades', 'local_subscriptions'),
            get_string('commerce_identity_merge_final_grades_value', 'local_subscriptions', (object)[
                'count' => $target->gradecount,
                'average' => number_format($target->averagegradepercent, 1),
            ])
        );
        if ($learningtotal > 0) {
            $out .= html_writer::div(
                get_string('commerce_identity_merge_final_learning_consolidation', 'local_subscriptions', $learningtotal),
                'm13d-inline-note'
            );
        } else {
            $out .= html_writer::div(
                get_string('commerce_identity_merge_final_learning_unchanged', 'local_subscriptions'),
                'm13d-inline-note m13d-inline-note--success'
            );
        }
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('m13d-info-panel');
        $out .= html_writer::tag('h4', get_string('commerce_identity_merge_final_commerce_title', 'local_subscriptions'));
        $out .= self::metric(get_string('commerce_identity_merge_final_purchases', 'local_subscriptions'), (string)$commerce['purchases']);
        $out .= self::metric(get_string('commerce_identity_merge_final_grants', 'local_subscriptions'), (string)$commerce['grants']);
        $out .= self::metric(get_string('commerce_identity_merge_final_digital', 'local_subscriptions'), (string)$commerce['digital']);
        $out .= self::metric(get_string('commerce_identity_merge_final_guests', 'local_subscriptions'), (string)$commerce['guests']);
        $out .= html_writer::div(
            get_string('commerce_identity_merge_final_commerce_consolidated', 'local_subscriptions'),
            'm13d-inline-note m13d-inline-note--success'
        );
        $out .= html_writer::end_div();
        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('article');
        return $out;
    }

    /** @param callable(object):string $fullname */
    private static function absorbed_card(
        CommerceCustomerMergePlan $plan,
        CommerceCustomerMergeAccountProfile $target,
        CommerceCustomerMergeAccountProfile $preferred,
        bool $identitytransfer,
        callable $fullname
    ): string {
        $out = html_writer::start_tag('article', ['class' => 'm13d-account-card m13d-account-card--absorbed']);
        $out .= html_writer::div(
            html_writer::span('!', 'm13d-account-card__icon') .
            html_writer::div(
                html_writer::tag('strong', get_string('commerce_identity_merge_final_absorbed_title', 'local_subscriptions')) .
                html_writer::div(get_string('commerce_identity_merge_final_absorbed_help', 'local_subscriptions'), 'small'),
                'm13d-account-card__heading-copy'
            ),
            'm13d-account-card__heading'
        );

        foreach ($plan->source_profiles() as $source) {
            $ispreferred = $identitytransfer && $source->userid() === $preferred->userid();
            $afteremail = $ispreferred ? (string)$target->user->email : (string)$source->user->email;
            $afterusername = $ispreferred ? (string)$target->user->username : (string)$source->user->username;
            $out .= html_writer::start_div('m13d-absorbed-account');
            $out .= html_writer::div(
                html_writer::tag('strong', '#' . $source->userid() . ' — ' . s($fullname($source->user))) .
                html_writer::span(get_string('commerce_identity_merge_final_suspended_badge', 'local_subscriptions'), 'm13d-badge m13d-badge--danger'),
                'm13d-account-card__identity'
            );
            $out .= self::row(
                get_string('commerce_identity_merge_final_after_merge', 'local_subscriptions'),
                get_string('commerce_identity_merge_final_absorbed_status', 'local_subscriptions'),
                'm13d-value--danger'
            );
            $out .= self::identity_row(
                get_string('commerce_identity_merge_final_email', 'local_subscriptions'),
                (string)$source->user->email,
                $afteremail,
                $ispreferred ? $target->userid() : null,
                $ispreferred
            );
            $out .= self::identity_row(
                get_string('commerce_identity_merge_final_username', 'local_subscriptions'),
                (string)$source->user->username,
                $afterusername,
                $ispreferred ? $target->userid() : null,
                $ispreferred
            );
            $out .= html_writer::div(
                get_string(
                    $ispreferred
                        ? 'commerce_identity_merge_final_absorbed_identity_swap_note'
                        : 'commerce_identity_merge_final_absorbed_regular_note',
                    'local_subscriptions'
                ),
                'm13d-inline-note m13d-inline-note--danger'
            );
            $out .= html_writer::end_div();
        }
        $out .= html_writer::end_tag('article');
        return $out;
    }

    /** @param callable(object):string $fullname */
    private static function technical_details(
        CommerceCustomerMergePlan $plan,
        CommerceCustomerMergeAccountProfile $target,
        CommerceCustomerMergeAccountProfile $preferred,
        bool $identitytransfer,
        string $finalemail,
        string $finalusername,
        array $learningbyuser,
        array $legacybyuser,
        int $learningtotal,
        int $legacytotal,
        array $learningresolutions,
        array $technicaldetail,
        callable $fullname
    ): string {
        $overview = [];
        $overview[] = self::technical_row('targetuserid', (string)$target->userid());
        $overview[] = self::technical_row('preferredidentityuserid', (string)$preferred->userid());
        $overview[] = self::technical_row('identity_transfer', $identitytransfer ? 'yes' : 'no');
        $overview[] = self::technical_row('target.email before → after', (string)$target->user->email . ' → ' . $finalemail);
        $overview[] = self::technical_row('target.username before → after', (string)$target->user->username . ' → ' . $finalusername);
        $overview[] = self::technical_row('learning records detected on sources', (string)$learningtotal);
        $overview[] = self::technical_row('Commerce/Legacy records detected on sources', (string)$legacytotal);
        $overview[] = self::technical_row('shared courses', (string)$plan->sharedcoursecount);
        $overview[] = self::technical_row('learning conflict resolutions', $learningresolutions === [] ? 'none' : json_encode($learningresolutions));

        $sections = '';
        $sections .= self::technical_section(
            get_string('commerce_identity_merge_technical_courses', 'local_subscriptions'),
            self::render_course_details($plan, $technicaldetail, $target->userid(), $fullname),
            self::detail_count($technicaldetail, 'courses')
        );
        $sections .= self::technical_section(
            get_string('commerce_identity_merge_technical_purchases', 'local_subscriptions'),
            self::render_purchase_details($plan, $technicaldetail, $target->userid(), $fullname),
            self::detail_count($technicaldetail, 'purchases')
        );
        $sections .= self::technical_section(
            get_string('commerce_identity_merge_technical_legacy', 'local_subscriptions'),
            self::render_legacy_details($plan, $technicaldetail, $target->userid(), $fullname),
            self::detail_count($technicaldetail, 'subscriptions') + self::detail_count($technicaldetail, 'digital')
        );
        $sections .= self::technical_section(
            get_string('commerce_identity_merge_technical_rights', 'local_subscriptions'),
            self::render_rights_details($plan, $technicaldetail, $target->userid(), $fullname),
            self::detail_count($technicaldetail, 'grants') + self::detail_count($technicaldetail, 'digitalaccesses')
        );
        $sections .= self::technical_section(
            get_string('commerce_identity_merge_technical_identity_audit', 'local_subscriptions'),
            html_writer::div(implode('', $overview), 'm13d-technical-body') .
                self::render_source_counters($plan, $learningbyuser, $legacybyuser, $fullname),
            null
        );

        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('strong', get_string('commerce_identity_merge_technical_title', 'local_subscriptions')) .
                html_writer::span(get_string('commerce_identity_merge_technical_badge', 'local_subscriptions'), 'm13d-technical-badge') .
                html_writer::div(get_string('commerce_identity_merge_technical_help_detailed', 'local_subscriptions'), 'small text-muted'),
                ['class' => 'm13d-technical-summary']
            ) .
            html_writer::div($sections, 'm13d-technical-sections'),
            ['class' => 'm13d-technical']
        );
    }

    private static function technical_section(string $title, string $body, ?int $count): string {
        $badge = $count === null ? '' : html_writer::span((string)$count, 'm13d-section-count');
        return html_writer::tag(
            'details',
            html_writer::tag('summary', html_writer::span(s($title)) . $badge, ['class' => 'm13d-subdetail-summary']) .
                html_writer::div($body, 'm13d-subdetail-body'),
            ['class' => 'm13d-subdetail']
        );
    }

    private static function render_course_details(
        CommerceCustomerMergePlan $plan,
        array $detail,
        int $targetuserid,
        callable $fullname
    ): string {
        $headers = [
            get_string('commerce_identity_merge_detail_origin', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_course', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_enrolment', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_completion', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_activity_grade', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_decision', 'local_subscriptions'),
        ];
        $rows = [];
        $targetcourseids = array_column($detail[$targetuserid]['courses'] ?? [], 'id');
        foreach ($plan->profiles as $profile) {
            foreach (($detail[$profile->userid()]['courses'] ?? []) as $course) {
                $issource = $profile->userid() !== $targetuserid;
                $shared = $issource && in_array($course['id'], $targetcourseids, true);
                $decision = !$issource
                    ? get_string('commerce_identity_merge_detail_already_target', 'local_subscriptions')
                    : ($shared
                        ? get_string('commerce_identity_merge_detail_course_consolidate', 'local_subscriptions')
                        : get_string('commerce_identity_merge_detail_course_transfer', 'local_subscriptions'));
                $rows[] = [
                    self::origin_label($profile, $targetuserid, $fullname),
                    '#' . $course['id'] . ' · ' . $course['fullname'] . ($course['shortname'] !== '' ? ' [' . $course['shortname'] . ']' : ''),
                    get_string('commerce_identity_merge_detail_enrolment_value', 'local_subscriptions', (object)[
                        'status' => (int)$course['enrolstatus'] === 0 ? 'active' : 'suspended',
                        'date' => self::date_value((int)$course['enrolledat']),
                    ]) .
                    "\n" . get_string('commerce_identity_merge_detail_course_roles', 'local_subscriptions', (object)[
                        'roles' => ($course['roles'] ?? []) === [] ? '—' : implode(', ', $course['roles']),
                        'start' => self::date_value((int)$course['enrolledat']),
                        'end' => (int)$course['timeend'] === 0 ? '∞' : self::date_value((int)$course['timeend']),
                    ]),
                    (int)$course['timecompleted'] > 0
                        ? get_string('commerce_identity_merge_detail_completed_on', 'local_subscriptions', self::date_value((int)$course['timecompleted']))
                        : get_string('commerce_identity_merge_detail_not_completed', 'local_subscriptions'),
                    get_string('commerce_identity_merge_detail_activity_grade_value', 'local_subscriptions', (object)[
                        'activities' => $course['activities'],
                        'grades' => $course['gradecount'],
                        'average' => $course['gradeaverage'] === null ? '—' : number_format((float)$course['gradeaverage'], 1) . '%',
                        'lastaccess' => self::date_value((int)$course['lastaccess']),
                    ]),
                    $decision,
                ];
            }
        }
        return self::technical_table($headers, $rows);
    }

    private static function render_purchase_details(
        CommerceCustomerMergePlan $plan,
        array $detail,
        int $targetuserid,
        callable $fullname
    ): string {
        $headers = [
            get_string('commerce_identity_merge_detail_origin', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_purchase', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_product', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_status_amount', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_legacy_link', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_decision', 'local_subscriptions'),
        ];
        $rows = [];
        foreach ($plan->profiles as $profile) {
            foreach (($detail[$profile->userid()]['purchases'] ?? []) as $purchase) {
                $rows[] = [
                    self::origin_label($profile, $targetuserid, $fullname),
                    '#' . $purchase['id'] . ' · ' . $purchase['reference'] . "\n" . self::date_value((int)$purchase['timecreated']),
                    ($purchase['label'] !== '' ? $purchase['label'] : $purchase['type']) . "\n" . $purchase['email'],
                    $purchase['status'] . ' · ' . self::money((int)$purchase['totalminor'], (string)$purchase['currency']),
                    $purchase['legacyfamily'] !== ''
                        ? $purchase['legacyfamily'] . '#' . ($purchase['legacyid'] ?? '—')
                        : 'native',
                    $profile->userid() === $targetuserid
                        ? get_string('commerce_identity_merge_detail_already_target', 'local_subscriptions')
                        : get_string('commerce_identity_merge_detail_purchase_transfer', 'local_subscriptions', $targetuserid),
                ];
            }
        }
        return self::technical_table($headers, $rows);
    }

    private static function render_legacy_details(
        CommerceCustomerMergePlan $plan,
        array $detail,
        int $targetuserid,
        callable $fullname
    ): string {
        $headers = [
            get_string('commerce_identity_merge_detail_origin', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_record', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_product', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_status_amount', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_period_access', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_decision', 'local_subscriptions'),
        ];
        $rows = [];
        foreach ($plan->profiles as $profile) {
            $decision = $profile->userid() === $targetuserid
                ? get_string('commerce_identity_merge_detail_already_target', 'local_subscriptions')
                : get_string('commerce_identity_merge_detail_legacy_transfer', 'local_subscriptions', $targetuserid);
            foreach (($detail[$profile->userid()]['subscriptions'] ?? []) as $record) {
                $rows[] = [
                    self::origin_label($profile, $targetuserid, $fullname),
                    'subscription #' . $record['id'],
                    ($record['planname'] !== '' ? $record['planname'] : 'plan #' . $record['planid']),
                    $record['status'] . ' · ' . self::decimal_money((float)$record['price'], (string)$record['currency']),
                    self::date_value((int)$record['start']) . ' → ' . self::date_value((int)$record['end']) . "\n" . $record['provider'],
                    $decision,
                ];
            }
            foreach (($detail[$profile->userid()]['digital'] ?? []) as $record) {
                $rows[] = [
                    self::origin_label($profile, $targetuserid, $fullname),
                    'digital #' . $record['id'],
                    ($record['productname'] !== '' ? $record['productname'] : 'product #' . $record['productid']) . "\n" . $record['email'],
                    $record['status'] . ' · ' . self::money((int)$record['amountminor'], (string)$record['currency']),
                    ($record['hastoken'] ? 'download token: yes' : 'download token: no') . "\n" . self::date_value((int)$record['paidat']),
                    $decision,
                ];
            }
        }
        return self::technical_table($headers, $rows);
    }

    private static function render_rights_details(
        CommerceCustomerMergePlan $plan,
        array $detail,
        int $targetuserid,
        callable $fullname
    ): string {
        $headers = [
            get_string('commerce_identity_merge_detail_origin', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_record', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_product', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_status', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_period_access', 'local_subscriptions'),
            get_string('commerce_identity_merge_detail_decision', 'local_subscriptions'),
        ];
        $rows = [];
        foreach ($plan->profiles as $profile) {
            $decision = $profile->userid() === $targetuserid
                ? get_string('commerce_identity_merge_detail_already_target', 'local_subscriptions')
                : get_string('commerce_identity_merge_detail_right_transfer', 'local_subscriptions', $targetuserid);
            foreach (($detail[$profile->userid()]['grants'] ?? []) as $record) {
                $rows[] = [
                    self::origin_label($profile, $targetuserid, $fullname),
                    'grant #' . $record['id'] . "\n" . $record['reference'],
                    $record['sku'] . "\n" . $record['type'] . ' · ' . $record['resource'],
                    $record['status'],
                    self::date_value((int)$record['validfrom']) . ' → ' . self::date_value((int)$record['validuntil']),
                    $decision,
                ];
            }
            foreach (($detail[$profile->userid()]['digitalaccesses'] ?? []) as $record) {
                $rows[] = [
                    self::origin_label($profile, $targetuserid, $fullname),
                    'digital access #' . $record['id'],
                    $record['sku'] . "\n" . $record['resource'],
                    $record['status'],
                    get_string('commerce_identity_merge_detail_download_value', 'local_subscriptions', (object)[
                        'count' => $record['downloads'],
                        'max' => $record['maxdownloads'] === 0 ? '∞' : $record['maxdownloads'],
                    ]) . "\n" . self::date_value((int)$record['validfrom']) . ' → ' . self::date_value((int)$record['validuntil']),
                    $decision,
                ];
            }
        }
        return self::technical_table($headers, $rows);
    }

    private static function render_source_counters(
        CommerceCustomerMergePlan $plan,
        array $learningbyuser,
        array $legacybyuser,
        callable $fullname
    ): string {
        $rows = [];
        foreach ($plan->source_profiles() as $source) {
            $rows[] = self::technical_row(
                'source #' . $source->userid() . ' ' . $fullname($source->user) . ' · learning',
                json_encode($learningbyuser[$source->userid()] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $rows[] = self::technical_row(
                'source #' . $source->userid() . ' · Commerce/Legacy',
                json_encode($legacybyuser[$source->userid()] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }
        return html_writer::div(implode('', $rows), 'm13d-technical-body');
    }

    private static function technical_table(array $headers, array $rows): string {
        if ($rows === []) {
            return html_writer::div(get_string('commerce_identity_merge_detail_none', 'local_subscriptions'), 'text-muted small');
        }
        $head = html_writer::tag('tr', implode('', array_map(
            static fn(string $header): string => html_writer::tag('th', s($header)),
            $headers
        )));
        $body = '';
        foreach ($rows as $row) {
            $body .= html_writer::tag('tr', implode('', array_map(
                static fn(string $value): string => html_writer::tag('td', nl2br(s($value))),
                $row
            )));
        }
        return html_writer::tag(
            'div',
            html_writer::tag('table', html_writer::tag('thead', $head) . html_writer::tag('tbody', $body), [
                'class' => 'generaltable table table-sm m13d-detail-table',
            ]),
            ['class' => 'table-responsive']
        );
    }

    private static function origin_label(
        CommerceCustomerMergeAccountProfile $profile,
        int $targetuserid,
        callable $fullname
    ): string {
        return '#' . $profile->userid() . ' · ' . $fullname($profile->user) .
            ($profile->userid() === $targetuserid ? ' · TARGET' : ' · SOURCE');
    }

    private static function detail_count(array $technicaldetail, string $key): int {
        $count = 0;
        foreach ($technicaldetail as $detail) {
            $count += count($detail[$key] ?? []);
        }
        return $count;
    }

    private static function date_value(int $timestamp): string {
        return $timestamp > 0 ? userdate($timestamp) : '—';
    }

    private static function money(int $minor, string $currency): string {
        return number_format($minor / 100, 2, '.', ' ') . ' ' . strtoupper($currency);
    }

    private static function decimal_money(float $amount, string $currency): string {
        return number_format($amount, 2, '.', ' ') . ' ' . strtoupper($currency);
    }

    private static function row(string $label, string $value, string $valueclass = ''): string {
        return html_writer::div(
            html_writer::span(s($label), 'm13d-row__label') .
            html_writer::span(s($value), trim('m13d-row__value ' . $valueclass)),
            'm13d-row'
        );
    }

    private static function metric(string $label, string $value): string {
        return html_writer::div(
            html_writer::span(s($value), 'm13d-metric__value') .
            html_writer::span(s($label), 'm13d-metric__label'),
            'm13d-metric'
        );
    }

    private static function identity_row(
        string $label,
        string $before,
        string $after,
        ?int $fromuserid,
        bool $absorbed = false
    ): string {
        if ($before === $after) {
            return self::row($label, $after);
        }
        $badge = $fromuserid !== null
            ? html_writer::span(
                get_string(
                    $absorbed ? 'commerce_identity_merge_final_replaced_by_target' : 'commerce_identity_merge_final_transferred_from',
                    'local_subscriptions',
                    $fromuserid
                ),
                'm13d-origin-badge'
            )
            : '';
        return html_writer::div(
            html_writer::span(s($label), 'm13d-row__label') .
            html_writer::span(
                html_writer::tag('del', s($before)) .
                html_writer::span('→', 'm13d-identity-arrow') .
                html_writer::tag('strong', s($after)) .
                $badge,
                'm13d-row__value m13d-row__value--identity'
            ),
            'm13d-row'
        );
    }

    private static function technical_row(string $label, string $value): string {
        return html_writer::div(
            html_writer::tag('code', s($label), ['class' => 'm13d-technical-key']) .
            html_writer::tag('code', s($value), ['class' => 'm13d-technical-value']),
            'm13d-technical-row'
        );
    }
}
