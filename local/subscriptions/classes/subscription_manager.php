<?php
namespace local_subscriptions;
use local_subscriptions\subscription_config;
use local_subscriptions\constants\Status;

defined('MOODLE_INTERNAL') || die();


class subscription_manager {

    public static function expire_subscription_if_needed() {
        global $DB;

        $now = time();
        $sql = "UPDATE {user_subscription}
                SET status = '".Status::EXPIRED."'
                WHERE status = '".Status::ACTIVE."' AND end_date < :now";

        $DB->execute($sql, ['now' => $now]);
    }
    
	public static function get_end_date_from_duration_key(string $duration_key, ?int $startdate = null): int {
		$start = $startdate ?? time();
	
		switch ($duration_key) {
			case '1week':
				return strtotime('+1 week', $start);
			case '1month':
				return strtotime('+1 month', $start);
			case '3months':
				return strtotime('+3 months', $start);
			case '6months':
				return strtotime('+6 months', $start);
			case '1year':
				return strtotime('+1 year', $start);
			case '3years':
				return strtotime('+3 years', $start);
			case 'lifetime':
				return strtotime('+100 years', $start);
			default:
				return $start;
		}
	}

	public static function calculate_end_date(int $duration, ?int $startdate = null): int {
		$start = $startdate ?? time();
		$duration_days = '+ '.($duration ?? 0).' days';
	
		return strtotime($duration_days, $start);
	}	

	public static function get_access_scope_from_planid(int $planid){
		global $DB;

		$plan = $DB->get_record('subscription_plan', ['id' => $planid], 'accessscopeid', IGNORE_MISSING);
		if (!$plan) {
			return null;
		}

		$scope = $DB->get_record('subscription_access_scope', ['id' => $plan->accessscopeid], '*', IGNORE_MISSING);
		return $scope ?: null;
	}


    // public static function enrol_user_to_courses(int $userid, int $planid, int $startdate, int $enddate): void {
	// 	global $DB;

	// 	// 1) Récupérer le scope -> course_ids "1,2,3"
	// 	$scope = self::get_access_scope_from_planid($planid);
	// 	if (!$scope || empty($scope->course_ids)) {
	// 		return;
	// 	}

	// 	// 2) Rôle "student" (fallback 5)
	// 	$roleid = 5;
	// 	if ($role = $DB->get_record('role', ['shortname' => 'student'], 'id', IGNORE_MISSING)) {
	// 		$roleid = (int)$role->id;
	// 	}

	// 	// 3) enddate illimitée si > 2100-01-01
	// 	if ($enddate > strtotime('2100-01-01')) {
	// 		$enddate = 0;
	// 	}

	// 	// 4) Parser course_ids (tolère virgule/point-virgule/espaces)
	// 	$courseids = preg_split('/[,\;\s]+/', (string)$scope->course_ids, -1, PREG_SPLIT_NO_EMPTY);
	// 	$courseids = array_values(array_unique(array_map('intval', $courseids)));
	// 	if (empty($courseids)) {
	// 		return;
	// 	}

	// 	// 5) Plugin manual
	// 	$manual = enrol_get_plugin('manual');
	// 	if (!$manual) {
	// 		return; // pas d’inscription manuelle disponible
	// 	}

    // 	$now = time();

	// 	foreach ($courseids as $courseid) {
	// 		if ($courseid <= 0) { continue; }

	// 		// 6) Trouver (ou créer) une instance 'manual' activée sur ce cours
	// 		$instances = enrol_get_instances($courseid, /* enabled only */ true);
	// 		$instance  = null;
    //     	$candidateEnabled = null;

	// 		foreach ($instances as $inst) {
	// 			if ($inst->enrol !== 'manual') { continue; }
	// 			// S'il y a déjà une UE sur cette instance, on la choisit
	// 			if ($DB->record_exists('user_enrolments', ['enrolid' => $inst->id, 'userid' => $userid])) {
	// 				$instance = $inst;
	// 				break;
	// 			}
	// 			// Sinon on mémorise une instance manual activée comme candidat
	// 			if ((int)$inst->status === ENROL_INSTANCE_ENABLED && !$candidateEnabled) {
	// 				$candidateEnabled = $inst;
	// 			}
	// 		}

