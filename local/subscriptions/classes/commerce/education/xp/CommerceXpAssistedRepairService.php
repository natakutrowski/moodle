<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Opt-in, operator-confirmed XP repair. There is intentionally NO scheduled
 * task calling this class. All write operations require an explicit POST.
 *
 * The service refuses unproven replays, uncertain rule identity, unsupported
 * modules and inconsistencies. In particular an audit candidate != entitlement.
 */
final class CommerceXpAssistedRepairService {
    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * Resolve the real activity title for any Moodle module with a name field.
     * This method does not confer permission to repair non-H5P activities.
     */
    public static function activity_name(\moodle_database $db, string $modname,
            int $instanceid, int $cmid): string {
        $fallback = '#' . $cmid;
        if ($instanceid <= 0 || !preg_match('/^[a-z][a-z0-9_]*$/D', $modname)) {
            return $fallback;
        }
        try {
            $name = $db->get_field($modname, 'name', ['id' => $instanceid], IGNORE_MISSING);
            return is_string($name) && trim($name) !== '' ? $name : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public static function request_key(object $ob): string {
        return hash('sha256', implode(':', ['campusfr', 'xp-assisted-v2',
            (string)$ob->kind, (int)$ob->userid, (int)$ob->cmid,
            (int)$ob->ruleid, (int)$ob->promotionid]));
    }

    public static function preview_token(array $report): string {
        // Derived from persisted state; the POST recomputes it under lock.
        return hash('sha256', implode(':', [
            (int)$report['audit']->id, (int)$report['audit']->userid,
            (int)$report['audit']->cmid, (int)$report['audit']->ruleid,
            (int)$report['audit']->promotionid, (int)$report['audit']->points,
            $report['audit']->status, $report['kind'], $report['eligible'] ? 'yes' : 'no',
            (int)$report['proofat'], (int)$report['proofid'],
            $report['badge'], (int)$report['existing'], (int)$report['xp'],
        ]));
    }

    /**
     * Read-only proof inspection. Never assumes that missing credits are due.
     * @return array<string,mixed>
     */
    public function analyse(int $auditid): array {
        $ob = $this->db->get_record('local_subs_xp_audit', ['id' => $auditid], '*', MUST_EXIST);
        $report = ['audit' => $ob, 'kind' => (string)$ob->kind, 'issues' => [],
            'eligible' => false, 'proofat' => 0, 'proofid' => 0, 'badge' => 'missing',
            'existing' => 0, 'xp' => 0, 'sourcekey' => '', 'reference' => null,
            'rule' => null, 'user' => null, 'modname' => '', 'course' => null];
        $add = static function(string $message) use (&$report): void {
            $report['issues'][] = $message;
        };
        if (!in_array($ob->kind, ['personal', 'promotion'], true)) {
            $add('Type de récompense non pris en charge.');
            return $report;
        }
        if ($ob->status !== 'review') {
            $add('Observation non ouverte : le cron ou une correction l’a déjà résolue.');
        }
        if ((int)$ob->points <= 0 || (int)$ob->points > 100) {
            $add('Montant hors plage de sécurité (1 à 100).');
        }
        if ($this->db->record_exists('local_subs_xp_audit_fix',
                ['requestkey' => self::request_key($ob)])) {
            $add('Une régularisation assistée existe déjà.');
        }
        $user = $this->db->get_record('user', ['id' => (int)$ob->userid],
            'id,firstname,lastname,deleted,suspended', IGNORE_MISSING);
        $report['user'] = $user;
        if (!$user || $user->deleted || $user->suspended) {
            $add('Compte absent, supprimé ou suspendu.');
        }
        $cm = $this->db->get_record('course_modules', ['id' => (int)$ob->cmid],
            'id,course,instance,module,completion', IGNORE_MISSING);
        if (!$cm || (int)$cm->course !== (int)$ob->courseid) {
            $add('Module supprimé ou associé à un autre cours.');
            return $report;
        }
        $report['course'] = $this->db->get_record('course', ['id' => $cm->course],
            'id,fullname', IGNORE_MISSING);
        $module = $this->db->get_record('modules', ['id' => $cm->module], 'id,name', IGNORE_MISSING);
        $report['modname'] = $module ? (string)$module->name : '';
        $report['activityname'] = self::activity_name($this->db, $report['modname'],
            (int)$cm->instance, (int)$cm->id);
        // Expose the Moodle completion as observational evidence for non-H5P
        // modules. It is not proof that an XP reward is historically owed.
        $report['completion'] = $this->db->get_record('course_modules_completion',
            ['coursemoduleid' => $ob->cmid, 'userid' => $ob->userid],
            'id,completionstate,timemodified,overrideby', IGNORE_MISSING);
        if ($user && is_siteadmin((int)$user->id)) {
            $add('Compte administrateur : récompenses pédagogiques CampusFR non applicables.');
        }
        if (!$module || $module->name !== 'h5pactivity') {
            $add('V2 : correction assistée limitée aux activités H5P.');
            return $report;
        }
        $h5p = $this->db->get_record('h5pactivity', ['id' => $cm->instance], 'id,name,course', IGNORE_MISSING);
        if (!$h5p || (int)$h5p->course !== (int)$cm->course) {
            $add('Instance H5P incohérente.');
            return $report;
        }
        $report['activityname'] = (string)$h5p->name;
        $rule = $this->db->get_record('block_xp_rule', ['id' => $ob->ruleid], '*', IGNORE_MISSING);
        $report['rule'] = $rule;
        if (!$rule || (int)$rule->points !== (int)$ob->points ||
                $rule->type !== 'cm_completion') {
            $add('Règle Level Up supprimée, modifiée ou montant différent.');
        } else {
            $effective = $this->effective_rules((int)$ob->courseid);
            $matched = CommerceXpAuditReconciler::matched_rule($effective, (int)$ob->cmid, $h5p->name);
            if (!$matched || (int)$matched->id !== (int)$ob->ruleid) {
                $add('La règle applicable est différente ou ambiguë.');
            }
        }
        $comp = $report['completion'];
        if ((int)$cm->completion !== 2 || !$comp || (int)$comp->completionstate !== 2 ||
                $comp->overrideby !== null) {
            $add('Achèvement Moodle non réussi, forcé ou incomplet.');
        }
        $ctx = \context_module::instance((int)$ob->cmid, IGNORE_MISSING);
        if (!$ctx) {
            $add('Contexte Moodle du module absent.');
            return $report;
        }
        $report['context'] = $ctx;
        $positive = $this->db->get_records_sql(
            "SELECT id,ruleid,points,subtype,reasontypehash FROM {block_xp_logs}
              WHERE userid=:uid AND envid=:env AND reason=:reason
                AND points>0 AND ruleid>0 ORDER BY id DESC",
            ['uid' => $ob->userid, 'env' => $ctx->id, 'reason' => 'activity_completion'], 0, 30);
        $report['personal'] = $positive;
        $report['existing'] = count($positive);
        $badges = CommercePedagogicalCompactPointsBadgeService::create($this->db)
            ->for_user_course((int)$ob->userid, (int)$ob->courseid);
        foreach ($badges as $entry) {
            if ((int)$entry['cmid'] !== (int)$ob->cmid) {
                continue;
            }
            foreach ($entry['badges'] as $badge) {
                if ($badge['kind'] === $ob->kind) {
                    $report['badge'] = (string)$badge['state'];
                    if ((int)$badge['points'] !== (int)$ob->points) {
                        $add('Montant du badge différent de celui de l’observation.');
                    }
                }
            }
        }
        if ($report['badge'] !== 'available') {
            $add('Le badge n’est pas disponible pour une correction (état : '.$report['badge'].').');
        }
        if ($ob->kind === 'personal') {
            if ($positive) {
                $add('Un gain personnel existe déjà pour cette activité.');
            }
            // Only the FIRST ever completed and successful attempt qualifies.
            $attempts = $this->db->get_records_sql(
                "SELECT id,timecreated,timemodified,rawscore,maxscore,success,completion
                   FROM {h5pactivity_attempts}
                  WHERE userid=:uid AND h5pactivityid=:h5p AND success=1 AND completion=1
               ORDER BY timecreated ASC, id ASC",
                ['uid' => $ob->userid, 'h5p' => $h5p->id], 0, 2);
            $first = $attempts ? reset($attempts) : null;
            if (!$first || (int)$first->maxscore <= 0 || (int)$first->rawscore < 0) {
                $add('Aucune première réussite H5P exploitable.');
            } else {
                $report['proofat'] = (int)$first->timecreated;
                $report['proofid'] = (int)$first->id;
                if (!$comp || abs((int)$comp->timemodified - (int)$first->timecreated) > 120) {
                    $add('Achèvement Moodle non corrélé à la première réussite (±120 secondes).');
                }
                if ((int)$first->timecreated < CommerceXpAuditReconciler::START_AT) {
                    $add('Première réussite antérieure à l’incident B1 : droit personnel historique indéterminé.');
                }
                if ((int)$first->timecreated > (int)$ob->evidenceat + 120) {
                    $add('Preuve H5P postérieure à l’observation.');
                }
            }
            if (!$this->db->record_exists('block_xp_config', ['courseid' => SITEID, 'enabled' => 1])) {
                $add('Level Up désactivé pour le monde du site.');
            }
            try {
                $factory = \block_xp\di::get('course_world_factory');
                $world = $factory->get_world((int)$ob->courseid);
                if ((int)$world->get_courseid() !== (int)SITEID) {
                    $add('Le monde XP du cours n’est pas le monde du site.');
                }
                $store = $world->get_store();
                $state = (int)$store->get_state((int)$ob->userid)->get_xp();
                $row = $this->db->get_record('block_xp',
                    ['userid' => $ob->userid, 'courseid' => SITEID], 'id,xp', IGNORE_MISSING);
                if (!$row || (int)$row->xp !== $state || $this->db->record_exists('block_xp',
                        ['userid' => $ob->userid, 'courseid' => $ob->courseid])) {
                    $add('Solde Level Up / base incohérent ou compteur séparé.');
                }
                $report['xp'] = $state;
                $report['store'] = $store;
            } catch (\Throwable $e) {
                $add('Impossible de vérifier le monde et le solde Level Up.');
            }
            $reference = $this->db->get_record_sql(
                "SELECT id,contextid,reason,subtype,points,ruleid,reasontypehash
                   FROM {block_xp_logs}
                  WHERE ruleid=:ruleid AND reason=:reason AND points=:points
               ORDER BY id DESC",
                ['ruleid' => $ob->ruleid, 'reason' => 'activity_completion',
                    'points' => $ob->points], IGNORE_MISSING);
            if (!$reference || !$rule || (int)$reference->contextid !== (int)$rule->contextid ||
                    ($reference->subtype !== null && $reference->subtype !== '') ||
                    strlen((string)$reference->reasontypehash) !== 9) {
                $add('Journal Level Up de référence authentique indisponible ou incompatible.');
            }
            $report['reference'] = $reference;
        } else {
            $this->analyse_promotion($report, $ob, $positive, $h5p, $ctx, $add);
        }
        $report['eligible'] = !$report['issues'];
        return $report;
    }

    /** @param object[] $positive */
    private function analyse_promotion(array &$report, object $ob, array $positive,
            object $h5p, \context $ctx, callable $add): void {
        if ((int)$ob->promotionid <= 0) {
            $add('Identifiant de promotion absent.');
            return;
        }
        $promo = $this->db->get_record('local_subs_commerce_ped_promo',
            ['id' => $ob->promotionid], '*', IGNORE_MISSING);
        if (!$promo || (int)$promo->courseid !== (int)$ob->courseid ||
                !in_array((string)$promo->status, ['scheduled', 'open', 'full', 'started'], true)) {
            $add('Promotion inexistante, clôturée ou associée à un autre cours.');
            return;
        }
        $join = $this->db->get_record('local_subs_commerce_ped_join',
            ['promotionid' => $ob->promotionid, 'userid' => $ob->userid,
                'state' => 'active'], '*', IGNORE_MISSING);
        $access = $this->db->record_exists('local_subs_commerce_ped_access',
            ['promotionid' => $ob->promotionid, 'userid' => $ob->userid,
                'courseid' => $ob->courseid]);
        if (!$join || !$access || $promo->xpstartsat === null) {
            $add('Participant ou accès promotionnel absent ; début des XP indéfini.');
            return;
        }
        if (!$positive) {
            $add('Aucun journal personnel d’origine : la provenance promotionnelle ne peut pas être liée au badge.');
            return;
        }
        if (count($positive) !== 1) {
            $add('Plusieurs gains personnels pour l’activité : provenance ambiguë.');
            return;
        }
        $log = reset($positive);
        if ((int)$log->ruleid !== (int)$ob->ruleid ||
                (int)$log->points !== (int)$ob->points) {
            $add('Règle ou montant du gain personnel différent de l’observation.');
            return;
        }
        $start = max((int)$promo->xpstartsat, (int)$join->timecreated);
        $success = $this->db->get_records_sql(
            "SELECT id,timecreated,timemodified,maxscore,rawscore
               FROM {h5pactivity_attempts}
              WHERE userid=:uid AND h5pactivityid=:h5p AND success=1 AND completion=1
                AND timecreated >= :startat AND timemodified >= :fromat
           ORDER BY timecreated ASC, id ASC",
            ['uid' => $ob->userid, 'h5p' => $h5p->id,
                'startat' => $start, 'fromat' => $start], 0, 1);
        $attempt = $success ? reset($success) : null;
        if (!$attempt) {
            $add('Aucune réussite H5P pendant la période promotionnelle éligible.');
            return;
        }
        $when = (int)$attempt->timemodified;
        if ($promo->endsat !== null && $when > (int)$promo->endsat) {
            $add('Réussite après la fin de la promotion.');
        }
        if ((new CommercePedagogicalActivityXpGate($this->db))
                ->eligible_cmid((int)$ob->promotionid, (int)$ob->courseid,
                    (int)$ctx->id, $when) !== (int)$ob->cmid) {
            $add('Activité non débloquée/éligible à cette date.');
        }
        $sourcekey = CommercePedagogicalActivityXpGate::source_key(
            (int)$ob->cmid, (int)$log->ruleid, 'activity_completion',
            $log->subtype === null ? null : (string)$log->subtype);
        $report['sourcekey'] = $sourcekey;
        if ($this->db->record_exists('local_subs_commerce_ped_xp', [
            'promotionid' => $ob->promotionid, 'userid' => $ob->userid,
            'sourcecomponent' => 'block_xp', 'sourcetype' => 'eligible_activity',
            'sourcekey' => $sourcekey])) {
            $add('Une attribution promotionnelle existe déjà pour cette clé.');
            $report['existing'] = 1;
        } else {
            $report['existing'] = 0;
        }
        $report['proofat'] = $when;
        $report['proofid'] = (int)$attempt->id;
    }

    /** @return object[] */
    private function effective_rules(int $courseid): array {
        $ctx = \context_course::instance($courseid);
        $sys = (int)\context_system::instance()->id;
        $rules = $this->db->get_records_sql(
            "SELECT id,contextid,childcontextid,points,type,filter,filtercmid,filterint1,filterchar1
               FROM {block_xp_rule}
              WHERE ((contextid=:ctx AND childcontextid=0)
                 OR (contextid=:sys AND childcontextid IN (0,:scoped)))
                AND type=:ruletype AND points>0",
            ['ctx' => (int)$ctx->id, 'sys' => $sys, 'scoped' => (int)$ctx->id,
                'ruletype' => 'cm_completion']);
        $local = array_filter($rules, static fn($r) => (int)$r->contextid === (int)$ctx->id);
        $scoped = array_filter($rules, static fn($r) => (int)$r->childcontextid === (int)$ctx->id);
        return $local ?: ($scoped ?: $rules);
    }

    /**
     * Called ONLY on explicit POST, after authorization and sesskey checks at endpoint.
     * @return array{fixid:int,sourceid:int,kind:string,points:int}
     */
    public function repair(int $auditid, string $token, int $actorid, string $reason): array {
        if ((int)get_config('local_subscriptions', 'xp_assisted_enabled') !== 1) {
            throw new \RuntimeException('Assisted XP is disabled by configuration.');
        }
        if ($actorid <= 1 || strlen(trim($reason)) < 12 || strlen(trim($reason)) > 2000) {
            throw new \moodle_exception('invalidparameter');
        }
        return $this->commit_repair($auditid, $token, $actorid, $reason, 'assisted');
    }

    /** Automatic path. No operator impersonation; actorid remains NULL. */
    public function repair_automatically(int $auditid): array {
        if (!CommerceXpAutomaticRepairService::is_live_enabled()) {
            throw new \RuntimeException('Automatic XP awards are disabled.');
        }
        $report = $this->analyse($auditid);
        $policy = CommerceXpAutomaticRepairService::eligibility($report, $this->db);
        if (!$policy['allowed']) {
            throw new \RuntimeException('Automatic policy refused: ' . $policy['reason']);
        }
        return $this->commit_repair($auditid, self::preview_token($report),
            null, 'Correction automatique : preuve revalidée et politique stricte', 'automatic');
    }

    /** Shared transactional grant; independently rechecks eligibility UNDER lock. */
    private function commit_repair(int $auditid, string $token, ?int $actorid,
            string $reason, string $mode): array {
        if (!in_array($mode, ['assisted', 'automatic'], true)) {
            throw new \coding_exception('Invalid repair mode.');
        }
        $ob = $this->db->get_record('local_subs_xp_audit', ['id' => $auditid], '*', MUST_EXIST);
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_subscriptions_ped_xp');
        $lock = $lockfactory->get_lock('repair:' . (int)$ob->cmid . ':' . (int)$ob->userid, 10);
        if (!$lock) {
            throw new \RuntimeException('Verrou de régularisation indisponible');
        }
        $tx = null;
        try {
            $report = $this->analyse($auditid); // Check again UNDER LOCK.
            if ($mode === 'automatic') {
                if (!CommerceXpAutomaticRepairService::is_live_enabled()) {
                    throw new \RuntimeException('Automatic XP awards were disabled during processing.');
                }
                $policy = CommerceXpAutomaticRepairService::eligibility($report, $this->db);
                if (!$policy['allowed']) {
                    throw new \RuntimeException('Automatic eligibility changed: ' . $policy['reason']);
                }
            }
            if (!$report['eligible'] || !hash_equals(self::preview_token($report), $token)) {
                throw new \RuntimeException('Analyse obsolète ou non éligible. Actualisez la fiche.');
            }
            $tx = $this->db->start_delegated_transaction();
            $sourceid = 0;
            if ($ob->kind === 'personal') {
                $report['store']->increase((int)$ob->userid, (int)$ob->points);
                $sourceid = (int)$this->db->insert_record('block_xp_logs', (object)[
                    'contextid' => (int)$report['reference']->contextid,
                    'userid' => (int)$ob->userid, 'points' => (int)$ob->points,
                    'reason' => 'activity_completion', 'subtype' => null,
                    'envid' => (int)$report['context']->id,
                    'parentid' => null, 'objectid' => null, 'ruleid' => (int)$ob->ruleid,
                    'reasontypehash' => (string)$report['reference']->reasontypehash,
                    'timerecorded' => (int)$report['proofat'],
                    'legacysource' => null, 'legacyid' => null,
                ]);
            } else {
                $result = CommercePedagogicalXpService::create($this->db)->record_once(
                    (int)$ob->promotionid, (int)$ob->userid, 'block_xp',
                    'eligible_activity', (string)$report['sourcekey'], (int)$ob->points,
                    (int)$report['proofat']);
                if (!$result->was_created()) {
                    throw new \RuntimeException('Attribution déjà enregistrée.');
                }
                $sourceid = (int)$result->get_contribution()->get_id();
            }
            $fixid = (int)$this->db->insert_record('local_subs_xp_audit_fix', (object)[
                'requestkey' => self::request_key($ob), 'auditid' => (int)$ob->id,
                'userid' => (int)$ob->userid, 'cmid' => (int)$ob->cmid,
                'promotionid' => (int)$ob->promotionid, 'kind' => (string)$ob->kind,
                'points' => (int)$ob->points, 'mode' => $mode, 'sourceid' => $sourceid,
                'actorid' => $actorid, 'reason' => trim($reason), 'timecreated' => time(),
            ]);
            $this->db->update_record('local_subs_xp_audit', (object)[
                'id' => (int)$ob->id, 'status' => 'resolved', 'resolvedat' => time(),
                'timemodified' => time(),
            ]);
            // The badge is proof that the history has the exact provenance key.
            $badge = 'missing';
            $all = CommercePedagogicalCompactPointsBadgeService::create($this->db)
                ->for_user_course((int)$ob->userid, (int)$ob->courseid);
            foreach ($all as $item) {
                if ((int)$item['cmid'] !== (int)$ob->cmid) { continue; }
                foreach ($item['badges'] as $entry) {
                    if ($entry['kind'] === $ob->kind) { $badge = $entry['state']; }
                }
            }
            if ($badge !== 'earned' || !$this->db->record_exists('local_subs_xp_audit_fix',
                    ['id' => $fixid, 'sourceid' => $sourceid]) ||
                    ($ob->kind === 'personal' &&
                        (int)$report['store']->get_state((int)$ob->userid)->get_xp() !==
                        (int)$report['xp'] + (int)$ob->points)) {
                throw new \RuntimeException('Contrôle après écriture échoué : transaction annulée.');
            }
            $tx->allow_commit();
            $tx = null;
            return ['fixid' => $fixid, 'sourceid' => $sourceid,
                'kind' => (string)$ob->kind, 'points' => (int)$ob->points];
        } catch (\Throwable $e) {
            if ($tx !== null) {
                try { $tx->rollback($e); } catch (\Throwable $ignored) { /* Moodle rolls back. */ }
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
