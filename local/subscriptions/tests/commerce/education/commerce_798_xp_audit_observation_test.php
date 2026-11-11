<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommerceXpAuditReconciler as Audit;

/** @covers \local_subscriptions\commerce\education\xp\CommerceXpAuditReconciler */
final class commerce_798_xp_audit_observation_test extends \advanced_testcase {
    public function test_rule_identity_respects_specificity_and_ambiguity(): void {
        $rules = [
            (object)['id' => 1, 'type' => 'cm_completion', 'points' => 7,
                'filter' => 'anycm', 'filtercmid' => null],
            (object)['id' => 2, 'type' => 'cm_completion', 'points' => 9,
                'filter' => 'cmname', 'filterint1' => 1, 'filterchar1' => 'Новая история',
                'filtercmid' => null],
        ];
        $this->assertSame(2, (int)Audit::matched_rule($rules, 3075, 'День 3 · Новая история в эфире')->id);
        $this->assertSame(1, (int)Audit::matched_rule($rules, 3075, 'Unrelated')->id);
        $rules[] = (object)['id' => 3, 'type' => 'cm_completion', 'points' => 11,
            'filter' => 'cmname', 'filterint1' => 1, 'filterchar1' => 'история',
            'filtercmid' => null];
        $this->assertNull(Audit::matched_rule($rules, 3075, 'День 3 · Новая история в эфире'));
        $rules[] = (object)['id' => 4, 'type' => 'cm_completion', 'points' => 5,
            'filter' => 'cm', 'filtercmid' => 3075];
        $this->assertSame(4, (int)Audit::matched_rule($rules, 3075, 'День 3 · Новая история в эфире')->id);
    }

    public function test_fingerprint_is_stable_and_currency_and_promotion_specific(): void {
        $this->assertSame(Audit::fingerprint('personal', 59, 3075, 309, 0),
            Audit::fingerprint('personal', 59, 3075, 309, 0));
        $this->assertNotSame(Audit::fingerprint('personal', 59, 3075, 309, 0),
            Audit::fingerprint('promotion', 59, 3075, 309, 1));
        $this->assertNotSame(Audit::fingerprint('promotion', 59, 3075, 309, 1),
            Audit::fingerprint('promotion', 59, 3075, 309, 2));
    }

    public function test_audit_candidates_are_idempotent_and_can_be_resolved_without_credit(): void {
        global $DB;
        $this->resetAfterTest();
        $audit = Audit::create($DB);
        $stats = ['examined' => 0, 'candidates' => 0, 'new' => 0,
            'seen' => 0, 'resolved' => 0, 'skipped' => 0, 'partial' => 0];
        $fingerprint = Audit::fingerprint('personal', 59, 3075, 309, 0);
        $observe = new \ReflectionMethod($audit, 'observe');
        $resolve = new \ReflectionMethod($audit, 'resolve');
        $now = time();
        $args = [$fingerprint, 'personal', 59, 15, 3075, 309, 0, 9,
            $now - 60, 'no_personal_completion_log', $now, &$stats];
        $observe->invokeArgs($audit, $args);
        $observe->invokeArgs($audit, $args);
        $this->assertSame(1, $DB->count_records('local_subs_xp_audit'));
        $this->assertSame(1, $stats['new']);
        $this->assertSame(1, $stats['seen']);
        $resolve->invokeArgs($audit, [$fingerprint, $now + 30, &$stats]);
        $this->assertSame('resolved', $DB->get_field('local_subs_xp_audit',
            'status', ['fingerprint' => $fingerprint]));
        $this->assertSame(0, $DB->count_records('local_subs_xp_audit_fix'));
    }

    public function test_empty_scan_never_changes_xp_ledgers(): void {
        global $DB;
        $this->resetAfterTest();
        $this->assertSame(0, $DB->count_records('local_subs_xp_audit'));
        $this->assertSame(0, $DB->count_records('local_subs_xp_audit_fix'));
        $beforepersonal = $DB->count_records('block_xp_logs');
        $beforepromo = $DB->count_records('local_subs_commerce_ped_xp');
        $stats = Audit::create($DB)->run(time());
        $this->assertSame(0, $stats['candidates']);
        $this->assertSame($beforepersonal, $DB->count_records('block_xp_logs'));
        $this->assertSame($beforepromo, $DB->count_records('local_subs_commerce_ped_xp'));
        $this->assertSame(0, $DB->count_records('local_subs_xp_audit_fix'));
        $this->assertSame(1, $DB->count_records('local_subs_xp_audit_run'));
    }
}