	// 		if (!$instance) {
	// 			// Pas d'UE existante -> prendre une instance manual activée si dispo
	// 			if ($candidateEnabled) {
	// 				$instance = $candidateEnabled;
	// 			} else {
	// 				// Sinon on crée une instance activée
	// 				$course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
	// 				if (!$course) { continue; }
	// 				$instanceid = $manual->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
	// 				if (!$instanceid) { continue; }
	// 				$instance = $DB->get_record('enrol', ['id' => $instanceid], '*', IGNORE_MISSING);
	// 				if (!$instance) { continue; }
	// 			}
	// 		}

	// 		// 7) Idempotence CORRECTE :
	// 		//    - si déjà une ligne user_enrolments -> UPDATE (status + dates)
	// 		//    - sinon -> ENROL (création)
	// 		try {
	// 			$ue = $DB->get_record('user_enrolments', [
	// 				'enrolid' => $instance->id,
	// 				'userid'  => $userid
	// 			], '*', IGNORE_MISSING);

	// 			if ($ue) {
	// 				// --- Cas PROLONGATION / AJUSTEMENT ---
	// 				// Ne JAMAIS pousser le début dans le futur si l'UE existe déjà.
	// 				// On conserve le début le plus ancien (ou celui passé s'il est dans le passé).
	// 				$newStart = (int)($ue->timestart ?? 0);
	// 				if ($startdate <= $now) {
	// 					$newStart = $newStart ? min($newStart, (int)$startdate) : (int)$startdate;
	// 				}
	// 				// Étendre la fin (0 = illimité)
	// 				if ((int)$enddate === 0 || (int)$ue->timeend === 0) {
	// 					$newEnd = 0;
	// 				} else {
	// 					$newEnd = max((int)$ue->timeend, (int)$enddate);
	// 				}

	// 				// Réactiver au passage
	// 				$manual->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, $newStart, $newEnd);

	// 			} else {
	// 				// --- Cas PREMIÈRE INSCRIPTION SUR CE COURS ---
	// 				// Ne PAS créer d'UE si elle commencerait dans le futur
	// 				// (pour éviter de "perdre" l'accès actuel en file d'attente).
	// 				if ($startdate > $now) {
	// 					continue;
	// 				}

	// 				$manual->enrol_user($instance, $userid, $roleid, (int)$startdate, (int)$enddate, ENROL_USER_ACTIVE);
	// 			}
				
	// 		} catch (\Throwable $e) {
	// 			// error_log('[subs][enrol] course '.$courseid.' user '.$userid.' : '.$e->getMessage());
	// 			continue;
	// 		}
	// 	}
	// }


	public static function enrol_user_to_courses(int $userid, int $planid, int $startdate, int $enddate): void {
		global $DB;

		$entitlements = self::get_plan_entitlements($planid);
		if (empty($entitlements)) {
			return;
		}

		$manual = enrol_get_plugin('manual');
		if (!$manual) {
			return;
		}

		$now = time();

		if ($enddate > strtotime('2100-01-01')) {
			$enddate = 0;
		}

		foreach ($entitlements as $entitlement) {
			$courseid = (int)$entitlement->courseid;

			if ($courseid <= 0) {
				continue;
			}

			$instance = self::get_manual_enrol_instance($courseid);
			if (!$instance) {
				continue;
			}

			try {
				$ue = $DB->get_record('user_enrolments', [
					'enrolid' => $instance->id,
					'userid' => $userid,
				], '*', IGNORE_MISSING);

				if ($ue) {
					$newstart = (int)($ue->timestart ?? 0);

					if ($startdate <= $now) {
						$newstart = $newstart ? min($newstart, (int)$startdate) : (int)$startdate;
					}

					if ((int)$enddate === 0 || (int)$ue->timeend === 0) {
						$newend = 0;
					} else {
						$newend = max((int)$ue->timeend, (int)$enddate);
					}

					$manual->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, $newstart, $newend);

				} else {
					if ($startdate > $now) {
						continue;
					}

					// Le rôle exact est géré juste après par les entitlements.
					$roleid = (int)$DB->get_field('role', 'id', [
						'shortname' => $entitlement->roleshortname ?: 'student',
					], IGNORE_MISSING);

					if (!$roleid) {
						$roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
					}

					$manual->enrol_user($instance, $userid, $roleid, (int)$startdate, (int)$enddate, ENROL_USER_ACTIVE);
				}

				self::assign_entitlement_role($userid, $entitlement);
				self::ensure_user_group($userid, $courseid, (string)($entitlement->groupname ?? ''));

			} catch (\Throwable $e) {
				debugging('[local_subscriptions] enrol entitlement failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
				continue;
			}
		}
		self::cleanup_trial_subscription_if_unused($userid);
	}	

