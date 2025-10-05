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


    public static function enrol_user_to_courses(int $userid, int $planid, int $startdate, int $enddate): void {
		global $DB;

		// 1) Récupérer le scope -> course_ids "1,2,3"
		$scope = self::get_access_scope_from_planid($planid);
		if (!$scope || empty($scope->course_ids)) {
			return;
		}

		// 2) Rôle "student" (fallback 5)
		$roleid = 5;
		if ($role = $DB->get_record('role', ['shortname' => 'student'], 'id', IGNORE_MISSING)) {
			$roleid = (int)$role->id;
		}

		// 3) enddate illimitée si > 2100-01-01
		if ($enddate > strtotime('2100-01-01')) {
			$enddate = 0;
		}

		// 4) Parser course_ids (tolère virgule/point-virgule/espaces)
		$courseids = preg_split('/[,\;\s]+/', (string)$scope->course_ids, -1, PREG_SPLIT_NO_EMPTY);
		$courseids = array_values(array_unique(array_map('intval', $courseids)));
		if (empty($courseids)) {
			return;
		}

		// 5) Plugin manual
		$manual = enrol_get_plugin('manual');
		if (!$manual) {
			return; // pas d’inscription manuelle disponible
		}

		foreach ($courseids as $courseid) {
			if ($courseid <= 0) { continue; }

			// 6) Trouver (ou créer) une instance 'manual' activée sur ce cours
			$instances = enrol_get_instances($courseid, /* enabled only */ true);
			$instance  = null;
			foreach ($instances as $inst) {
				if ($inst->enrol === 'manual') { $instance = $inst; break; }
			}
			if (!$instance) {
				// Créer une instance manual activée
				$course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
				if (!$course) { continue; }
				$instanceid = $manual->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
				if (!$instanceid) { continue; }
				$instance = $DB->get_record('enrol', ['id' => $instanceid], '*', IGNORE_MISSING);
				if (!$instance) { continue; }
			}

			// 7) Idempotence CORRECTE :
			//    - si déjà une ligne user_enrolments -> UPDATE (status + dates)
			//    - sinon -> ENROL (création)
			try {
				$ue = $DB->get_record('user_enrolments', [
					'enrolid' => $instance->id,
					'userid'  => $userid
				], '*', IGNORE_MISSING);

				if ($ue) {
					// Mettre à jour (même si déjà actif) pour pousser les nouvelles dates
					// update_user_enrol($instance, $userid, $status=null|0|1, $timestart=null, $timeend=null)
					$manual->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, (int)$startdate, (int)$enddate);
				} else {
					// Inscription initiale
					$manual->enrol_user($instance, $userid, $roleid, (int)$startdate, (int)$enddate, ENROL_USER_ACTIVE);
				}
			} catch (\Throwable $e) {
				// error_log('[subs][enrol] course '.$courseid.' user '.$userid.' : '.$e->getMessage());
				continue;
			}
		}
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
		bool $allowupdate = false
	): string {
		global $DB;

		$existing = $DB->get_record('user_subscription', [
			'userid' => $userid,
			'planid' => $planid,
			'status' => Status::ACTIVE
		]);

		if ($existing) {
			if ($allowupdate) {
				$existing->payment_provider = $payment_provider;
				$existing->transactionid = $transactionid;
				$existing->start_date = $start_date;
				$existing->end_date = $end_date;
				$existing->pricepaid = $pricepaid;
				$existing->currency = $currency;
				$existing->last_update = time();

				$DB->update_record('user_subscription', $existing);
				return 'updated';
			} else {
				return 'exists';
			}
		}

		$record = (object)[
			'userid' => $userid,
			'planid' => $planid,
			'payment_provider' => $payment_provider,
			'transactionid' => $transactionid,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'pricepaid' => $pricepaid,
			'currency' => $currency,
			'status' => Status::ACTIVE,
			'creation_date' => $creation_date,
			'last_update' => time()
		];

		$DB->insert_record('user_subscription', $record);
		return 'created';
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
}