	public static function create_or_extend_subscription(
		int $userid,
		int $planid,
		string $payment_provider,
		string $transactionid,
		int $start_date,
		int $end_date,
		float $pricepaid,
		string $currency,
		int $creation_date,
		bool $allowupdate = false,
		int $discount_percent = 0,
		?string $discount_reason = null,
		float $discount_amount = 0.0
	): array {
		global $DB;

		// Normalisation simple.
		$currency = $currency !== '' ? strtoupper($currency) : 'EUR';
		if (strlen($currency) > 10) {
			$currency = substr($currency, 0, 10);
		}

		// Cherche une souscription ACTIVE sur le même plan.
		$existing = $DB->get_record('user_subscription', [
			'userid' => $userid,
			'planid' => $planid,
			'status' => Status::ACTIVE
		]);

		if ($existing) {
			if ($allowupdate) {
				// Mise à jour (extension / correction).
				$existing->payment_provider  = $payment_provider;
				$existing->transactionid     = $transactionid;
				$existing->start_date        = $start_date;
				$existing->end_date          = $end_date;
				$existing->pricepaid         = $pricepaid;
				$existing->currency          = $currency;
				$existing->discount_percent  = (int)$discount_percent;
				$existing->discount_reason   = $discount_reason;   // NULL accepté
				$existing->discount_amount   = (float)$discount_amount;
				$existing->last_update       = time();

				$DB->update_record('user_subscription', $existing);
				return ['status' => 'updated', 'subscription' => $existing];
			} else {
				return ['status' => 'exists', 'subscription' => $existing];
			}
		}

		// Création (nouvelle souscription).
		$record = (object)[
			'userid'           => $userid,
			'planid'           => $planid,
			'payment_provider' => $payment_provider,      // ex: 'trial', 'stripe', 'alfa'
			'transactionid'    => $transactionid,         // ex: 'trial:{userid}:{ts}'
			'start_date'       => $start_date,
			'end_date'         => $end_date,
			'pricepaid'        => $pricepaid,             // 0.00 pour essai
			'currency'         => $currency,              // 'EUR' par défaut si inconnu
			'status'           => Status::ACTIVE,
			'creation_date'    => $creation_date,
			'last_update'      => time(),
			'discount_percent' => (int)$discount_percent,
			'discount_reason'  => $discount_reason,       // NULL ok
			'discount_amount'  => (float)$discount_amount
		];

		$record->id = $DB->insert_record('user_subscription', $record);

		return ['status' => 'created', 'subscription' => $record];
	}


	public static function create_paid_subscription(
		int $userid,
		int $planid,
		string $payment_provider,
		string $transactionid,
		int $start_date,
		int $end_date,
		string $currency,
		float $baseprice,
		int $creation_date,
		bool $allowupdate = false,
		?array $discount_override = null
	): array {
		global $DB, $CFG;

		require_once($CFG->dirroot.'/local/subscriptions/classes/pricing_manager.php');
		require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

		// Sécurité : on n'achète pas un plan d'essai
		if (\local_subscriptions\trial_manager::is_trial_planid($planid)) {
			throw new \moodle_exception('cannot_purchase_trial_plan', 'local_subscriptions');
		}

		$currency = strtoupper(trim($currency ?: 'EUR'));
		$baseprice = round(max(0.0, $baseprice), 2);

		// 1) Déterminer la remise
		if ($discount_override && is_array($discount_override)) {
			$dpct = isset($discount_override['percent']) ? (int)$discount_override['percent'] : 0;
			$damt = isset($discount_override['amount'])  ? (float)$discount_override['amount']  : 0.0;
			$reas = $discount_override['reason'] ?? null;

			$dpct = max(0, min(100, $dpct));
			$damt = round(max(0.0, min($damt, $baseprice)), 2);
			$reason = $reas;
		} else {
			// Calcul "officiel"
			$calc = \local_subscriptions\pricing_manager::compute_trial_discount($userid, $planid, $baseprice);
			$dpct = (int)$calc['percent'];
			$damt = (float)$calc['amount'];
			$reason = $calc['reason'];
		}

		// 2) Prix payé = base - remise
		$pricepaid = round(max(0.0, $baseprice - $damt), 2);

		// 3) Persist
		$res = self::create_or_extend_subscription(
			$userid,
			$planid,
			$payment_provider,
			$transactionid,
			$start_date,
			$end_date,
			$pricepaid,
			$currency,
			$creation_date,
			$allowupdate,
			$dpct,
			$reason,
			$damt
		);

		// 4) Inscriptions + rôle student (et retrait de trialstudent si présent)
		self::enrol_user_to_courses($userid, $planid, $start_date, $end_date);

		// Les rôles sont appliqués par les entitlements.
		// \local_subscriptions\trial_manager::force_role_student($userid, $planid);

		return $res;
	}


	public static function unenrol_user_from_plan(int $userid, int $planid): void {
		$scope = self::get_access_scope_from_planid($planid);
		if (!$scope || empty($scope->course_ids)) {
			return;
		}

		$courseids = array_map('intval', explode(',', $scope->course_ids));

		foreach ($courseids as $courseid) {
			$instances = enrol_get_instances($courseid, true);
			foreach ($instances as $instance) {
				if ($instance->enrol === 'manual') {
					$plugin = enrol_get_plugin('manual');
					if ($plugin) {
						$plugin->unenrol_user($instance, $userid);
					}
				}
			}
		}
	}

	public static function save_user_subscription(\stdClass $data): int {
		global $DB;

		$now = time();

		if (empty($data->id)) {
			$data->creation_date = $data->creation_date ?? $now;
			$data->status = $data->status ?? Status::ACTIVE;
			return $DB->insert_record('user_subscription', $data);
		} else {
			$data->last_update = $now;
			$DB->update_record('user_subscription', $data);
			return $data->id;
		}
	}

	public static function get_plan_id_by_name(string $name): ?int {
		global $DB;
		$record = $DB->get_record('subscription_plan', ['name' => $name], 'id', IGNORE_MISSING);
		return $record ? (int)$record->id : null;
	}

	public static function get_translated_plan_name(int $planid, string $lang): ?string {
		global $DB;

		$translation = $DB->get_record('subscription_plan_translation', [
			'planid' => $planid,
			'lang' => $lang
		], 'name', IGNORE_MULTIPLE); // Juste au cas où, même si UNIQUE(planid, lang)

		return $translation ? format_string($translation->name) : null;
	}

    public static function suspend_user_in_plan_courses(int $userid, int $planid): void {
        global $DB;

        $scope = self::get_access_scope_from_planid($planid);
        if (!$scope || empty($scope->course_ids)) { return; }

        $courseids = array_map('intval', explode(',', (string)$scope->course_ids));
        if (empty($courseids)) { return; }

        $manual = enrol_get_plugin('manual');
        if (!$manual) { return; }

        list($inSql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        // Toutes les instances manual des cours du scope.
        $enrols = $DB->get_records_select('enrol', "enrol='manual' AND courseid $inSql", $params);
        if (empty($enrols)) { return; }

        // Map enrolid → instance.
        $byid = [];
        foreach ($enrols as $e) { $byid[$e->id] = $e; }

        // User enrolments de cet utilisateur sur ces instances.
        $ueSql = "SELECT ue.*
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid
                   WHERE ue.userid = :uid AND e.id $inSql";
        $ues = $DB->get_records_sql($ueSql, ['uid' => $userid] + $params);

        foreach ($ues as $ue) {
            if (!isset($byid[$ue->enrolid])) { continue; }
            if ((int)$ue->status === ENROL_USER_SUSPENDED) { continue; }
            $manual->update_user_enrol($byid[$ue->enrolid], $userid, ENROL_USER_SUSPENDED);
        }
    }

	public static function get_last_created_subscription(int $userid, int $planid): ?\stdClass {
		global $DB;
		return $DB->get_record('user_subscription', [
			'userid' => $userid,
			'planid' => $planid
		], '*', IGNORE_MULTIPLE); // ou trié par timecreated desc si plusieurs
	}

	public static function get_plan_entitlements(int $planid): array {
		global $DB;

		$records = $DB->get_records(
			'subscription_plan_entitlement',
			['planid' => $planid],
			'priority DESC, courseid ASC, accesslevel ASC'
		);

		// Fallback ancien système si aucun entitlement n'est configuré.
		if (!empty($records)) {
			return array_values($records);
		}

		$scope = self::get_access_scope_from_planid($planid);
		if (!$scope || empty($scope->course_ids)) {
			return [];
		}

		$courseids = preg_split('/[,\;\s]+/', (string)$scope->course_ids, -1, PREG_SPLIT_NO_EMPTY);
		$courseids = array_values(array_unique(array_map('intval', $courseids)));

		$fallback = [];

		foreach ($courseids as $courseid) {
			if ($courseid <= 0) {
				continue;
			}

			$fallback[] = (object)[
				'planid' => $planid,
				'courseid' => $courseid,
				'accesslevel' => 'full',
				'roleshortname' => 'student',
				'groupname' => '',
				'priority' => 100,
			];
		}

		return $fallback;
	}

	private static function get_manual_enrol_instance(int $courseid): ?\stdClass {
		global $DB;

		$manual = enrol_get_plugin('manual');
		if (!$manual) {
			return null;
		}

		$instances = enrol_get_instances($courseid, true);

		foreach ($instances as $instance) {
			if ($instance->enrol === 'manual' && (int)$instance->status === ENROL_INSTANCE_ENABLED) {
				return $instance;
			}
		}

		$course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
		if (!$course) {
			return null;
		}

		$instanceid = $manual->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
		if (!$instanceid) {
			return null;
		}

		return $DB->get_record('enrol', ['id' => $instanceid], '*', IGNORE_MISSING) ?: null;
	}

	private static function ensure_user_group(int $userid, int $courseid, string $groupname): void {
		global $DB, $CFG;

		$groupname = trim($groupname);
		if ($groupname === '') {
			return;
		}

		require_once($CFG->dirroot . '/group/lib.php');

		$group = $DB->get_record('groups', [
			'courseid' => $courseid,
			'name' => $groupname,
		], '*', IGNORE_MISSING);

		if (!$group) {
			$group = (object)[
				'courseid' => $courseid,
				'name' => $groupname,
				'description' => '',
				'descriptionformat' => FORMAT_HTML,
				'timecreated' => time(),
				'timemodified' => time(),
			];

			$group->id = groups_create_group($group);
		}

		if (!groups_is_member((int)$group->id, $userid)) {
			groups_add_member((int)$group->id, $userid);
		}
	}

	private static function assign_entitlement_role(int $userid, \stdClass $entitlement): void {
		global $DB, $CFG;

		require_once($CFG->dirroot . '/lib/accesslib.php');

		$courseid = (int)$entitlement->courseid;
		$roleshortname = trim((string)$entitlement->roleshortname);

		if ($courseid <= 0 || $roleshortname === '') {
			return;
		}

		$roleid = (int)$DB->get_field('role', 'id', ['shortname' => $roleshortname], IGNORE_MISSING);
		if (!$roleid) {
			return;
		}

		$ctx = \context_course::instance($courseid);

		// Ne jamais ajouter trialstudent si l'utilisateur a déjà un accès supérieur
		// sur CE cours.
		if ($roleshortname === 'trialstudent') {
			$higherroles = ['student', 'grammarstudent'];

			foreach ($higherroles as $higherrole) {
				$higherroleid = (int)$DB->get_field('role', 'id', ['shortname' => $higherrole], IGNORE_MISSING);

				if ($higherroleid && user_has_role_assignment($userid, $higherroleid, $ctx->id)) {
					return;
				}
			}
		}

		// Si on donne un vrai accès étudiant, on retire le rôle trialstudent uniquement sur CE cours.
		$rolesToRemove = [];

		if ($roleshortname === 'grammarstudent') {
			// Grammar remplace Trial sur ce cours.
			$rolesToRemove = ['trialstudent'];

		} else if ($roleshortname === 'student') {
			// Full remplace Trial et Grammar sur ce cours.
			$rolesToRemove = ['trialstudent', 'grammarstudent'];

		} else if ($roleshortname !== 'trialstudent') {
			// Sécurité pour d'autres rôles futurs.
			$rolesToRemove = ['trialstudent'];
		}

		foreach ($rolesToRemove as $shortnameToRemove) {
			$roleidToRemove = (int)$DB->get_field('role', 'id', ['shortname' => $shortnameToRemove], IGNORE_MISSING);

			if ($roleidToRemove) {
				role_unassign($roleidToRemove, $userid, $ctx->id);
			}
		}

		if (!user_has_role_assignment($userid, $roleid, $ctx->id)) {
			role_assign($roleid, $userid, $ctx->id);
		}
	}

	public static function cleanup_trial_subscription_if_unused(int $userid): void {
		global $DB;

		$trialroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
		if (!$trialroleid) {
			return;
		}

		$now = time();

		// Est-ce que l'utilisateur a encore trialstudent dans au moins un cours ?
		$hastrialrole = $DB->record_exists_select('role_assignments',
			'userid = :userid
			AND roleid = :roleid',
			[
				'userid' => $userid,
				'roleid' => $trialroleid,
			]
		);

		if ($hastrialrole) {
			return;
		}

		// Plus aucun rôle trialstudent : on remplace les subscriptions Trial actives.
		$trialplanids = $DB->get_fieldset_select(
			'subscription_plan',
			'id',
			'is_trial = 1'
		);

		if (empty($trialplanids)) {
			return;
		}

		[$insql, $params] = $DB->get_in_or_equal($trialplanids, SQL_PARAMS_NAMED, 'trialplan');
		$params['userid'] = $userid;
		$params['status'] = \local_subscriptions\constants\Status::ACTIVE;
		$params['now'] = $now;

		$sql = "userid = :userid
				AND status = :status
				AND planid $insql
				AND (end_date = 0 OR end_date >= :now)";

		$trials = $DB->get_records_select('user_subscription', $sql, $params);

		foreach ($trials as $trial) {
			$trial->status = \local_subscriptions\constants\Status::REPLACED;
			$trial->last_update = $now;
			$DB->update_record('user_subscription', $trial);
		}
	}

	public static function update_subscription_from_admin(
		int $subscriptionid,
		int $startdate,
		int $enddate,
		string $status
	): \stdClass {
		global $DB;

		$allowedstatuses = [
			Status::ACTIVE,
			Status::QUEUED,
			Status::INACTIVE,
			Status::EXPIRED,
			Status::SUSPENDED,
			Status::CANCELED,
			Status::REPLACED,
			Status::PENDING,
			Status::FAILED,
			Status::ERROR,
			Status::PAID,
			Status::COMPLETED,
		];

		if (!in_array($status, $allowedstatuses, true)) {
			throw new \moodle_exception('invalid_subscription_status', 'local_subscriptions');
		}

		$subscription = $DB->get_record('user_subscription', ['id' => $subscriptionid], '*', MUST_EXIST);

		$subscription->start_date = $startdate;
		$subscription->end_date = $enddate;
		$subscription->status = $status;
		$subscription->last_update = time();

		$DB->update_record('user_subscription', $subscription);

		self::sync_subscription_enrolments_from_admin($subscription);

		return $subscription;
	}

	private static function sync_subscription_enrolments_from_admin(\stdClass $subscription): void {
		global $DB;

		$userid = (int)$subscription->userid;
		$planid = (int)$subscription->planid;
		$startdate = (int)$subscription->start_date;
		$enddate = (int)$subscription->end_date;
		$status = (string)$subscription->status;

		$entitlements = self::get_plan_entitlements($planid);
		if (empty($entitlements)) {
			return;
		}

		$manual = enrol_get_plugin('manual');
		if (!$manual) {
			return;
		}

		if ($enddate > strtotime('2100-01-01')) {
			$enddate = 0;
		}

		$shouldbeactive = ($status === Status::ACTIVE && $startdate <= time());

		foreach ($entitlements as $entitlement) {
			$courseid = (int)$entitlement->courseid;
			if ($courseid <= 0) {
				continue;
			}

			$instance = self::get_manual_enrol_instance($courseid);
			if (!$instance) {
				continue;
			}

			$ue = $DB->get_record('user_enrolments', [
				'enrolid' => $instance->id,
				'userid' => $userid,
			], '*', IGNORE_MISSING);

			if ($shouldbeactive) {
				if ($ue) {
					$manual->update_user_enrol(
						$instance,
						$userid,
						ENROL_USER_ACTIVE,
						$startdate,
						$enddate
					);
				} else {
					$roleid = (int)$DB->get_field('role', 'id', [
						'shortname' => $entitlement->roleshortname ?: 'student',
					], IGNORE_MISSING);

					if (!$roleid) {
						$roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
					}

					if ($roleid) {
						$manual->enrol_user(
							$instance,
							$userid,
							$roleid,
							$startdate,
							$enddate,
							ENROL_USER_ACTIVE
						);
					}
				}

				self::assign_entitlement_role($userid, $entitlement);
				self::ensure_user_group($userid, $courseid, (string)($entitlement->groupname ?? ''));

			} else if ($ue) {
				$manual->update_user_enrol(
					$instance,
					$userid,
					ENROL_USER_SUSPENDED,
					$startdate,
					$enddate
				);
			}
		}
	}

}